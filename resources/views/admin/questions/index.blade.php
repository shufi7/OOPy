@extends('admin.layouts.app')
@section('title', 'Bank Soal')
@section('content')
<div class="admin-heading">
    <div><h1>Bank Soal</h1><p>Susun revisi sebagai draft, lalu terbitkan satu paket soal yang lengkap.</p></div>
    <a class="btn btn-primary" href="{{ route('admin.questions.create', ['quiz_id' => $quiz?->id]) }}">Tambah soal</a>
</div>
<form method="get" class="admin-filters">
    <div><label for="quiz_id" class="form-label">Kuis</label>
        <select class="form-select" id="quiz_id" name="quiz_id">
            @foreach($quizzes as $item)<option value="{{ $item->id }}" @selected($quiz?->id === $item->id)>{{ $item->title }}</option>@endforeach
        </select>
    </div>
    <button class="btn btn-outline-primary" type="submit">Tampilkan</button>
</form>
@if($quiz)
<section class="admin-panel" aria-labelledby="publication-title">
    <h2 id="publication-title">{{ $quiz->title }}</h2>
    <p>{{ $quiz->type === 'final_exam' ? 'Pilih 10 pilihan ganda, 5 isian kode, dan 5 uraian.' : 'Pilih '.config('quiz.minimum_questions').' sampai '.config('quiz.maximum_questions').' soal pilihan ganda atau isian kode. Komposisi kedua jenis soal boleh berbeda untuk setiap BAB.' }} Urutan terpilih harus berbeda. Soal aktif yang tidak dipilih akan diarsipkan saat terbit.</p>
    @if($quiz->type === 'chapter_quiz')<p>Soal yang diarsipkan dapat dicentang untuk diaktifkan kembali saat paket diterbitkan.</p>@endif
    @php
        $active = $questions->where('publication_status', 'published');
        $selectedIds = old('question_ids', $active->pluck('id')->all());
        $selectedIds = is_array($selectedIds) ? $selectedIds : [];
        $selectable = $quiz->type === 'chapter_quiz' ? $questions : $questions->where('publication_status', '!=', 'archived');
        $selected = $selectable->whereIn('id', $selectedIds);
    @endphp
    @if($quiz->type === 'chapter_quiz')
    <h3>Informasi penilaian {{ $quiz->title }}</h3>
    <dl class="admin-metrics" aria-label="Penilaian paket aktif">
        <div><dt>Jumlah soal aktif</dt><dd data-publication-active="count">{{ $active->count() }}</dd></div>
        <div><dt>Pilihan ganda aktif</dt><dd>{{ $active->where('question_type', 'multiple_choice')->count() }}</dd></div>
        <div><dt>Isian kode aktif</dt><dd>{{ $active->where('question_type', 'code_fill')->count() }}</dd></div>
        <div><dt>Bobot setiap soal aktif</dt><dd data-publication-active="weight">{{ $active->isEmpty() ? 'Belum tersedia' : \App\Services\ChapterQuizScoring::format(100 / $active->count()).' poin' }}</dd></div>
        <div><dt>Nilai maksimal</dt><dd>100</dd></div>
        <div><dt>Nilai minimum kelulusan</dt><dd>{{ \App\Services\ChapterQuizScoring::format(\App\Services\ChapterQuizScoring::passingScore()) }}</dd></div>
    </dl>
    @endif
    @error('question_ids')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
    @error('publication')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
    <form method="post" action="{{ route('admin.questions.publish', $quiz) }}" @if($quiz->type === 'chapter_quiz') data-question-publication data-minimum="{{ config('quiz.minimum_questions') }}" data-maximum="{{ config('quiz.maximum_questions') }}" @endif>
        @csrf
        <input type="hidden" name="package_version" value="{{ $packageVersion }}">
        @if($quiz->type === 'chapter_quiz')
        <h3>Preview paket terpilih</h3>
        <dl class="admin-metrics" aria-label="Preview penilaian paket terpilih">
            <div><dt>Soal terpilih</dt><dd data-publication="count">{{ $selected->count() }}</dd></div>
            <div><dt>Pilihan ganda terpilih</dt><dd data-publication="multiple-choice">{{ $selected->where('question_type', 'multiple_choice')->count() }}</dd></div>
            <div><dt>Isian kode terpilih</dt><dd data-publication="code-fill">{{ $selected->where('question_type', 'code_fill')->count() }}</dd></div>
            <div><dt>Bobot setiap soal terpilih</dt><dd data-publication="weight">{{ $selected->isEmpty() ? 'Belum tersedia' : \App\Services\ChapterQuizScoring::format(100 / $selected->count()).' poin' }}</dd></div>
        </dl>
        <p data-publication="notice" role="status" aria-live="polite"></p>
        <p>Bobot yang ditampilkan dibulatkan maksimal dua desimal. Nilai resmi dihitung dari rasio jawaban benar terhadap seluruh soal, dengan nilai maksimal 100. Preview berlaku setelah paket diterbitkan; draft tidak mengubah paket aktif.</p>
        <noscript><p>Aktifkan JavaScript untuk memperbarui preview saat memilih soal. Server tetap memvalidasi seluruh pilihan saat diterbitkan.</p></noscript>
        @endif
        <div class="table-responsive" tabindex="0" role="region" aria-label="Daftar bank soal">
            <table class="table align-middle admin-question-bank-table">
                <thead><tr><th scope="col">Terbitkan</th><th scope="col">Urutan</th><th scope="col">Soal</th><th scope="col">Jenis</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
                <tbody>
                @forelse($questions as $question)
                    <tr>
                        <td>@if($quiz->type === 'chapter_quiz' || $question->publication_status !== 'archived')<label class="admin-question-select"><input class="form-check-input" type="checkbox" name="question_ids[]" value="{{ $question->id }}" data-question-type="{{ $question->question_type }}" @checked(in_array($question->id, $selectedIds))><span class="visually-hidden">Terbitkan soal {{ $question->id }}</span></label>@else &mdash; @endif</td>
                        <td>{{ $question->display_order ?? $question->question_order }}</td>
                        <td><a href="{{ route('admin.questions.show', $question) }}">{{ Str::limit($question->question_text, 120) }}</a>@if($question->revision_of_id)<small class="d-block">Revisi soal #{{ $question->revision_of_id }}</small>@endif</td>
                        <td>{{ ['multiple_choice' => 'Pilihan ganda', 'code_fill' => 'Isian kode', 'essay' => 'Uraian'][$question->question_type] }}</td>
                        <td>{{ ['published' => 'Aktif', 'draft' => 'Draft', 'archived' => 'Diarsipkan'][$question->publication_status] }}</td>
                        <td><div class="admin-row-actions"><a href="{{ route('admin.questions.edit', $question) }}">{{ $question->publication_status === 'draft' ? 'Edit' : 'Buat revisi' }}</a><a class="btn admin-danger" href="{{ route('admin.questions.confirm-delete', $question) }}" aria-label="Hapus soal {{ $question->id }}">Hapus</a></div></td>
                    </tr>
                @empty<tr><td colspan="6">Belum ada soal. Jalankan seeder konten awal atau tambahkan draft.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        <button class="btn btn-primary" type="submit">Terbitkan paket terpilih</button>
    </form>
</section>
@else
<p>Belum ada kuis. Jalankan seeder konten OOPy terlebih dahulu.</p>
@endif
@endsection
@push('scripts')
<script type="module" src="{{ asset('js/admin/question-publication.js') }}"></script>
@endpush
