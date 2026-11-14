@php($me = auth()->user())
<aside class="sidebar" aria-label="Menu utama">
    <a class="brand" href="{{ route('home') }}">
        <span class="brand-mark"><x-icon name="k" :size="20" /></span>
        <span class="brand-name">KasKu</span>
    </a>

    @if ($me->isAdmin())
        <nav>
            <ul class="side-nav">
                <li><a class="side-link" href="{{ route('admin.dashboard') }}" @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif>
                    <x-icon name="shield" /> Ringkasan</a></li>
                <li><a class="side-link" href="{{ route('admin.users') }}" @if (request()->routeIs('admin.users*')) aria-current="page" @endif>
                    <x-icon name="users" /> Pengguna</a></li>
                <li><a class="side-link" href="{{ route('admin.logs') }}" @if (request()->routeIs('admin.logs*')) aria-current="page" @endif>
                    <x-icon name="activity" /> Log aktivitas</a></li>
            </ul>
        </nav>
    @else
        <a class="btn btn-accent side-cta" href="{{ route('transactions.create') }}">
            <x-icon name="plus" :size="18" /> Catat transaksi
        </a>
        <nav>
            <ul class="side-nav">
                <li><a class="side-link" href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>
                    <x-icon name="home" /> Beranda</a></li>
                <li><a class="side-link" href="{{ route('transactions.index') }}" @if (request()->routeIs('transactions.*')) aria-current="page" @endif>
                    <x-icon name="receipt" /> Transaksi</a></li>
                <li><a class="side-link" href="{{ route('categories.index') }}" @if (request()->routeIs('categories.*')) aria-current="page" @endif>
                    <x-icon name="tag" /> Kategori</a></li>
                <li><a class="side-link" href="{{ route('report.show') }}" @if (request()->routeIs('report.*')) aria-current="page" @endif>
                    <x-icon name="chart" /> Laporan</a></li>
            </ul>
        </nav>
    @endif

    <div class="side-foot">
        <div class="me">
            <span class="avatar" aria-hidden="true">{{ \App\Support\Format::initial($me->username) }}</span>
            <div class="me-text">
                <div class="me-name">{{ $me->username }}</div>
                <div class="me-role">{{ $me->isAdmin() ? 'Admin' : 'Pengguna' }}</div>
            </div>
        </div>
        <a class="side-link" href="{{ route('password.edit') }}" @if (request()->routeIs('password.*')) aria-current="page" @endif>
            <x-icon name="key" /> Ganti password</a>
        {{-- Keluar lewat POST + token CSRF, bukan link GET --}}
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="side-link side-btn"><x-icon name="logout" /> Keluar</button>
        </form>
    </div>
</aside>
