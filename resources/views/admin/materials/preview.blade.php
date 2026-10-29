@extends('admin.layouts.app')
@section('title', 'Pratinjau Materi')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/oopy/material/material.css') }}">
<link rel="stylesheet" href="{{ asset('css/oopy/material/code.css') }}">
<style>
    .oopy-admin .admin-material-preview { min-width: 0; max-width: 100%; padding: 0; background: none; overflow-wrap: anywhere; }
</style>
@endpush
@push('scripts')
<script src="{{ asset('js/vendor/prism/prism.min.js') }}" data-manual defer></script>
<script src="{{ asset('js/oopy-syntax.js') }}" defer></script>
@endpush
@section('content')
<header class="mb-4">
    <a href="{{ route('admin.materials.edit', $material) }}">Kembali ke editor</a>
    <h1 class="mt-3">Pratinjau materi</h1>
    <p>{{ $saved ? 'Draf tersimpan. Publikasikan setelah seluruh isi diperiksa.' : 'Perubahan formulir belum disimpan. Kembali ke formulir dengan tombol Back browser untuk melanjutkan.' }}</p>
    @if ($saved && $record)
    <form method="post" action="{{ route('admin.materials.publish', $material) }}">
        @csrf
        <input type="hidden" name="version" value="{{ $version }}">
        <button type="submit" class="btn btn-primary">Publikasikan draf ini</button>
    </form>
    @endif
</header>
<div class="oopy-material admin-material-preview">
<article class="material-article" aria-labelledby="preview-title">
    <h2 id="preview-title">{{ $title }}</h2>
    <p>{{ $content['description'] ?? '' }}</p>
    @foreach (['objectives' => 'Tujuan pembelajaran'] as $key => $label)
    <h3>{{ $label }}</h3>
    <ul>@foreach ($content[$key] ?? [] as $line)<li>{{ $line }}</li>@endforeach</ul>
    @endforeach
    @foreach ($content['sections'] ?? [] as $section)
        @include('materi.partials.section', ['number' => $loop->iteration])
    @endforeach
    @foreach (['summary' => 'Rangkuman', 'reflection' => 'Refleksi'] as $key => $label)
    <h3>{{ $label }}</h3>
    <ul>@foreach ($content[$key] ?? [] as $line)<li>{{ $line }}</li>@endforeach</ul>
    @endforeach
</article>
</div>
@endsection
