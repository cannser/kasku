<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\SessionPurge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password'         => ['required', 'string', 'confirmed', 'max:72', 'different:current_password',
                                   Password::min(8)->letters()->numbers()],
        ]);

        $user = $request->user();
        $user->update(['password' => $data['password']]);   // di-hash oleh cast

        $request->session()->regenerate();                  // ID sesi baru setelah kredensial berubah
        SessionPurge::forUser($user->id, $request->session()->getId()); // sesi di perangkat lain berakhir

        ActivityLog::record('Password diganti oleh pemilik akun');

        return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'dashboard')
            ->with('success', 'Password berhasil diganti.');
    }
}
