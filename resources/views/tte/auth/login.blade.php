@extends('tte.layout')

@section('title', 'Login Admin')

@section('content')
<div class="login-wrap">
    <form class="login-card" method="POST" action="{{ route('admin.login.store') }}">
        @csrf
        <p class="eyebrow">AKSES TERBATAS</p>
        <h1>Login Admin</h1>
        <p class="subtitle">Masuk untuk melihat data rinci pengguna dan sertifikat TTE.</p>
        <label class="form-label" for="email">Email</label>
        <input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
        @error('email')<small class="field-error">{{ $message }}</small>@enderror
        <label class="form-label" for="password">Password</label>
        <input class="form-input" id="password" name="password" type="password" autocomplete="current-password" required>
        @error('password')<small class="field-error">{{ $message }}</small>@enderror
        <button class="button button-primary login-submit" type="submit">Masuk</button>
        <a class="back-link" href="{{ route('dashboard') }}">Kembali ke dashboard publik</a>
    </form>
</div>
@endsection
