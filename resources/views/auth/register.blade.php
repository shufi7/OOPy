@extends('layouts.app')

@section('title', 'Daftar')

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
            <h1 id="auth-title">Buat Akun OOPy</h1>
            <p class="oopy-auth-intro">Mulai belajar Python OOP bersama OOPy.</p>
            <form class="oopy-auth-form" method="POST" action="{{ route('register.store') }}">
                @csrf
                <x-auth-field name="name" label="Nama lengkap" autocomplete="name" :autofocus="true" />
                <x-auth-field name="email" label="Email" type="email" autocomplete="username" />
                <x-auth-field name="password" label="Password" type="password" autocomplete="new-password" hint="Gunakan minimal 8 karakter." />
                <x-auth-field name="password_confirmation" label="Konfirmasi Password" type="password" autocomplete="new-password" />
                <button type="submit" class="oopy-auth-button btn btn-brand">Daftar</button>
            </form>
            <p class="oopy-auth-switch">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/oopy-auth.js') }}" defer></script>
@endpush
