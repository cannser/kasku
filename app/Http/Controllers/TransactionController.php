<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ReadsQuery;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\ActivityLog;
use App\Models\Transaction;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CRUD transaksi. Setiap query lewat $request->user()->transactions() sehingga otomatis
 * berkondisi "user_id = pengguna login". Mengakses id milik orang lain menghasilkan 404 (IDOR dicegah).
 */
class TransactionController extends Controller
{
    use ReadsQuery;

    public function index(Request $request): View
    {
        $user       = $request->user();
        $month      = $this->qs($request, 'month');
        $type       = $this->qs($request, 'type');
        $categoryId = $this->qs($request, 'category');

        $query = $user->transactions()->with('category:id,name');

        if ($month !== '' && Format::validMonth($month)) {
            [$start, $end] = Format::monthBounds($month);
            $query->where('trx_date', '>=', $start)->where('trx_date', '<', $end);
        } else {
            $month = '';
        }

        if (in_array($type, ['income', 'expense'], true)) {
            $query->where('type', $type);
        } else {
            $type = '';
        }

        // Filter kategori: hanya angka murni yang dipakai, selain itu diabaikan
        $categoryId = ($categoryId !== '' && ctype_digit($categoryId)) ? (int) $categoryId : 0;
        if ($categoryId > 0) {
            $query->where('category_id', $categoryId);
        }

        $transactions = $query->orderByDesc('trx_date')->orderByDesc('id')
            ->paginate((int) config('kasku.per_page'))
            ->withQueryString();

        $categories = $user->categories()->orderBy('name')->get(['id', 'name']);

        return view('transactions.index', compact('transactions', 'categories', 'month', 'type', 'categoryId'));
    }

    public function create(Request $request): View
    {
        return view('transactions.form', [
            'transaction' => null,
            'categories'  => $request->user()->categories()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        // user_id diisi dari relasi, bukan dari input (dan tidak ada di $fillable)
        $transaction = $request->user()->transactions()->create($request->validated());
        ActivityLog::record('Tambah transaksi #' . $transaction->id);

        return redirect()->route('transactions.index')->with('success', 'Transaksi ditambahkan.');
    }

    public function edit(Request $request, int $transaction): View
    {
        return view('transactions.form', [
            'transaction' => $this->findOwned($request, $transaction),
            'categories'  => $request->user()->categories()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(StoreTransactionRequest $request, int $transaction): RedirectResponse
    {
        $model = $this->findOwned($request, $transaction);
        $model->update($request->validated());
        ActivityLog::record('Ubah transaksi #' . $model->id);

        return redirect()->route('transactions.index')->with('success', 'Transaksi diperbarui.');
    }

    public function destroy(Request $request, int $transaction): RedirectResponse
    {
        $model = $this->findOwned($request, $transaction);
        $model->delete();
        ActivityLog::record('Hapus transaksi #' . $transaction);

        return redirect()->route('transactions.index')->with('success', 'Transaksi dihapus.');
    }

    /** Cari transaksi MILIK pengguna login. Tidak ada atau milik orang lain: sama-sama 404. */
    private function findOwned(Request $request, int $id): Transaction
    {
        $transaction = $request->user()->transactions()->find($id);

        if (! $transaction) {
            ActivityLog::record('Akses transaksi tidak valid #' . $id);
            abort(404, 'Transaksi tidak ditemukan.');
        }

        return $transaction;
    }
}
