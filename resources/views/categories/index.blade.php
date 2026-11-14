@extends('layouts.app')
@section('title', 'Kategori')

@section('content')
<div class="page-head">
    <div>
        <h1 class="page-title">Kategori</h1>
        <p class="page-sub">Kelompokkan transaksimu supaya rekapnya mudah dibaca.</p>
    </div>
</div>

<div class="panel panel-pad mb-4">
    <form method="post" action="{{ route('categories.store') }}" class="inline-add">
        @csrf
        <div class="field">
            <label for="new_name" class="form-label">Kategori baru</label>
            <input type="text" class="form-control" id="new_name" name="name" maxlength="50" required
                   placeholder="Contoh: Pulsa dan internet">
        </div>
        <button type="submit" class="btn btn-primary"><x-icon name="plus" :size="18" /> Tambah kategori</button>
    </form>
</div>

@if ($categories->isEmpty())
    <div class="panel">
        <div class="empty">
            <div class="empty-icon"><x-icon name="tag" :size="26" /></div>
            <h3>Belum ada kategori</h3>
            <p>Tambahkan kategori pertamamu pada kolom di atas.</p>
        </div>
    </div>
@else
    <ul class="cat-grid">
        @foreach ($categories as $c)
            <li class="cat-card">
                <span class="cat-chip cat-{{ $c->id % 8 }}" aria-hidden="true">{{ \App\Support\Format::initial($c->name) }}</span>
                <div class="cat-main">
                    <form method="post" action="{{ route('categories.update', $c->id) }}" class="cat-edit">
                        @csrf
                        @method('PUT')
                        <input type="text" class="form-control form-control-sm" name="name"
                               value="{{ $c->name }}" maxlength="50" required aria-label="Nama kategori {{ $c->name }}">
                        <button type="submit" class="btn btn-ghost btn-sm btn-icon" aria-label="Simpan nama kategori {{ $c->name }}">
                            <x-icon name="check" :size="16" /></button>
                    </form>
                    <div class="cat-count">{{ $c->transactions_count }} transaksi</div>
                </div>
                <form method="post" action="{{ route('categories.destroy', $c->id) }}"
                      data-confirm="Hapus kategori ini? Kategori yang masih dipakai transaksi tidak bisa dihapus.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger-soft btn-icon" aria-label="Hapus kategori {{ $c->name }}">
                        <x-icon name="trash" :size="18" /></button>
                </form>
            </li>
        @endforeach
    </ul>
@endif
@endsection
