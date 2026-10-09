@extends('layouts.app')

@section('title', 'Dashboard')
@section('body-class', 'oopy-dashboard-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/oopy/dashboard/dashboard.css') }}">
@endpush

@section('app-navigation')
    @include('dashboard.partials.navigation')
@endsection

@section('content')
<div class="oopy-dashboard">
    <div class="oopy-dashboard-container">
        <section class="oopy-dashboard-progress oopy-dashboard-panel" aria-labelledby="progress-title">
            <div class="oopy-dashboard-section-heading">
                <h1 id="progress-title">Progres Belajarmu</h1>
                <strong class="oopy-dashboard-percentage">{{ $dashboard['progress_percent'] }}%</strong>
            </div>
            <p>{{ $dashboard['completed_chapters'] }} dari {{ $dashboard['total_chapters'] }} BAB inti telah selesai.</p>
            <div class="oopy-dashboard-progress-track" role="progressbar" aria-label="Progres enam BAB inti" aria-valuenow="{{ $dashboard['progress_percent'] }}" aria-valuemin="0" aria-valuemax="100" aria-valuetext="{{ $dashboard['completed_chapters'] }} dari {{ $dashboard['total_chapters'] }} BAB selesai"><span style="width: {{ $dashboard['progress_percent'] }}%"></span></div>
        </section>

        <div class="oopy-dashboard-overview">
            <section class="oopy-dashboard-profile oopy-dashboard-panel" id="profil" aria-labelledby="profile-title">
                <h2 id="profile-title" class="oopy-dashboard-panel-title"><x-dashboard-icon name="user" />Data Profil</h2>
                <div class="oopy-dashboard-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim(auth()->user()->name), 0, 1)) }}</div>
                <p class="oopy-dashboard-profile-name">{{ auth()->user()->name }}</p>
                <p class="oopy-dashboard-profile-caption">Akun pembelajaran OOPy</p>
                <dl class="oopy-dashboard-profile-data">
                    <div><dt>Nama</dt><dd>{{ auth()->user()->name }}</dd></div>
                    <div><dt>Email</dt><dd>{{ auth()->user()->email }}</dd></div>
                    <div><dt>Role Akun</dt><dd>{{ auth()->user()->role === 'admin' ? 'Admin' : 'User' }}</dd></div>
                </dl>
                <div class="oopy-dashboard-profile-next">
                    <p class="oopy-dashboard-next-label">Berikutnya untukmu</p>
                    <p class="oopy-dashboard-next-title">{{ $dashboard['recommendation']['title'] }}</p>
                    <a class="btn oopy-dashboard-primary-action" href="{{ $dashboard['recommendation']['url'] }}" data-dashboard="continue">{{ $dashboard['recommendation']['action'] }} <span aria-hidden="true">→</span></a>
                </div>
            </section>

            <section class="oopy-dashboard-grades oopy-dashboard-panel" aria-labelledby="grades-title">
                <div class="oopy-dashboard-section-heading">
                    <h2 id="grades-title" class="oopy-dashboard-panel-title"><x-dashboard-icon name="scores" />Daftar Nilai</h2>
                    <span class="oopy-dashboard-table-caption">Kuis BAB 1–6</span>
                </div>
                <div class="oopy-dashboard-table-wrap">
                    <table class="oopy-dashboard-grade-table">
                        <caption class="visually-hidden">Nilai kuis terbaik dan progres setiap BAB pada akunmu</caption>
                        <thead><tr><th scope="col">No.</th><th scope="col">BAB</th><th scope="col">Nilai Terbaik</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
                        <tbody>
                            @foreach ($dashboard['chapters'] as $chapter)
                                <tr data-dashboard-chapter="{{ $chapter['slug'] }}">
                                    <td data-label="No.">{{ $loop->iteration }}</td>
                                    <th scope="row"><span class="oopy-dashboard-chapter-number">{{ $chapter['bab'] }}</span><span>{{ $chapter['title'] }}</span></th>
                                    <td data-label="Nilai terbaik" class="oopy-dashboard-grade-value">{{ $chapter['best_score_label'] }}</td>
                                    <td data-label="Status"><span class="oopy-dashboard-status is-{{ $chapter['status'] }}">{{ $chapter['status_label'] }}</span></td>
                                    <td data-label="Aksi"><a class="oopy-dashboard-row-action is-{{ $chapter['status'] }}" href="{{ $chapter['url'] }}">{{ $chapter['action'] }}<span class="visually-hidden"> {{ $chapter['bab'] }}</span><span aria-hidden="true"> →</span></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="oopy-dashboard-table-note">Nilai terbaik dari kuis yang telah kamu selesaikan. Minimal {{ config('quiz.minimum_correct') }} dari {{ config('quiz.total_questions') }} jawaban benar untuk lulus.</p>
                <dl class="oopy-dashboard-stats" aria-label="Ringkasan pembelajaran">
                    <div><dt>BAB Selesai</dt><dd data-dashboard="completed">{{ $dashboard['completed_chapters'] }} <span>/ {{ $dashboard['total_chapters'] }}</span></dd></div>
                    <div><dt>Rata-rata Nilai Kuis</dt><dd data-dashboard="average">{{ $dashboard['average_score_label'] }}</dd>@if ($dashboard['average_score'] === null)<span class="oopy-dashboard-stat-note">Belum ada nilai kuis</span>@endif</div>
                    <div><dt>Total Kuis Dikerjakan</dt><dd data-dashboard="attempts">{{ $dashboard['completed_attempts'] }}</dd></div>
                </dl>
            </section>
        </div>

        <details class="oopy-dashboard-additional" id="informasi">
            <summary><x-dashboard-icon name="info" /><span>Aktivitas &amp; Informasi Belajar</span><span class="oopy-dashboard-disclosure" aria-hidden="true">⌄</span></summary>
            <div class="oopy-dashboard-bottom-grid">
            <section class="oopy-dashboard-activity oopy-dashboard-panel" aria-labelledby="activity-title">
                <h2 id="activity-title">Aktivitas Terbaru</h2>
                @if ($dashboard['recent_activity'])
                    <ol class="oopy-dashboard-activity-list">
                        @foreach ($dashboard['recent_activity'] as $activity)
                            <li><div><a href="{{ $activity['url'] }}">{{ $activity['title'] }}</a><time datetime="{{ $activity['datetime'] }}">{{ $activity['date_label'] }}</time></div><p>Nilai: <strong>{{ $activity['score'] }}</strong><span class="oopy-dashboard-status {{ $activity['passed'] ? 'is-completed' : 'is-in_progress' }}">{{ $activity['passed'] ? 'Lulus' : 'Belum Lulus' }}</span></p></li>
                        @endforeach
                    </ol>
                @else
                    <div class="oopy-dashboard-empty"><p>Belum ada aktivitas belajar yang tersimpan. Mulai BAB pertama untuk membangun progres belajarmu.</p><a href="{{ $dashboard['chapters'][0]['url'] }}">Mulai Belajar <span aria-hidden="true">→</span></a></div>
                @endif
            </section>
            <section class="oopy-dashboard-information oopy-dashboard-panel" aria-labelledby="information-title">
                <h2 id="information-title" class="oopy-dashboard-panel-title"><x-dashboard-icon name="info" />Informasi Belajar</h2>
                <p>Pelajari materi Python OOP secara bertahap, lalu selesaikan kuis setiap BAB untuk membangun progresmu.</p>
                <div class="oopy-dashboard-evaluation"><span class="oopy-dashboard-eyebrow">BAB 7</span><h3>Evaluasi Akhir OOPy</h3><p>Uji pemahamanmu setelah mempelajari BAB 1–6.</p><a class="btn oopy-dashboard-secondary-action" href="{{ route('evaluasi.index') }}">Buka Evaluasi Akhir <span aria-hidden="true">→</span></a></div>
            </section>
            </div>
        </details>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/oopy-dashboard.js') }}" defer></script>
@endpush
