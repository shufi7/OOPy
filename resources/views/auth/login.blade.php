@extends('layouts.app')

@section('title', 'Masuk')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/oopy/auth/auth.css') }}">
@endpush

@section('content')
    <section class="oopy-auth" aria-labelledby="auth-title">
        <div class="oopy-auth-card">
            <a class="oopy-auth-brand" href="{{ route('home') }}" aria-label="OOPy, kembali ke Beranda">
                <img src="{{ asset('images/oopy-logo.png') }}" width="64" height="64" alt="">
                <span>OOPy</span>
            </a>
            <h1 id="auth-title">Selamat Datang</h1>
            <p class="oopy-auth-intro">Masuk untuk melanjutkan pembelajaran Python OOP.</p>
            <form class="oopy-auth-form" method="POST" action="{{ route('login.store') }}">
                @csrf
                <x-auth-field name="email" label="Email" type="email" autocomplete="username" :autofocus="true" />
                <x-auth-field name="password" label="Password" type="password" autocomplete="current-password" />
                <button type="submit" class="oopy-auth-button btn btn-brand">Masuk</button>
            </form>
            <p class="oopy-auth-switch">Belum punya akun? <a href="{{ route('register') }}">Daftar</a></p>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/oopy-auth.js') }}" defer></script>
@endpush
