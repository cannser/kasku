@extends('layouts.app')
@section('title', 'Buat Password Baru')

@section('content')
<div class="auth">
    @include('partials.auth-bar')
    <section class="auth-panel">
        <div class="auth-form">
            <h1 class="auth-title">Password baru</h1>
            <p class="auth-sub">Tautan ini hanya bisa dipakai satu kali.</p>
            @include('partials.errors')
            <form method="post" action="{{ route('password.store') }}" novalidate class="form-stack">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                @include('partials.password-field', [
                    'id' => 'password', 'name' => 'password', 'label' => 'Password baru',
                    'autocomplete' => 'new-password', 'rules' => true, 'autofocus' => true,
                ])
                @include('partials.password-field', [
                    'id' => 'password_confirmation', 'name' => 'password_confirmation', 'label' => 'Ulangi password baru',
                    'autocomplete' => 'new-password',
                ])
                <button type="submit" class="btn btn-primary w-100">Simpan password</button>
            </form>
        </div>
    </section>
    <footer class="auth-foot">&copy; {{ date('Y') }} KasKu</footer>
</div>
@endsection
