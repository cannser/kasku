@extends('layouts.app')
@section('title', 'Beranda')

@push('scripts')
    <script src="{{ config('kasku.cdn.chartjs.url') }}"
            integrity="{{ config('kasku.cdn.chartjs.sri') }}" crossorigin="anonymous"></script>
@endpush

@php
    $monthName  = \App\Support\Format::monthLabel($thisMonth);
    $monthTotal = $monthIncome + $monthExpense;
    $incPct     = (float) \App\Support\Format::pct($monthIncome, $monthTotal);
    $net        = $monthIncome - $monthExpense;
    $saved      = $monthIncome > 0 ? (int) round($net / $monthIncome * 100) : null;
    $totalSpend = array_sum($values);
    // Bar pemasukan vs pengeluaran: segmen pengeluaran dimulai sedikit setelah segmen pemasukan (celah kecil)
    $expX = ($incPct > 0 && $incPct < 100) ? $incPct + 0.6 : $incPct;
@endphp

@section('content')
<div class="page-head">
    <div>
        <h1 class="page-title">Halo, {{ auth()->user()->username }}</h1>
        <p class="page-sub">Ringkasan keuanganmu, {{ $monthName }}.</p>
    </div>
    <div class="page-actions pair">
        <a href="{{ route('transactions.create', ['type' => 'expense']) }}" class="btn btn-ghost">
            <x-icon name="arrow-up" :size="18" />
            <span class="d-none d-sm-inline">Catat pengeluaran</span><span class="d-sm-none">Pengeluaran</span></a>
        <a href="{{ route('transactions.create', ['type' => 'income']) }}" class="btn btn-ghost">
            <x-icon name="arrow-down" :size="18" />
            <span class="d-none d-sm-inline">Catat pemasukan</span><span class="d-sm-none">Pemasukan</span></a>
    </div>
</div>

<section class="hero" aria-label="Saldo">
    <p class="hero-label">Saldo saat ini</p>
    <p class="hero-amount {{ $balance < 0 ? 'is-neg' : '' }}">@rupiah($balance)</p>

    <svg class="split" viewBox="0 0 100 12" preserveAspectRatio="none" role="img"
         aria-label="Perbandingan pemasukan dan pengeluaran {{ $monthName }}">
        @if ($monthTotal > 0)
            @if ($incPct > 0)
                <rect class="inc" x="0" y="0" width="{{ number_format($incPct, 2, '.', '') }}" height="12"/>
            @endif
            @if ($incPct < 100)
                <rect class="exp" x="{{ number_format($expX, 2, '.', '') }}" y="0"
                      width="{{ number_format(100 - $expX, 2, '.', '') }}" height="12"/>
            @endif
        @endif
    </svg>

    <div class="hero-legend">
        <div>
            <div class="hero-fig-label"><span class="dot dot-in"></span> Pemasukan {{ $monthName }}</div>
            <div class="hero-fig-value">@rupiah($monthIncome)</div>
        </div>
        <div>
            <div class="hero-fig-label"><span class="dot dot-out"></span> Pengeluaran {{ $monthName }}</div>
            <div class="hero-fig-value">@rupiah($monthExpense)</div>
        </div>
    </div>

    <p class="hero-note">
        @if ($monthTotal <= 0)
            Belum ada transaksi bulan ini. Mulai dengan mencatat pemasukan atau pengeluaran pertamamu.
        @elseif ($net >= 0 && $saved !== null)
            Kamu menyisihkan {{ $saved }}% dari pemasukan bulan ini.
        @elseif ($net >= 0)
            Bulan ini belum ada pemasukan yang tercatat.
        @else
            Pengeluaran bulan ini melebihi pemasukan sebesar @rupiah(abs($net)).
        @endif
    </p>
</section>

<div class="dash-grid">
    <section class="panel" aria-labelledby="spendTitle">
        <div class="panel-head"><h2 class="panel-title" id="spendTitle">Ke mana uangmu pergi</h2></div>
        <div class="panel-body">
            @if (count($values) > 0)
                <div class="spend">
                    <div class="donut">
                        {{-- Data grafik lewat atribut (di-escape), bukan inline script: sesuai CSP --}}
                        <canvas id="expenseChart" role="img" aria-label="Grafik pengeluaran per kategori"
                                data-labels="{{ json_encode($labels, JSON_UNESCAPED_UNICODE) }}"
                                data-values="{{ json_encode($values) }}"></canvas>
                        <div class="donut-center"><small>Total keluar</small><strong>@rupiah($totalSpend)</strong></div>
                    </div>
                    <ul class="rank">
                        @foreach ($labels as $i => $name)
                            @php($share = \App\Support\Format::pct($values[$i], $totalSpend))
                            <li class="rank-row">
                                <span class="rank-name"><span class="dot dot-{{ $i % 8 }}"></span><span>{{ $name }}</span></span>
                                <span class="rank-val">@rupiah($values[$i])<small>{{ (int) round((float) $share) }}%</small></span>
                                <svg class="mini-bar" viewBox="0 0 100 6" preserveAspectRatio="none" aria-hidden="true">
                                    <rect class="fill-{{ $i % 8 }}" width="{{ $share }}" height="6"/>
                                </svg>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="empty">
                    <div class="empty-icon"><x-icon name="chart" :size="26" /></div>
                    <h3>Belum ada pengeluaran bulan ini</h3>
                    <p>Catat pengeluaran pertamamu untuk melihat ke mana uangmu pergi.</p>
                    <a href="{{ route('transactions.create', ['type' => 'expense']) }}" class="btn btn-primary">
                        <x-icon name="plus" :size="18" /> Catat pengeluaran</a>
                </div>
            @endif
        </div>
    </section>

    <section class="panel" aria-labelledby="recentTitle">
        <div class="panel-head">
            <h2 class="panel-title" id="recentTitle">Transaksi terbaru</h2>
            <a href="{{ route('transactions.index') }}" class="btn btn-ghost btn-sm">Lihat semua</a>
        </div>
        <div class="panel-body">
            @if ($recent->isEmpty())
                <div class="empty">
                    <div class="empty-icon"><x-icon name="receipt" :size="26" /></div>
                    <h3>Belum ada transaksi</h3>
                    <p>Transaksi yang kamu catat akan muncul di sini.</p>
                    <a href="{{ route('transactions.create') }}" class="btn btn-primary">
                        <x-icon name="plus" :size="18" /> Catat transaksi</a>
                </div>
            @else
                <ul class="ledger">
                    @foreach ($recent as $r)
                        <li class="ledger-row">
                            <div class="datebox">
                                <b>{{ $r->trx_date->format('j') }}</b>
                                <span>{{ \App\Support\Format::monthShort((int) $r->trx_date->format('n')) }}</span>
                            </div>
                            <div class="ledger-main">
                                <div class="ledger-title">{{ $r->description ?: $r->category?->name }}</div>
                                <div class="ledger-sub">
                                    <span class="cat-tag cat-{{ ($r->category_id ?? 0) % 8 }}">{{ $r->category?->name }}</span>
                                </div>
                            </div>
                            <div class="amount amount-{{ $r->type }}">{{ $r->type === 'income' ? '+' : '-' }} @rupiah($r->amount)</div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
</div>
@endsection
