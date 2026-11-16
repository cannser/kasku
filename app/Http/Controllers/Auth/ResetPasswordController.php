<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PasswordReset;
use App\Models\User;
use App\Support\SessionPurge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Halaman tujuan tautan email: membuat password baru memakai token sekali pakai. */
class ResetPasswordController extends Controller
{
    private const INVALID = 'Tautan reset tidak valid atau sudah kedaluwarsa. Ajukan permintaan baru.';

    public function create(string $token): View|RedirectResponse
    {
        $usable = preg_match('/^[A-Za-z0-9]{64}$/', $token) === 1
            && PasswordReset::usable()->where('token_hash', PasswordReset::hashToken($token))->exists();

        if (! $usable) {
            return redirect()->route('password.forgot')->with('danger', self::INVALID);
        }

        return view('auth.reset-password', ['token' => $token]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Validasi dulu: kalau password lemah, token BELUM terpakai dan bisa dicoba lagi
        $data = $request->validate([
            'token'    => ['required', 'string', 'regex:/^[A-Za-z0-9]{64}$/'],
            'password' => ['required', 'string', 'confirmed', 'max:72', Password::min(8)->letters()->numbers()],
        ]);

        // Transaksi + kunci baris: dua permintaan bersamaan tidak bisa memakai token yang sama
        $user = DB::transaction(function () use ($data) {
            $row = PasswordReset::usable()
                ->where('token_hash', PasswordReset::hashToken($data['token']))
                ->lockForUpdate()
                ->first();

            if (! $row) {
                return null;
            }

            $row->forceFill(['status' => 'done', 'token_hash' => null, 'handled_at' => now()])->save();
            $user = User::find($row->user_id);

            return ($user && $user->is_active) ? $user : null;
        });

        if (! $user) {
            return redirect()->route('password.forgot')->with('danger', self::INVALID);
        }

        $user->forceFill(['password' => $data['password']])->save();   // di-hash oleh cast
        SessionPurge::forUser((int) $user->id);                        // semua sesi lama berakhir

        ActivityLog::record('Password diganti lewat tautan reset', $user->id);

        return redirect()->route('login')->with('success', 'Password berhasil diganti. Silakan login.');
    }
}
