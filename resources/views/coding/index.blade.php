@extends('layouts.app')
@section('title', 'Latihan Coding OOPy')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/oopy/coding.css') }}">
@endpush
@section('content')
<div class="coding-page flex-grow-1">
    <div class="container py-5">
        <header class="coding-intro">
            <span class="coding-eyebrow"><i class="bi bi-code-slash" aria-hidden="true"></i> BELAJAR MELALUI PRAKTIK</span>
            <h1>Latihan Coding OOPy</h1>
            <p>Latih kemampuan pemrograman Python dan pahami konsep Object-Oriented Programming melalui latihan interaktif. Tulis kode, jalankan program, dan selesaikan tantangan secara langsung.</p>
        </header>
        @guest
            <p class="coding-notice"><a href="{{ route('login') }}">Masuk untuk menyimpan hasil latihan</a>. Kamu tetap bisa membaca soal dan menjalankan Python.</p>
        @endguest
        <div class="row g-4">
            @foreach ($exercises as $slug => $exercise)
                @php($done = in_array($exercise['key'], $completed, true))
                <div class="col-12 col-lg-6">
                    <article class="coding-card h-100">
                        <span class="coding-number">0{{ $exercise['number'] }}</span>
                        @auth
                            <span class="coding-status {{ $done ? 'is-completed' : '' }}">@if($done)<i class="bi bi-check-circle-fill" aria-hidden="true"></i> @endif{{ $done ? 'Selesai' : 'Belum Dikerjakan' }}</span>
                        @endauth
                        <h2>{{ $exercise['title'] }}</h2>
                        <p>{{ $exercise['description'] }}</p>
                        <a class="btn btn-brand mt-auto align-self-start" href="{{ route('coding.show', $slug) }}">{{ $done ? 'Kerjakan Lagi' : 'Mulai Latihan' }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </article>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
