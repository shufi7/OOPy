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
    <p>{{ $quiz->type === 'final_exam' ? 'Pilih 10 pilihan ganda, 5 isian kode, dan 5 uraian.' : 'Pilih 3 pilihan ganda dan 2 isian kode.' }} Urutan terpilih harus berbeda. Soal aktif yang tidak dipilih akan diarsipkan saat terbit.</p>
    @error('question_ids')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
    @error('publication')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
    <form method="post" action="{{ route('admin.questions.publish', $quiz) }}">
        @csrf
        <div class="table-responsive" tabindex="0" role="region" aria-label="Daftar bank soal">
            <table class="table align-middle">
                <thead><tr><th scope="col">Terbitkan</th><th scope="col">Urutan</th><th scope="col">Soal</th><th scope="col">Jenis</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
                <tbody>
                @forelse($questions as $question)
                    <tr>
                        <td>@if($question->publication_status !== 'archived')<input class="form-check-input" type="checkbox" name="question_ids[]" value="{{ $question->id }}" aria-label="Terbitkan soal {{ $question->id }}" @checked(in_array($question->id, old('question_ids', $questions->where('publication_status', 'published')->pluck('id')->all())) )>@else &mdash; @endif</td>
                        <td>{{ $question->display_order ?? $question->question_order }}</td>
                        <td><a href="{{ route('admin.questions.show', $question) }}">{{ Str::limit($question->question_text, 120) }}</a>@if($question->revision_of_id)<small class="d-block">Revisi soal #{{ $question->revision_of_id }}</small>@endif</td>
                        <td>{{ ['multiple_choice' => 'Pilihan ganda', 'code_fill' => 'Isian kode', 'essay' => 'Uraian'][$question->question_type] }}</td>
                        <td>{{ ['published' => 'Aktif', 'draft' => 'Draft', 'archived' => 'Diarsipkan'][$question->publication_status] }}</td>
                        <td><a href="{{ route('admin.questions.edit', $question) }}">{{ $question->publication_status === 'draft' ? 'Edit' : 'Buat revisi' }}</a></td>
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
