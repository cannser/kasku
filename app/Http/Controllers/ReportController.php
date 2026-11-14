<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ReadsQuery;
use App\Models\ActivityLog;
use App\Support\Format;
use App\Support\ReportPdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    use ReadsQuery;

    public function show(Request $request): View
    {
        $month = $this->monthFrom($request);
        [$start, $end] = Format::monthBounds($month);

        $recap   = $this->recap($request, $start, $end);
        $income  = (float) $recap->where('trx_type', 'income')->sum('total');
        $expense = (float) $recap->where('trx_type', 'expense')->sum('total');

        return view('report.show', compact('month', 'recap', 'income', 'expense'));
    }

    /**
     * Ekspor laporan bulanan. Format dipilih lewat ?format=pdf atau ?format=csv
     * (daftar putih; nilai lain dianggap csv). Semua data dibatasi ke milik pengguna yang login.
     */
    public function export(Request $request): Response
    {
        $month = $this->monthFrom($request);
        [$start, $end] = Format::monthBounds($month);

        $rows = $request->user()->transactions()->with('category:id,name')
            ->where('trx_date', '>=', $start)->where('trx_date', '<', $end)
            ->orderBy('trx_date')->orderBy('id')
            ->get();

        if ($this->qs($request, 'format') === 'pdf') {
            return $this->exportPdf($request, $month, $start, $end, $rows);
        }

        return $this->exportCsv($month, $rows);
    }

    private function exportCsv(string $month, $rows): Response
    {
        ActivityLog::record('Ekspor CSV bulan ' . $month);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");  // BOM agar Excel membaca UTF-8 dengan benar
            fwrite($out, "sep=,\r\n");      // petunjuk Excel: pemisah koma (Excel berlocale Indonesia biasanya memakai titik koma)
            fputcsv($out, ['Tanggal', 'Jenis', 'Kategori', 'Keterangan', 'Nominal'], ',', '"', '\\');
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->trx_date->format('Y-m-d'),
                    $r->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
                    Format::csvSafe($r->category?->name),     // cegah CSV/formula injection
                    Format::csvSafe($r->description),
                    Format::plainNumber($r->amount),           // 3000000 (bukan 3000000.00) agar terbaca sebagai angka
                ], ',', '"', '\\');
            }
            fclose($out);
        }, 'kasku-' . $month . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportPdf(Request $request, string $month, string $start, string $end, $rows): Response
    {
        $recap = $this->recap($request, $start, $end);
        ActivityLog::record('Ekspor PDF bulan ' . $month);

        $pdf = ReportPdf::render([
            'user'      => $request->user()->username,
            'month'     => $month,
            'generated' => now(),
            'income'    => (float) $recap->where('trx_type', 'income')->sum('total'),
            'expense'   => (float) $recap->where('trx_type', 'expense')->sum('total'),
            'recap'     => $recap->map(fn ($r) => [
                'category' => (string) $r->category_name,
                'type'     => (string) $r->trx_type,
                'count'    => (int) $r->cnt,
                'total'    => (float) $r->total,
            ])->all(),
            'rows'      => $rows->map(fn ($r) => [
                'date'        => $r->trx_date,
                'type'        => $r->type,
                'category'    => (string) $r->category?->name,
                'description' => (string) $r->description,
                'amount'      => (float) $r->amount,
            ])->all(),
        ]);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="kasku-' . $month . '.pdf"',
            'Cache-Control'       => 'private, no-store',
        ]);
    }

    /** Rekap per kategori untuk satu bulan, hanya transaksi milik pengguna yang login. */
    private function recap(Request $request, string $start, string $end)
    {
        return $request->user()->transactions()
            ->join('categories', 'categories.id', '=', 'transactions.category_id')
            ->where('transactions.trx_date', '>=', $start)
            ->where('transactions.trx_date', '<', $end)
            ->selectRaw('categories.name as category_name, transactions.type as trx_type, COUNT(*) as cnt, SUM(transactions.amount) as total')
            ->groupBy('categories.id', 'categories.name', 'transactions.type')
            ->orderBy('transactions.type', 'desc')
            ->orderByDesc('total')
            ->get();
    }

    /** Bulan dari query string divalidasi ketat; jika tidak valid dipakai bulan ini. */
    private function monthFrom(Request $request): string
    {
        $month = $this->qs($request, 'month');

        return Format::validMonth($month) ? $month : now()->format('Y-m');
    }
}
