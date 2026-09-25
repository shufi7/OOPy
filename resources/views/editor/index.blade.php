@extends('layouts.app')

@section('title', 'Live Coding OOPy')
@section('body-class', 'oopy-learning-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/oopy-editor.css') }}">
@endpush

@section('learning-sidebar')
<aside class="oopy-sidebar" id="learning-sidebar" aria-label="Navigasi pembelajaran">
    <div class="oopy-sidebar-brand">
        <a href="{{ route('home') }}" aria-label="OOPy — Beranda">OOP<span>y</span></a>
        <button type="button" class="oopy-icon-button" id="sidebar-close" aria-label="Tutup sidebar" aria-controls="learning-sidebar" aria-expanded="true">
            <i class="bi bi-layout-sidebar" aria-hidden="true"></i>
        </button>
    </div>

    <div class="oopy-progress-card">
        <div><span>Progres latihanmu</span><strong id="practice-percentage">0%</strong></div>
        <div class="oopy-progress-track" role="progressbar" aria-label="Progres latihan" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="practice-progress">
            <span id="practice-progress-fill"></span>
        </div>
    </div>

    <nav class="oopy-course-nav" aria-label="Daftar materi">
        @foreach (['Dasar Pemrograman & OOP', 'Kelas & Objek', 'Enkapsulasi'] as $chapter)
            <a class="oopy-chapter" href="{{ route('materi.index') }}#bab-{{ $loop->iteration }}">
                <i class="bi bi-book" aria-hidden="true"></i><span>{{ $chapter }}</span><i class="bi bi-chevron-right" aria-hidden="true"></i>
            </a>
        @endforeach
        <details class="oopy-current-chapter" open>
            <summary class="oopy-chapter"><i class="bi bi-book" aria-hidden="true"></i><span>Pewarisan</span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
            <div class="oopy-subchapters">
                <button type="button" data-lesson="inheritance">Pengertian Pewarisan</button>
                <button type="button" data-lesson="syntax">Sintaks Pewarisan</button>
                <button type="button" data-lesson="overriding">Metode Overriding</button>
                <a href="{{ route('editor.index') }}" class="is-current" aria-current="page">Live Coding Multi-File <i class="bi bi-play" aria-hidden="true"></i></a>
                <button type="button" data-lesson="exercise">Latihan Soal</button>
            </div>
        </details>
        <a class="oopy-chapter" href="{{ route('materi.index') }}#bab-5"><i class="bi bi-book" aria-hidden="true"></i><span>Polimorfisme</span><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
        <a class="oopy-chapter" href="{{ route('materi.index') }}#bab-6"><i class="bi bi-book" aria-hidden="true"></i><span>Kelas Abstrak</span><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
        <button type="button" class="oopy-chapter" id="show-results"><i class="bi bi-clipboard-check" aria-hidden="true"></i><span>Evaluasi Hasil</span></button>
    </nav>

    <div class="oopy-sidebar-bottom">
        <div class="oopy-sidebar-note"><i class="bi bi-code-slash" aria-hidden="true"></i><span>Sedikit kode, banyak pemahaman.</span></div>
        <a href="{{ route('materi.index') }}" class="oopy-exit"><i class="bi bi-box-arrow-left" aria-hidden="true"></i> Keluar dari latihan</a>
    </div>
</aside>
<button type="button" class="oopy-sidebar-backdrop" id="sidebar-backdrop" aria-label="Tutup sidebar" hidden></button>
@endsection

@section('content')
<div class="oopy-learning-content" id="oopy-editor-page">
    <header class="oopy-lesson-heading">
        <button type="button" class="oopy-icon-button oopy-sidebar-open" id="sidebar-open" aria-label="Buka sidebar materi" aria-controls="learning-sidebar" aria-expanded="false"><i class="bi bi-list" aria-hidden="true"></i></button>
        <div>
            <h1>Pewarisan <span>(Inheritance)</span></h1>
            <p>Membuat kelas baru dengan mewarisi sifat dan perilaku dari kelas yang sudah ada.</p>
        </div>
        <span class="oopy-lesson-badge"><i class="bi bi-code-slash" aria-hidden="true"></i> PRAKTIK PYTHON</span>
    </header>

    <section class="oopy-objectives" aria-labelledby="objectives-title">
        <h2 id="objectives-title">Tujuan Pembelajaran</h2>
        <ul>
            <li><i class="bi bi-check" aria-hidden="true"></i><span>Memahami konsep kelas induk (Parent) dan kelas anak (Child) pada Python.</span></li>
            <li><i class="bi bi-check" aria-hidden="true"></i><span>Mampu mendefinisikan subclass dan superclass menggunakan sintaks yang tepat.</span></li>
            <li><i class="bi bi-check" aria-hidden="true"></i><span>Mengimplementasikan pemanggilan metode superclass dengan fungsi <code>super()</code>.</span></li>
        </ul>
    </section>

    <section class="oopy-workspace" aria-labelledby="workspace-title">
        <div class="oopy-workspace-heading">
            <h2 id="workspace-title"><i class="bi bi-code-slash" aria-hidden="true"></i> Live Coding Multi-File</h2>
            <span>Terapkan pewarisan antar modul secara modular</span>
        </div>
        <div class="oopy-workspace-body">
            <nav class="oopy-file-explorer" aria-label="File project">
                <h3><i class="bi bi-folder2" aria-hidden="true"></i> File Project</h3>
                @foreach (['ekosistem.py', 'sungai.py', 'rawa.py', 'main.py'] as $file)
                    <button type="button" class="oopy-file-button {{ $file === 'rawa.py' ? 'is-active' : '' }}" data-file="{{ $file }}" aria-pressed="{{ $file === 'rawa.py' ? 'true' : 'false' }}"><i class="bi bi-file-earmark-code" aria-hidden="true"></i>{{ $file }}<span class="oopy-file-dirty" aria-label="Diubah" hidden></span></button>
                @endforeach
                <div class="oopy-explorer-hint"><span class="oopy-python-dot"></span> Python <span>4 file</span></div>
            </nav>
            <div class="oopy-code-pane">
                <div class="oopy-file-tabs" role="tablist" aria-label="File Python">
                    @foreach (['ekosistem.py', 'sungai.py', 'rawa.py', 'main.py'] as $file)
                        <button type="button" role="tab" id="tab-{{ str_replace('.', '-', $file) }}" class="oopy-file-tab {{ $file === 'rawa.py' ? 'is-active' : '' }}" data-file="{{ $file }}" aria-selected="{{ $file === 'rawa.py' ? 'true' : 'false' }}" aria-controls="editor-panel" tabindex="{{ $file === 'rawa.py' ? '0' : '-1' }}"><i class="bi bi-file-earmark-code" aria-hidden="true"></i>{{ $file }}<span class="oopy-file-dirty" aria-label="Diubah" hidden></span></button>
                    @endforeach
                </div>
                <div class="oopy-editor-panel" id="editor-panel" role="tabpanel" aria-labelledby="tab-rawa-py">
                    <div id="monaco-editor" aria-label="Editor kode Python"></div>
                    <div class="oopy-editor-loading" id="editor-loading" role="status"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Menyiapkan editor...</span></div>
                </div>
                <div class="oopy-editor-statusbar"><span id="active-file-path">workspace / rawa.py</span><span>UTF-8 <span>Python</span></span></div>
            </div>
        </div>
        <div class="oopy-editor-actions">
            <div class="oopy-action-group">
                <button type="button" class="oopy-button oopy-button-primary" id="run-code" disabled title="Jalankan main.py (Ctrl+Enter)"><i class="bi bi-play" aria-hidden="true"></i> Run Code</button>
                <button type="button" class="oopy-button oopy-button-danger" id="stop-code" hidden><i class="bi bi-stop" aria-hidden="true"></i> Hentikan</button>
                <button type="button" class="oopy-button oopy-button-muted" id="reset-code" disabled><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset</button>
                <span class="oopy-run-hint">Jalankan <code>main.py</code><kbd>Ctrl ↵</kbd></span>
            </div>
            <button type="button" class="oopy-button oopy-button-success" id="check-code" disabled title="Periksa jawaban dari keempat file"><i class="bi bi-check2" aria-hidden="true"></i> Submit</button>
        </div>
    </section>

    <section class="oopy-terminal" aria-labelledby="output-title">
        <div class="oopy-terminal-heading">
            <h2 id="output-title"><i class="bi bi-terminal" aria-hidden="true"></i> Output</h2>
            <div class="oopy-terminal-tools">
                <span class="oopy-runtime-status" id="python-status" data-state="loading" role="status"><span></span><span id="python-status-text">Menyiapkan Python...</span></span>
                <button type="button" class="oopy-terminal-retry" id="retry-runtime" hidden>Coba lagi</button>
                <button type="button" class="oopy-icon-button" id="clear-output" aria-label="Bersihkan output" title="Bersihkan output"><i class="bi bi-trash3" aria-hidden="true"></i></button>
            </div>
        </div>
        <pre class="oopy-output" id="code-output" aria-label="Output program" tabindex="0"><span class="oopy-output-muted">Klik Run Code untuk menjalankan main.py.
Hasil program dan pesan error akan muncul di sini.</span></pre>
    </section>

    <section class="oopy-check-results" id="check-results" aria-labelledby="results-title" tabindex="-1" hidden>
        <div class="oopy-results-heading"><h2 id="results-title">Hasil Pemeriksaan</h2><strong id="check-score"></strong></div>
        <p id="check-summary" role="status"></p>
        <ul id="check-list"></ul>
    </section>

    <nav class="oopy-page-navigation" aria-label="Navigasi materi">
        <button type="button" class="oopy-button oopy-button-white" data-lesson="overriding"><i class="bi bi-arrow-left" aria-hidden="true"></i> Halaman Sebelumnya</button>
        <a href="{{ route('materi.index') }}#bab-5" class="oopy-button oopy-button-primary">Halaman Selanjutnya <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </nav>
    <p class="oopy-page-note">Live Coding OOPy <span>·</span> Praktik Object-Oriented Programming Python langsung di browser.</p>
    <noscript><p class="alert alert-warning">Aktifkan JavaScript untuk menggunakan editor dan menjalankan Python.</p></noscript>
</div>

<dialog class="oopy-lesson-dialog" id="lesson-dialog" aria-labelledby="lesson-dialog-title">
    <div class="oopy-dialog-heading"><span>CATATAN MATERI</span><button type="button" class="oopy-icon-button" id="lesson-dialog-close" aria-label="Tutup catatan"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
    <h2 id="lesson-dialog-title"></h2>
    <div id="lesson-dialog-content"></div>
    <form method="dialog"><button class="oopy-button oopy-button-primary">Kembali ke editor <i class="bi bi-arrow-right" aria-hidden="true"></i></button></form>
</dialog>
<template id="lesson-inheritance"><p>Pewarisan memungkinkan kelas anak menggunakan atribut dan metode dari kelas induk. Pada project ini, <code>Ekosistem</code> adalah kelas induk, sedangkan <code>Sungai</code> dan <code>Rawa</code> adalah kelas anak.</p><p>Buka <strong>ekosistem.py</strong> untuk melihat atribut <code>nama</code> dan <code>lokasi</code> yang digunakan kedua kelas anak.</p></template>
<template id="lesson-syntax"><p>Tulis nama kelas induk di dalam tanda kurung. Gunakan <code>super()</code> untuk memanggil konstruktor induk.</p><pre>from ekosistem import Ekosistem

class Sungai(Ekosistem):
    def __init__(self, nama, lokasi, panjang_km):
        super().__init__(nama, lokasi)
        self.panjang_km = panjang_km</pre></template>
<template id="lesson-overriding"><p><em>Method overriding</em> terjadi ketika kelas anak mendefinisikan kembali metode dari kelas induk. Metode <code>status()</code> pada <code>Sungai</code> dan <code>Rawa</code> mengembalikan hasil yang berbeda.</p><pre>def status(self):
    return "Genangan rawa dipantau"</pre><p>Pada <strong>main.py</strong>, metode yang sama dipanggil pada dua jenis objek. Inilah contoh polimorfisme.</p></template>
<template id="lesson-exercise"><p>Pelajari hubungan antar-file Python berikut. Lengkapi kode jika diperlukan, kemudian jalankan <strong>main.py</strong>.</p><ol><li>Pastikan <code>Sungai</code> dan <code>Rawa</code> mewarisi <code>Ekosistem</code>.</li><li>Gunakan <code>super()</code> untuk mengisi atribut <code>nama</code> dan <code>lokasi</code>.</li><li>Metode <code>Sungai.status()</code> harus menghasilkan <code>Arus sungai dipantau</code>.</li><li>Metode <code>Rawa.status()</code> harus menghasilkan <code>Genangan rawa dipantau</code>.</li><li>Klik <strong>Submit</strong> untuk memeriksa perilaku kelas dan melihat skor latihan.</li></ol></template>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/editor/oopy-editor.js') }}"></script>
@endpush
