@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
<div class="hero-section flex-grow-1 d-flex align-items-center py-5">
    <div class="container-fluid px-lg-5 px-4 py-lg-4">
        <div class="row align-items-center justify-content-between g-5">
            <div class="col-lg-7 col-xl-6 text-start">
                <h1 class="hero-title fw-bolder mb-3">Selamat Datang</h1>

                <p class="hero-description mb-4">
                    Sebuah website pembelajaran interaktif yang dirancang khusus untuk menjembatani kebutuhan mahasiswa dalam memahami OOP Python secara komprehensif dari konsep dasar hingga tingkat lanjut.
                </p>

                <div class="pt-2">
                    <a href="{{ route('materi.index') }}" class="btn btn-hero-start fw-bold">
                        Mulai Belajar
                    </a>
                </div>
            </div>

            <div class="col-lg-5 col-xl-5 d-flex justify-content-center justify-content-lg-end">
                <div class="stacked-card-wrapper position-relative">
                    <div class="stacked-card-back position-absolute" aria-hidden="true"></div>

                    <div class="stacked-card-front text-center position-relative">
                        <img src="{{ asset('images/oopy-logo.png') }}"
                             alt="Logo OOPy: bekantan dengan laptop untuk belajar pemrograman"
                             class="hero-logo mb-3" width="1254" height="1254" fetchpriority="high">
                        <p class="card-tagline mb-0">Learn OOP Python, Step by Step</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
