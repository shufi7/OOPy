
@extends('layouts.app')

@section('title', 'Materi')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/oopy/material/index.css') }}">
@endpush

@section('content')
<div class="materi-section flex-grow-1 d-flex align-items-center py-5">
    <div class="container-fluid px-lg-5 px-3 py-lg-3">
        <div class="row g-4 justify-content-center">

            @foreach ($materiList as $slug => $materi)
                <div class="col-12 col-md-6 col-lg-4">

                    <div class="card materi-card h-100 border-0"
                         id="bab-{{ $loop->iteration }}">

                        <div class="card-body p-4">

                            {{-- Thumbnail BAB --}}
                            @if (!empty($materi['thumbnail']))
                                <div class="materi-card-cover">
                                    <img
                                        src="{{ asset($materi['thumbnail']) }}"
                                        alt="Ilustrasi {{ $materi['judul'] }}"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </div>
                            @endif

                            {{-- Label BAB --}}
                            <div class="materi-bab fw-bold mb-1">
                                {{ $materi['bab'] }}
                            </div>

                            {{-- Judul Materi --}}
                            <h3 class="materi-title fw-bold mb-3">
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

                            {{-- Tombol Pelajari BAB --}}
                            <div class="materi-card-footer">
                                @if (isset($materi['content']))
                                    <a
                                        class="btn btn-brand materi-card-link stretched-link"
                                        href="{{ route('materi.show', $slug) }}"
                                        aria-label="Pelajari {{ $materi['bab'] }}: {{ $materi['judul'] }}"
                                    >
                                        <span>
                                            Pelajari {{ $materi['bab'] }}
                                        </span>

                                        <i
                                            class="bi bi-arrow-right"
                                            aria-hidden="true"
                                        ></i>
                                    </a>
                                @else
                                    <span class="badge text-bg-light">
                                        Segera hadir
                                    </span>
                                @endif
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach

        </div>
    </div>
</div>
@endsection
