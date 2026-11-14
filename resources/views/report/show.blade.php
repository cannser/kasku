@extends('layouts.app')
@section('title', 'Laporan')

@php
    $net  = $income - $expense;
    $prev = \App\Support\Format::shiftMonth($month, -1);
    $next = \App\Support\Format::shiftMonth($month, 1);
@endphp

@section('content')
<div class="page-head">
    <div>
        <h1 class="page-title">Laporan {{ \App\Support\Format::monthLabel($month) }}</h1>
        <p class="page-sub">Rekap pemasukan dan pengeluaran per kategori.</p>
    </div>
    <div class="page-actions">
        <div class="month-nav">
            <a href="{{ route('report.show', ['month' => $prev]) }}" class="btn btn-icon btn-sm" aria-label="Bulan sebelumnya">
                <x-icon name="chevron-left" :size="18" /></a>
            <form method="get" action="{{ route('report.show') }}">
                <label for="month" class="visually-hidden">Pilih bulan</label>
                <input type="month" class="form-control form-control-sm" id="month" name="month" value="{{ $month }}"
                       required data-autosubmit>
                <noscript><button type="submit" class="btn btn-primary btn-sm">Tampilkan</button></noscript>
            </form>
            <a href="{{ route('report.show', ['month' => $next]) }}" class="btn btn-icon btn-sm" aria-label="Bulan berikutnya">
                <x-icon name="chevron-right" :size="18" /></a>
        </div>
        <div class="export-group" role="group" aria-label="Ekspor laporan">
            <span class="export-label"><x-icon name="download" :size="18" /> Ekspor</span>
            <a class="btn" href="{{ route('report.export', ['month' => $month, 'format' => 'pdf']) }}">PDF</a>
            <a class="btn" href="{{ route('report.export', ['month' => $month, 'format' => 'csv']) }}">CSV</a>
        </div>
    </div>
</div>

<div class="panel mb-4">
    <div class="fig-strip">
        <div class="fig">
            <div class="fig-label">Total pemasukan</div>
            <div class="fig-value stat-income">@rupiah($income)</div>
        </div>
        <div class="fig">
            <div class="fig-label">Total pengeluaran</div>
            <div class="fig-value stat-expense">@rupiah($expense)</div>
        </div>
        <div class="fig">
            <div class="fig-label">Selisih bulan ini</div>
            <div class="fig-value {{ $net >= 0 ? 'stat-income' : 'stat-expense' }}">@rupiah($net)</div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h2 class="panel-title">Rekap per kategori</h2></div>
    @if ($recap->isEmpty())
        <div class="empty">
            <div class="empty-icon"><x-icon name="chart" :size="26" /></div>
            <h3>Tidak ada transaksi pada bulan ini</h3>
            <p>Pilih bulan lain atau catat transaksi baru.</p>
            <a href="{{ route('transactions.create') }}" class="btn btn-primary"><x-icon name="plus" :size="18" /> Catat transaksi</a>
        </div>
    @else
        <div class="scroll-x mt-3">
            <table class="dtable">
                <thead>
                    <tr><th>Kategori</th><th>Jenis</th><th class="t-center">Jumlah transaksi</th><th>Porsi</th><th class="t-right">Total</th></tr>
                </thead>
                <tbody>
                @foreach ($recap as $r)
                    @php($share = \App\Support\Format::pct($r->total, $r->trx_type === 'income' ? $income : $expense))
                    <tr>
                        <td>{{ $r->category_name }}</td>
                        <td>
                            @if ($r->trx_type === 'income')
                                <span class="pill pill-ok">Pemasukan</span>
                            @else
                                <span class="pill pill-off">Pengeluaran</span>
                            @endif
                        </td>
                        <td class="t-center">{{ $r->cnt }}</td>
                        <td class="share">
                            <svg class="mini-bar" viewBox="0 0 100 6" preserveAspectRatio="none" role="img"
                                 aria-label="{{ (int) round((float) $share) }} persen dari total {{ $r->trx_type === 'income' ? 'pemasukan' : 'pengeluaran' }}">
                                <rect class="fill {{ $r->trx_type === 'income' ? 'fill-in' : 'fill-out' }}" width="{{ $share }}" height="6"/>
                            </svg>
                        </td>
                        <td class="t-right amount amount-{{ $r->trx_type }}">@rupiah($r->total)</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
