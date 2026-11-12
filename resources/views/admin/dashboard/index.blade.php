@extends('admin.layouts.app')
@section('title', 'Dashboard')
@section('content')
@php
    $format = \App\Services\Admin\LearningReportService::class;
    $quizMax = max(1, (int) (collect($stats['quiz_by_chapter'])->max('attempts') ?? 0));
    $activityMax = max(1, (int) (collect($stats['activity_by_day'])->max('count') ?? 0));
@endphp
<div class="admin-dashboard">
    <header class="admin-heading admin-dashboard-header">
        <div>
            <p class="admin-dashboard-eyebrow">Admin OOPy</p>
            <h1>Dashboard</h1>
            <p>Ringkasan aktivitas belajar dan hal yang perlu ditindaklanjuti.</p>
        </div>
        <a href="{{ route('admin.scores.index') }}" class="btn btn-brand">Buka Rekap Nilai</a>
    </header>

    <section class="admin-dashboard-section" aria-labelledby="admin-main-stats-title">
        <h2 id="admin-main-stats-title">Ringkasan utama</h2>
        <dl class="admin-dashboard-stats">
            <div class="admin-dashboard-stat">
                <dt><span class="admin-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM20 8v6m3-3h-6"/></svg></span>Pengguna terdaftar</dt>
                <dd>{{ number_format($stats['users'], 0, ',', '.') }}</dd>
            </div>
            <div class="admin-dashboard-stat">
                <dt><span class="admin-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h4l3-8 4 16 3-8h4"/></svg></span>Pengguna aktif belajar</dt>
                <dd>{{ number_format($stats['active_users'], 0, ',', '.') }}</dd>
            </div>
            <div class="admin-dashboard-stat">
                <dt><span class="admin-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h8M8 11h8M8 15h5"/></svg></span>Percobaan kuis selesai</dt>
                <dd>{{ number_format($stats['attempts'], 0, ',', '.') }}</dd>
            </div>
            <div class="admin-dashboard-stat">
                <dt><span class="admin-stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9m-5 8V5m-5 12v-4"/></svg></span>Rata-rata nilai kuis</dt>
                <dd>{{ $format::score($stats['average']) }}</dd>
            </div>
        </dl>
    </section>

    <section class="admin-dashboard-section" aria-labelledby="admin-support-stats-title">
        <h2 id="admin-support-stats-title">Statistik pendukung</h2>
        <dl class="admin-support-stats">
            <div><dt>Pernah memulai kuis</dt><dd>{{ number_format($stats['quiz_users'], 0, ',', '.') }}</dd></div>
            <div><dt>BAB tersedia</dt><dd>{{ number_format($stats['chapters'], 0, ',', '.') }}</dd></div>
            <div><dt>Soal dipublikasikan</dt><dd>{{ number_format($stats['questions'], 0, ',', '.') }}</dd></div>
        </dl>
    </section>

    <section class="admin-dashboard-section" aria-labelledby="admin-learning-activity-title">
        <h2 id="admin-learning-activity-title">Aktivitas pembelajaran</h2>
        <div class="admin-chart-grid">
            <article class="admin-chart-panel" aria-labelledby="admin-chapter-chart-title">
                <h3 id="admin-chapter-chart-title">Percobaan kuis selesai per BAB</h3>
                <p>Jumlah percobaan yang selesai dan memiliki nilai valid.</p>
                @if(collect($stats['quiz_by_chapter'])->sum('attempts') > 0)
                    <ol class="admin-bar-chart" aria-label="Percobaan kuis selesai per BAB">
                        @foreach($stats['quiz_by_chapter'] as $chapter)
                            <li>
                                <span class="admin-bar-label"><span>BAB {{ $chapter['number'] }}</span><span class="admin-bar-title">{{ $chapter['title'] }}</span></span>
                                <span class="admin-bar-track" aria-hidden="true"><span style="width: {{ number_format(($chapter['attempts'] / $quizMax) * 100, 1, '.', '') }}%"></span></span>
                                <span class="admin-bar-value">{{ number_format($chapter['attempts'], 0, ',', '.') }}</span>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <div class="admin-chart-empty">
                        <span class="admin-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V5m0 14h16M8 15v-3m4 3V8m4 7v-6"/></svg></span>
                        <p>Belum ada percobaan kuis selesai untuk ditampilkan.</p>
                    </div>
                @endif
            </article>

            <article class="admin-chart-panel" aria-labelledby="admin-time-chart-title">
                <h3 id="admin-time-chart-title">Aktivitas belajar · 7 hari terakhir</h3>
                <p>Jumlah kuis selesai, submission coding, dan evaluasi yang dikumpulkan per hari.</p>
                @if($stats['has_activity_history'])
                    <ol class="admin-time-chart" aria-label="Aktivitas belajar selama tujuh hari terakhir">
                        @foreach($stats['activity_by_day'] as $day)
                            <li>
                                <span class="admin-time-label">{{ $day['label'] }}</span>
                                <span class="admin-bar-track" aria-hidden="true"><span style="width: {{ number_format(($day['count'] / $activityMax) * 100, 1, '.', '') }}%"></span></span>
                                <span class="admin-bar-value">{{ number_format($day['count'], 0, ',', '.') }}</span>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <div class="admin-chart-empty">
                        <span class="admin-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h3"/></svg></span>
                        @if(collect($stats['activity_by_day'])->sum('count') > 0)
                            <p>Aktivitas tercatat pada satu hari saja. Tren akan terlihat setelah ada aktivitas di hari lain.</p>
                        @else
                            <p>Belum ada aktivitas belajar dalam tujuh hari terakhir.</p>
                        @endif
                    </div>
                @endif
            </article>
        </div>
    </section>

    <section class="admin-dashboard-section" aria-labelledby="admin-coding-title">
        <h2 id="admin-coding-title">Ringkasan Latihan Coding</h2>
        <dl class="admin-coding-stats" data-admin-coding-stats>
            @foreach(['attempted_users' => 'Pernah mencoba Coding', 'exercise_1_completed' => 'Selesai Latihan 1', 'exercise_2_completed' => 'Selesai Latihan 2', 'both_completed' => 'Selesai kedua latihan'] as $key => $label)
                <div><dt>{{ $label }}</dt><dd data-coding-stat="{{ $key }}">{{ number_format($stats['coding'][$key], 0, ',', '.') }}</dd></div>
            @endforeach
        </dl>
        <p class="admin-coding-note">
            @if($stats['coding']['attempted_users'] === 0)
                Belum ada pengguna yang mencoba latihan Coding.
            @elseif($stats['coding']['none_passed'] > 0)
                {{ number_format($stats['coding']['none_passed'], 0, ',', '.') }} pengguna sudah mencoba, tetapi belum lulus latihan mana pun.
            @else
                Semua pengguna yang mencoba sudah lulus setidaknya satu latihan.
            @endif
        </p>
    </section>

    <section class="admin-dashboard-section" aria-labelledby="admin-coding-activity-title">
        <div class="admin-section-heading">
            <div><h2 id="admin-coding-activity-title">Aktivitas Coding Terbaru</h2><p>Status menunjukkan hasil setiap submission. Latihan tetap dihitung selesai jika pengguna pernah lulus sebelumnya.</p></div>
        </div>
        @if($stats['recent_coding']->isNotEmpty())
            <div class="table-responsive admin-dashboard-table" role="region" aria-label="Aktivitas Coding Terbaru" tabindex="0">
                <table class="table admin-coding-history">
                    <thead><tr><th scope="col">Pengguna</th><th scope="col">Latihan</th><th scope="col">Skor</th><th scope="col">Status submission</th><th scope="col">Dikerjakan</th></tr></thead>
                    <tbody>
                        @foreach($stats['recent_coding'] as $activity)
                            <tr data-coding-submission="{{ $activity['id'] }}">
                                <td><a href="{{ $activity['user_url'] }}">{{ $activity['user_name'] }}</a></td>
                                <td>{{ $activity['title'] }}</td>
                                <td><span class="admin-score-value">{{ $activity['score'] }}</span></td>
                                <td><span class="admin-status-badge is-{{ $activity['status'] }}">{{ $activity['status_label'] }}</span></td>
                                <td><time datetime="{{ $activity['datetime'] }}">{{ $activity['date_label'] }}</time></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="admin-empty-state">
                <span class="admin-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m8 8-4 4 4 4m8-8 4 4-4 4m-3-10-2 12"/></svg></span>
                <h3>Belum ada aktivitas Coding</h3>
                <p>Submission latihan Coding pengguna akan muncul di sini.</p>
            </div>
        @endif
    </section>

    <section class="admin-dashboard-section" aria-labelledby="admin-quiz-activity-title">
        <div class="admin-section-heading">
            <div><h2 id="admin-quiz-activity-title">Aktivitas Kuis Terbaru</h2><p>Maksimal 10 percobaan selesai terbaru.</p></div>
        </div>
        @if($stats['recent']->isNotEmpty())
            <div class="table-responsive admin-dashboard-table" role="region" aria-label="Aktivitas kuis terbaru" tabindex="0">
                <table class="table">
                    <thead><tr><th scope="col">Pengguna</th><th scope="col">Kuis / BAB</th><th scope="col">Nilai</th><th scope="col">Status</th><th scope="col">Dikerjakan</th></tr></thead>
                    <tbody>
                        @foreach($stats['recent'] as $attempt)
                            @php($passed = $attempt->score >= $attempt->quiz->passing_score)
                            <tr>
                                <td><a href="{{ route('admin.users.show', $attempt->user_id) }}">{{ $attempt->user->name }}</a></td>
                                <td>{{ $attempt->quiz->title }}</td>
                                <td><span class="admin-score-value">{{ $format::score($attempt->score) }}</span></td>
                                <td><span class="admin-status-badge {{ $passed ? 'is-success' : 'is-warning' }}">{{ $passed ? 'Lulus' : 'Belum lulus' }}</span></td>
                                <td><time datetime="{{ $attempt->completed_at->toIso8601String() }}">{{ $format::date($attempt->completed_at) }}</time></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="admin-empty-state">
                <span class="admin-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11h6m-6 4h6m2 6H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z"/><path d="M14 3v5h5"/></svg></span>
                <h3>Belum ada riwayat kuis</h3>
                <p>Percobaan kuis yang selesai akan muncul di sini.</p>
            </div>
        @endif
    </section>

    <section class="admin-dashboard-section" aria-labelledby="admin-final-title">
        <div class="admin-section-heading">
            <div><h2 id="admin-final-title">Evaluasi Akhir</h2><p>Evaluasi yang menunggu penilaian uraian ditampilkan lebih dahulu.</p></div>
        </div>
        <dl class="admin-evaluation-summary" aria-label="Ringkasan Evaluasi Akhir">
            <div><dt>Dikumpulkan</dt><dd>{{ number_format($stats['finals'], 0, ',', '.') }}</dd></div>
            <div><dt>Menunggu penilaian uraian</dt><dd>{{ number_format($stats['pending'], 0, ',', '.') }}</dd></div>
            <div><dt>Rata-rata nilai lengkap</dt><dd>{{ $format::score($stats['final_average']) }}</dd></div>
        </dl>
        @if($stats['recent_finals']->isNotEmpty())
            <div class="table-responsive admin-dashboard-table" role="region" aria-label="Evaluasi Akhir terbaru dan menunggu penilaian" tabindex="0">
                <table class="table">
                    <thead><tr><th scope="col">Pengguna</th><th scope="col">Poin objektif</th><th scope="col">Poin uraian (riwayat lama)</th><th scope="col">Total / 100</th><th scope="col">Status</th><th scope="col">Dikumpulkan</th><th scope="col">Tindakan</th></tr></thead>
                    <tbody>
                        @foreach($stats['recent_finals'] as $attempt)
                            @php($pending = $attempt->status === 'pending_review')
                            <tr>
                                <td>{{ $attempt->user->name }}</td>
                                <td>{{ $format::score($attempt->objective_points) }}</td>
                                <td>{{ $pending ? '—' : $format::score($attempt->essay_points) }}</td>
                                <td><span class="admin-score-value">{{ $pending ? '—' : $format::score($attempt->total_score) }}</span></td>
                                <td>
                                    @if($pending)
                                        <span class="admin-status-badge is-warning">Menunggu penilaian</span>
                                    @else
                                        @php($passed = $attempt->total_score !== null && $attempt->total_score >= $attempt->passing_score)
                                        <span class="admin-status-badge {{ $passed ? 'is-success' : 'is-warning' }}">{{ $passed ? 'Lulus' : 'Belum lulus' }}</span>
                                    @endif
                                </td>
                                <td><time datetime="{{ $attempt->submitted_at->toIso8601String() }}">{{ $format::date($attempt->submitted_at) }}</time></td>
                                <td><a class="admin-table-action" href="{{ route('admin.evaluations.show', $attempt) }}">{{ $pending ? 'Nilai uraian' : 'Lihat penilaian' }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="admin-empty-state">
                <span class="admin-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11h6m-6 4h6m2 6H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z"/><path d="M14 3v5h5"/></svg></span>
                <h3>Belum ada Evaluasi Akhir</h3>
                <p>Pengumpulan evaluasi pengguna akan muncul di sini.</p>
            </div>
        @endif
    </section>
</div>
@endsection
