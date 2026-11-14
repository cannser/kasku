@php($navUser = auth()->user())
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('home') }}">KasKu</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu"
                aria-controls="navMenu" aria-expanded="false" aria-label="Buka menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto">
                @if ($navUser && $navUser->isAdmin())
                    <li class="nav-item"><a class="nav-link" href="{{ route('admin.dashboard') }}">Dashboard Admin</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('admin.users') }}">Pengguna</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('admin.logs') }}">Log Aktivitas</a></li>
                @elseif ($navUser)
                    <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('transactions.index') }}">Transaksi</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('categories.index') }}">Kategori</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('report.show') }}">Laporan</a></li>
                @endif
            </ul>
            <ul class="navbar-nav align-items-lg-center gap-lg-2">
                @if ($navUser)
                    <li class="nav-item">
                        <span class="navbar-text">{{ $navUser->username }}
                            <span class="badge text-bg-light">{{ $navUser->role }}</span></span>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('password.edit') }}">Ganti Password</a></li>
                    <li class="nav-item">
                        {{-- Logout lewat POST + token CSRF (@csrf), bukan link GET --}}
                        <form method="post" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-light">Keluar</button>
                        </form>
                    </li>
                @else
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Masuk</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('register') }}">Daftar</a></li>
                @endif
            </ul>
        </div>
    </div>
</nav>
