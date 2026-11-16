@extends('layouts.app')
@section('title', 'Lupa Password')

@section('content')
<div class="auth">
    @include('partials.auth-bar')
    <section class="auth-panel">
        <div class="auth-form">
            <h1 class="auth-title">Lupa password</h1>
            <p class="auth-sub">Masukkan username atau email akunmu.</p>
            @include('partials.errors')
            <form method="post" action="{{ route('password.email') }}" novalidate class="form-stack">
                @csrf
                <div>
                    <label for="identifier" class="form-label">Username atau email</label>
                    <input type="text" class="form-control" id="identifier" name="identifier" maxlength="120"
                           value="{{ old('identifier') }}" autocomplete="username" required autofocus>
                    <div class="form-text">
                        Permintaanmu dicatat untuk admin, yang bisa memberimu password sementara.
                        Jika email di server sudah aktif, tautan reset juga dikirim ke email akunmu.
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Kirim permintaan</button>
            </form>
            <p class="auth-switch"><a href="{{ route('login') }}">Kembali ke halaman masuk</a></p>
        </div>
    </section>
    <footer class="auth-foot">&copy; {{ date('Y') }} KasKu</footer>
</div>
@endsection
