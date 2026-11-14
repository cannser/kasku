<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    private const GENERIC_ERROR = 'Username atau password salah, atau akun tidak aktif.';

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:72'],
        ]);

        $username = $credentials['username'];
        $max      = (int) config('kasku.login_max_attempts');
        $decay    = (int) config('kasku.login_decay_seconds');

        // Dua penghitung: per IP dan per username (sha1 agar karakter aneh aman dipakai sebagai key cache)
        $ipKey   = 'login-ip:' . $request->ip();
        $userKey = 'login-user:' . sha1(Str::lower($username));

        if (RateLimiter::tooManyAttempts($ipKey, $max) || RateLimiter::tooManyAttempts($userKey, $max)) {
            ActivityLog::record('Login diblokir rate limit (username: ' . $username . ')');
            throw ValidationException::withMessages([
                'username' => 'Terlalu banyak percobaan login. Silakan coba lagi beberapa menit lagi.',
            ]);
        }

        $existing = User::where('username', $username)->first();

        // Username tidak ada: tetap lakukan 1x pengecekan hash agar waktu respons sama
        // (mencegah user enumeration lewat perbedaan waktu).
        if (! $existing) {
            Hash::check($credentials['password'], (string) config('kasku.dummy_hash'));
        }

        // "is_active" ikut dalam kondisi: akun nonaktif tidak bisa login
        $ok = Auth::attempt([
            'username'  => $username,
            'password'  => $credentials['password'],
            'is_active' => 1,
        ]);

        if ($ok) {
            RateLimiter::clear($userKey);              // hanya reset hitungan username ini, bukan IP
            $request->session()->regenerate();         // ID sesi baru: cegah session fixation

            $user = Auth::user();
            ActivityLog::record('Login berhasil', $user->id);

            return redirect()->intended(route($user->isAdmin() ? 'admin.dashboard' : 'dashboard'));
        }

        RateLimiter::hit($ipKey, $decay);
        RateLimiter::hit($userKey, $decay);

        $reason = ! $existing ? 'username tidak dikenal' : ($existing->is_active ? 'password salah' : 'akun nonaktif');
        ActivityLog::record('Login gagal (' . $reason . ', username: ' . $username . ')', $existing?->id);

        // Pesan SAMA untuk username salah, password salah, maupun akun nonaktif
        throw ValidationException::withMessages(['username' => self::GENERIC_ERROR]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        ActivityLog::record('Logout');

        Auth::guard('web')->logout();
        $request->session()->invalidate();       // hancurkan sesi lama
        $request->session()->regenerateToken();  // token CSRF baru

        return redirect()->route('login')->with('success', 'Anda telah keluar.');
    }
}
