@extends('layouts.app')
@section('title', 'Kelola Pengguna')

@section('content')
<div class="page-head">
    <div>
        <h1 class="page-title">Kelola pengguna</h1>
        <p class="page-sub">Aktifkan atau nonaktifkan akun.</p>
    </div>
</div>

<form method="get" action="{{ route('admin.users') }}" class="searchbar" role="search">
    <input type="search" class="form-control" name="q" value="{{ $q }}" maxlength="50"
           placeholder="Cari username atau email" aria-label="Cari pengguna">
    <button class="btn btn-primary" type="submit"><x-icon name="search" :size="18" /> Cari</button>
</form>

<div class="panel">
    <div class="scroll-x">
        <table class="dtable">
            <thead>
                <tr><th>ID</th><th>Username</th><th>Email</th><th>Peran</th><th>Status</th><th>Terdaftar</th><th class="t-right">Aksi</th></tr>
            </thead>
            <tbody>
            @forelse ($users as $u)
                @php($self = (int) $u->id === (int) auth()->id())
                <tr>
                    <td>{{ $u->id }}</td>
                    <td>{{ $u->username }} @if ($self)<span class="pill pill-me">Anda</span>@endif</td>
                    <td>{{ $u->email }}</td>
                    <td>
                        @if ($u->role === 'admin')
                            <span class="pill pill-admin">Admin</span>
                        @else
                            <span class="pill pill-user">Pengguna</span>
                        @endif
                    </td>
                    <td>
                        @if ($u->is_active)
                            <span class="pill pill-ok">Aktif</span>
                        @else
                            <span class="pill pill-off">Nonaktif</span>
                        @endif
                    </td>
                    <td class="nowrap">{{ $u->created_at?->format('d-m-Y') }}</td>
                    <td>
                        @if (! $self)
                            <div class="row-actions">
                                <form method="post" action="{{ route('admin.users.toggle', $u->id) }}"
                                      data-confirm="{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $u->username }}?">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $u->is_active ? 'btn-danger-soft' : 'btn-ok-soft' }}">
                                        {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="row-actions"><span class="text-muted small">-</span></div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="t-center text-muted py-4">Tidak ada pengguna yang cocok.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($users->hasPages())
        <div class="panel-foot">{{ $users->links() }}</div>
    @endif
</div>
@endsection