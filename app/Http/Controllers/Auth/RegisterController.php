<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        // Hanya field yang tercantum di rules yang ada di $data. Field lain (mis. role)
        // dibuang, dan User::$fillable juga menolak role/is_active (mass assignment).
        $data = $request->validate([
            'username' => ['required', 'string', 'regex:/^[A-Za-z0-9_]{3,30}$/', 'unique:users,username'],
            'email'    => ['required', 'string', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', 'max:72', 'different:username',
                           Password::min(8)->letters()->numbers()],
        ]);

        $user = User::create($data);   // password otomatis di-hash oleh cast 'hashed'
        Category::createDefaultsFor($user);

        ActivityLog::record('Registrasi akun baru: ' . $user->username, $user->id);

        return redirect()->route('login')->with('success', 'Registrasi berhasil. Silakan login.');
    }
}
