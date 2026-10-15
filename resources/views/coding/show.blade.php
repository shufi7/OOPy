@extends('layouts.app')
@section('title', $exercise['title'])
@push('styles')
<link rel="stylesheet" href="{{ asset('css/oopy/coding.css') }}">
@endpush
@section('content')
<div class="coding-page flex-grow-1">
    <div class="container py-4 py-lg-5">
        <nav aria-label="Breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Beranda</a></li>
            <li class="breadcrumb-item"><a href="{{ route('coding.index') }}">Coding</a></li>
            <li class="breadcrumb-item active" aria-current="page">Latihan {{ $exercise['number'] }}</li>
        </ol></nav>
        <header class="coding-intro">
            <span class="coding-eyebrow"><i class="bi bi-code-slash" aria-hidden="true"></i> Aktivitas Live Coding</span>
            <h1>{{ $exercise['title'] }}</h1>
            <p>{{ $exercise['description'] }}</p>
            @auth
                <span class="coding-status {{ $completed ? 'is-completed' : '' }}" data-coding-completion role="status">{{ $completed ? '✓ Selesai' : 'Belum Dikerjakan' }}</span>
            @else
                <p class="coding-notice"><a href="{{ route('login') }}">Masuk untuk menyimpan hasil latihan</a>. Submit akan memberikan feedback lokal.</p>
            @endauth
        </header>
        <section class="coding-instructions" aria-labelledby="coding-instructions-title">
            <h2 id="coding-instructions-title">Soal Latihan</h2>
            <h3 class="coding-problem-title">{{ $exercise['problem_title'] }}</h3>
            <h4>Deskripsi Soal</h4>
            <p>{{ $exercise['case'] }} {{ $exercise['objective'] }}</p>
            <h4>Instruksi Pengerjaan</h4>
            <ol>@foreach($exercise['steps'] as $step)<li>{{ $step }}</li>@endforeach</ol>
            <h4>Ketentuan Program</h4>
            <ul>@foreach($exercise['requirements'] as $requirement)<li>{{ $requirement }}</li>@endforeach</ul>
            <h4>Contoh Output</h4><pre><code>{{ $exercise['example'] }}</code></pre>
            <h4>Kriteria Keberhasilan</h4>
            <ul>@foreach($exercise['criteria'] as $criterion)<li>{{ $criterion }}</li>@endforeach</ul>
            <p class="coding-hint"><strong>Petunjuk:</strong> {{ $exercise['hint'] }}</p>
            <p class="small mb-0">Editor dimulai kosong. Gunakan Python dasar tanpa import atau akses sistem. Run Code menampilkan output program; Submit memberikan feedback lokal dan meminta penilaian resmi untuk akun yang masuk.</p>
        </section>
        <x-live-code :config="$config" />
        <a class="coding-back" href="{{ route('coding.index') }}">← Kembali ke daftar latihan</a>
    </div>
</div>
@endsection
