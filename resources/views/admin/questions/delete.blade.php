@extends('admin.layouts.app')
@section('title', 'Hapus Soal')
@section('content')
<div class="admin-heading"><div><h1>Hapus Soal</h1><p>Periksa soal sebelum menghapusnya dari Bank Soal.</p></div></div>
<section class="admin-panel" aria-labelledby="delete-question-title">
    <h2 id="delete-question-title">Soal #{{ $question->id }}</h2>
    <p>{{ $question->quiz->title }}</p>
    <p class="admin-preserve">{{ $question->question_text }}</p>
    @if($deletionError)
        <p class="alert alert-warning" role="status">{{ $deletionError }}</p>
        <a class="btn btn-outline-secondary" href="{{ route('admin.questions.index', ['quiz_id' => $question->quiz_id]) }}">Kembali ke Bank Soal</a>
    @else
        <p>Soal akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.</p>
        <form class="admin-delete-form" method="POST" action="{{ route('admin.questions.destroy', $question) }}">
            @csrf
            @method('DELETE')
            <div class="form-check"><input class="form-check-input" type="checkbox" id="confirmation" name="confirmation" value="1" required><label class="form-check-label" for="confirmation">Saya memahami bahwa soal ini akan dihapus permanen.</label></div>
            <div class="admin-row-actions"><a class="btn btn-outline-secondary" href="{{ route('admin.questions.index', ['quiz_id' => $question->quiz_id]) }}">Batal</a><button class="btn admin-danger" type="submit">Hapus soal permanen</button></div>
        </form>
    @endif
</section>
@endsection
