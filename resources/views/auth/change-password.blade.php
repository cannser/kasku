@extends('layouts.app')
@section('title', 'Ganti Password')

@section('content')
<div class="page-head">
    <div>
        <h1 class="page-title">Ganti password</h1>
        <p class="page-sub">Gunakan password yang tidak dipakai di situs lain.</p>
    </div>
</div>
<div class="panel panel-pad form-narrow">
    @include('partials.errors')
    <form method="post" action="{{ route('password.update') }}" novalidate class="form-stack">
        @csrf
        @method('PUT')
        @include('partials.password-field', [
            'id' => 'current_password', 'name' => 'current_password', 'label' => 'Password saat ini',
            'autocomplete' => 'current-password',
        ])
        @include('partials.password-field', [
            'id' => 'password', 'name' => 'password', 'label' => 'Password baru',
            'autocomplete' => 'new-password', 'rules' => true,
        ])
        @include('partials.password-field', [
            'id' => 'password_confirmation', 'name' => 'password_confirmation', 'label' => 'Ulangi password baru',
            'autocomplete' => 'new-password',
        ])
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan password</button>
        </div>
    </form>
</div>
@endsection
