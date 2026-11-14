@php($me = auth()->user())
<nav class="tabbar {{ $me->isAdmin() ? 'cols-4' : '' }}" aria-label="Menu utama">
    @if ($me->isAdmin())
        <a class="tab" href="{{ route('admin.dashboard') }}" @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif>
            <x-icon name="shield" /> Ringkasan</a>
        <a class="tab" href="{{ route('admin.users') }}" @if (request()->routeIs('admin.users*')) aria-current="page" @endif>
            <x-icon name="users" /> Pengguna</a>
        <a class="tab" href="{{ route('admin.logs') }}" @if (request()->routeIs('admin.logs*')) aria-current="page" @endif>
            <x-icon name="activity" /> Log</a>
    @else
        <a class="tab" href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>
            <x-icon name="home" /> Beranda</a>
        <a class="tab" href="{{ route('transactions.index') }}" @if (request()->routeIs('transactions.index')) aria-current="page" @endif>
            <x-icon name="receipt" /> Transaksi</a>
        <a class="tab tab-fab" href="{{ route('transactions.create') }}" aria-label="Catat transaksi baru">
            <span class="fab"><x-icon name="plus" :size="26" /></span>
        </a>
        <a class="tab" href="{{ route('report.show') }}" @if (request()->routeIs('report.*')) aria-current="page" @endif>
            <x-icon name="chart" /> Laporan</a>
    @endif

    <details class="more">
        <summary class="tab"><x-icon name="more" /> Lainnya</summary>
        <div class="more-panel">
            @unless ($me->isAdmin())
                <a class="side-link" href="{{ route('categories.index') }}"><x-icon name="tag" /> Kategori</a>
            @endunless
            <a class="side-link" href="{{ route('password.edit') }}"><x-icon name="key" /> Ganti password</a>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="side-link side-btn"><x-icon name="logout" /> Keluar ({{ $me->username }})</button>
            </form>
        </div>
    </details>
</nav>
