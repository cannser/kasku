@extends('layouts.app')
@section('title', 'Log Aktivitas')

@section('content')
<div class="page-head">
    <div>
        <h1 class="page-title">Log aktivitas</h1>
        <p class="page-sub">Catatan login, perubahan data, dan tindakan admin. Password tidak pernah dicatat.</p>
    </div>
</div>

<form method="get" action="{{ route('admin.logs') }}" class="searchbar" role="search">
    <input type="search" class="form-control" name="q" value="{{ $q }}" maxlength="50"
           placeholder="Cari aksi, username, atau IP (mis. Login gagal)" aria-label="Cari log">
    <button class="btn btn-primary" type="submit"><x-icon name="search" :size="18" /> Cari</button>
</form>

<div class="panel">
    <div class="scroll-x">
        <table class="dtable">
            <thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>IP</th></tr></thead>
            <tbody>
            @forelse ($logs as $l)
                <tr>
                    <td class="nowrap">{{ $l->created_at?->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $l->user?->username ?? '-' }}</td>
                    <td class="log-action {{ \Illuminate\Support\Str::startsWith($l->action, 'Login gagal') ? 'log-warn' : '' }}">{{ $l->action }}</td>
                    <td>{{ $l->ip }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="t-center text-muted py-4">Tidak ada log yang cocok.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-foot d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="text-muted small">{{ $logs->total() }} entri.</span>
        @if ($logs->hasPages())
            {{ $logs->links() }}
        @endif
    </div>
</div>
@endsection
