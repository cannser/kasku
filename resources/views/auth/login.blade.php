@extends('layouts.app')
@section('title', 'Masuk')

@section('content')
<div class="auth">
    @include('partials.auth-bar')
    <section class="auth-panel">
        <div class="auth-form">
            <h1 class="auth-title">Masuk</h1>
            <p class="auth-sub">Lanjutkan mencatat keuanganmu.</p>
            @include('partials.errors')
            <form method="post" action="{{ route('login.store') }}" novalidate class="form-stack">
                @csrf
                <div>
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" maxlength="50"
                           value="{{ old('username') }}" autocomplete="username" required autofocus>
                </div>
                @include('partials.password-field', [
                    'id' => 'password', 'name' => 'password', 'label' => 'Password', 'autocomplete' => 'current-password',
                ])
                <a class="auth-forgot" href="{{ route('password.forgot') }}">Lupa password?</a>
                <button type="submit" class="btn btn-primary w-100">Masuk</button>
            </form>
            <p class="auth-switch">Belum punya akun? <a href="{{ route('register') }}">Daftar</a></p>
        </div>
    </section>
    <footer class="auth-foot">&copy; {{ date('Y') }} KasKu</footer>
</div>
@endsection
