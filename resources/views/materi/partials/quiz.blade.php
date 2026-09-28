<section class="material-section material-quiz oopy-quiz" id="kuis" aria-labelledby="kuis-title" tabindex="-1" data-material-section data-oopy-quiz>
    <span class="material-eyebrow">CEK PEMAHAMAN</span>
    <h2 id="kuis-title">Kuis {{ $chapter['bab'] }}</h2>
    <p>Pilih jawabanmu, lalu tinjau hasil dan pembahasan setelah menyelesaikan semua soal.</p>
    <script type="application/json" data-quiz="questions">{!! \Illuminate\Support\Js::encode($content['quiz']) !!}</script>

    <button type="button" class="oopy-quiz-instructions-button" data-quiz="instructions-toggle" aria-expanded="false" aria-controls="quiz-instructions" hidden>
        <i class="bi bi-info-circle" aria-hidden="true"></i> Instruksi Pengerjaan
    </button>
    <div class="oopy-quiz-instructions" id="quiz-instructions" hidden>
        <h3>Petunjuk Pengerjaan</h3>
        <ol>
            <li>Bacalah setiap pertanyaan dengan teliti.</li>
            <li>Pilih satu jawaban yang menurutmu paling tepat.</li>
            <li>Gunakan tombol Soal Sebelumnya dan Soal Selanjutnya untuk berpindah soal.</li>
            <li>Kamu dapat mengganti jawaban sebelum menyelesaikan kuis.</li>
            <li>Setelah semua soal dijawab, tekan tombol Selesai Kuis.</li>
        </ol>
        <p>Jawaban dan hasil akan kembali dari awal jika halaman dimuat ulang.</p>
    </div>

    <p data-quiz="loading">Kuis sedang disiapkan. Jika kuis tidak muncul, muat ulang halaman.</p>
    <noscript><p>Aktifkan JavaScript untuk mengerjakan kuis interaktif.</p></noscript>

    <form data-quiz="form" hidden>
        <div class="oopy-quiz-counter">
            <h3 data-quiz="counter" tabindex="-1"></h3>
            <span data-quiz="answered" role="status"></span>
        </div>
        <div class="oopy-quiz-question">
            <p id="quiz-question" data-quiz="question"></p>
            <figure class="material-code" data-quiz="code-card" hidden>
                <figcaption>Contoh Python</figcaption>
                <pre tabindex="0" aria-label="Kode Python pada soal"><code data-quiz="code"></code></pre>
            </figure>
        </div>
        <fieldset class="oopy-quiz-options" aria-describedby="quiz-question">
            <legend>Pilih satu jawaban</legend>
            <div data-quiz="options"></div>
        </fieldset>
        <p class="oopy-quiz-validation" data-quiz="validation" role="alert"></p>
        <div class="oopy-quiz-navigation">
            <button class="btn oopy-quiz-previous" type="button" data-quiz="previous">Soal Sebelumnya</button>
            <button class="btn btn-brand" type="submit" data-quiz="next">Soal Selanjutnya</button>
        </div>
    </form>

    <div data-quiz="results" hidden>
        <h3 data-quiz="result-title" tabindex="-1">Kuis Selesai</h3>
        <div class="oopy-quiz-score">
            <p>Skor kamu</p>
            <strong data-quiz="score"></strong>
            <span data-quiz="percentage"></span>
            <p data-quiz="totals"></p>
        </div>
        <h3>Pembahasan</h3>
        <ol class="oopy-quiz-review" data-quiz="review"></ol>
        <button class="btn btn-brand" type="button" data-quiz="retry">Coba Lagi</button>
    </div>
</section>
