@extends('layouts.app')

@section('title', 'Materi')

@section('content')
<div class="materi-section flex-grow-1 d-flex align-items-center py-5">
    <div class="container-fluid px-lg-5 px-3 py-lg-3">
        <div class="row g-4 justify-content-center">
            @foreach ($materiList as $materi)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card materi-card h-100 border-0 bg-white shadow-sm">
                        <div class="card-body p-4">
                            {{-- Label BAB --}}
                            <div class="materi-bab fw-bold mb-1">
                                {{ $materi['bab'] }}
                            </div>

                            {{-- Judul Materi --}}
                            <h3 class="materi-title fw-bold text-dark mb-3">
                                {{ $materi['judul'] }}
                            </h3>

                            {{-- Daftar Poin Materi --}}
                            <ul class="materi-list ps-3 mb-0">
                                @foreach ($materi['poin'] as $poin)
                                    <li class="materi-item mb-1">
                                        {{ $poin }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Background Gradient Halaman Materi */
    .materi-section {
        background: linear-gradient(112deg, #44719c 0%, #6892af 26%, #a7c2be 60%, #e2deb7 82%, #fce7aa 100%);
        min-height: calc(100vh - 145px);
    }

    /* Styling Card Materi */
    .materi-card {
        border-radius: 22px;
        box-shadow: 0 12px 32px rgba(22, 54, 88, 0.12);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .materi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 36px rgba(22, 54, 88, 0.18);
    }

    /* Label BAB */
    .materi-bab {
        color: #e59b20;
        font-size: 1.05rem;
        letter-spacing: 0.3px;
    }

    /* Judul BAB */
    .materi-title {
        font-size: 1.25rem;
        color: #111827;
    }

    /* List Poin */
    .materi-list {
        list-style-type: disc;
    }

    .materi-item {
        color: #1e293b;
        font-size: 0.95rem;
        line-height: 1.5;
    }
</style>
@endpush
