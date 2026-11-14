@extends('layouts.app')
@section('title', 'Transaksi')

@php
    $groups   = $transactions->getCollection()->groupBy(fn ($t) => $t->trx_date->format('Y-m-d'));
    $filtered = ! empty($type) || ! empty($categoryId);
@endphp

@section('content')
<div class="page-head">
    <div>
        <h1 class="page-title">Transaksi</h1>
        @if (\App\Support\Format::validMonth((string) $month))
            <p class="page-sub">{{ \App\Support\Format::monthLabel($month) }}</p>
        @endif
    </div>
    <div class="page-actions">
        <a href="{{ route('transactions.create') }}" class="btn btn-primary">
            <x-icon name="plus" :size="18" /> Catat transaksi</a>
    </div>
</div>

<div class="panel">
    <form method="get" action="{{ route('transactions.index') }}" class="filters">
        <div class="field">
            <label for="month" class="form-label">Bulan</label>
            <input type="month" class="form-control" id="month" name="month" value="{{ $month }}">
        </div>
        <div class="field">
            <label for="type" class="form-label">Jenis</label>
            <select class="form-select" id="type" name="type">
                <option value="">Semua</option>
                <option value="income" @selected($type === 'income')>Pemasukan</option>
                <option value="expense" @selected($type === 'expense')>Pengeluaran</option>
            </select>
        </div>
        <div class="field field-wide">
            <label for="category" class="form-label">Kategori</label>
            <select class="form-select" id="category" name="category">
                <option value="">Semua</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected($categoryId === (int) $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Terapkan</button>
            <a href="{{ route('transactions.index') }}" class="btn btn-ghost">Reset</a>
        </div>
    </form>

    @if ($transactions->isEmpty())
        <div class="empty">
            <div class="empty-icon"><x-icon name="receipt" :size="26" /></div>
            @if ($filtered)
                <h3>Tidak ada transaksi yang cocok</h3>
                <p>Coba ubah filter atau pilih bulan lain.</p>
                <a href="{{ route('transactions.index') }}" class="btn btn-ghost">Reset filter</a>
            @else
                <h3>Belum ada transaksi di bulan ini</h3>
                <p>Catat pemasukan atau pengeluaran pertamamu.</p>
                <a href="{{ route('transactions.create') }}" class="btn btn-primary">
                    <x-icon name="plus" :size="18" /> Catat transaksi</a>
            @endif
        </div>
    @else
        @foreach ($groups as $date => $items)
            <section class="day">
                <h2 class="day-head">{{ \App\Support\Format::dateLabel($items->first()->trx_date) }}</h2>
                <ul class="ledger">
                    @foreach ($items as $t)
                        <li class="trx">
                            <span class="cat-chip cat-{{ ($t->category_id ?? 0) % 8 }}" aria-hidden="true">{{ \App\Support\Format::initial($t->category?->name) }}</span>
                            <div class="trx-main">
                                <div class="ledger-title">{{ $t->description ?: $t->category?->name }}</div>
                                <div class="ledger-sub">
                                    <span class="cat-tag cat-{{ ($t->category_id ?? 0) % 8 }}">{{ $t->category?->name }}</span>
                                </div>
                            </div>
                            <div class="amount amount-{{ $t->type }}">{{ $t->type === 'income' ? '+' : '-' }} @rupiah($t->amount)</div>
                            <div class="trx-actions">
                                <a href="{{ route('transactions.edit', $t->id) }}" class="btn btn-ghost btn-sm btn-icon"
                                   aria-label="Ubah transaksi {{ $t->category?->name }} {{ $t->trx_date->format('d-m-Y') }}">
                                    <x-icon name="edit" :size="16" /></a>
                                <form method="post" action="{{ route('transactions.destroy', $t->id) }}"
                                      data-confirm="Hapus transaksi ini? Tindakan ini tidak bisa dibatalkan.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger-soft btn-sm btn-icon"
                                            aria-label="Hapus transaksi {{ $t->category?->name }} {{ $t->trx_date->format('d-m-Y') }}">
                                        <x-icon name="trash" :size="16" /></button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    @endif

    <div class="panel-foot d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="text-muted small">{{ $transactions->total() }} transaksi ditemukan.</span>
        @if ($transactions->hasPages())
            {{ $transactions->links() }}
        @endif
    </div>
</div>
@endsection
