@extends('layouts.app')
@section('title', 'Ringkasan Admin')

@section('content')
<div class="page-head">
    <div>
        <h1 class="page-title">Ringkasan admin</h1>
        <p class="page-sub">Demi privasi, admin hanya melihat statistik agregat dan tidak bisa membuka isi transaksi pengguna.</p>
    </div>
</div>

<div class="tiles">
    @foreach ($stats as $label => $item)
        <div class="tile">
            <div class="tile-label">{{ $label }}</div>
            <div class="tile-value {{ $item[1] }}">{{ number_format((int) $item[0], 0, ',', '.') }}</div>
        </div>
    @endforeach
</div>

<div class="panel">
    <div class="panel-head">
        <h2 class="panel-title">Aktivitas terbaru</h2>
        <a href="{{ route('admin.logs') }}" class="btn btn-ghost btn-sm">Lihat semua</a>
    </div>
    <div class="scroll-x mt-3">
        <table class="dtable">
            <thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>IP</th></tr></thead>
            <tbody>
            @forelse ($recentLogs as $l)
                <tr>
                    <td class="nowrap">{{ $l->created_at?->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $l->user?->username ?? '-' }}</td>
                    <td class="log-action {{ \Illuminate\Support\Str::startsWith($l->action, 'Login gagal') ? 'log-warn' : '' }}">{{ $l->action }}</td>
                    <td>{{ $l->ip }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="t-center text-muted py-4">Belum ada aktivitas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
