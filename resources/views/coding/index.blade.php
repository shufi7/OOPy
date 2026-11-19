@extends('layouts.app')
@section('title', 'Latihan Coding OOPy')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/oopy/coding.css') }}">
@endpush
@section('content')
<div class="coding-page coding-index flex-grow-1">
    <div class="container coding-index-content">
        <header class="coding-intro">
            <span class="coding-eyebrow">BELAJAR MELALUI PRAKTIK</span>
            <h1>Latihan Coding OOPy</h1>
            <p>Latih kemampuan pemrograman Python dan pahami konsep Object-Oriented Programming melalui latihan interaktif. Tulis kode, jalankan program, dan selesaikan tantangan secara langsung.</p>
        </header>
        @guest
            <aside class="coding-notice" aria-label="Penyimpanan hasil latihan">
                <i class="bi bi-lock" aria-hidden="true"></i>
                <p><strong>Masuk untuk menyimpan hasil latihan.</strong> Kamu tetap bisa membaca soal dan menjalankan Python.</p>
                <a class="btn coding-login" href="{{ route('login') }}">Masuk</a>
            </aside>
        @endguest
        @php
            $presentations = [
                'latihan-1' => [
                    'skills' => ['Python', 'Class & Object', 'SensorAir'],
                    'image' => 'images/coding/latihan-1-sensorair.svg',
                    'alt' => 'Class SensorAir di main.py dengan atribut lokasi dan ph serta method status_air(); objek Rawa Bangkau pada pH 7.0 menghasilkan Normal.',
                ],
                'latihan-2' => [
                    'skills' => ['Python', 'Inheritance', 'Multi-File'],
                    'image' => 'images/coding/latihan-2-ekosistem-rawa.svg',
                    'alt' => 'Pewarisan Ekosistem di ekosistem.py ke Rawa di rawa.py; main.py menjalankan pemantauan Rawa Bangkau, Hulu Sungai Selatan, dengan tinggi air 85 cm dan status Siaga.',
                ],
            ];
        @endphp
        <div class="coding-grid">
            @foreach ($exercises as $slug => $exercise)
                @php($done = in_array($exercise['key'], $completed, true))
                @php($started = in_array($exercise['key'], $attempted, true))
                @php($presentation = $presentations[$slug] ?? null)
                <article class="coding-card" aria-labelledby="coding-title-{{ $slug }}">
                    <span class="coding-number" aria-label="Nomor latihan {{ $exercise['number'] }}">{{ sprintf('%02d', $exercise['number']) }}</span>
                    <h2 id="coding-title-{{ $slug }}">{{ $exercise['title'] }}</h2>
                    <p class="coding-description">{{ $exercise['description'] }}</p>
                    @if ($presentation)
                        <ul class="coding-skills" aria-label="Kompetensi latihan {{ $exercise['number'] }}">
                            @foreach ($presentation['skills'] as $skill)
                                <li>{{ $skill }}</li>
                            @endforeach
                        </ul>
                        @if (is_file(public_path($presentation['image'])))
                            <div class="coding-illustration">
                                <img src="{{ asset($presentation['image']) }}" alt="{{ $presentation['alt'] }}" loading="lazy" decoding="async">
                            </div>
                        @endif
                    @endif
                    <div class="coding-card-footer">
                        @auth
                            <span class="coding-status {{ $done ? 'is-completed' : ($started ? 'is-attempted' : '') }}">@if($done)<i class="bi bi-check-circle-fill" aria-hidden="true"></i> @endif{{ $done ? 'Selesai' : ($started ? 'Belum Lulus' : 'Belum Dikerjakan') }}</span>
                        @endauth
                        <a class="btn btn-brand coding-action" href="{{ route('coding.show', $slug) }}" aria-describedby="coding-title-{{ $slug }}">{{ $done ? 'Kerjakan Lagi' : ($started ? 'Lanjutkan Latihan' : 'Mulai Latihan') }}</a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</div>
@endsection
