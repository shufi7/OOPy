@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
<div class="hero-section flex-grow-1 d-flex align-items-center py-5">
    <div class="container-fluid px-lg-5 px-4 py-lg-4">
        <div class="row align-items-center justify-content-between g-5">
            {{-- Kolom Kiri: Teks Selamat Datang --}}
            <div class="col-lg-7 col-xl-6 text-start text-white">
                <h1 class="hero-title fw-bolder mb-3">
                    Selamat Datang
                </h1>

                <p class="hero-description text-white text-opacity-95 mb-4">
                    Sebuah website pembelajaran interaktif yang dirancang khusus untuk menjembatani kebutuhan mahasiswa dalam memahami OOP Python secara komprehensif dari konsep dasar hingga tingkat lanjut..
                </p>

                <div class="pt-2">
                    <a href="{{ route('materi.index') }}" class="btn btn-hero-start fw-bold shadow">
                        Mulai Belajar
                    </a>
                </div>
            </div>

            {{-- Kolom Kanan: Kartu Grafis OOPy Stacked --}}
            <div class="col-lg-5 col-xl-5 d-flex justify-content-center justify-content-lg-end">
                <div class="stacked-card-wrapper position-relative">
                    {{-- Layer Kartu Belakang (Offset) --}}
                    <div class="stacked-card-back position-absolute"></div>

                    {{-- Layer Kartu Utama (Depan) --}}
                    <div class="stacked-card-front bg-white text-center position-relative">
                        {{-- Logo Python OOP SVG dengan Cincin & Tag Kode < > --}}
                        <div class="d-flex justify-content-center mb-3">
                            <svg width="150" height="150" viewBox="0 0 150 150" fill="none" xmlns="http://www.w3.org/2000/svg">
                                {{-- Cincin Biru Tua dengan Gap di Kanan Atas --}}
                                <path d="M 116 52 A 48 48 0 1 1 86 23" fill="none" stroke="#173b5e" stroke-width="7" stroke-linecap="round"/>
                                
                                {{-- Ikon Tag Kode < > di Kanan Atas --}}
                                <g stroke="#173b5e" stroke-width="5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M 110 23 L 102 30 L 110 37" />
                                    <path d="M 124 23 L 132 30 L 124 37" />
                                </g>

                                {{-- Logo Python: Ular Biru (Atas) & Ular Kuning (Bawah) --}}
                                <g transform="translate(42, 42) scale(0.66)">
                                    {{-- Ular Biru --}}
                                    <path d="M49.4 0C22.4 0 24 11.9 24 11.9L24.1 24.2H49.8V27.9H14.7C14.7 27.9 0 26.3 0 53.6C0 80.9 12.9 79.8 12.9 79.8H20.6V69C20.6 69 20.2 56.1 33.3 56.1H58.8C58.8 56.1 71.3 56.5 71.3 44.3V12.3C71.3 12.3 73.1 0 49.4 0ZM35.9 7.4C38.7 7.4 41 9.7 41 12.5C41 15.3 38.7 17.6 35.9 17.6C33.1 17.6 30.8 15.3 30.8 12.5C30.8 9.7 33.1 7.4 35.9 7.4Z" fill="#2b5b84"/>
                                    
                                    {{-- Ular Kuning --}}
                                    <path d="M50.6 100C77.6 100 76 88.1 76 88.1L75.9 75.8H50.2V72.1H85.3C85.3 72.1 100 73.7 100 46.4C100 19.1 87.1 20.2 87.1 20.2H79.4V31C79.4 31 79.8 43.9 66.7 43.9H41.2C41.2 43.9 28.7 43.5 28.7 55.7V87.7C28.7 87.7 26.9 100 50.6 100ZM64.1 92.6C61.3 92.6 59 90.3 59 87.5C59 84.7 61.3 82.4 64.1 82.4C66.9 82.4 69.2 84.7 69.2 87.5C69.2 90.3 66.9 92.6 64.1 92.6Z" fill="#f8bf27"/>
                                </g>
                            </svg>
                        </div>

                        {{-- Teks Brand OOPy --}}
                        <h2 class="card-brand fw-bolder mb-1" style="letter-spacing: -1px;">
                            <span style="color: #173b5e;">OOP</span><span style="color: #e59b20;">y</span>
                        </h2>

                        {{-- Tagline --}}
                        <p class="card-tagline mb-0" style="color: #173b5e; font-size: 0.88rem; font-weight: 600;">
                            Learn OOP Python, Step by Step
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Background Gradient Hero Section */
    .hero-section {
        background: linear-gradient(112deg, #44719c 0%, #6892af 26%, #a7c2be 60%, #e2deb7 82%, #fce7aa 100%);
        min-height: calc(100vh - 145px);
    }

    /* Judul Selamat Datang */
    .hero-title {
        font-size: clamp(2.4rem, 4vw, 3.4rem);
        letter-spacing: -0.5px;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }

    /* Paragraf Deskripsi */
    .hero-description {
        font-size: clamp(1.05rem, 1.3vw, 1.18rem);
        line-height: 1.65;
        max-width: 550px;
        text-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
    }

    /* Tombol Mulai Belajar */
    .btn-hero-start {
        background-color: #ebb038;
        color: #ffffff;
        border: none;
        border-radius: 50px;
        padding: 12px 38px;
        font-size: 1.1rem;
        transition: all 0.25s ease-in-out;
        box-shadow: 0 6px 18px rgba(235, 176, 56, 0.45);
    }

    .btn-hero-start:hover {
        background-color: #dc9e25;
        color: #ffffff;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(220, 158, 37, 0.55);
    }

    /* Wrapper Kartu Grafis */
    .stacked-card-wrapper {
        width: 100%;
        max-width: 390px;
    }

    /* Kartu Belakang (Offset 3D Effect) */
    .stacked-card-back {
        top: -18px;
        right: -24px;
        width: 100%;
        height: 100%;
        background-color: #ffffff;
        border-radius: 24px;
        box-shadow: 0 12px 30px rgba(18, 48, 80, 0.08);
        z-index: 1;
        pointer-events: none;
    }

    /* Kartu Depan */
    .stacked-card-front {
        border-radius: 24px;
        padding: 45px 35px 35px 35px;
        box-shadow: 0 20px 45px rgba(23, 59, 94, 0.16);
        z-index: 2;
    }

    /* Judul OOPy di Kartu */
    .card-brand {
        font-size: 3rem;
    }
</style>
@endpush
