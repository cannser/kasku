<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\View\View;

/**
 * PRINSIP LEAST PRIVILEGE: dashboard admin hanya memakai count() (angka agregat).
 * Tidak ada query yang membaca isi transaksi (deskripsi, nominal, kategori) milik pengguna.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'Total pengguna'         => [User::where('role', 'user')->count(), ''],
            'Pengguna aktif'         => [User::where('role', 'user')->where('is_active', true)->count(), 'stat-income'],
            'Pengguna nonaktif'      => [User::where('role', 'user')->where('is_active', false)->count(), 'stat-expense'],
            'Pengguna baru (7 hari)' => [User::where('role', 'user')->where('created_at', '>=', now()->subDays(7))->count(), ''],
            'Total transaksi'        => [Transaction::count(), ''],
            'Transaksi bulan ini'    => [Transaction::where('trx_date', '>=', now()->startOfMonth()->toDateString())->count(), ''],
            'Login gagal (24 jam)'   => [ActivityLog::where('action', 'like', 'Login gagal%')
                                            ->where('created_at', '>=', now()->subDay())->count(), 'stat-expense'],
        ];

        $recentLogs = ActivityLog::with('user:id,username')->orderByDesc('id')->limit(8)->get();

        return view('admin.dashboard', compact('stats', 'recentLogs'));
    }
}
