{{-- Kerangka halaman galat. Mandiri (tidak memakai sesi/auth) supaya tetap aman tampil saat terjadi error. --}}
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') @yield('title') - KasKu</title>
    <link rel="stylesheet" href="{{ config('kasku.cdn.bootstrap_css.url') }}"
          integrity="{{ config('kasku.cdn.bootstrap_css.sri') }}" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>
<body>
<main class="err">
    <div class="err-box">
        <p class="err-code">@yield('code')</p>
        <h1 class="err-title">@yield('title')</h1>
        <p class="err-text">@yield('message')</p>
        <a href="{{ url('/') }}" class="btn btn-primary">Kembali ke beranda</a>
    </div>
</main>
</body>
</html>
