@extends('layouts.app')

@section('title', $chapter['bab'].' — '.$chapter['judul'])

@push('styles')
<link rel="stylesheet" href="{{ asset('css/oopy-material.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/vendor/prism/prism.min.js') }}" data-manual defer></script>
<script src="{{ asset('js/oopy-syntax.js') }}" defer></script>
<script src="{{ asset('js/oopy-material.js') }}" defer></script>
<script src="{{ asset('js/oopy-quiz.js') }}" defer></script>
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
                    @if (!empty($content['description']))
                    <p>{{ $content['description'] }}</p>
                    @endif
                    <a class="material-start" href="#tujuan">Mulai dari tujuan pembelajaran <i class="bi bi-arrow-down" aria-hidden="true"></i></a>
                </header>

                <section class="material-section" id="tujuan" aria-labelledby="tujuan-title" tabindex="-1" data-material-section>
                    <h2 id="tujuan-title">Tujuan Pembelajaran</h2>
                    <p>Setelah menyelesaikan BAB ini, mahasiswa diharapkan mampu:</p>
                    <ul class="material-objectives">
                        @foreach ($content['objectives'] ?? [] as $objective)
                        <li>{{ $objective }}</li>
                        @endforeach
                    </ul>
                </section>

                @foreach ($content['sections'] ?? [] as $section)
                @include('materi.partials.section', ['number' => $loop->iteration])
                @endforeach

                @if (!empty($content['summary']))
                <section class="material-section" id="rangkuman" aria-labelledby="rangkuman-title" tabindex="-1" data-material-section>
                    <span class="material-eyebrow">TINJAU KEMBALI</span>
                    <h2 id="rangkuman-title">Rangkuman</h2>
                    <ul class="material-list">
                        @foreach ($content['summary'] as $point)
                        <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                </section>
                @endif

                @if (!empty($content['reflection']))
                <section class="material-section" id="refleksi" aria-labelledby="refleksi-title" tabindex="-1" data-material-section>
                    <span class="material-eyebrow">REFLEKSI</span>
                    <h2 id="refleksi-title">Refleksi</h2>

                    <p>
                        Setelah mempelajari BAB ini, coba jawab pertanyaan berikut
                        untuk mengingat kembali konsep yang sudah dipelajari.
                    </p>

                    <ol class="material-list">
                        @foreach ($content['reflection'] as $question)
                        <li>{{ $question }}</li>
                        @endforeach
                    </ol>
                </section>
                @endif

                @if (!empty($content['quiz']))
                @include('materi.partials.quiz')
                @endif

                <nav class="material-navigation" aria-label="Navigasi antar BAB">
                    @if (!empty($previousChapter))
                    <a href="{{ route('materi.show', $previousChapter['slug']) }}"
                        class="material-nav-button previous" rel="prev">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i> BAB Sebelumnya: {{ $previousChapter['bab'] }}
                    </a>
                    @endif
                    <a class="material-nav-list" href="{{ route('materi.index') }}">Kembali ke Daftar Materi</a>
                    @if (!empty($nextChapter))
                    <a href="{{ route('materi.show', $nextChapter['slug']) }}"
                        class="material-nav-button next" rel="next">
                        Lanjut ke {{ $nextChapter['bab'] }} <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                    @endif
                </nav>

            </article>
        </div>
    </div>
</div>
@endsection
