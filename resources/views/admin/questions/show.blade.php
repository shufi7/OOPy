@extends('admin.layouts.app')
@section('title', 'Pratinjau Soal')
@section('content')
<div class="admin-heading"><div><h1>Pratinjau Soal</h1><p>{{ $question->quiz->title }} · {{ ['published' => 'Aktif', 'draft' => 'Draft', 'archived' => 'Diarsipkan'][$question->publication_status] }}</p></div><a href="{{ route('admin.questions.index', ['quiz_id' => $question->quiz_id]) }}">Kembali ke bank soal</a></div>
<article class="admin-panel">
    <h2>Urutan {{ $question->display_order ?? $question->question_order }}</h2>
    <p class="admin-preserve">{{ $question->question_text }}</p>
    @if($question->code_snippet)<pre><code>{{ $question->code_snippet }}</code></pre>@endif
    @if($question->options)<ol start="0">@foreach($question->options as $option)<li>{{ $option }}</li>@endforeach</ol>@endif
    @if($question->question_type !== 'essay')<h3>Kunci jawaban</h3><pre><code>{{ $question->correct_answer }}</code></pre>@else<h3>Penilaian manual</h3><p>0–5 poin. Jawaban uraian belum dinilai sampai Admin memberikan skor.</p><p class="admin-preserve">{{ $question->essay_rubric ?: 'Rubrik khusus belum ditambahkan.' }}</p>@endif
    @if($question->explanation)<h3>Penjelasan</h3><p class="admin-preserve">{{ $question->explanation }}</p>@endif
    <div class="admin-actions"><a class="btn btn-primary" href="{{ route('admin.questions.edit', $question) }}">{{ $question->publication_status === 'draft' ? 'Edit draft' : 'Buat revisi' }}</a></div>
    @if($question->publication_status === 'draft')
    <form class="mt-3" action="{{ route('admin.questions.archive', $question) }}" method="post">@csrf @method('patch')<button class="btn btn-outline-secondary" type="submit">Arsipkan draft</button></form>
    @endif
</article>
@endsection
