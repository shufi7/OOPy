@extends('layouts.app')

@section('title', $chapter['bab'].' — '.$chapter['judul'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/oopy-material.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/oopy-material.js') }}" defer></script>
@endpush

@section('content')
<div class="oopy-material">
    <div class="material-container">
        <nav class="material-breadcrumb" aria-label="Breadcrumb">
            <ol>
                <li><a href="{{ route('home') }}">Beranda</a></li>
                <li><a href="{{ route('materi.index') }}">Materi</a></li>
                <li aria-current="page">{{ $chapter['bab'] }}</li>
            </ol>
        </nav>

        <div class="material-layout">
            @include('materi.partials.navigation')

            <article class="material-article" aria-labelledby="chapter-title">
                <header class="material-header">
                    <span class="material-eyebrow">{{ $chapter['bab'] }} · MATERI PEMBELAJARAN</span>
                    <h1 id="chapter-title">{{ $chapter['judul'] }}</h1>
                    <p>{{ $content['description'] }}</p>
                    <a class="material-start" href="#tujuan">Mulai dari tujuan pembelajaran <i class="bi bi-arrow-down" aria-hidden="true"></i></a>
                </header>

                <section class="material-section" id="tujuan" aria-labelledby="tujuan-title" tabindex="-1" data-material-section>
                    <h2 id="tujuan-title">Tujuan Pembelajaran</h2>
                    <p>Setelah menyelesaikan BAB ini, mahasiswa diharapkan mampu:</p>
                    <ul class="material-objectives">
                        @foreach ($content['objectives'] as $objective)
                            <li>{{ $objective }}</li>
                        @endforeach
                    </ul>
                </section>

                @foreach ($content['sections'] as $section)
                    @include('materi.partials.section', ['number' => $loop->iteration])
                @endforeach

                <section class="material-section" id="rangkuman" aria-labelledby="rangkuman-title" tabindex="-1" data-material-section>
                    <span class="material-eyebrow">TINJAU KEMBALI</span>
                    <h2 id="rangkuman-title">Rangkuman</h2>
                    <ul class="material-list">
                        @foreach ($content['summary'] as $point)
                            <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                </section>

                <section class="material-section" id="latihan" aria-labelledby="latihan-title" tabindex="-1" data-material-section>
                    <span class="material-eyebrow">TERAPKAN PEMAHAMANMU</span>
                    <h2 id="latihan-title">Latihan {{ $chapter['bab'] }}</h2>
                    <p>{{ $content['exercise']['description'] }}</p>
                    <ol class="material-list">
                        @foreach ($content['exercise']['steps'] as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    </ol>
                    {{-- Optional chapter exercise editors use the same reusable component. --}}
                    @foreach ($content['exercise']['live_codes'] ?? [] as $exercise)
                        <x-live-code :config="$exercise" />
                    @endforeach
                    <aside class="material-tip" aria-label="Petunjuk latihan mandiri">
                        <strong>Latihan mandiri</strong>
                        <p>Kerjakan di editor Python pilihanmu. Bandingkan output dengan data yang kamu masukkan. Pengumpulan jawaban latihan BAB belum tersedia.</p>
                    </aside>
                </section>

                <section class="material-section material-quiz" id="kuis" aria-labelledby="kuis-title" tabindex="-1" data-material-section>
                    <span class="material-eyebrow">CEK PEMAHAMAN</span>
                    <h2 id="kuis-title">Kuis {{ $chapter['bab'] }}</h2>
                    <p>Uji pemahamanmu setelah menyelesaikan materi dan latihan.</p>
                    <p id="quiz-availability">Kuis BAB ini sedang disiapkan. Kamu dapat meninjau rangkuman sambil menunggu kuis tersedia.</p>
                    <button class="btn btn-brand" type="button" disabled aria-describedby="quiz-availability">Segera tersedia</button>
                </section>

                <nav class="material-bottom-nav" aria-label="Navigasi antar BAB">
                    <a class="btn btn-brand" href="{{ route('materi.index') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke Daftar Materi</a>
                    <span>Materi BAB berikutnya segera hadir.</span>
                </nav>
            </article>
        </div>
    </div>
</div>
@endsection
