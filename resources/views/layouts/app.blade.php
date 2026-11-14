<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f1b3d">
    <title>@yield('title', 'KasKu') - KasKu</title>
    <link rel="stylesheet" href="{{ config('kasku.cdn.bootstrap_css.url') }}"
          integrity="{{ config('kasku.cdn.bootstrap_css.sri') }}" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>
<body>
<a class="skip-link" href="#main">Lewati ke konten</a>

@auth
    @include('partials.sidebar')
    <div class="shell">
        <header class="topbar">
            <a class="brand" href="{{ route('home') }}">
                <span class="brand-mark"><x-icon name="k" :size="20" /></span>
                <span class="brand-name">KasKu</span>
            </a>
            <span class="avatar" aria-hidden="true">{{ \App\Support\Format::initial(auth()->user()->username) }}</span>
        </header>
        <main id="main" class="content">
            @yield('content')
            <p class="foot">&copy; {{ date('Y') }} KasKu</p>
        </main>
    </div>
    @include('partials.tabbar')
@else
    <main id="main">
        @yield('content')
    </main>
@endauth

@include('partials.flash')

{{-- Dialog konfirmasi (menggantikan window.confirm); dikendalikan oleh assets/js/app.js --}}
<dialog id="confirmDialog" class="confirm" aria-labelledby="confirmTitle">
    <form method="dialog">
        <div class="confirm-body">
            <h2 id="confirmTitle">Konfirmasi</h2>
            <p id="confirmText"></p>
        </div>
        <div class="confirm-actions">
            <button type="submit" value="cancel" class="btn btn-ghost">Batal</button>
            <button type="submit" value="ok" class="btn btn-primary">Ya, lanjutkan</button>
        </div>
    </form>
</dialog>

{{-- Bootstrap JS tidak dipakai lagi: lebih sedikit script pihak ketiga --}}
@stack('scripts')
<script src="{{ asset('assets/js/app.js') }}"></script>
</body>
</html>
