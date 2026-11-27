@php
    $chapters = collect($eligibility['chapters'] ?? []);
    $coding = collect($eligibility['coding'] ?? []);
    $quizThreshold = \App\Services\ChapterQuizScoring::passingScore();
    $groups = $eligibility ? [
        [
            'title' => 'Materi BAB 1–6',
            'note' => 'Menuntaskan enam BAB inti.',
            'value' => $eligibility['completed_chapters'].' / '.$eligibility['total_chapters'],
            'done' => $eligibility['completed_chapters'] === $eligibility['total_chapters'],
            'status' => $chapters->contains(fn ($chapter) => $chapter['status'] !== 'not_started') ? 'Dalam Proses' : 'Belum Dimulai',
        ],
        [
            'title' => 'Kuis setiap BAB',
            'note' => 'Lulus enam kuis dengan nilai minimal '.\App\Services\ChapterQuizScoring::format($quizThreshold).'.',
            'value' => $eligibility['passed_quizzes'].' / '.$eligibility['total_quizzes'],
            'done' => $eligibility['passed_quizzes'] === $eligibility['total_quizzes'],
            'status' => $chapters->contains(fn ($chapter) => $chapter['best_score'] !== null && $chapter['best_score'] < $quizThreshold) ? 'Belum Lulus' : ($chapters->contains(fn ($chapter) => $chapter['status'] !== 'not_started') ? 'Dalam Proses' : 'Belum Dimulai'),
        ],
        [
            'title' => 'Latihan Coding',
            'note' => 'Lulus Latihan 1 dan Latihan 2 multi-file versi aktif.',
            'value' => $eligibility['passed_coding'].' / '.$eligibility['total_coding'],
            'done' => $eligibility['passed_coding'] === $eligibility['total_coding'],
            'status' => $coding->contains('status', 'in_progress') ? 'Belum Lulus' : ($eligibility['passed_coding'] > 0 ? 'Dalam Proses' : 'Belum Dimulai'),
        ],
        [
            'title' => 'Evaluasi Akhir',
            'note' => 'Percobaan resmi selesai dinilai dan mencapai ambang kelulusan.',
            'value' => $eligibility['transcript_final_score'] === null ? 'Belum ada nilai' : \App\Services\ChapterQuizScoring::format($eligibility['transcript_final_score']).' / 100',
            'done' => $eligibility['final_passed'],
            'status' => $eligibility['final_status'],
        ],
    ] : [];
@endphp

<div class="certification-overview">
    <section class="certification-eligibility" aria-labelledby="eligibility-title">
        <div class="certification-section-heading">
            <h2 id="eligibility-title">Persyaratan kelulusan</h2>
            @if ($eligibility)
                <span class="certification-state {{ $eligibility['eligible'] ? 'is-complete' : '' }}">{{ $eligibility['eligible'] ? 'Semua syarat terpenuhi' : 'Belum memenuhi syarat' }}</span>
            @endif
        </div>
        @if ($eligibility)
            <dl class="certification-checklist">
                @foreach ($groups as $group)
                    <div class="{{ $group['done'] ? 'is-complete' : '' }}">
                        <dt>
                            <span>{{ $group['title'] }}</span>
                            @unless ($certificate)<small>{{ $group['note'] }}</small>@endunless
                        </dt>
                        <dd><strong>{{ $group['value'] }}</strong><span>{{ $group['done'] ? ($loop->first ? 'Selesai' : 'Lulus') : $group['status'] }}</span></dd>
                    </div>
                @endforeach
            </dl>
            @if ($eligibility['missing'])
                <details class="certification-missing">
                    <summary>Lihat yang perlu diselesaikan <span>({{ count($eligibility['missing']) }})</span></summary>
                    <ul>
                        @foreach ($eligibility['missing'] as $requirement)
                            <li><a href="{{ $requirement['url'] }}">{{ $requirement['label'] }}</a></li>
                        @endforeach
                    </ul>
                </details>
            @endif
        @else
            <ol class="certification-guest-requirements">
                <li><strong>Materi dan kuis BAB 1–6.</strong> Selesaikan enam BAB dan lulus setiap kuis, minimal {{ \App\Services\ChapterQuizScoring::format($quizThreshold) }}.</li>
                <li><strong>Dua latihan Coding.</strong> Lulus Latihan 1 dan Latihan 2 multi-file versi aktif.</li>
                <li><strong>Evaluasi Akhir resmi.</strong> Minimal {{ config('evaluasi.pass_threshold') }} atau ambang resmi percobaan, dan sudah selesai dinilai.</li>
            </ol>
            <p class="certification-section-note">Masuk untuk melihat kelayakan akun Anda. Latihan Evaluasi Akhir sebagai tamu tidak dihitung.</p>
        @endif
    </section>
    @if ($transcript)
        <section class="certification-results" aria-labelledby="results-title">
            <h2 id="results-title">Ringkasan nilai pembelajaran</h2>
            @if ($archivedCaptureDate)
                <p>Nilai resmi dicatat pada {{ $archivedCaptureDate }} setelah sertifikat diterbitkan.</p>
            @elseif ($transcript['captured_at_checkout'] ?? false)
                <p>Nilai resmi saat pesanan dibuat.</p>
            @elseif ($certificate)
                <p>Nilai yang tercatat saat sertifikat diterbitkan.</p>
            @endif
            @include('certification.partials.transcript-summary')
            <a class="certification-next-step" href="#sertifikat" data-show-transcript>Lihat rincian nilai</a>
        </section>
    @endif
</div>
