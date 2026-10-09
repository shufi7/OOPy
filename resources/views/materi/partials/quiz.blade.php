<section class="material-section material-quiz oopy-quiz" id="kuis" aria-labelledby="kuis-title" tabindex="-1" data-material-section data-oopy-quiz data-chapter-slug="{{ $chapter['slug'] }}">
    <span class="material-eyebrow">CEK PEMAHAMAN</span>
    <h2 id="kuis-title">Kuis {{ $chapter['bab'] }}</h2>
    <p>Jawab semua soal, lalu lihat nilai, jumlah benar dan salah, serta status kelulusanmu.</p>
    <script type="application/json" data-quiz="questions">{!! \Illuminate\Support\Js::encode($quizQuestions) !!}</script>
    <script type="application/json" data-quiz="config">{!! \Illuminate\Support\Js::encode($quizConfig) !!}</script>

    @guest
    <div class="oopy-quiz-account-notice">
        <p>Masuk untuk mengerjakan kuis yang dinilai dan menyimpan nilai, riwayat, serta kelulusan pada akunmu.</p>
        <a class="btn btn-brand" href="{{ route('login') }}">Masuk untuk Mengerjakan Kuis</a>
    </div>
    @endguest

    <button type="button" class="oopy-quiz-instructions-button" data-quiz="instructions-toggle" aria-expanded="false" aria-controls="quiz-instructions" hidden>
        <i class="bi bi-info-circle" aria-hidden="true"></i> Instruksi Pengerjaan
    </button>
    <div class="oopy-quiz-instructions" id="quiz-instructions" hidden>
        <h3>Petunjuk Pengerjaan</h3>
        <ol>
            <li>Bacalah setiap pertanyaan dengan teliti.</li>
            @if (collect($content['quiz'])->contains(fn ($question) => ($question['type'] ?? 'multiple_choice') === 'code_fill'))
            <li>Pilih satu jawaban untuk soal pilihan ganda; untuk isian kode, ketik jawaban langsung di kotak kosong dalam potongan kode. Huruf besar/kecil mengikuti sintaks Python.</li>
            @else
            <li>Pilih satu jawaban yang menurutmu paling tepat.</li>
            @endif
            <li>Gunakan tombol Soal Sebelumnya dan Soal Selanjutnya untuk berpindah soal.</li>
            <li>Kamu dapat mengganti jawaban sebelum menyelesaikan kuis.</li>
            <li>Setelah semua soal dijawab, tekan tombol Selesai Kuis.</li>
        </ol>
        <p>Jawaban sementara kembali dari awal jika halaman dimuat ulang. Hasil yang sudah dikumpulkan dan kelulusan tersimpan pada akunmu.</p>
    </div>

    <p data-quiz="loading" @guest hidden @endguest>Kuis sedang disiapkan. Jika kuis tidak muncul, muat ulang halaman.</p>
    <noscript><p>Aktifkan JavaScript untuk mengerjakan kuis interaktif.</p></noscript>

    <form data-quiz="form" hidden>
        @csrf
        <div class="oopy-quiz-counter">
            <h3 data-quiz="counter" tabindex="-1"></h3>
            <span data-quiz="answered" role="status"></span>
        </div>
        <div class="oopy-quiz-question">
            <p id="quiz-question" data-quiz="question"></p>
            <figure class="material-code" data-quiz="code-card" hidden>
                <figcaption>Contoh Python</figcaption>
                <pre tabindex="0" aria-label="Kode Python pada soal"><code data-quiz="code"><span class="language-python" data-quiz="code-before"></span><input class="oopy-quiz-code-fill" id="quiz-code-fill" data-quiz="code-fill" type="text" aria-label="Lengkapi bagian kosong pada kode Python" aria-describedby="quiz-question quiz-code-help" autocomplete="off" autocapitalize="off" spellcheck="false" disabled hidden><span class="language-python" data-quiz="code-after"></span></code></pre>
            </figure>
        </div>
        <fieldset class="oopy-quiz-options" aria-describedby="quiz-question" data-quiz="options-group">
            <legend>Pilih satu jawaban</legend>
            <div data-quiz="options"></div>
        </fieldset>
        <div class="oopy-quiz-code-fill-group" data-quiz="code-fill-group" hidden>
            <p id="quiz-code-help">Isi kotak kosong langsung di dalam kode. Huruf besar/kecil dan penulisan jawaban mengikuti sintaks yang diminta.</p>
        </div>
        <p class="oopy-quiz-validation" data-quiz="validation" role="alert"></p>
        <div class="oopy-quiz-navigation">
            <button class="btn oopy-quiz-previous" type="button" data-quiz="previous">Soal Sebelumnya</button>
            <button class="btn btn-brand" type="submit" data-quiz="next">Soal Selanjutnya</button>
        </div>
    </form>

    <div data-quiz="results" hidden>
        <h3 data-quiz="result-title" tabindex="-1">Kuis Selesai</h3>
        <div class="oopy-quiz-score" role="status" aria-live="polite" aria-atomic="true">
            <dl class="oopy-quiz-summary">
                <div class="oopy-quiz-value"><dt>Nilai</dt><dd data-quiz="score"></dd></div>
                <div><dt>Benar</dt><dd data-quiz="correct"></dd></div>
                <div><dt>Salah</dt><dd data-quiz="incorrect"></dd></div>
                <div class="oopy-quiz-status"><dt>Status</dt><dd data-quiz="status"></dd></div>
            </dl>
            <p data-quiz="message"></p>
        </div>
        <div class="oopy-quiz-result-actions">
            <button class="btn oopy-quiz-previous" type="button" data-quiz="retry">Coba Lagi</button>
            @if (!empty($nextChapter))
            <a class="btn btn-brand" href="{{ route('materi.show', $nextChapter['slug']) }}" data-quiz="continue" hidden>Lanjut ke {{ $nextChapter['bab'] }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            @endif
        </div>
    </div>
</section>
