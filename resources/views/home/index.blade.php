@extends('layouts.app')

@section('title', 'Beranda')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/oopy/home.css') }}">
@endpush

@section('content')
<div class="oopy-home">
    <div class="home-container">
        <section class="home-hero" aria-labelledby="home-title">
            <div class="home-intro">
                <h1 class="hero-title" id="home-title">Belajar OOP Python, <span>langsung lewat praktik.</span></h1>
                <p class="hero-description">
                    Pahami konsep Object-Oriented Programming melalui materi interaktif, kuis, dan latihan coding Python yang dapat dijalankan langsung di browser.
                </p>
                <div class="home-actions">
                    <a href="{{ route('materi.index') }}" class="btn btn-brand btn-hero-start">Mulai Belajar</a>
                    <a href="{{ route('coding.index') }}" class="btn btn-home-secondary">Latihan Coding</a>
                </div>
            </div>
            <div class="home-identity">
                <img src="{{ asset('images/oopy-logo.png') }}"
                     alt="Logo OOPy: bekantan dengan laptop untuk belajar pemrograman"
                     class="hero-logo" width="1254" height="1254" fetchpriority="high">
            </div>
        </section>

        <section class="home-learning" aria-labelledby="home-learning-title">
            <h2 id="home-learning-title">Materi dan latihan dalam satu tempat.</h2>
            <div class="home-learning-content">
                <dl class="home-learning-list">
                    <div>
                        <dt>Materi dan kuis per BAB</dt>
                        <dd>Pelajari class, object, pewarisan, dan konsep OOP lainnya. Jawab kuis untuk memeriksa pemahamanmu.</dd>
                    </div>
                    <div>
                        <dt>Praktik Python di browser</dt>
                        <dd>Tulis kode sendiri, jalankan program, lalu baca output dan feedback hasil latihan.</dd>
                    </div>
                </dl>
                <div class="home-account-note">
                    @guest
                        <strong>Simpan progres belajarmu.</strong>
                        <p>Masuk untuk menyimpan hasil kuis dan kelulusan latihan pada akunmu.</p>
                        <a href="{{ route('login') }}">Masuk ke akun</a>
                    @else
                        <strong>Lihat progres belajarmu.</strong>
                        <p>Progres BAB dan nilai kuis tersedia di dashboard akunmu.</p>
                        <a href="{{ route('dashboard') }}">Buka Dashboard</a>
                    @endguest
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
