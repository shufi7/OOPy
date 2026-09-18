@extends('layouts.app')

@section('title', 'Materi Pembelajaran OOP')

@section('content')
{{-- Header Halaman Materi --}}
<div class="py-5 bg-white border-bottom shadow-sm">
    <div class="container text-center">
        <div class="d-inline-flex align-items-center justify-content-center gap-2 mb-3 px-3 py-1 bg-dark text-white rounded-pill shadow-sm">
            <i class="bi bi-journal-code text-warning fs-5"></i>
            <span class="fw-bold fs-6">Kurikulum Terstruktur</span>
        </div>
        <h1 class="display-5 fw-bold text-dark mb-3">Materi Pembelajaran OOP</h1>
        <p class="lead text-secondary mx-auto mb-0" style="max-width: 720px;">
            Pelajari konsep Object-Oriented Programming dengan Python secara bertahap melalui materi, contoh kode, dan latihan interaktif.
        </p>
    </div>
</div>

{{-- Daftar Card Materi --}}
<div class="py-5 bg-light">
    <div class="container">
        <div class="row g-4">
            @foreach ($materiList as $materi)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm rounded-3">
                        <div class="card-body p-4 d-flex flex-column">
                            {{-- Indikator Urutan & Progress Visual Sederhana --}}
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
                                    <i class="bi bi-layers-fill me-1"></i>Materi {{ $materi['nomor'] }} dari {{ count($materiList) }}
                                </span>
                                @if (isset($materi['badge']))
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1 small">
                                        {{ $materi['badge'] }}
                                    </span>
                                @endif
                            </div>

                            {{-- Ikon & Judul --}}
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="d-inline-flex align-items-center justify-content-center bg-dark text-warning rounded-3 flex-shrink-0" style="width: 48px; height: 48px;">
                                    <i class="bi {{ $materi['icon'] }} fs-4"></i>
                                </div>
                                <h5 class="card-title fw-bold mb-0 text-dark">
                                    {{ $materi['judul'] }}
                                </h5>
                            </div>

                            {{-- Deskripsi Materi --}}
                            <p class="card-text text-muted mb-4 flex-grow-1">
                                {{ $materi['deskripsi'] }}
                            </p>

                            {{-- Tombol Aksi --}}
                            <div class="pt-2 border-top">
                                <a href="#" class="btn btn-outline-primary w-100 fw-semibold d-flex align-items-center justify-content-center gap-2">
                                    <span>Pelajari Materi</span>
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

