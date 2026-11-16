<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordLink;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Skenario uji fitur "Lupa password" (halaman login, permintaan ke admin, tautan email).
 * Jalankan: php artisan test --filter=PasswordResetTest
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Passw0rd123';
    private const NEW_PASSWORD = 'BaruPass456';

    private function makeUser(string $username = 'alice', string $role = 'user', bool $active = true): User
    {
        $user = new User(['username' => $username, 'email' => $username . '@example.com', 'password' => self::PASSWORD]);
        $user->forceFill(['role' => $role, 'is_active' => $active])->save();
        Category::createDefaultsFor($user);

        return $user;
    }

    /** Minta reset dengan email aktif (palsu) dan kembalikan tautan yang "dikirim". */
    private function requestLink(string $identifier = 'alice'): string
    {
        Mail::fake();
        config(['mail.default' => 'smtp']);

        $this->post('/forgot-password', ['identifier' => $identifier])->assertRedirect(route('login'));

        $url = '';
        Mail::assertSent(ResetPasswordLink::class, function (ResetPasswordLink $m) use (&$url) {
            $url = $m->url;

            return true;
        });

        return $url;
    }

    public function test_login_page_has_forgot_password_link(): void
    {
        $this->get('/login')->assertOk()->assertSee('Lupa password?')->assertSee(route('password.forgot'));
        $this->get('/forgot-password')->assertOk()->assertSee('Username atau email');
    }

    public function test_response_is_identical_for_existing_and_unknown_account(): void
    {
        $this->makeUser('alice');

        $this->post('/forgot-password', ['identifier' => 'alice'])->assertRedirect(route('login'));
        $existing = session('info');

        $this->post('/forgot-password', ['identifier' => 'tidak_ada'])->assertRedirect(route('login'));
        $unknown = session('info');

        $this->assertNotEmpty($existing);
        $this->assertSame($existing, $unknown);   // tidak bisa menebak akun mana yang ada
    }

    public function test_request_is_stored_with_hashed_token_for_active_user_only(): void
    {
        $alice = $this->makeUser('alice');
        $this->makeUser('nonaktif', 'user', false);

        $this->post('/forgot-password', ['identifier' => 'Alice@Example.com'])->assertRedirect(route('login'));
        $this->post('/forgot-password', ['identifier' => 'nonaktif'])->assertRedirect(route('login'));
        $this->post('/forgot-password', ['identifier' => 'tidak_ada'])->assertRedirect(route('login'));

        $this->assertSame(1, PasswordReset::count());
        $row = PasswordReset::firstOrFail();
        $this->assertSame($alice->id, $row->user_id);
        $this->assertSame('pending', $row->status);
        $this->assertSame(64, strlen((string) $row->token_hash));   // hash SHA-256, bukan token mentah
    }

    public function test_no_email_is_sent_when_mail_is_not_configured(): void
    {
        Mail::fake();
        config(['mail.default' => 'log']);
        $this->makeUser('alice');

        $this->post('/forgot-password', ['identifier' => 'alice'])->assertRedirect(route('login'));

        Mail::assertNothingSent();
        $this->assertSame(1, PasswordReset::count());   // tetap tercatat untuk admin
    }

    public function test_email_link_uses_app_url_and_plain_token_is_not_stored(): void
    {
        $this->makeUser('alice');
        $url = $this->requestLink('alice');

        $this->assertStringStartsWith(rtrim((string) config('app.url'), '/') . '/reset-password/', $url);

        $token = basename($url);
        $this->assertDatabaseMissing('password_resets', ['token_hash' => $token]);
        $this->assertDatabaseHas('password_resets', ['token_hash' => hash('sha256', $token)]);
    }

    public function test_full_reset_flow_and_token_is_single_use(): void
    {
        $this->makeUser('alice');
        $token = basename($this->requestLink('alice'));

        $this->get('/reset-password/' . $token)->assertOk()->assertSee('Password baru');

        // password lemah ditolak dan token belum terpakai
        $this->post('/reset-password', [
            'token' => $token, 'password' => 'abcdefgh', 'password_confirmation' => 'abcdefgh',
        ])->assertSessionHasErrors('password');
        $this->assertDatabaseHas('password_resets', ['token_hash' => hash('sha256', $token), 'status' => 'pending']);

        // password kuat diterima
        $this->post('/reset-password', [
            'token' => $token, 'password' => self::NEW_PASSWORD, 'password_confirmation' => self::NEW_PASSWORD,
        ])->assertRedirect(route('login'));

        // password lama tidak berlaku, password baru berlaku
        $this->post('/login', ['username' => 'alice', 'password' => self::PASSWORD])->assertSessionHasErrors('username');
        $this->post('/login', ['username' => 'alice', 'password' => self::NEW_PASSWORD])->assertRedirect(route('dashboard'));
        $this->post('/logout');

        // token sekali pakai: percobaan kedua ditolak
        $this->post('/reset-password', [
            'token' => $token, 'password' => 'LainPass789', 'password_confirmation' => 'LainPass789',
        ])->assertRedirect(route('password.forgot'))->assertSessionHas('danger');

        $this->assertDatabaseHas('password_resets', ['status' => 'done', 'token_hash' => null]);
    }

    public function test_expired_or_invalid_token_is_rejected(): void
    {
        $this->makeUser('alice');
        $token = basename($this->requestLink('alice'));

        PasswordReset::query()->update(['expires_at' => now()->subMinute()]);

        $this->get('/reset-password/' . $token)->assertRedirect(route('password.forgot'));
        $this->post('/reset-password', [
            'token' => $token, 'password' => self::NEW_PASSWORD, 'password_confirmation' => self::NEW_PASSWORD,
        ])->assertRedirect(route('password.forgot'));

        // token sembarang (bukan 64 karakter alfanumerik) tidak dikenali route sama sekali
        $this->get('/reset-password/pendek')->assertNotFound();
        $this->get('/reset-password/' . str_repeat('a', 64))->assertRedirect(route('password.forgot'));

        // password lama tetap berlaku karena reset tidak pernah berhasil
        $this->post('/login', ['username' => 'alice', 'password' => self::PASSWORD])->assertRedirect(route('dashboard'));
    }

    public function test_per_account_limit_stops_extra_requests_silently(): void
    {
        $this->makeUser('alice');

        for ($i = 0; $i < 4; $i++) {
            $this->post('/forgot-password', ['identifier' => 'alice'])->assertRedirect(route('login'));
        }

        // 3 permintaan pertama dicatat, yang ke-4 diabaikan (respons tetap sama)
        $this->assertSame(3, ActivityLog::where('action', 'like', 'Permintaan reset password%')->count());
    }

    public function test_per_ip_throttle_blocks_flooding(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/forgot-password', ['identifier' => 'orang' . $i])->assertRedirect(route('login'));
        }

        $this->post('/forgot-password', ['identifier' => 'orang_lain'])->assertStatus(429);
    }

    public function test_admin_sees_request_and_reset_marks_it_done(): void
    {
        $admin = $this->makeUser('boss', 'admin');
        $alice = $this->makeUser('alice');

        $this->post('/forgot-password', ['identifier' => 'alice'])->assertRedirect(route('login'));

        $this->actingAs($admin)->get('/admin/users')
            ->assertOk()->assertSee('Permintaan reset password')->assertSee('Reset sekarang');

        $this->actingAs($admin)->post('/admin/users/' . $alice->id . '/reset')->assertRedirect();

        $this->assertDatabaseHas('password_resets', ['user_id' => $alice->id, 'status' => 'done']);
        $this->actingAs($admin)->get('/admin/users')->assertDontSee('Permintaan reset password');
    }

    public function test_regular_user_cannot_see_reset_requests(): void
    {
        $alice = $this->makeUser('alice');
        $this->post('/forgot-password', ['identifier' => 'alice'])->assertRedirect(route('login'));

        $this->actingAs($alice)->get('/admin/users')->assertForbidden();
    }
}
