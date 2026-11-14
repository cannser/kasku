@extends('layouts.app')
@section('title', $transaction ? 'Ubah Transaksi' : 'Catat Transaksi')

@php
    $action        = $transaction ? route('transactions.update', $transaction->id) : route('transactions.store');
    // Jenis awal: dari data lama, atau dari tautan cepat (?type=income|expense, hanya dua nilai ini yang diterima)
    $requested     = in_array(request('type'), ['income', 'expense'], true) ? request('type') : 'expense';
    $typeValue     = old('type', $transaction->type ?? $requested);
    $amountDefault = $transaction ? rtrim(rtrim((string) $transaction->amount, '0'), '.') : '';
    $dateDefault   = $transaction ? $transaction->trx_date->format('Y-m-d') : now()->format('Y-m-d');
@endphp

@section('content')
<a href="{{ route('transactions.index') }}" class="back-link"><x-icon name="chevron-left" :size="18" /> Daftar transaksi</a>
<div class="page-head">
    <h1 class="page-title">{{ $transaction ? 'Ubah transaksi' : 'Catat transaksi' }}</h1>
</div>

<div class="panel panel-pad form-narrow">
    @if ($categories->isEmpty())
        <div class="note"><x-icon name="alert" class="icon" :size="18" />
            <span>Kamu belum punya kategori. <a href="{{ route('categories.index') }}">Buat kategori</a> dulu sebelum mencatat transaksi.</span></div>
    @endif
    @include('partials.errors')
    <form method="post" action="{{ $action }}" novalidate class="form-stack">
        @csrf
        @if ($transaction) @method('PUT') @endif

        <div>
            <span class="form-label d-block" id="typeLabel">Jenis</span>
            <div class="seg" role="radiogroup" aria-labelledby="typeLabel">
                <input type="radio" name="type" id="t_expense" value="expense" @checked($typeValue === 'expense')>
                <label for="t_expense" class="is-expense"><x-icon name="arrow-up" :size="18" /> Pengeluaran</label>
                <input type="radio" name="type" id="t_income" value="income" @checked($typeValue === 'income')>
                <label for="t_income" class="is-income"><x-icon name="arrow-down" :size="18" /> Pemasukan</label>
            </div>
        </div>

        <div>
            <label for="amount" class="form-label">Nominal</label>
            <div class="money">
                <span class="money-prefix" aria-hidden="true">Rp</span>
                <input type="text" inputmode="decimal" class="form-control" id="amount" name="amount"
                       value="{{ old('amount', $amountDefault) }}" maxlength="16" placeholder="25000" required
                       aria-describedby="amountPreview" autocomplete="off">
            </div>
            <div class="money-preview" id="amountPreview" aria-live="polite">Angka saja. Desimal pakai titik, contoh 25000.50.</div>
        </div>

        <div>
            <label for="category_id" class="form-label">Kategori</label>
            <select class="form-select" id="category_id" name="category_id" required>
                <option value="">Pilih kategori</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected((string) old('category_id', $transaction->category_id ?? '') === (string) $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="trx_date" class="form-label">Tanggal</label>
            <input type="date" class="form-control" id="trx_date" name="trx_date"
                   value="{{ old('trx_date', $dateDefault) }}" required>
            <div class="chips">
                <button type="button" class="chip-btn" data-date-target="trx_date" data-date-offset="0">Hari ini</button>
                <button type="button" class="chip-btn" data-date-target="trx_date" data-date-offset="-1">Kemarin</button>
            </div>
        </div>

        <div>
            <label for="description" class="form-label">Keterangan <span class="text-muted fw-normal">(opsional)</span></label>
            <input type="text" class="form-control" id="description" name="description" maxlength="255"
                   value="{{ old('description', $transaction->description ?? '') }}" placeholder="Contoh: makan siang">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">{{ $transaction ? 'Simpan perubahan' : 'Simpan transaksi' }}</button>
            <a href="{{ route('transactions.index') }}" class="btn btn-ghost">Batal</a>
        </div>
    </form>
</div>
@endsection
