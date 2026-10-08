@extends('evaluasi.layout')
@section('description', 'Fokus pada satu soal, simpan jawabanmu, lalu tinjau sebelum menyelesaikan evaluasi.')

@section('evaluation-content')
<section class="exam-card" data-exam="no-session" hidden><div class="exam-card-body"><h2>Belum ada sesi aktif</h2><p>Mulai evaluasi melalui halaman aturan setelah membaca ketentuannya.</p><a class="exam-button" href="{{ route('evaluasi.index') }}">Buka Aturan Evaluasi</a></div></section>
<div class="exam-layout" data-exam="workspace" hidden>
    <aside class="exam-sidebar" aria-label="Waktu dan navigasi soal">
        <div class="exam-sidebar-heading"><strong>Evaluasi Akhir OOPy</strong><span>Penguasaan Konsep Python OOP</span></div>
        <div class="exam-timer"><span>Sisa Waktu</span><strong data-exam="timer" aria-label="Sisa waktu">{{ sprintf('%02d:%02d', intdiv($settings['duration_seconds'], 60), $settings['duration_seconds'] % 60) }}</strong><p data-exam="timer-warning" role="status" aria-live="polite"></p></div>
        <details class="exam-number-panel" data-exam="number-panel" open>
            <summary>Navigasi Soal <span aria-hidden="true">⌄</span></summary>
            <div class="exam-number-grid" data-exam="numbers" aria-label="Nomor soal"></div>
            <ul class="exam-legend"><li><span aria-hidden="true">✓</span> Sudah dijawab</li><li><span aria-hidden="true">○</span> Belum dijawab</li><li><span aria-hidden="true">◉</span> Sedang aktif</li><li><span aria-hidden="true">⚑</span> Ditandai untuk ditinjau</li></ul>
        </details>
        <div class="exam-progress"><p data-exam="progress-text" role="status"></p><progress data-exam="progress" max="{{ count($content['questions']) }}" value="0" aria-label="Soal terjawab"></progress><span data-exam="save-status">Jawaban disimpan otomatis.</span></div>
    </aside>
    <section class="exam-card exam-question" aria-labelledby="question-counter">
        <div class="exam-question-heading"><h2 id="question-counter" data-exam="counter" tabindex="-1"></h2><span class="exam-tag" data-exam="type"></span></div>
        <div class="exam-card-body">
            <p class="exam-question-status" data-exam="answer-status"></p>
            <p class="exam-question-text" id="question-text" data-exam="question"></p>
            <figure class="exam-code" data-exam="code-card" hidden><figcaption>Potongan kode Python — isi kotak kosong</figcaption><pre tabindex="0"><code data-exam="code"><span class="language-python" data-exam="code-before"></span><input id="code-answer" class="exam-inline-answer" type="text" data-exam="code-answer" aria-label="Lengkapi bagian kosong pada kode Python" autocomplete="off" autocapitalize="off" spellcheck="false" maxlength="500" aria-describedby="question-text code-help" disabled><span class="language-python" data-exam="code-after"></span></code></pre></figure>
            <fieldset class="exam-options" data-exam="options-group" aria-describedby="question-text"><legend>Pilih satu jawaban</legend><div data-exam="options"></div></fieldset>
            <div data-exam="code-group" hidden><p class="exam-muted" id="code-help">Ketik jawaban langsung di kotak kosong dalam kode. Huruf besar/kecil mengikuti Python; spasi luar dan jarak antartoken yang setara diabaikan.</p></div>
            <div class="exam-answer" data-exam="essay-group" hidden><label for="essay-answer">Jawaban uraian</label><textarea id="essay-answer" rows="7" data-exam="essay-answer" maxlength="10000" aria-describedby="question-text essay-help"></textarea><p id="essay-help">Jawaban disimpan untuk ditinjau. Uraian belum dinilai otomatis.</p></div>
            <div class="exam-navigation"><button type="button" class="exam-button secondary" data-exam="previous">Soal Sebelumnya</button><button type="button" class="exam-button secondary" data-exam="mark" aria-pressed="false">Tandai untuk Ditinjau</button><button type="button" class="exam-button" data-exam="next">Soal Selanjutnya</button></div>
        </div>
    </section>
    <div class="exam-workspace-footer"><a href="{{ route('evaluasi.index') }}">Kembali ke Evaluasi</a><button type="button" class="exam-button" data-exam="finish">Ringkasan Pengerjaan / Selesai Evaluasi</button></div>
</div>
<section class="exam-card" data-exam="local-results" hidden><div class="exam-card-body"><h2>Hasil sementara pada halaman ini</h2><p data-exam="local-summary"></p><p>Hasil belum tersimpan. Tetap buka halaman ini dan coba simpan lagi sebelum berpindah halaman.</p><button type="button" class="exam-button" data-exam="retry-save">Coba Simpan Hasil Lagi</button></div></section>
<dialog class="exam-dialog" data-exam="finish-dialog" aria-labelledby="finish-title">
    <h2 id="finish-title">Ringkasan Pengerjaan</h2><p data-exam="finish-summary"></p><p>Soal objektif yang belum dijawab dihitung sebagai tidak benar. Uraian tetap berstatus Belum dinilai.</p><div class="exam-actions"><button type="button" class="exam-button secondary" data-exam="finish-cancel" autofocus>Kembali mengerjakan</button><button type="button" class="exam-button" data-exam="finish-confirm">Kumpulkan Evaluasi</button></div>
</dialog>
@endsection
