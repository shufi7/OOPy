@extends('admin.layouts.app')
@section('title', 'Tinjau perubahan sumber · '.$material->title)
@section('content')
<div class="admin-material-editor">
    <nav aria-label="Breadcrumb"><a href="{{ route('admin.materials.index') }}">Manajemen Materi</a> / <a href="{{ route('admin.materials.edit', $material) }}">Edit materi</a> / Tinjau sumber</nav>
    <header class="admin-material-heading">
        <div><p class="admin-material-kicker">{{ $material->title }}</p><h1>Tinjau perubahan file sumber</h1><p>Perbandingan ini tidak mengubah draf atau publikasi. Pilih baris yang ingin disalin ke draf.</p></div>
        <span class="admin-material-status admin-material-status--{{ $source_status['tone'] }}">{{ $source_status['label'] }}</span>
    </header>
    <section class="admin-material-publish">
        <div><h2>Tiga versi materi</h2><p><strong>Sumber file:</strong> SHA-256 {{ substr($source_hash, 0, 16) }} · <strong>Draf:</strong> {{ $record ? 'tersimpan, versi '.$version : 'belum ada' }} · <strong>Publikasi:</strong> {{ $record?->published_at?->timezone('Asia/Makassar')->format('d/m/Y H:i') ?? 'belum pernah diterbitkan' }}.</p><p>Publikasi lama tetap dibaca siswa sampai draf baru diterbitkan. Untuk record legacy, hash publikasi tetap berstatus perlu ditinjau sampai publikasi baru dibuat.</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('materi.show', $material->slug) }}">Lihat materi terbit</a>
    </section>
    @if ($publishedSummary)
    <details class="admin-material-published-summary"><summary>Ringkasan snapshot terbit saat ini: {{ $publishedSummary['title'] }}</summary>
        <div class="admin-material-diff-grid"><div><h3>Deskripsi terbit</h3><pre>{{ $publishedSummary['description'] }}</pre></div><div><h3>Deskripsi sumber</h3><pre>{{ $publishedSummary['source_description'] }}</pre></div></div>
        <p>Snapshot terbit membandingkan {{ count($publishedSummary['global_changed']) }} field BAB dan {{ count($publishedSummary['section_changes']) }} perubahan section dengan file saat ini. Kolom cek di bawah membandingkan file dengan draf; publikasi tidak pernah diubah dari halaman ini.</p>
        @if ($publishedSummary['global_changed'])<p><strong>Field BAB berbeda:</strong> {{ implode(', ', $publishedSummary['global_changed']) }}</p>@endif
        @if ($publishedSummary['section_changes'])<ul>@foreach ($publishedSummary['section_changes'] as $id => $fields)<li><code>{{ $id }}</code>: {{ implode(', ', $fields) }}</li>@endforeach</ul>@endif
        <p class="small text-muted">Urutan section {{ $publishedSummary['order_changed'] ? 'berbeda dari file sumber' : 'sama dengan file sumber' }}. ID section terbit: {{ implode(', ', $publishedSummary['published_section_ids']) }}</p>
    </details>
    @endif
    @if ($errors->any())<div class="alert alert-danger" role="alert">@foreach ($errors->all() as $error)<p class="mb-1">{{ $error }}</p>@endforeach</div>@endif
    @if (!$changes)
    <div class="alert alert-success" role="status">Tidak ada perbedaan dekomposisi konten antara file saat ini dan draf.</div>
    @else
    <form method="post" action="{{ route('admin.materials.synchronize', $material) }}" class="admin-material-sync-form" onsubmit="return confirm('Salin hanya baris yang dicentang ke draf? Materi terbit tidak berubah.');">
        @csrf
        <input type="hidden" name="version" value="{{ $version }}">
        <div class="admin-material-sync-toolbar"><p>Centang <strong>Ambil dari file</strong> untuk menyalin versi sumber ke draf. Baris tanpa centang tetap memakai draf admin. Bagian yang tidak ada lagi di sumber hanya ditandai; tidak dihapus.</p><button type="submit" class="btn btn-primary">Simpan keputusan ke draf</button></div>
        @foreach ($changes as $change)
        <article class="admin-material-diff-card">
            <header><h2>{{ $change['label'] }}</h2>@if ($change['field'] === '__removed')<span class="admin-material-status admin-material-status--warning">Tidak disalin / tidak dihapus</span>@elseif ($change['field'] === '__protected')<span class="admin-material-status admin-material-status--warning">Perlu tinjauan kode</span>@else<label class="form-check"><input class="form-check-input" type="checkbox" name="sync_fields[]" value="{{ $change['key'] }}"><span class="form-check-label">Ambil dari file</span></label>@endif</header>
            <div class="admin-material-diff-grid">
                <div><h3>{{ $change['field'] === '__new' ? 'Belum ada di draf' : 'Draf saat ini' }}</h3><pre>{{ is_null($change['current']) ? '—' : (is_scalar($change['current']) ? $change['current'] : json_encode($change['current'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}</pre></div>
                <div><h3>{{ $change['field'] === '__removed' ? 'Status file sumber' : 'Versi file PHP' }}</h3><pre>{{ is_null($change['source']) ? 'Bagian tidak ditemukan di sumber. Bagian lama tetap tersimpan.' : (is_scalar($change['source']) ? $change['source'] : json_encode($change['source'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}</pre></div>
            </div>
        </article>
        @endforeach
        <div class="admin-material-sync-footer"><label class="form-check"><input class="form-check-input" type="checkbox" name="acknowledge_source" value="1" required><span class="form-check-label">Saya sudah meninjau. Salin baris yang dicentang, pertahankan yang lain, dan catat pilihan ini pada draf saja.</span></label><button type="submit" class="btn btn-primary">Simpan keputusan ke draf</button></div>
    </form>
    @endif
</div>
@endsection
