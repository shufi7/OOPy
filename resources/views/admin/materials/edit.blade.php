@extends('admin.layouts.app')
@section('title', 'Edit Materi '.$material->title)
@push('styles')
<link rel="stylesheet" href="{{ asset('css/oopy/material/material.css') }}">
<link rel="stylesheet" href="{{ asset('css/oopy/material/interactions.css') }}">
@endpush
@push('scripts')
<script src="{{ asset('js/oopy-learning-checks.js') }}" defer></script>
<script src="{{ asset('js/admin/materials.js') }}" defer></script>
@endpush
@section('content')
<div class="admin-material-editor">
    <nav aria-label="Breadcrumb"><a href="{{ route('admin.materials.index') }}">Manajemen Materi</a> / {{ $material->title }}</nav>
    <header class="admin-material-heading">
        <div>
            <p class="admin-material-kicker">{{ $chapterMeta['bab'] }} · MATERI PEMBELAJARAN</p>
            <h1>Edit Materi — {{ $chapterMeta['bab'] }}</h1>
            <p class="admin-material-chapter-title">{{ $title }}</p>
            <p>Perubahan disimpan sebagai draf. Pratinjau dan publikasi dilakukan terpisah.</p>
        </div>
        <span class="admin-material-status admin-material-status--{{ $source_status['tone'] }}">{{ $source_status['label'] }}</span>
    </header>
    @if ($source_status['drift'] || $source_status['review'])
    <aside class="alert alert-warning admin-material-drift" role="status">
        <strong>{{ $source_status['review'] ? 'Snapshot lama perlu ditinjau.' : 'File sumber berubah.' }}</strong>
        <span>Versi draf dan publikasi tetap dipertahankan. Bandingkan perubahan lalu pilih bagian sumber yang ingin disalin.</span>
        <a class="btn btn-sm btn-outline-dark" href="{{ route('admin.materials.sync', $material) }}">Tinjau perubahan sumber</a>
    </aside>
    @else
    <p class="admin-material-version-note">SHA-256 sumber saat ini: <code>{{ substr($source_hash, 0, 16) }}</code> · publikasi terakhir: {{ $record?->published_at?->timezone('Asia/Makassar')->format('d/m/Y H:i') ?? 'belum ada' }}</p>
    @endif
    @error('version')<p class="alert alert-danger" role="alert">{{ $message }}</p>@enderror
    @error('sections')<p class="alert alert-danger" role="alert">{{ $message }}</p>@enderror
    <form method="post" enctype="multipart/form-data" action="{{ route('admin.materials.update', $material) }}" id="material-editor">
        @csrf
        @method('PUT')
        <input type="hidden" name="version" value="{{ old('version', $version) }}">
        <details class="admin-material-global" open>
            <summary>Informasi BAB dan tujuan belajar</summary>
            <div class="admin-material-global-grid">
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
                    <label for="material-{{ $field }}" class="form-label">{{ $label }} <small>(satu poin per baris)</small></label>
                    <textarea id="material-{{ $field }}" name="{{ $field }}" class="form-control" rows="4" maxlength="15000">{{ old($field, implode("\n", $content[$field] ?? [])) }}</textarea>
                    @error($field)<p class="text-danger">{{ $message }}</p>@enderror
                </div>
                @endforeach
            </div>
        </details>
        <div class="admin-material-workspace">
            <aside class="admin-material-section-nav" aria-label="Navigasi bagian materi">
                <h2>Bagian materi</h2>
                <p>Pilih bagian untuk melompat ke editor. ID anchor tetap stabil saat judul atau urutan berubah.</p>
                <ol>
                    @foreach ($content['sections'] as $index => $navSection)
                    <li><a href="#material-section-{{ $index }}">{{ $index + 1 }}. {{ $navSection['title'] }}</a></li>
                    @endforeach
                </ol>
                <button type="button" class="btn btn-outline-primary w-100" id="add-material-section">Tambah bagian</button>
            </aside>
            <div class="admin-material-sections-column">
                <div id="material-sections">
                @php
                    $sectionDefaults = [
                        'id' => '', 'title' => '', 'order' => 1, 'paragraphs' => '', 'code' => '', 'output' => '', 'tip' => '', 'media' => [],
                        'learning_check' => ['enabled' => 0, 'kind' => '', 'prompt' => '', 'code' => '', 'options' => [], 'visualization' => ['enabled' => 0, 'caption' => '', 'layout' => 'compare', 'items' => []]],
                    ];
                    $formSections = old('sections');
                    if (!is_array($formSections)) {
                        $formSections = null;
                    } else {
                        $formSections = array_map(fn ($section, $index) => array_merge([...$sectionDefaults, 'order' => $index + 1], is_array($section) ? $section : []), $formSections, array_keys($formSections));
                        foreach ($formSections as $index => &$formSection) {
                            $trusted = $content['sections'][$index] ?? [];
                            foreach (['live_codes', 'tables', 'examples'] as $protectedField) {
                                $formSection[$protectedField] = $trusted[$protectedField] ?? [];
                            }
                        }
                        unset($formSection);
                    }
                    if ($formSections === null) {
                        $formSections = array_map(function ($section, $index) {
                            $check = $section['learning_check'] ?? null;
                            $check = is_array($check) ? [
                                'enabled' => 1, 'kind' => $check['kind'] ?? '', 'prompt' => $check['prompt'] ?? '', 'code' => $check['code'] ?? '',
                                'options' => $check['options'] ?? [],
                                'visualization' => array_merge(['enabled' => isset($check['visualization']) ? 1 : 0], $check['visualization'] ?? []),
                            ] : ['enabled' => 0, 'kind' => '', 'prompt' => '', 'code' => '', 'options' => [], 'visualization' => ['enabled' => 0, 'caption' => '', 'layout' => 'compare', 'items' => []]];
                            return [
                                'id' => $section['id'], 'title' => $section['title'], 'order' => $index + 1,
                                'paragraphs' => implode("\n\n", $section['paragraphs'] ?? []), 'code' => $section['code'] ?? '',
                                'output' => $section['output'] ?? '', 'tip' => $section['tip'] ?? '', 'media' => $section['media'] ?? [], 'learning_check' => $check,
                                'live_codes' => $section['live_codes'] ?? [], 'tables' => $section['tables'] ?? [], 'examples' => $section['examples'] ?? [],
                            ];
                        }, $content['sections'], array_keys($content['sections']));
                    }
                @endphp
                @foreach ($formSections as $index => $section)
                    <div id="material-section-{{ $index }}">
                        @include('admin.materials.section-form', ['index' => $index, 'section' => $section, 'assetChoices' => $assetChoices])
                    </div>
                @endforeach
                </div>
            </div>
        </div>
        <div class="admin-material-actions">
            <p>Periksa hasil di pratinjau. Menyimpan draf tidak mengubah materi yang sedang dibaca mahasiswa.</p>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary" type="submit">Simpan draf</button>
                <button class="btn btn-outline-primary" type="submit" formaction="{{ route('admin.materials.preview', $material) }}" name="_method" value="POST">Pratinjau perubahan</button>
                <a class="btn btn-outline-secondary" href="{{ route('materi.show', $material->slug) }}">Lihat materi terbit</a>
                <a class="btn btn-outline-secondary" href="{{ route('admin.materials.sync', $material) }}">Tinjau sumber</a>
            </div>
        </div>
    </form>
    @if ($record)
    <section class="admin-material-publish" aria-labelledby="publish-heading">
        <div><h2 id="publish-heading">Publikasi</h2><p>Publikasikan draf yang sudah disimpan setelah meninjau pratinjau.</p></div>
        <a class="btn btn-success" href="{{ route('admin.materials.preview-saved', $material) }}">Periksa draf tersimpan</a>
    </section>
    @endif
</div>
<template id="material-section-template">
    <div id="material-section-__INDEX__">
        @include('admin.materials.section-form', ['index' => '__INDEX__', 'section' => ['id' => '', 'title' => '', 'order' => '__ORDER__', 'media' => [], 'learning_check' => ['enabled' => 0, 'options' => [], 'visualization' => ['enabled' => 0, 'layout' => 'compare', 'items' => []]]], 'assetChoices' => $assetChoices])
    </div>
</template>
@endsection
