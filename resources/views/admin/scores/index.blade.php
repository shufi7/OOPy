@extends('admin.layouts.app')
@section('title', 'Rekap Nilai')
@section('content')
<div class="admin-scores-page">
    <header class="admin-heading admin-scores-heading">
        <div class="admin-scores-title">
            <span class="admin-scores-title-icon"><x-dashboard-icon name="scores" /></span>
            <div>
                <h1>Rekap Nilai</h1>
                <p>Pantau hasil pembelajaran, evaluasi, dan progres latihan coding pengguna.</p>
            </div>
        </div>
        <a href="{{ route('admin.scores.export', $filters) }}" class="btn btn-outline-secondary">Export CSV</a>
    </header>

    <section class="admin-scores-filter-panel" aria-labelledby="scores-filter-title">
        <h2 id="scores-filter-title">Filter Rekap Nilai</h2>
        <form method="GET" action="{{ route('admin.scores.index') }}" class="admin-filters admin-scores-filters">
            <div class="admin-scores-search-row">
                <div class="admin-search">
                    <label for="q">Nama atau email</label>
                    <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="150" class="form-control" placeholder="Cari nama atau email pengguna..." @error('q') aria-invalid="true" aria-describedby="scores-search-error" @enderror>
                    @error('q')<p id="scores-search-error" class="text-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="sort">Urutkan berdasarkan</label>
                    <select id="sort" name="sort" class="form-select">
                        @foreach(['name' => 'Nama pengguna', 'latest' => 'Kuis terbaru', 'best' => 'Nilai terbaik tertinggi', 'coding_latest' => 'Aktivitas Coding terbaru', 'coding_best' => 'Nilai Coding tertinggi'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['sort'] ?? 'name') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="admin-scores-filter-groups">
                <fieldset>
                    <legend>Filter kuis</legend>
                    <div class="admin-scores-field-pair">
                        <div>
                            <label for="chapter">BAB untuk filter status</label>
                            <select id="chapter" name="chapter" class="form-select">
                                <option value="">Semua BAB</option>
                                @foreach($chapters as $chapter)
                                    <option value="{{ $chapter->chapter_order }}" @selected(($filters['chapter'] ?? '') == $chapter->chapter_order)>BAB {{ $chapter->chapter_order }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="status">Status kuis</label>
                            <select id="status" name="status" class="form-select">
                                <option value="">Semua status</option>
                                @foreach(['attempted' => 'Sudah mengerjakan', 'not_started' => 'Belum mengerjakan', 'passed' => 'Lulus', 'not_passed' => 'Ada nilai belum lulus'] as $value => $label)
                                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </fieldset>
                <fieldset aria-describedby="coding-filter-help">
                    <legend>Filter coding</legend>
                    <div class="admin-scores-field-pair">
                        <div>
                            <label for="coding_exercise">Latihan Coding</label>
                            <select id="coding_exercise" name="coding_exercise" class="form-select" aria-describedby="coding-filter-help">
                                <option value="">Semua latihan</option>
                                @foreach(\App\Services\CodingProgressService::EXERCISES as $number => $exercise)
                                    <option value="{{ $exercise['key'] }}" @selected(($filters['coding_exercise'] ?? '') === $exercise['key'])>Latihan {{ $number }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="coding_status">Status Coding</label>
                            <select id="coding_status" name="coding_status" class="form-select" aria-describedby="coding-filter-help">
                                <option value="">Semua status</option>
                                @foreach(['not_started' => 'Belum Dikerjakan', 'in_progress' => 'Belum Lulus', 'completed' => 'Selesai'] as $value => $label)
                                    <option value="{{ $value }}" @selected(($filters['coding_status'] ?? '') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </fieldset>
            </div>

            <div class="admin-scores-filter-footer">
                <p id="coding-filter-help">Untuk Semua latihan, Selesai berarti kedua latihan selesai. Pilih satu latihan untuk memfilter status atau mengurutkan hasil latihan tersebut.</p>
                <div class="admin-scores-filter-actions">
                    <a href="{{ route('admin.scores.index') }}" class="btn btn-outline-secondary">Reset</a>
                    <button type="submit" class="btn btn-brand">Terapkan Filter</button>
                </div>
            </div>
        </form>
    </section>

    <section class="admin-scores-results" aria-labelledby="scores-results-title">
        <div class="admin-scores-results-heading">
            <div>
                <h2 id="scores-results-title">Hasil Rekap Nilai</h2>
                <p><span class="admin-scores-count">{{ $users->total() }} pengguna</span><span>Tanda &mdash; berarti belum ada nilai.</span></p>
            </div>
            <details class="admin-scores-help">
                <summary>Penjelasan nilai dan status</summary>
                <div>
                    <p>Nilai kuis menggunakan nilai terbaik dari percobaan selesai. Ambang lulus kuis BAB: {{ $reports::score($reports->passingScore()) }}. Rata-rata dihitung dari nilai terbaik BAB yang tersedia.</p>
                    <p>Untuk Semua latihan, Selesai berarti kedua latihan selesai. Belum Lulus berarti sudah mencoba tetapi belum menyelesaikan keduanya. Nilai Coding yang ditampilkan adalah nilai terbaik.</p>
                    <p>Evaluasi Akhir baru dinilai otomatis. Uraian pada riwayat lama tetap memerlukan penilaian Admin.</p>
                </div>
            </details>
        </div>

        <div class="admin-scores-table-panel">
            <div class="table-responsive admin-scores-scroll" role="region" aria-label="Rekap nilai BAB, Evaluasi Akhir, dan Coding" aria-describedby="scores-scroll-help" tabindex="0">
                <table class="table admin-scores-table">
                    <caption class="visually-hidden">Nilai terbaik kuis BAB 1 sampai 6, Evaluasi Akhir, ringkasan pembelajaran, dan progres Coding per pengguna.</caption>
                    <colgroup span="1"></colgroup>
                    <colgroup span="6"></colgroup>
                    <colgroup span="1"></colgroup>
                    <colgroup span="4"></colgroup>
                    <colgroup span="3"></colgroup>
                    <thead>
                        <tr>
                            <th scope="col" rowspan="2" id="scores-user" class="admin-scores-name">Nama Pengguna</th>
                            <th scope="colgroup" colspan="6" id="scores-quiz">Nilai Kuis BAB</th>
                            <th scope="col" rowspan="2" id="scores-final" class="admin-scores-group-start">Evaluasi Akhir</th>
                            <th scope="colgroup" colspan="4" id="scores-summary" class="admin-scores-group-start">Ringkasan Pembelajaran</th>
                            <th scope="colgroup" colspan="3" id="scores-coding" class="admin-scores-group-start">Latihan Coding</th>
                        </tr>
                        <tr>
                            @for($number = 1; $number <= 6; $number++)
                                <th scope="col" id="scores-bab-{{ $number }}" headers="scores-quiz">BAB {{ $number }}</th>
                            @endfor
                            <th scope="col" id="scores-completed" headers="scores-summary" class="admin-scores-group-start">Kuis selesai</th>
                            <th scope="col" id="scores-average" headers="scores-summary">Rata-rata</th>
                            <th scope="col" id="scores-status" headers="scores-summary">Status kuis</th>
                            <th scope="col" id="scores-latest" headers="scores-summary">Pengerjaan terakhir</th>
                            <th scope="col" id="scores-coding-1" headers="scores-coding" class="admin-scores-group-start">Coding Latihan 1</th>
                            <th scope="col" id="scores-coding-2" headers="scores-coding">Coding Latihan 2</th>
                            <th scope="col" id="scores-progress" headers="scores-coding">Progres Coding</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            @php($row = $reports->row($user))
                            <tr data-score-user="{{ $user->id }}">
                                <th scope="row" id="scores-user-{{ $user->id }}" headers="scores-user" class="admin-scores-name"><a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a></th>
                                @foreach($row['scores'] as $number => $score)
                                    <td headers="scores-user-{{ $user->id }} scores-quiz scores-bab-{{ $number }}" class="admin-scores-number">{{ $reports::score($score) }}</td>
                                @endforeach
                                <td headers="scores-user-{{ $user->id }} scores-final" class="admin-scores-number admin-scores-group-start">
                                    <span>{{ $reports::score($user->final_score) }}</span>
                                    @if($user->final_pending > 0)<small class="admin-scores-status is-pending">Menunggu uraian: {{ $user->final_pending }}</small>@endif
                                </td>
                                <td headers="scores-user-{{ $user->id }} scores-summary scores-completed" class="admin-scores-number admin-scores-group-start">{{ $row['completed_quizzes'] }} / 6</td>
                                <td headers="scores-user-{{ $user->id }} scores-summary scores-average" class="admin-scores-number">{{ $reports::score($row['average']) }}</td>
                                <td headers="scores-user-{{ $user->id }} scores-summary scores-status"><span class="admin-scores-status" data-status="{{ $row['status'] }}">{{ $row['status'] }}</span></td>
                                <td headers="scores-user-{{ $user->id }} scores-summary scores-latest" class="admin-scores-date">{{ $reports::date($row['last_at']) }}</td>
                                @foreach($row['coding']['exercises'] as $exercise)
                                    <td headers="scores-user-{{ $user->id }} scores-coding scores-coding-{{ $loop->iteration }}" @class(['admin-scores-group-start' => $loop->first])>
                                        <span class="admin-coding-score">{{ $exercise['best_score_label'] }}</span>
                                        <span class="admin-coding-status is-{{ $exercise['status'] }}">{{ $exercise['status_label'] }}</span>
                                    </td>
                                @endforeach
                                <td headers="scores-user-{{ $user->id }} scores-coding scores-progress">
                                    <div class="admin-scores-progress"><progress value="{{ $row['coding']['progress_percent'] }}" max="100" aria-label="Progres Coding {{ $user->name }}"></progress><span>{{ $row['coding']['progress_percent'] }}%</span></div>
                                    <small>{{ $row['coding']['completed_exercises'] }} dari 2 latihan</small>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="15" class="admin-empty">Tidak ada pengguna yang sesuai filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p id="scores-scroll-help" class="admin-scores-scroll-help">Geser tabel untuk melihat kolom lainnya. Dengan keyboard, fokuskan tabel lalu gunakan tombol panah.</p>
        </div>
        <div class="admin-scores-pagination">{{ $users->links('pagination::bootstrap-5') }}</div>
    </section>
</div>
@endsection
