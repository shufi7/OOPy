@extends('layouts.app')

@section('title', 'Evaluasi Akhir OOPy')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/oopy/evaluasi/evaluasi.css') }}">
@endpush
@push('scripts')
<script src="{{ asset('js/vendor/prism/prism.min.js') }}" data-manual defer></script>
<script src="{{ asset('js/oopy-syntax.js') }}" defer></script>
<script type="module" src="{{ asset('js/evaluasi/exam.js') }}"></script>
@endpush

@section('content')
<div class="oopy-final-exam" data-exam-page="{{ $page }}">
    <script type="application/json" data-exam="config">{!! \Illuminate\Support\Js::encode($config) !!}</script>
    <div class="exam-container">
        <nav class="exam-breadcrumb" aria-label="Breadcrumb">
            <ol><li><a href="{{ route('home') }}">Beranda</a></li><li><a href="{{ route('materi.index') }}">Materi</a></li><li><a href="{{ route('evaluasi.index') }}">BAB 7</a></li><li aria-current="page">@yield('heading', 'Evaluasi Akhir OOPy')</li></ol>
        </nav>
        <header class="exam-intro">
            <span class="exam-eyebrow">BAB 7 — EVALUASI AKHIR</span>
            <h1>@yield('heading', 'Evaluasi Akhir OOPy')</h1>
            <p>@yield('description', $content['description'])</p>
        </header>
        <p class="exam-notice" data-exam="storage-warning" role="status" aria-live="polite" hidden></p>
        <noscript><p class="exam-notice">Aktifkan JavaScript untuk mengerjakan evaluasi dan membaca riwayat browser. Aturan evaluasi tetap dapat dibaca.</p></noscript>
        @yield('evaluation-content')
        <nav class="exam-page-links" aria-label="Navigasi evaluasi">
            <a href="{{ route('materi.show', 'kelas-abstrak') }}" rel="prev">← Kembali ke BAB 6</a>
            <a href="{{ route('materi.index') }}">Kembali ke Daftar Materi</a>
            @if ($page !== 'intro')<a href="{{ route('evaluasi.index') }}">Kembali ke Evaluasi Akhir</a>@endif
        </nav>
    </div>
    <dialog class="exam-dialog" data-exam="start-dialog" aria-labelledby="start-title" aria-describedby="start-description">
        <h2 id="start-title">Mulai Evaluasi?</h2>
        <p id="start-description">Evaluasi terdiri dari {{ count($content['questions']) }} soal dengan durasi {{ $settings['duration_seconds'] / 60 }} menit. Waktu mulai dihitung setelah kamu melanjutkan. Yakin ingin memulai?</p>
        <div class="exam-actions"><button type="button" class="exam-button secondary" data-exam="start-cancel" autofocus>Batal</button><button type="button" class="exam-button" data-exam="start-confirm">Ya, mulai evaluasi</button></div>
    </dialog>
</div>
@endsection
