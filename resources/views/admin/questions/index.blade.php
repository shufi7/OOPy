@extends('admin.layouts.app')
@section('title', 'Bank Soal')
@section('content')
<div class="admin-question-page">
    <div class="admin-heading admin-question-heading">
        <div><h1>Bank Soal</h1><p>Kelola draft, tinjau revisi, dan terbitkan paket soal untuk setiap kuis.</p></div>
        <a class="btn btn-primary" href="{{ route('admin.questions.create', ['quiz_id' => $quiz?->id]) }}">Tambah soal</a>
    </div>
    <form method="get" class="admin-filters admin-question-filters">
        <div><label for="quiz_id" class="form-label">Pilih kuis</label>
            <select class="form-select" id="quiz_id" name="quiz_id">
                @foreach($quizzes as $item)<option value="{{ $item->id }}" @selected($quiz?->id === $item->id)>{{ $item->title }}</option>@endforeach
            </select>
        </div>
        <button class="btn btn-outline-primary" type="submit">Tampilkan kuis</button>
    </form>
    @if($quiz)
    <section class="admin-panel admin-question-panel" aria-labelledby="publication-title">
        <div class="admin-question-section-heading">
            <div><h2 id="publication-title">{{ $quiz->title }}</h2><p>{{ $quiz->type === 'final_exam' ? 'Pilih tepat 10 pilihan ganda dan 10 isian kode. Seluruh soal dinilai otomatis.' : 'Pilih '.config('quiz.minimum_questions').' sampai '.config('quiz.maximum_questions').' soal pilihan ganda atau isian kode. Komposisi kedua jenis soal boleh berbeda untuk setiap BAB.' }} Urutan terpilih harus berbeda. Soal aktif yang tidak dipilih akan diarsipkan saat terbit.</p>
            @if($quiz->type === 'chapter_quiz')<p>Soal yang diarsipkan dapat dicentang untuk diaktifkan kembali saat paket diterbitkan.</p>@endif</div>
        </div>
    @php
        $active = $questions->where('publication_status', 'published');
        $selectedIds = old('question_ids', $active->pluck('id')->all());
        $selectedIds = is_array($selectedIds) ? $selectedIds : [];
        $selectable = $quiz->type === 'chapter_quiz' ? $questions : $questions->where('publication_status', '!=', 'archived');
        $selected = $selectable->whereIn('id', $selectedIds);
    @endphp
    @if($quiz->type === 'chapter_quiz')
    <div class="admin-question-subsection"><h3>Penilaian paket aktif</h3>
    <dl class="admin-metrics" aria-label="Penilaian paket aktif">
        <div><dt>Jumlah soal aktif</dt><dd data-publication-active="count">{{ $active->count() }}</dd></div>
        <div><dt>Pilihan ganda aktif</dt><dd>{{ $active->where('question_type', 'multiple_choice')->count() }}</dd></div>
        <div><dt>Isian kode aktif</dt><dd>{{ $active->where('question_type', 'code_fill')->count() }}</dd></div>
        <div><dt>Bobot setiap soal aktif</dt><dd data-publication-active="weight">{{ $active->isEmpty() ? 'Belum tersedia' : \App\Services\ChapterQuizScoring::format(100 / $active->count()).' poin' }}</dd></div>
        <div><dt>Nilai maksimal</dt><dd>100</dd></div>
        <div><dt>Nilai minimum kelulusan</dt><dd>{{ \App\Services\ChapterQuizScoring::format(\App\Services\ChapterQuizScoring::passingScore()) }}</dd></div>
    </dl>
    </div>
    @endif
    @error('question_ids')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
    @error('publication')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
    <form method="post" action="{{ route('admin.questions.publish', $quiz) }}" @if($quiz->type === 'chapter_quiz') data-question-publication data-minimum="{{ config('quiz.minimum_questions') }}" data-maximum="{{ config('quiz.maximum_questions') }}" @endif>
        @csrf
        <input type="hidden" name="package_version" value="{{ $packageVersion }}">
        @if($quiz->type === 'chapter_quiz')
        <div class="admin-question-preview" aria-labelledby="selected-preview-title">
        <h3 id="selected-preview-title">Preview paket terpilih</h3>
        <dl class="admin-metrics" aria-label="Preview penilaian paket terpilih">
            <div><dt>Soal terpilih</dt><dd data-publication="count">{{ $selected->count() }}</dd></div>
            <div><dt>Pilihan ganda terpilih</dt><dd data-publication="multiple-choice">{{ $selected->where('question_type', 'multiple_choice')->count() }}</dd></div>
            <div><dt>Isian kode terpilih</dt><dd data-publication="code-fill">{{ $selected->where('question_type', 'code_fill')->count() }}</dd></div>
            <div><dt>Bobot setiap soal terpilih</dt><dd data-publication="weight">{{ $selected->isEmpty() ? 'Belum tersedia' : \App\Services\ChapterQuizScoring::format(100 / $selected->count()).' poin' }}</dd></div>
        </dl>
        <p data-publication="notice" role="status" aria-live="polite"></p>
        <p>Bobot yang ditampilkan dibulatkan maksimal dua desimal. Nilai resmi dihitung dari rasio jawaban benar terhadap seluruh soal, dengan nilai maksimal 100. Preview berlaku setelah paket diterbitkan; draft tidak mengubah paket aktif.</p>
        <noscript><p>Aktifkan JavaScript untuk memperbarui preview saat memilih soal. Server tetap memvalidasi seluruh pilihan saat diterbitkan.</p></noscript>
        </div>
        @endif
        <div class="admin-question-list-heading"><h3>Daftar soal</h3><p>Pilih soal yang akan masuk ke paket {{ $quiz->title }}.</p></div>
        <div class="table-responsive" tabindex="0" role="region" aria-label="Daftar bank soal">
            <table class="table align-middle admin-question-bank-table">
                <thead><tr><th scope="col">Terbitkan</th><th scope="col">Urutan</th><th scope="col">Soal</th><th scope="col">Jenis</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
                <tbody>
                @forelse($questions as $question)
                    <tr>
                        <td>@if($quiz->type === 'chapter_quiz' || $question->publication_status !== 'archived')<label class="admin-question-select"><input class="form-check-input" type="checkbox" name="question_ids[]" value="{{ $question->id }}" data-question-type="{{ $question->question_type }}" @checked(in_array($question->id, $selectedIds))><span class="visually-hidden">Terbitkan soal {{ $question->id }}</span></label>@else &mdash; @endif</td>
                        <td class="admin-question-order">{{ $question->display_order ?? $question->question_order }}</td>
                        <td class="admin-question-copy"><a href="{{ route('admin.questions.show', $question) }}">{{ Str::limit($question->question_text, 120) }}</a>@if($question->revision_of_id)<small class="d-block">Revisi soal #{{ $question->revision_of_id }}</small>@endif</td>
                        <td>{{ ['multiple_choice' => 'Pilihan ganda', 'code_fill' => 'Isian kode', 'essay' => 'Uraian'][$question->question_type] }}</td>
                        <td><span class="admin-status-badge {{ ['published' => 'is-success', 'draft' => 'is-warning', 'archived' => 'is-neutral'][$question->publication_status] }}">{{ ['published' => 'Aktif', 'draft' => 'Draft', 'archived' => 'Diarsipkan'][$question->publication_status] }}</span></td>
                        <td><div class="admin-row-actions"><a href="{{ route('admin.questions.edit', $question) }}">{{ $question->publication_status === 'draft' ? 'Edit' : 'Buat revisi' }}</a><a class="btn admin-danger" href="{{ route('admin.questions.confirm-delete', $question) }}" aria-label="Hapus soal {{ $question->id }}">Hapus</a></div></td>
                    </tr>
                @empty<tr><td class="admin-empty" colspan="6"><strong>Belum ada soal untuk kuis ini.</strong><span>Tambahkan soal sebagai draft, lalu pilih untuk menerbitkan paket.</span></td></tr>@endforelse
                </tbody>
            </table>
        </div>
        <p class="admin-question-scroll-hint">Pada layar kecil, geser tabel ke samping untuk melihat semua kolom.</p>
        <button class="btn btn-primary" type="submit">Terbitkan paket terpilih</button>
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
