<dl class="certification-grade-summary">
    <div><dt>BAB diselesaikan</dt><dd>{{ implode(' / ', $transcript['summary']['chapters']) }}</dd></div>
    <div><dt>Kuis lulus</dt><dd>{{ implode(' / ', $transcript['summary']['quizzes']) }}</dd></div>
    <div><dt>Latihan coding lulus</dt><dd>{{ implode(' / ', $transcript['summary']['coding']) }}</dd></div>
    <div><dt>Nilai Evaluasi Akhir</dt><dd>{{ \App\Services\CertificateTranscriptService::score($transcript['summary']['final_score']) }}</dd></div>
    <div class="certification-grade-outcome"><dt>{{ $archivedCaptureDate ? 'Status saat pencatatan' : 'Status kelulusan' }}</dt><dd>{{ $transcript['summary']['graduation'] }}</dd></div>
</dl>
