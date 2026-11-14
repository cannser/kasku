<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kontrol akses berbasis peran di sisi server. Dipakai: ->middleware('role:admin') atau 'role:user'.
 * Peran dibaca dari database (lewat $request->user()), bukan dari input pengguna.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->role !== $role) {
            // Admin yang membuka halaman pengguna biasa diarahkan ke area admin
            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            ActivityLog::record('Akses halaman admin ditolak', $user->id);
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
