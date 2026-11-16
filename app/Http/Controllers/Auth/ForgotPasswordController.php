<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordLink;
use App\Models\ActivityLog;
use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * "Lupa password" di halaman login. Dua jalur sekaligus:
 *  1. Permintaan dicatat dan muncul di halaman admin (admin memberi password sementara).
 *  2. Jika email server sudah dikonfigurasi (MAIL_MAILER bukan log), tautan reset berbatas waktu dikirim ke email.
 *
 * Keamanan:
 *  - Respons SAMA untuk akun yang ada maupun tidak (mencegah user enumeration).
 *  - Dibatasi per IP (throttle:forgot di routes) dan per akun (3 permintaan per jam).
 *  - Token acak 64 karakter (CSPRNG); hanya hash SHA-256 yang disimpan.
 *  - Tautan dibuat dari APP_URL, bukan dari header Host permintaan (mencegah host header poisoning).
 */
class ForgotPasswordController extends Controller
{
    public const TOKEN_MINUTES = 60;

    private const MAX_PER_ACCOUNT = 3;

    private const MESSAGE = 'Jika akun ditemukan, permintaan reset password sudah dicatat untuk admin. '
        . 'Jika email di server aktif, tautan reset juga dikirim ke email akun tersebut.';

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $data       = $request->validate(['identifier' => ['required', 'string', 'max:120']]);
        $identifier = Str::lower(trim($data['identifier']));

        // Batas per akun: diam-diam diabaikan (respons tetap sama) supaya akun orang lain tidak bisa dibanjiri email
        $key = 'forgot:' . sha1($identifier);
        if (RateLimiter::tooManyAttempts($key, self::MAX_PER_ACCOUNT)) {
            return $this->finish();
        }
        RateLimiter::hit($key, 3600);

        $user = User::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('username', $identifier)->orWhere('email', $identifier))
            ->first();

        if ($user) {
            $this->createRequest($request, $user);
        }

        return $this->finish();
    }

    private function finish(): RedirectResponse
    {
        return redirect()->route('login')->with('info', self::MESSAGE);
    }

    private function createRequest(Request $request, User $user): void
    {
        $token = Str::random(64);

        // Satu permintaan aktif per akun: yang lama diganti
        PasswordReset::where('user_id', $user->id)->where('status', 'pending')->delete();

        $reset = new PasswordReset();
        $reset->forceFill([
            'user_id'      => $user->id,
            'token_hash'   => PasswordReset::hashToken($token),
            'status'       => 'pending',
            'requested_ip' => $request->ip(),
            'expires_at'   => now()->addMinutes(self::TOKEN_MINUTES),
        ])->save();

        ActivityLog::record('Permintaan reset password dari halaman login', $user->id);

        if ($this->mailConfigured()) {
            $this->sendLink($user, $token);
        }
    }

    /** Email dianggap aktif hanya jika MAIL_MAILER bukan log/array. */
    private function mailConfigured(): bool
    {
        return ! in_array((string) config('mail.default'), ['', 'log', 'array'], true);
    }

    private function sendLink(User $user, string $token): void
    {
        try {
            // Dari APP_URL + path relatif, bukan dari header Host milik pengunjung
            $url = rtrim((string) config('app.url'), '/') . route('password.reset', ['token' => $token], false);

            Mail::to($user->email)->send(new ResetPasswordLink($user->username, $url, self::TOKEN_MINUTES));
        } catch (\Throwable $e) {
            report($e);
            ActivityLog::record('Email reset password gagal dikirim', $user->id);
        }
    }
}
