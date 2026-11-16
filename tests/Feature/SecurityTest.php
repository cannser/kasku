<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Skenario self-testing keamanan KasKu (versi Laravel).
 * Jalankan: php artisan test --filter=SecurityTest
 * Hasilnya bisa dilampirkan sebagai bukti di laporan.
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Passw0rd123';

    private function makeUser(string $username = 'alice', string $role = 'user', bool $active = true): User
    {
        $user = new User(['username' => $username, 'email' => $username . '@example.com', 'password' => self::PASSWORD]);
        $user->forceFill(['role' => $role, 'is_active' => $active])->save();
        Category::createDefaultsFor($user);

        return $user;
    }

    private function makeTransaction(User $user, array $override = []): Transaction
    {
        return $user->transactions()->create(array_merge([
            'category_id' => $user->categories()->first()->id,
            'type'        => 'expense',
            'amount'      => '1000',
            'description' => 'contoh',
            'trx_date'    => now()->format('Y-m-d'),
        ], $override));
    }

    // ---------- Autentikasi ----------

    public function test_guest_is_redirected_to_login_from_protected_pages(): void
    {
        foreach (['/dashboard', '/transactions', '/categories', '/report', '/admin'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_registration_rejects_weak_password(): void
    {
        $this->post('/register', [
            'username' => 'newbie', 'email' => 'newbie@example.com',
            'password' => 'abcdefgh', 'password_confirmation' => 'abcdefgh',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['username' => 'newbie']);
    }

    public function test_registration_ignores_role_parameter_mass_assignment(): void
    {
        $this->post('/register', [
            'username' => 'newbie', 'email' => 'newbie@example.com',
            'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD,
            'role' => 'admin', 'is_active' => 0,
        ])->assertRedirect(route('login'));

        $created = User::where('username', 'newbie')->first();
        $this->assertNotNull($created);
        $this->assertSame('user', $created->role);
        $this->assertTrue((bool) $created->is_active);
    }

    public function test_password_is_stored_hashed(): void
    {
        $user = $this->makeUser();
        $this->assertNotSame(self::PASSWORD, $user->fresh()->password);
        $this->assertStringStartsWith('$2y$', $user->fresh()->password);
    }

    public function test_login_error_message_is_identical_for_wrong_username_and_wrong_password(): void
    {
        $this->makeUser('alice');
        $expected = 'Username atau password salah, atau akun tidak aktif.';

        $this->post('/login', ['username' => 'alice', 'password' => 'SalahBanget1'])
            ->assertSessionHasErrors(['username' => $expected]);
        $this->post('/login', ['username' => 'tidak_ada', 'password' => 'SalahBanget1'])
            ->assertSessionHasErrors(['username' => $expected]);
        $this->assertGuest();
    }

    public function test_inactive_account_cannot_login(): void
    {
        $this->makeUser('alice', 'user', false);

        $this->post('/login', ['username' => 'alice', 'password' => self::PASSWORD])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_login_success_redirects_by_role(): void
    {
        $this->makeUser('alice');
        $this->makeUser('boss', 'admin');

        $this->post('/login', ['username' => 'alice', 'password' => self::PASSWORD])
            ->assertRedirect(route('dashboard'));
        $this->post('/logout');

        $this->post('/login', ['username' => 'boss', 'password' => self::PASSWORD])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_login_is_rate_limited_even_with_correct_password(): void
    {
        $this->makeUser('alice');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['username' => 'alice', 'password' => 'Salah' . $i . 'abc']);
        }

        $this->post('/login', ['username' => 'alice', 'password' => self::PASSWORD])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_logout_ends_session(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    // ---------- Otorisasi ----------

    public function test_regular_user_cannot_open_admin_pages(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->get('/admin/logs')->assertForbidden();
        $this->actingAs($user)->post('/admin/users/' . $user->id . '/toggle')->assertForbidden();
    }

    public function test_admin_can_open_admin_pages(): void
    {
        $admin = $this->makeUser('boss', 'admin');

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/logs')->assertOk();
    }

    public function test_user_cannot_view_other_users_transaction_idor(): void
    {
        $alice = $this->makeUser('alice');
        $bob   = $this->makeUser('bob');
        $trx   = $this->makeTransaction($bob, ['description' => 'rahasia bob']);

        $this->actingAs($alice)->get('/transactions/' . $trx->id . '/edit')->assertNotFound();
        $this->actingAs($alice)->get('/transactions')->assertDontSee('rahasia bob');
    }

    public function test_user_cannot_update_or_delete_other_users_transaction_idor(): void
    {
        $alice = $this->makeUser('alice');
        $bob   = $this->makeUser('bob');
        $trx   = $this->makeTransaction($bob);

        $this->actingAs($alice)->put('/transactions/' . $trx->id, [
            'type' => 'expense', 'category_id' => $alice->categories()->first()->id,
            'amount' => '99999', 'trx_date' => now()->format('Y-m-d'),
        ])->assertNotFound();

        $this->actingAs($alice)->delete('/transactions/' . $trx->id)->assertNotFound();

        // Nominal tidak berubah (tetap 1000, bukan 99999)
        $this->assertSame('1000.00', (string) $trx->fresh()->amount);
    }

    public function test_user_cannot_use_another_users_category(): void
    {
        $alice = $this->makeUser('alice');
        $bob   = $this->makeUser('bob');
        $bobCategory = $bob->categories()->first();

        $this->actingAs($alice)->post('/transactions', [
            'type' => 'expense', 'category_id' => $bobCategory->id,
            'amount' => '5000', 'trx_date' => now()->format('Y-m-d'),
        ])->assertSessionHasErrors('category_id');

        $this->assertSame(0, Transaction::count());
    }

    public function test_transaction_cannot_be_deleted_with_get_request(): void
    {
        $alice = $this->makeUser('alice');
        $trx   = $this->makeTransaction($alice);

        $this->actingAs($alice)->get('/transactions/' . $trx->id)->assertStatus(405);
        $this->assertDatabaseHas('transactions', ['id' => $trx->id]);
    }

    // ---------- Admin ----------

    public function test_admin_cannot_deactivate_self(): void
    {
        $admin = $this->makeUser('boss', 'admin');

        $this->actingAs($admin)->post('/admin/users/' . $admin->id . '/toggle')
            ->assertSessionHas('danger');

        $this->assertTrue((bool) $admin->fresh()->is_active);
    }

    public function test_deactivated_user_loses_access_immediately(): void
    {
        $admin = $this->makeUser('boss', 'admin');
        $user  = $this->makeUser('alice');

        $this->actingAs($admin)->post('/admin/users/' . $user->id . '/toggle');
        $this->assertFalse((bool) $user->fresh()->is_active);

        $this->actingAs($user->fresh())->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_admin_actions_are_logged(): void
    {
        $admin = $this->makeUser('boss', 'admin');
        $user  = $this->makeUser('alice');

        $this->actingAs($admin)->post('/admin/users/' . $user->id . '/toggle');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action'  => 'Admin menonaktifkan user #' . $user->id . ' (alice)',
        ]);
    }

    // ---------- Input dan output ----------

    public function test_output_is_escaped_against_xss(): void
    {
        $alice = $this->makeUser('alice');
        $this->makeTransaction($alice, ['description' => '<script>alert(1)</script>']);

        $this->actingAs($alice)->get('/transactions')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_transaction_validation_rejects_bad_input(): void
    {
        $alice = $this->makeUser('alice');
        $cat   = $alice->categories()->first()->id;
        $base  = ['type' => 'expense', 'category_id' => $cat, 'amount' => '1000', 'trx_date' => '2026-11-05'];

        $this->actingAs($alice);

        foreach (['-5', 'abc', '1e5', '0', '99999999999999999'] as $amount) {
            $this->post('/transactions', array_merge($base, ['amount' => $amount]))
                ->assertSessionHasErrors('amount');
        }
        $this->post('/transactions', array_merge($base, ['trx_date' => '2026-02-30']))->assertSessionHasErrors('trx_date');
        $this->post('/transactions', array_merge($base, ['type' => 'hack']))->assertSessionHasErrors('type');

        $this->assertSame(0, Transaction::count());
    }

    public function test_sql_injection_in_filters_does_not_break_or_leak(): void
    {
        $alice = $this->makeUser('alice');
        $bob   = $this->makeUser('bob');
        $this->makeTransaction($bob, ['description' => 'rahasia bob']);

        $url = '/transactions?month=' . urlencode("2026-11' OR '1'='1")
            . '&category=' . urlencode('1 OR 1=1') . '&type[]=x';

        $this->actingAs($alice)->get($url)->assertOk()->assertDontSee('rahasia bob');
    }

    public function test_csv_export_neutralizes_formula_injection(): void
    {
        $alice = $this->makeUser('alice');
        $this->makeTransaction($alice, ['description' => '=HYPERLINK("http://evil.example")']);

        $response = $this->actingAs($alice)->get('/report/export?month=' . now()->format('Y-m'));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_csv_export_only_contains_own_data(): void
    {
        $alice = $this->makeUser('alice');
        $bob   = $this->makeUser('bob');
        $this->makeTransaction($alice, ['description' => 'milik alice']);
        $this->makeTransaction($bob, ['description' => 'milik bob']);

        $csv = $this->actingAs($alice)->get('/report/export?month=' . now()->format('Y-m'))->streamedContent();

        $this->assertStringContainsString('milik alice', $csv);
        $this->assertStringNotContainsString('milik bob', $csv);
    }

    public function test_csv_export_is_tidy_for_excel(): void
    {
        $alice = $this->makeUser('alice');
        $this->makeTransaction($alice, ['amount' => '1500000', 'description' => 'gaji kecil']);

        $csv = $this->actingAs($alice)->get('/report/export?month=' . now()->format('Y-m'))->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBFsep=,\r\n", $csv);  // BOM + petunjuk pemisah untuk Excel
        $this->assertStringContainsString('Tanggal,Jenis,Kategori,Keterangan,Nominal', $csv);
        $this->assertStringContainsString(',1500000', $csv);        // angka bulat, bukan 1500000.00
        $this->assertStringNotContainsString('1500000.00', $csv);
    }

    public function test_pdf_export_returns_valid_pdf_with_own_data_only(): void
    {
        $alice = $this->makeUser('alice');
        $bob   = $this->makeUser('bob');
        $this->makeTransaction($alice, ['description' => 'milik alice']);
        $this->makeTransaction($bob, ['description' => 'milik bob']);

        $response = $this->actingAs($alice)->get('/report/export?format=pdf&month=' . now()->format('Y-m'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $pdf = $response->getContent();
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('%%EOF', $pdf);
        $this->assertStringContainsString('milik alice', $pdf);
        $this->assertStringNotContainsString('milik bob', $pdf);
        $this->assertDatabaseHas('activity_logs', ['action' => 'Ekspor PDF bulan ' . now()->format('Y-m')]);
    }

    public function test_pdf_export_escapes_user_text_so_it_cannot_inject_pdf_operators(): void
    {
        $alice = $this->makeUser('alice');
        $this->makeTransaction($alice, ['description' => 'a(b)\\c) Tj ET']);

        $pdf = $this->actingAs($alice)->get('/report/export?format=pdf&month=' . now()->format('Y-m'))->getContent();

        $this->assertStringContainsString('a\\(b\\)\\\\c\\) Tj ET', $pdf);  // ( ) dan \ di-escape
    }

    public function test_export_requires_login_and_unknown_format_falls_back_to_csv(): void
    {
        $this->get('/report/export?format=pdf')->assertRedirect(route('login'));

        $alice = $this->makeUser('alice');
        $response = $this->actingAs($alice)->get('/report/export?format=exe&month=' . now()->format('Y-m'));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
    }

    public function test_pages_do_not_show_dummy_wording(): void
    {
        $alice = $this->makeUser('alice');
        foreach (['/login', '/register'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsStringIgnoringCase('dummy', $html);
        }
        $html = $this->actingAs($alice)->get('/dashboard')->assertOk()->getContent();
        $this->assertStringNotContainsStringIgnoringCase('dummy', $html);
    }

    // ---------- Sesi, CSRF, header ----------

    public function test_post_without_csrf_token_is_rejected(): void
    {
        // Selama testing, Laravel mematikan pemeriksaan CSRF. Env diubah sementara agar aktif.
        $this->app['env'] = 'production';

        $this->post('/login', ['username' => 'alice', 'password' => self::PASSWORD])->assertStatus(419);
    }

    public function test_security_headers_are_present(): void
    {
        config(['app.debug' => false]);

        $this->get('/login')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_changing_password_requires_current_password(): void
    {
        $alice = $this->makeUser('alice');

        $this->actingAs($alice)->put('/password', [
            'current_password' => 'SalahBanget1',
            'password' => 'BaruPass99', 'password_confirmation' => 'BaruPass99',
        ])->assertSessionHasErrors('current_password');
    }

    // ---------- Fitur ----------

    public function test_dashboard_shows_correct_totals(): void
    {
        $alice = $this->makeUser('alice');
        $this->makeTransaction($alice, ['type' => 'income', 'amount' => '3000000']);
        $this->makeTransaction($alice, ['type' => 'expense', 'amount' => '500000']);

        $this->actingAs($alice)->get('/dashboard')
            ->assertOk()
            ->assertSee('Rp 2.500.000')   // saldo
            ->assertSee('Rp 3.000.000')   // pemasukan bulan ini
            ->assertSee('Rp 500.000');    // pengeluaran bulan ini
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        $alice = $this->makeUser('alice');
        $trx   = $this->makeTransaction($alice);

        $this->actingAs($alice)->delete('/categories/' . $trx->category_id)->assertSessionHas('danger');
        $this->assertDatabaseHas('categories', ['id' => $trx->category_id]);
    }

    // ---------- Tampilan: halaman galat dan kepatuhan CSP ----------

    public function test_custom_error_pages_do_not_leak_details(): void
    {
        // 404 untuk alamat yang tidak ada
        $this->get('/halaman-yang-tidak-ada')
            ->assertStatus(404)
            ->assertSee('Halaman tidak ditemukan');

        // 403 untuk user biasa yang membuka area admin
        $alice = $this->makeUser('alice');
        $this->actingAs($alice)->get('/admin/users')
            ->assertStatus(403)
            ->assertSee('Akses ditolak')
            ->assertDontSee('Stack trace');
    }

    public function test_pages_follow_csp_no_inline_script_or_style(): void
    {
        $alice = $this->makeUser('alice');
        $this->makeTransaction($alice);
        $admin = $this->makeUser('boss', 'admin');

        $pages = [
            [null,   ['/login', '/register']],
            [$alice, ['/dashboard', '/transactions', '/transactions/create', '/categories', '/report', '/password']],
            [$admin, ['/admin', '/admin/users', '/admin/logs']],
        ];

        foreach ($pages as [$who, $urls]) {
            foreach ($urls as $url) {
                $html = ($who ? $this->actingAs($who) : $this)->get($url)->assertOk()->getContent();

                // Tidak ada atribut style="..." dan tidak ada <script> tanpa src (CSP memblokir keduanya)
                $this->assertDoesNotMatchRegularExpression('/<[^>]+\sstyle\s*=/i', $html, "inline style di {$url}");
                $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*\ssrc=)/i', $html, "inline script di {$url}");
                $this->assertDoesNotMatchRegularExpression('/\son(click|load|error|submit)\s*=/i', $html, "inline handler di {$url}");
            }
        }
    }
}
