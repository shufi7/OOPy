@extends('admin.layouts.app')
@section('title', 'Bank Soal')
@section('content')
<div class="admin-question-page admin-question-index">
    <div class="admin-heading admin-question-heading">
        <div><h1>Bank Soal</h1><p>Kelola draft, revisi, dan paket soal untuk setiap kuis dengan alur yang rapi dan mudah dipindai.</p></div>
        <a class="btn btn-primary" href="{{ route('admin.questions.create', ['quiz_id' => $quiz?->id]) }}"><x-dashboard-icon name="plus" />Tambah Soal</a>
    </div>
    <form method="get" class="admin-filters admin-question-filters">
        <label for="quiz_id" class="form-label"><span class="admin-question-label-icon"><x-dashboard-icon name="clipboard" /></span>Pilih kuis</label>
            <select class="form-select" id="quiz_id" name="quiz_id">
                @foreach($quizzes as $item)<option value="{{ $item->id }}" @selected($quiz?->id === $item->id)>{{ $item->title }}</option>@endforeach
            </select>
        <button class="btn btn-outline-primary" type="submit">Tampilkan Kuis</button>
    </form>
    @if($quiz)
    <section class="admin-panel admin-question-panel" aria-labelledby="publication-title">
        <div class="admin-question-section-heading">
            <div class="admin-question-identity">
                <span class="admin-question-identity-icon"><x-dashboard-icon name="scores" /></span>
                <div><h2 id="publication-title">{{ $quiz->title }}</h2><p>{{ $quiz->type === 'final_exam' ? 'Pilih tepat 10 pilihan ganda dan 10 isian kode. Seluruh soal dinilai otomatis.' : 'Pilih soal untuk paket kuis ini. Anda dapat mengatur komposisi dan urutan soal sesuai kebutuhan.' }}</p></div>
            </div>
            <div class="admin-question-rule-row">
                <p class="admin-question-archive-note"><x-dashboard-icon name="info" /><span>Soal aktif yang tidak dipilih akan diarsipkan saat terbit. @if($quiz->type === 'chapter_quiz')Arsip tetap tersimpan dan dapat diaktifkan kembali.@endif</span></p>
                <details class="admin-question-rules"><summary>Aturan paket</summary><p>{{ $quiz->type === 'final_exam' ? 'Pilih tepat 10 pilihan ganda dan 10 isian kode. Seluruh soal dinilai otomatis.' : 'Pilih '.config('quiz.minimum_questions').' sampai '.config('quiz.maximum_questions').' soal pilihan ganda atau isian kode. Komposisi kedua jenis soal boleh berbeda untuk setiap BAB.' }} Urutan terpilih harus berbeda.</p></details>
            </div>
        </div>
    @php
        $active = $questions->where('publication_status', 'published');
        $selectedIds = old('question_ids', $active->pluck('id')->all());
        $selectedIds = is_array($selectedIds) ? $selectedIds : [];
        $selectable = $quiz->type === 'chapter_quiz' ? $questions : $questions->where('publication_status', '!=', 'archived');
        $selected = $selectable->whereIn('id', $selectedIds);
    @endphp
    @error('question_ids')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
    @error('publication')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
    <form method="post" action="{{ route('admin.questions.publish', $quiz) }}" @if($quiz->type === 'chapter_quiz') data-question-publication data-minimum="{{ config('quiz.minimum_questions') }}" data-maximum="{{ config('quiz.maximum_questions') }}" @endif>
        @csrf
        <input type="hidden" name="package_version" value="{{ $packageVersion }}">
        @if($quiz->type === 'chapter_quiz')
        <div class="admin-question-package-grid">
        <section class="admin-question-subsection" aria-labelledby="active-package-title">
            <div class="admin-question-summary-heading"><h3 id="active-package-title"><x-dashboard-icon name="layers" />Paket aktif</h3><span>{{ $active->isEmpty() ? 'Belum ada soal aktif' : 'Paket yang sedang berlaku' }}</span></div>
            <dl class="admin-metrics" aria-label="Penilaian paket aktif">
                <div><dt><span class="admin-question-stat-icon"><x-dashboard-icon name="clipboard" /></span>Jumlah soal aktif</dt><dd data-publication-active="count">{{ $active->count() }}</dd></div>
                <div><dt><span class="admin-question-stat-icon"><x-dashboard-icon name="list" /></span>Pilihan ganda aktif</dt><dd>{{ $active->where('question_type', 'multiple_choice')->count() }}</dd></div>
                <div><dt><span class="admin-question-stat-icon"><x-dashboard-icon name="code" /></span>Isian kode aktif</dt><dd>{{ $active->where('question_type', 'code_fill')->count() }}</dd></div>
                <div><dt><span class="admin-question-stat-icon"><x-dashboard-icon name="star" /></span>Bobot per soal</dt><dd data-publication-active="weight">{{ $active->isEmpty() ? 'Belum tersedia' : \App\Services\ChapterQuizScoring::format(100 / $active->count()).' poin' }}</dd></div>
                <div><dt><span class="admin-question-stat-icon"><x-dashboard-icon name="trophy" /></span>Nilai maksimal</dt><dd>100</dd></div>
                <div><dt><span class="admin-question-stat-icon"><x-dashboard-icon name="shield-check" /></span>Nilai minimum kelulusan</dt><dd>{{ \App\Services\ChapterQuizScoring::format(\App\Services\ChapterQuizScoring::passingScore()) }}</dd></div>
            </dl>
        </section>
        <div class="admin-question-preview" aria-labelledby="selected-preview-title">
        <div class="admin-question-summary-heading"><h3 id="selected-preview-title"><x-dashboard-icon name="eye" />Preview paket terpilih</h3><span>Rangkuman seleksi</span></div>
        <dl class="admin-question-preview-metrics" aria-label="Preview penilaian paket terpilih">
            <div><dt><span class="admin-question-stat-icon"><x-dashboard-icon name="clipboard" /></span>Soal terpilih</dt><dd data-publication="count">{{ $selected->count() }}</dd></div>
            <div><dt><span class="admin-question-stat-icon"><x-dashboard-icon name="list" /></span>Pilihan ganda</dt><dd data-publication="multiple-choice">{{ $selected->where('question_type', 'multiple_choice')->count() }}</dd></div>
            <div><dt><span class="admin-question-stat-icon"><x-dashboard-icon name="code" /></span>Isian kode</dt><dd data-publication="code-fill">{{ $selected->where('question_type', 'code_fill')->count() }}</dd></div>
            <div><dt><span class="admin-question-stat-icon"><x-dashboard-icon name="star" /></span>Bobot per soal</dt><dd data-publication="weight">{{ $selected->isEmpty() ? 'Belum tersedia' : \App\Services\ChapterQuizScoring::format(100 / $selected->count()).' poin' }}</dd></div>
        </dl>
        <div class="admin-question-preview-feedback">
            <span class="admin-question-feedback-icon admin-question-valid"><x-dashboard-icon name="check-circle" /></span><span class="admin-question-feedback-icon admin-question-invalid"><x-dashboard-icon name="info" /></span>
            <div><p id="publication-notice" data-publication="notice" role="status" aria-live="polite"></p><p class="admin-question-scoring-help">Bobot tampilan dibulatkan maksimal dua desimal. Nilai resmi berasal dari rasio jawaban benar terhadap seluruh soal, maksimal 100. Preview berlaku setelah terbit; draft tidak mengubah paket aktif.</p></div>
        </div>
        <noscript><p>Aktifkan JavaScript untuk memperbarui preview saat memilih soal. Server tetap memvalidasi seluruh pilihan saat diterbitkan.</p></noscript>
        </div>
        </div>
        @endif
        <div class="admin-question-list-heading"><h3><span class="admin-question-label-icon"><x-dashboard-icon name="list" /></span>Daftar soal</h3><p>Pilih soal yang akan masuk ke paket {{ $quiz->title }}.</p></div>
        <div class="table-responsive" tabindex="0" role="region" aria-label="Daftar bank soal" aria-describedby="question-scroll-help">
            <table class="table align-middle admin-question-bank-table">
                <thead><tr><th scope="col">Terbitkan</th><th scope="col">Urutan</th><th scope="col">Soal</th><th scope="col">Jenis</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
                <tbody>
                @forelse($questions as $question)
                    <tr>
                        <td>@if($quiz->type === 'chapter_quiz' || $question->publication_status !== 'archived')<label class="admin-question-select"><input class="form-check-input" type="checkbox" name="question_ids[]" value="{{ $question->id }}" data-question-type="{{ $question->question_type }}" @checked(in_array($question->id, $selectedIds))><span class="visually-hidden">Terbitkan soal {{ $question->id }}</span></label>@else &mdash; @endif</td>
                        <td class="admin-question-order">{{ $question->display_order ?? $question->question_order }}</td>
                        <td class="admin-question-copy"><a href="{{ route('admin.questions.show', $question) }}">{{ Str::limit($question->question_text, 120) }}</a>@if($question->revision_of_id)<small><x-dashboard-icon name="refresh" />Revisi soal #{{ $question->revision_of_id }}</small>@endif</td>
                        <td>{{ ['multiple_choice' => 'Pilihan ganda', 'code_fill' => 'Isian kode', 'essay' => 'Uraian'][$question->question_type] }}</td>
                        <td><span class="admin-status-badge {{ ['published' => 'is-success', 'draft' => 'is-warning', 'archived' => 'is-neutral'][$question->publication_status] }}">{{ ['published' => 'Aktif', 'draft' => 'Draft', 'archived' => 'Diarsipkan'][$question->publication_status] }}</span></td>
                        <td><div class="admin-row-actions"><a class="btn btn-outline-secondary admin-question-revise" href="{{ route('admin.questions.edit', $question) }}"><x-dashboard-icon :name="$question->publication_status === 'draft' ? 'edit' : 'refresh'" />{{ $question->publication_status === 'draft' ? 'Edit' : 'Buat revisi' }}</a><a class="btn admin-question-delete" href="{{ route('admin.questions.confirm-delete', $question) }}" aria-label="Hapus soal {{ $question->id }}"><x-dashboard-icon name="trash" />Hapus</a></div></td>
                    </tr>
                @empty<tr><td class="admin-empty" colspan="6"><strong>Belum ada soal untuk kuis ini.</strong><span>Tambahkan soal sebagai draft, lalu pilih untuk menerbitkan paket.</span></td></tr>@endforelse
                </tbody>
            </table>
        </div>
        <p id="question-scroll-help" class="admin-question-scroll-hint">Geser tabel untuk melihat kolom lainnya. Dengan keyboard, fokuskan tabel lalu gunakan tombol panah.</p>
        <div class="admin-question-publication-footer">
            <span class="admin-question-footer-icon">@if($quiz->type === 'chapter_quiz')<span class="admin-question-valid"><x-dashboard-icon name="check" /></span><span class="admin-question-invalid"><x-dashboard-icon name="info" /></span>@else<x-dashboard-icon name="info" />@endif</span>
            <div>
                @if($quiz->type === 'chapter_quiz')
                    <strong data-publication="summary">{{ $selected->count() }} soal dipilih untuk diterbitkan</strong>
                    <p>Pastikan urutan, jenis, dan bobot soal sudah sesuai sebelum menerbitkan paket.</p>
                @else
                    <strong>Terbitkan paket Evaluasi Akhir</strong>
                    <p>Paket harus berisi tepat 10 pilihan ganda dan 10 isian kode. Server memvalidasi seluruh pilihan.</p>
                @endif
            </div>
            <button class="btn btn-primary" type="submit" @if($quiz->type === 'chapter_quiz') aria-describedby="publication-notice" @endif><x-dashboard-icon name="send" />Terbitkan paket terpilih</button>
        </div>
    </form>
</section>
    @else
    <div class="admin-empty-state admin-question-empty"><h2>Belum ada kuis</h2><p>Belum ada data kuis yang dapat dikelola. Periksa kembali konten awal OOPy.</p></div>
    @endif
</div>
@endsection
@push('scripts')
<script type="module" src="{{ asset('js/admin/question-publication.js') }}"></script>
@endpush
