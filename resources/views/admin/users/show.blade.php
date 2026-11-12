@extends('admin.layouts.app')
@section('title', 'Detail Pengguna')
@section('content')
@php($format = \App\Services\Admin\LearningReportService::class)
<div class="admin-heading"><div><h1>{{ $user->name }}</h1><p>{{ $user->email }} · Terdaftar {{ $format::date($user->created_at) }}</p></div><a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Daftar pengguna</a></div>
<p class="admin-note">{{ $dashboard['completed_chapters'] }} dari {{ $dashboard['total_chapters'] }} BAB selesai · {{ $dashboard['completed_attempts'] }} percobaan kuis selesai · Rata-rata nilai terbaik {{ $dashboard['average_score_label'] }}.</p>
<h2 class="mb-3">Progres setiap BAB</h2>
<div class="table-responsive" role="region" aria-label="Progres BAB pengguna" tabindex="0"><table class="table"><thead><tr><th scope="col">BAB</th><th scope="col">Materi</th><th scope="col">Progres</th><th scope="col">Nilai terbaik</th><th scope="col">Kelulusan kuis</th></tr></thead><tbody>
@foreach($dashboard['chapters'] as $chapter)<tr><th scope="row">{{ $chapter['bab'] }}</th><td>{{ $chapter['title'] }}</td><td>{{ $chapter['status_label'] }}</td><td>{{ $chapter['best_score_label'] }}</td><td>{{ $chapter['best_score'] === null ? 'Belum mengerjakan' : ($chapter['best_score'] >= \App\Services\ChapterQuizScoring::passingScore() ? 'Lulus' : 'Belum lulus') }}</td></tr>@endforeach
</tbody></table></div>
<section aria-labelledby="user-coding-title">
    <div class="admin-heading"><div><h2 id="user-coding-title">Progres Latihan Coding</h2><p>{{ $dashboard['coding']['completed_exercises'] }} dari {{ $dashboard['coding']['total_exercises'] }} latihan selesai · {{ $dashboard['coding']['progress_percent'] }}%</p></div></div>
    <div class="admin-coding-progress" role="progressbar" aria-labelledby="user-coding-title" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $dashboard['coding']['progress_percent'] }}" aria-valuetext="{{ $dashboard['coding']['completed_exercises'] }} dari {{ $dashboard['coding']['total_exercises'] }} latihan selesai"><span style="width: {{ $dashboard['coding']['progress_percent'] }}%"></span></div>
    <div class="table-responsive" role="region" aria-label="Progres Latihan Coding pengguna" tabindex="0"><table class="table admin-coding-history"><thead><tr><th scope="col">Latihan</th><th scope="col">Nilai terbaik</th><th scope="col">Status</th><th scope="col">Total percobaan</th><th scope="col">Terakhir dikerjakan</th></tr></thead><tbody>
    @foreach($dashboard['coding']['exercises'] as $exercise)
        <tr data-coding-exercise="{{ $exercise['key'] }}"><th scope="row">{{ $exercise['title'] }}</th><td>{{ $exercise['best_score_label'] }}</td><td><span class="admin-coding-status is-{{ $exercise['status'] }}">{{ $exercise['status'] === 'not_started' ? 'Belum Dikerjakan' : $exercise['status_label'] }}</span></td><td>{{ $exercise['attempts_count'] }}</td><td>{{ $format::date($exercise['last_at']) }}</td></tr>
    @endforeach
    </tbody></table></div>
</section>
<section aria-labelledby="user-coding-history-title">
    <h2 id="user-coding-history-title" class="mb-3">Riwayat Pengerjaan Coding</h2>
    <p>Status pada riwayat adalah hasil setiap submission, terpisah dari status penyelesaian latihan di atas.</p>
    <div class="table-responsive" role="region" aria-label="Riwayat Pengerjaan Coding" tabindex="0"><table class="table admin-coding-history"><thead><tr><th scope="col">ID percobaan</th><th scope="col">Latihan</th><th scope="col">Skor</th><th scope="col">Status submission</th><th scope="col">Waktu submit</th></tr></thead><tbody>
    @forelse($codingHistory as $submission)
        @php($activity = $coding->activity($submission))
        <tr data-coding-submission="{{ $activity['id'] }}"><td>#{{ $activity['id'] }}</td><td>{{ $activity['title'] }}</td><td>{{ $activity['score'] }}</td><td><span class="admin-coding-status is-{{ $activity['status'] }}">{{ $activity['status'] }}</span></td><td><time datetime="{{ $activity['datetime'] }}">{{ $activity['date_label'] }}</time></td></tr>
    @empty<tr><td colspan="5" class="admin-empty">Pengguna ini belum mengerjakan latihan Coding.</td></tr>@endforelse
    </tbody></table></div>
    {{ $codingHistory->links('pagination::bootstrap-5') }}
</section>
<h2 class="mb-3">Riwayat percobaan kuis</h2>
<div class="table-responsive" role="region" aria-label="Riwayat nilai pengguna" tabindex="0"><table class="table"><thead><tr><th scope="col">Kuis</th><th scope="col">Nilai</th><th scope="col">Status</th><th scope="col">Tanggal</th></tr></thead><tbody>
@forelse($history as $attempt)<tr><td>{{ $attempt->quiz->title }}</td><td>{{ $format::score($attempt->score) }}</td><td>{{ $attempt->score >= $attempt->quiz->passing_score ? 'Lulus' : 'Belum lulus' }}</td><td>{{ $format::date($attempt->completed_at) }}</td></tr>
@empty<tr><td colspan="4" class="admin-empty">Belum ada kuis yang selesai.</td></tr>@endforelse
</tbody></table></div>{{ $history->links('pagination::bootstrap-5') }}
<h2 class="mb-3">Hasil Evaluasi Akhir</h2>
<div class="table-responsive" role="region" aria-label="Hasil evaluasi pengguna" tabindex="0"><table class="table"><thead><tr><th scope="col">Tanggal</th><th scope="col">Poin objektif</th><th scope="col">Poin uraian (riwayat lama)</th><th scope="col">Total / 100</th><th scope="col">Status</th><th scope="col">Jawaban</th></tr></thead><tbody>
@forelse($finals as $attempt)<tr><td>{{ $format::date($attempt->submitted_at) }}</td><td>{{ $format::score($attempt->objective_points) }}</td><td>{{ $format::score($attempt->essay_points) }}</td><td>{{ $format::score($attempt->total_score) }}</td><td>{{ $attempt->status === 'pending_review' ? 'Menunggu penilaian uraian' : ($attempt->total_score >= $attempt->passing_score ? 'Lulus' : 'Belum lulus') }}</td><td><a href="{{ route('admin.evaluations.show', $attempt) }}">Periksa</a></td></tr>
@empty<tr><td colspan="6" class="admin-empty">Belum ada hasil evaluasi tersimpan.</td></tr>@endforelse
</tbody></table></div>{{ $finals->links('pagination::bootstrap-5') }}
@endsection
