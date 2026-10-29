@extends('admin.layouts.app')
@section('title', 'Manajemen Materi')
@section('content')
<header class="mb-4">
    <h1>Manajemen Materi</h1>
    <p>Kelola enam BAB pembelajaran. Draf baru tampil ke pengguna setelah dipublikasikan.</p>
</header>
<div class="table-responsive" tabindex="0" role="region" aria-label="Daftar materi">
    <table class="table align-middle">
        <thead><tr><th scope="col">BAB</th><th scope="col">Judul</th><th scope="col">Status konten</th><th scope="col">Publikasi terakhir</th><th scope="col">Tindakan</th></tr></thead>
        <tbody>
        @forelse ($materials as $material)
            @php($record = $records->get($material->id))
            <tr>
                <td>{{ $registry[$material->slug]['bab'] }}</td>
                <td>{{ $record?->draft_title ?? $material->title }}</td>
                <td>{{ $record ? ($record->published_at ? ($record->draft == $record->published && $record->draft_title === $record->published_title ? 'Dipublikasikan' : 'Draf perubahan') : 'Draf belum dipublikasikan') : 'Materi awal' }}</td>
                <td>{{ $record?->published_at?->timezone('Asia/Makassar')->format('d/m/Y H:i') ?? '—' }}</td>
                <td><a class="btn btn-outline-primary" href="{{ route('admin.materials.edit', $material) }}">Kelola materi</a></td>
            </tr>
        @empty
            <tr><td colspan="5">Materi belum tersedia di database. Jalankan seeder konten OOPy terlebih dahulu.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
