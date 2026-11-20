@extends('layouts.app')

@section('title', 'Beranda')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/oopy/home.css') }}">
@endpush

@section('content')
<div class="oopy-home">
    <div class="home-hero-region">
        <div class="home-hero-background" aria-hidden="true">
            <svg viewBox="0 0 1440 430" preserveAspectRatio="none" focusable="false">
                <defs><linearGradient id="home-hero-wave" x2="0" y2="1"><stop stop-color="var(--oopy-brand-mint)" stop-opacity="0"/><stop offset=".5" stop-color="var(--oopy-brand-mint)" stop-opacity=".6"/><stop offset="1" stop-color="var(--oopy-brand-mint)" stop-opacity=".15"/></linearGradient></defs>
                <path d="M0 0H1440V50C1300 110 1250 275 1070 300S810 205 650 285 300 400 170 235 75 75 0 40Z" fill="url(#home-hero-wave)"/>
                <path d="M0 30C120 55 90 190 250 260S520 365 720 270 1000 215 1160 255 1320 175 1440 155V280C1300 235 1260 350 1090 335S850 305 670 350 330 430 190 310 90 125 0 100Z" fill="var(--oopy-brand-aqua)" opacity=".05"/>
            </svg>
        </div>
        <div class="home-container">
            <section class="home-hero" aria-labelledby="home-title">
                <div class="home-intro">
                    <h1 class="hero-title" id="home-title">Belajar OOP Python, <span>langsung lewat praktik.</span></h1>
                    <p class="hero-description">Pahami konsep Object-Oriented Programming (OOP) melalui materi interaktif, kuis, dan latihan coding Python yang dapat dijalankan langsung di browser.</p>
                    <div class="home-actions">
                        <a href="{{ route('materi.index') }}" class="btn btn-brand btn-hero-start">Mulai Belajar <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        <a href="{{ route('coding.index') }}" class="btn btn-home-secondary">Latihan Coding</a>
                    </div>
                </div>
                <div class="home-identity">
                    <img src="{{ asset('images/oopy-logo.png') }}"
                         alt="Maskot OOPy: bekantan dengan laptop untuk belajar pemrograman"
                         class="hero-logo" width="1254" height="1254" fetchpriority="high">
                </div>
            </section>
        </div>
    </div>

    <div class="home-container home-sections">
        <section class="home-learning" aria-labelledby="home-learning-title">
            <div class="home-section-heading"><h2 id="home-learning-title">Perjalanan Belajar di OOPy</h2></div>
            <ol class="home-steps" role="list">
                @foreach([
                    ['title' => 'Pelajari Materi', 'icon' => 'book', 'description' => 'Pahami konsep OOP Python melalui materi yang interaktif dan terstruktur per BAB.'],
                    ['title' => 'Praktik Coding', 'icon' => 'laptop', 'description' => 'Tulis dan jalankan kode Python langsung di browser untuk menerapkan konsepnya.'],
                    ['title' => 'Uji Pemahaman', 'icon' => 'clipboard-check', 'description' => 'Kerjakan kuis interaktif untuk memeriksa sejauh mana pemahamanmu.'],
                ] as $step)
                    <li class="home-step">
                        <div class="home-step-symbol" aria-hidden="true"><span class="home-step-number">{{ $loop->iteration }}</span><span class="home-step-icon"><i class="bi bi-{{ $step['icon'] }}"></i>@if($step['icon'] === 'laptop')<i class="bi bi-code-slash home-step-code"></i>@endif</span></div>
                        <div class="home-step-copy"><h3>{{ $step['title'] }}</h3><p>{{ $step['description'] }}</p></div>
                        @unless($loop->last)<i class="bi bi-arrow-right home-step-arrow" aria-hidden="true"></i>@endunless
                    </li>
                @endforeach
            </ol>
        </section>

        <div class="home-learning-content">
            <section class="home-features" aria-labelledby="home-features-title">
                <h2 class="visually-hidden" id="home-features-title">Fitur pembelajaran OOPy</h2>
                <dl class="home-learning-list">
                    @foreach([
                        ['title' => 'Materi per BAB', 'icon' => 'book', 'description' => 'Pelajari class, object, pewarisan, dan konsep OOP lainnya.'],
                        ['title' => 'Kuis interaktif', 'icon' => 'check2-square', 'description' => 'Jawab kuis untuk memeriksa pemahamanmu di setiap BAB.'],
                        ['title' => 'Praktik Python di browser', 'icon' => 'code-slash', 'description' => 'Tulis kode sendiri, jalankan program, dan lihat output-nya langsung.'],
                    ] as $feature)
                        <div><dt><span class="home-feature-icon" aria-hidden="true"><i class="bi bi-{{ $feature['icon'] }}"></i></span>{{ $feature['title'] }}</dt><dd>{{ $feature['description'] }}</dd></div>
                    @endforeach
                </dl>
            </section>

            <section class="home-account-note" aria-labelledby="home-account-title">
                <div class="home-account-copy">
                    @guest
                        <h2 id="home-account-title">Simpan progres belajarmu.</h2>
                        <p>Masuk untuk menyimpan hasil kuis dan kelulusan latihan pada akunmu.</p>
                        <a class="btn btn-brand" href="{{ route('login') }}">Masuk ke akun <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                    @else
                        <h2 id="home-account-title">Lihat progres belajarmu.</h2>
                        <p>Pantau progres BAB dan nilai kuis yang tersedia di dashboard akunmu.</p>
                        <a class="btn btn-brand" href="{{ route('dashboard') }}">Buka Dashboard <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                    @endguest
                </div>
                <div class="home-account-art">@include('home.partials.progress-illustration')</div>
            </section>
        </div>
    </div>
</div>
@endsection
