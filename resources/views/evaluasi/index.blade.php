@extends('evaluasi.layout')

@section('evaluation-content')
<section class="exam-card exam-rules" aria-labelledby="rules-title">
    <div class="exam-card-heading"><h2 id="rules-title">Aturan Evaluasi</h2><span>Latihan mandiri</span></div>
    <div class="exam-card-body">
        <div class="exam-facts"><div><strong>{{ count($content['questions']) }}</strong><span>soal evaluasi</span></div><div><strong>{{ $settings['duration_seconds'] / 60 }} menit</strong><span>durasi pengerjaan</span></div><div><strong>{{ $settings['pass_threshold'] }}</strong><span>ambang nilai objektif</span></div></div>
        <ol class="exam-rule-list">
            <li>Kerjakan {{ $counts['multiple_choice'] }} pilihan ganda, {{ $counts['code_fill'] }} isian kode, dan {{ $counts['essay'] }} uraian singkat. Kamu dapat berpindah soal dan menandai soal untuk ditinjau.</li>
            <li>Nilai objektif dihitung dari {{ $counts['multiple_choice'] + $counts['code_fill'] }} soal. Uraian disimpan untuk ditinjau dan berstatus <strong>Belum dinilai</strong>.</li>
            <li>Timer dimulai setelah konfirmasi dan tetap berjalan saat kamu meninggalkan halaman. Saat waktu habis, sesi diselesaikan otomatis.</li>
            <li>Jawaban, penanda, dan soal aktif disimpan otomatis di browser. Refresh dapat melanjutkan sesi jika penyimpanan tersedia.</li>
            <li>Evaluasi dapat langsung diulang setelah selesai, tanpa jeda waktu latihan ulang.</li>
        </ol>
        <p class="exam-muted">Batas waktu adalah aturan latihan pada browser ini, bukan pengamanan ujian resmi. Riwayat tidak tersinkron antarperangkat dan dapat hilang jika storage browser dihapus.</p>
        <p data-exam="availability" role="status"></p>
        <div class="exam-actions"><button class="exam-button" type="button" data-exam="start" disabled>Mulai Evaluasi</button><a class="exam-button" href="{{ route('evaluasi.exam') }}" data-exam="resume" hidden>Lanjutkan pengerjaan</a></div>
    </div>
</section>
<section class="exam-card exam-history" id="riwayat" aria-labelledby="history-title">
    <div class="exam-card-heading light"><h2 id="history-title">Riwayat Evaluasi</h2><span>Disimpan pada browser ini</span></div>
    <div class="exam-card-body">
        <p data-exam="history-empty">Belum ada riwayat evaluasi. Mulai evaluasi pertama untuk melihat hasil pengerjaanmu.</p>
        <div class="exam-table-wrap" tabindex="0" role="region" aria-label="Riwayat evaluasi" data-exam="history-table" hidden>
            <table><caption>Hasil bagian objektif; uraian belum dinilai.</caption><thead><tr><th scope="col">Tanggal</th><th scope="col">Benar Objektif</th><th scope="col">Salah Objektif</th><th scope="col">Nilai Objektif</th><th scope="col">Uraian Terisi</th><th scope="col">Status</th></tr></thead><tbody data-exam="history"></tbody></table>
        </div>
    </div>
</section>
@endsection
