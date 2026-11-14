<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ReadsQuery;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Format;
use App\Support\SessionPurge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kelola pengguna: aktifkan/nonaktifkan dan reset password.
 * Admin tidak bisa menonaktifkan/mereset dirinya sendiri. Tidak ada fitur hapus akun.
 * Semua aksi admin dicatat di activity_logs.
 */
class UserController extends Controller
{
    use ReadsQuery;

    public function index(Request $request): View
    {
        $q = mb_substr($this->qs($request, 'q'), 0, 50);

        $users = User::query()
            ->when($q !== '', function ($query) use ($q) {
                // Escape wildcard LIKE supaya input tidak bisa dipakai sebagai pola % atau _
                $like = '%' . addcslashes($q, '%_\\') . '%';
                $query->where(fn ($w) => $w->where('username', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->orderBy('id')
            ->paginate((int) config('kasku.per_page'))
            ->withQueryString();

        return view('admin.users', compact('users', 'q'));
    }

    public function toggle(Request $request, int $user): RedirectResponse
    {
        $target = User::findOrFail($user);

        // Guard sisi server: tombolnya disembunyikan di UI, tetapi tetap divalidasi di sini
        if ((int) $target->id === (int) $request->user()->id) {
            ActivityLog::record('Admin mencoba menonaktifkan akunnya sendiri');

            return back()->with('danger', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $new = ! $target->is_active;
        $target->forceFill(['is_active' => $new])->save();   // forceFill: is_active tidak fillable

        if (! $new) {
            SessionPurge::forUser((int) $target->id);         // sesi aktifnya langsung dicabut
        }

        ActivityLog::record(($new ? 'Admin mengaktifkan' : 'Admin menonaktifkan')
            . ' user #' . $target->id . ' (' . $target->username . ')');

        return back()->with('success', 'Akun ' . $target->username . ($new ? ' diaktifkan.' : ' dinonaktifkan.'));
    }

    public function reset(Request $request, int $user): RedirectResponse
    {
        $target = User::findOrFail($user);

        if ((int) $target->id === (int) $request->user()->id) {
            ActivityLog::record('Admin mencoba mereset passwordnya sendiri lewat halaman pengguna');

            return back()->with('danger', 'Gunakan menu Ganti Password untuk akun Anda sendiri.');
        }

        $temp = Format::tempPassword(12);
        $target->forceFill(['password' => $temp])->save();    // di-hash oleh cast
        SessionPurge::forUser((int) $target->id);             // sesi lama pengguna itu berakhir

        // Password sementara TIDAK dicatat di log; hanya ditampilkan sekali ke admin
        ActivityLog::record('Admin mereset password user #' . $target->id . ' (' . $target->username . ')');

        return back()->with('warning', 'Password sementara untuk ' . $target->username . ': ' . $temp
            . ' (tampil sekali ini saja; minta pengguna segera menggantinya).');
    }
}
