@extends('layouts.app')
@section('title', 'Daftar')

@section('content')
<div class="auth">
    @include('partials.auth-bar')
    <section class="auth-panel">
        <div class="auth-form">
            <h1 class="auth-title">Buat akun</h1>
            <p class="auth-sub">Gratis dan cuma butuh satu menit.</p>
            @include('partials.errors')
            {{-- Form hanya memuat 4 field. Tidak ada field "role" (dan server menolaknya jika dikirim) --}}
            <form method="post" action="{{ route('register.store') }}" novalidate class="form-stack">
                @csrf
                <div>
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" maxlength="30"
                           value="{{ old('username') }}" autocomplete="username" required autofocus>
                    <div class="form-text">3-30 karakter: huruf, angka, underscore.</div>
                </div>
                <div>
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" maxlength="120"
                           value="{{ old('email') }}" autocomplete="email" required>
                </div>
                @include('partials.password-field', [
                    'id' => 'password', 'name' => 'password', 'label' => 'Password',
                    'autocomplete' => 'new-password', 'rules' => true,
                ])
                @include('partials.password-field', [
                    'id' => 'password_confirmation', 'name' => 'password_confirmation', 'label' => 'Ulangi password',
                    'autocomplete' => 'new-password',
                ])
                <button type="submit" class="btn btn-primary w-100">Buat akun</button>
            </form>
            <p class="auth-switch">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
        </div>
    </section>
    <footer class="auth-foot">&copy; {{ date('Y') }} KasKu</footer>
</div>
@endsection
