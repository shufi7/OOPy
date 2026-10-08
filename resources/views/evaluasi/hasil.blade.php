@extends('evaluasi.layout')
@section('heading', 'Hasil Evaluasi')
@section('description', 'Nilai bagian objektif dan jawaban uraian ditampilkan terpisah untuk membantu kamu meninjau pembelajaran.')

@section('evaluation-content')
<section class="exam-card" data-exam="no-result" hidden><div class="exam-card-body"><h2>Belum ada hasil untuk ditampilkan</h2><p>Selesaikan sesi evaluasi untuk melihat ringkasan dan jawabanmu.</p><a class="exam-button" href="{{ route('evaluasi.index') }}">Buka Evaluasi Akhir</a><a href="{{ route('evaluasi.exam') }}" data-exam="resume" hidden>Lanjutkan pengerjaan</a></div></section>
<div class="exam-results" data-exam="results" hidden>
    <section class="exam-card" aria-labelledby="results-title"><div class="exam-card-heading"><h2 id="results-title">Ringkasan Hasil</h2><span data-exam="completion-reason"></span></div><div class="exam-card-body">
        <div class="exam-result-lead"><div><span>Nilai objektif</span><strong data-exam="score"></strong><span>Dari {{ $counts['multiple_choice'] + $counts['code_fill'] }} soal objektif</span></div><p class="exam-result-status" data-exam="objective-status" role="status"></p></div>
        <dl class="exam-result-facts"><div><dt>Benar objektif</dt><dd data-exam="correct"></dd></div><div><dt>Salah / tidak benar objektif</dt><dd data-exam="incorrect"></dd></div><div><dt>Uraian terisi</dt><dd data-exam="essays"></dd></div><div><dt>Durasi pengerjaan</dt><dd data-exam="duration"></dd></div></dl>
        <p class="exam-notice">Uraian: <strong>Belum dinilai</strong>. Nilai objektif ini belum merupakan penilaian keseluruhan Evaluasi Akhir.</p>
        <p data-exam="availability" role="status"></p>
        <div class="exam-actions"><a class="exam-button secondary" href="{{ route('evaluasi.index') }}">Kembali ke Evaluasi Akhir</a><a class="exam-button secondary" href="{{ route('evaluasi.index') }}#riwayat">Lihat Riwayat</a><button type="button" class="exam-button secondary" data-exam="review-open">Tinjau Jawaban</button><button type="button" class="exam-button secondary" data-exam="start" disabled>Ulangi Evaluasi</button><a class="exam-button" href="{{ route('evaluasi.exam') }}" data-exam="resume" hidden>Lanjutkan pengerjaan</a></div>
    </div></section>
    <details class="exam-card exam-review" data-exam="review"><summary>Tinjau Jawaban Evaluasi</summary><div class="exam-card-body" data-exam="review-list"></div></details>
</div>
@endsection
