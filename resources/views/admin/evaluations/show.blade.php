@extends('admin.layouts.app')
@section('title', 'Penilaian Evaluasi Akhir')
@section('content')
<header class="admin-page-header"><div><h1>Penilaian Evaluasi Akhir</h1><p>{{ $evaluation->user->name }} · {{ $evaluation->user->email }}</p></div><a class="btn btn-outline-primary" href="{{ route('admin.users.show', $evaluation->user) }}">Detail pengguna</a></header>
<section class="admin-panel p-3 mb-4">
    <dl class="row mb-0"><dt class="col-sm-4">Dikumpulkan</dt><dd class="col-sm-8">{{ $evaluation->submitted_at->timezone('Asia/Makassar')->format('d M Y H:i') }} WITA</dd>
    <dt class="col-sm-4">Nilai objektif</dt><dd class="col-sm-8">{{ $evaluation->objective_score }} / 100 ({{ $evaluation->objective_points }} / 75 poin)</dd>
    <dt class="col-sm-4">Poin uraian</dt><dd class="col-sm-8">{{ $evaluation->essay_points ?? 'Belum dinilai' }} / 25</dd>
    <dt class="col-sm-4">Nilai total</dt><dd class="col-sm-8">{{ $evaluation->total_score ?? 'Menunggu penilaian uraian' }} @if($evaluation->total_score !== null) · {{ $evaluation->total_score >= $evaluation->passing_score ? 'Lulus' : 'Belum lulus' }} @endif</dd>
    @if($evaluation->grader)<dt class="col-sm-4">Penilai terakhir</dt><dd class="col-sm-8">{{ $evaluation->grader->name }} · {{ $evaluation->graded_at->timezone('Asia/Makassar')->format('d M Y H:i') }} WITA</dd>@endif</dl>
</section>
<form method="POST" action="{{ route('admin.evaluations.update', $evaluation) }}">
    @csrf @method('PUT')
    <p>Setiap jawaban uraian bernilai 0–5 poin. Nilai total = poin objektif (maksimal 75) + poin uraian (maksimal 25). Isi seluruh skor untuk menerbitkan nilai total.</p>
    <p class="text-muted">Rubrik umum: 0 = kosong/tidak relevan; 1–2 = pemahaman terbatas; 3 = konsep utama benar; 4 = penjelasan benar dengan contoh; 5 = lengkap, akurat, dan alasan jelas. Gunakan rubrik soal jika tersedia.</p>
    @error('scores')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
    @foreach($evaluation->snapshot as $index => $question)
    <section class="admin-panel p-3 mb-3">
        <h2 class="h5">Soal {{ $index + 1 }} · {{ ['multiple_choice' => 'Pilihan ganda', 'code_fill' => 'Isian kode', 'essay' => 'Uraian'][$question['type']] }}</h2>
        <p>{{ $question['question'] }}</p>
        @if(!empty($question['code']))<pre class="p-3 bg-light"><code>{{ $question['code'] }}</code></pre>@endif
        <p class="mb-1"><strong>Jawaban pengguna</strong></p>
        @php($answer = $evaluation->answers[$question['id']] ?? null)
        <pre class="p-3 bg-light text-wrap">{{ $question['type'] === 'multiple_choice' ? ($question['options'][$answer ?? -1] ?? 'Belum dijawab') : (is_string($answer) && trim($answer) !== '' ? $answer : 'Belum dijawab') }}</pre>
        @if($question['type'] === 'essay')
            @if(!empty($question['rubric']))<p><strong>Rubrik soal:</strong> {{ $question['rubric'] }}</p>@endif
            <div class="mb-3"><label class="form-label" for="score-{{ $question['id'] }}">Skor uraian (0–5 poin)</label><input class="form-control @error('scores.'.$question['id']) is-invalid @enderror" type="number" min="0" max="5" step="0.01" required id="score-{{ $question['id'] }}" name="scores[{{ $question['id'] }}]" value="{{ old('scores.'.$question['id'], $evaluation->essay_scores[$question['id']] ?? '') }}">@error('scores.'.$question['id'])<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div><label class="form-label" for="feedback-{{ $question['id'] }}">Catatan penilaian (opsional)</label><textarea class="form-control" id="feedback-{{ $question['id'] }}" name="feedback[{{ $question['id'] }}]" rows="2" maxlength="2000">{{ old('feedback.'.$question['id'], $evaluation->essay_feedback[$question['id']] ?? '') }}</textarea>@error('feedback.'.$question['id'])<p class="text-danger">{{ $message }}</p>@enderror</div>
        @else
            <p><strong>Kunci pada saat percobaan:</strong> {{ $question['type'] === 'multiple_choice' ? $question['options'][$question['correct']] : $question['answer'] }}</p>
        @endif
    </section>
    @endforeach
    <button class="btn btn-primary" type="submit">Simpan penilaian uraian</button>
</form>
@endsection
