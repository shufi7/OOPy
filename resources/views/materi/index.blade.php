@extends('layouts.app')

@section('title', 'Materi')

@section('content')
<div class="materi-section flex-grow-1 d-flex align-items-center py-5">
    <div class="container-fluid px-lg-5 px-3 py-lg-3">
        <div class="row g-4 justify-content-center">
            @foreach ($materiList as $materi)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card materi-card h-100 border-0">
                        <div class="card-body p-4">
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
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
