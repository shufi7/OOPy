@extends('admin.layouts.app')
@section('title', 'Edit Materi')
@push('scripts')
<script src="{{ asset('js/admin/materials.js') }}" defer></script>
@endpush
@section('content')
<header class="mb-4">
    <a href="{{ route('admin.materials.index') }}">Kembali ke daftar materi</a>
    <h1 class="mt-3">Edit materi</h1>
    <p>{{ $material->title }} · Teks disimpan sebagai teks biasa; HTML tidak dijalankan.</p>
</header>
@error('version')<p class="alert alert-danger" role="alert">{{ $message }}</p>@enderror
@error('sections')<p class="alert alert-danger" role="alert">{{ $message }}</p>@enderror
<form method="post" action="{{ route('admin.materials.update', $material) }}" id="material-editor">
    @csrf
    @method('PUT')
    <input type="hidden" name="version" value="{{ old('version', $version) }}">
    <div class="mb-3">
        <label for="material-title" class="form-label">Judul BAB</label>
        <input id="material-title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $title) }}" required maxlength="255">
        @error('title')<p class="invalid-feedback">{{ $message }}</p>@enderror
    </div>
    <div class="mb-3">
        <label for="material-description" class="form-label">Penjelasan singkat</label>
        <textarea id="material-description" name="description" class="form-control" rows="3" maxlength="5000">{{ old('description', $content['description'] ?? '') }}</textarea>
        @error('description')<p class="text-danger">{{ $message }}</p>@enderror
    </div>
    @foreach (['objectives' => 'Tujuan pembelajaran', 'summary' => 'Rangkuman', 'reflection' => 'Refleksi'] as $field => $label)
    <div class="mb-3">
        <label for="material-{{ $field }}" class="form-label">{{ $label }} (satu poin per baris)</label>
        <textarea id="material-{{ $field }}" name="{{ $field }}" class="form-control" rows="4" maxlength="15000">{{ old($field, implode("\n", $content[$field] ?? [])) }}</textarea>
        @error($field)<p class="text-danger">{{ $message }}</p>@enderror
    </div>
    @endforeach
    <h2 class="mt-4">Bagian pembelajaran</h2>
    <p>Ubah angka urutan untuk memindahkan bagian. Pisahkan paragraf dengan satu baris kosong. Tabel, contoh lanjutan, dan latihan interaktif lama tetap dipertahankan dan dapat diperiksa melalui pratinjau.</p>
    <div id="material-sections">
    @php
        $formSections = old('sections');
        if ($formSections === null) {
            $formSections = array_map(fn ($section, $index) => [
                'id' => $section['id'], 'title' => $section['title'], 'order' => $index + 1,
                'paragraphs' => implode("\n\n", $section['paragraphs'] ?? []),
                'code' => $section['code'] ?? '', 'output' => $section['output'] ?? '', 'tip' => $section['tip'] ?? '',
            ], $content['sections'], array_keys($content['sections']));
        }
    @endphp
    @foreach ($formSections as $index => $section)
        @include('admin.materials.section-form', ['index' => $index, 'section' => $section])
    @endforeach
    </div>
    <button type="button" class="btn btn-outline-primary mb-4" id="add-material-section">Tambah bagian</button>
    <div class="d-flex flex-wrap gap-2 mb-4">
        <button class="btn btn-primary" type="submit">Simpan draf</button>
        <button class="btn btn-outline-primary" type="submit" formaction="{{ route('admin.materials.preview', $material) }}" name="_method" value="POST">Pratinjau perubahan</button>
        <a class="btn btn-outline-secondary" href="{{ route('materi.show', $material->slug) }}">Lihat materi terbit</a>
    </div>
</form>
@if ($record)
<section class="border-top pt-4" aria-labelledby="publish-heading">
    <h2 id="publish-heading">Publikasi</h2>
    <p>Publikasikan draf yang sudah disimpan. Perubahan pada formulir harus disimpan terlebih dahulu.</p>
    <a class="btn btn-outline-primary" href="{{ route('admin.materials.preview-saved', $material) }}">Periksa draf tersimpan</a>
</section>
@endif
<template id="material-section-template">
    @include('admin.materials.section-form', ['index' => '__INDEX__', 'section' => ['id' => '', 'title' => '', 'order' => '__ORDER__']])
</template>
@endsection
