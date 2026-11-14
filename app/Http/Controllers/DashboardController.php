<?php

namespace App\Http\Controllers;

use App\Support\Format;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user      = $request->user();
        $thisMonth = now()->format('Y-m');
        [$start, $end] = Format::monthBounds($thisMonth);

        // Semua query lewat $user->transactions(): otomatis berkondisi user_id (cegah IDOR)
        $totalIncome  = (float) $user->transactions()->where('type', 'income')->sum('amount');
        $totalExpense = (float) $user->transactions()->where('type', 'expense')->sum('amount');
        $balance      = $totalIncome - $totalExpense;

        $monthIncome = (float) $user->transactions()->where('type', 'income')
            ->where('trx_date', '>=', $start)->where('trx_date', '<', $end)->sum('amount');
        $monthExpense = (float) $user->transactions()->where('type', 'expense')
            ->where('trx_date', '>=', $start)->where('trx_date', '<', $end)->sum('amount');

        // Pengeluaran per kategori bulan ini (untuk grafik)
        $byCategory = $user->transactions()
            ->join('categories', 'categories.id', '=', 'transactions.category_id')
            ->where('transactions.type', 'expense')
            ->where('transactions.trx_date', '>=', $start)
            ->where('transactions.trx_date', '<', $end)
            ->selectRaw('categories.name as category_name, SUM(transactions.amount) as total')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->get();

        $labels = $byCategory->pluck('category_name')->values()->all();
        $values = $byCategory->pluck('total')->map(fn ($v) => (float) $v)->values()->all();

        $recent = $user->transactions()->with('category:id,name')
            ->orderByDesc('trx_date')->orderByDesc('id')->limit(5)->get();

        return view('dashboard', [
            'thisMonth'    => $thisMonth,
            'balance'      => $balance,
            'monthIncome'  => $monthIncome,
            'monthExpense' => $monthExpense,
            'labels'       => $labels,
            'values'       => $values,
            'recent'       => $recent,
        ]);
    }
}
