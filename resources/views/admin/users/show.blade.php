@extends('admin.layouts.app')
@section('title', 'Detail Pengguna')
@section('content')
@php($format = \App\Services\Admin\LearningReportService::class)
<div class="admin-heading"><div><h1>{{ $user->name }}</h1><p>{{ $user->email }} · Terdaftar {{ $format::date($user->created_at) }}</p></div><a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Daftar pengguna</a></div>
<p class="admin-note">{{ $dashboard['completed_chapters'] }} dari {{ $dashboard['total_chapters'] }} BAB selesai · {{ $dashboard['completed_attempts'] }} percobaan kuis selesai · Rata-rata nilai terbaik {{ $dashboard['average_score_label'] }}.</p>
<h2 class="mb-3">Progres setiap BAB</h2>
<div class="table-responsive" role="region" aria-label="Progres BAB pengguna" tabindex="0"><table class="table"><thead><tr><th scope="col">BAB</th><th scope="col">Materi</th><th scope="col">Progres</th><th scope="col">Nilai terbaik</th><th scope="col">Kelulusan kuis</th></tr></thead><tbody>
@foreach($dashboard['chapters'] as $chapter)<tr><th scope="row">{{ $chapter['bab'] }}</th><td>{{ $chapter['title'] }}</td><td>{{ $chapter['status_label'] }}</td><td>{{ $chapter['best_score_label'] }}</td><td>{{ $chapter['best_score'] === null ? 'Belum mengerjakan' : ($chapter['best_score'] >= round(config('quiz.minimum_correct') / config('quiz.total_questions') * 100) ? 'Lulus' : 'Belum lulus') }}</td></tr>@endforeach
</tbody></table></div>
<h2 class="mb-3">Riwayat percobaan kuis</h2>
<div class="table-responsive" role="region" aria-label="Riwayat nilai pengguna" tabindex="0"><table class="table"><thead><tr><th scope="col">Kuis</th><th scope="col">Nilai</th><th scope="col">Status</th><th scope="col">Tanggal</th></tr></thead><tbody>
@forelse($history as $attempt)<tr><td>{{ $attempt->quiz->title }}</td><td>{{ $format::score($attempt->score) }}</td><td>{{ $attempt->score >= $attempt->quiz->passing_score ? 'Lulus' : 'Belum lulus' }}</td><td>{{ $format::date($attempt->completed_at) }}</td></tr>
@empty<tr><td colspan="4" class="admin-empty">Belum ada kuis yang selesai.</td></tr>@endforelse
</tbody></table></div>{{ $history->links('pagination::bootstrap-5') }}
<h2 class="mb-3">Hasil Evaluasi Akhir</h2>
<div class="table-responsive" role="region" aria-label="Hasil evaluasi pengguna" tabindex="0"><table class="table"><thead><tr><th scope="col">Tanggal</th><th scope="col">Objektif / 75</th><th scope="col">Uraian / 25</th><th scope="col">Total / 100</th><th scope="col">Status</th><th scope="col">Jawaban</th></tr></thead><tbody>
@forelse($finals as $attempt)<tr><td>{{ $format::date($attempt->submitted_at) }}</td><td>{{ $format::score($attempt->objective_points) }}</td><td>{{ $format::score($attempt->essay_points) }}</td><td>{{ $format::score($attempt->total_score) }}</td><td>{{ $attempt->status === 'pending_review' ? 'Menunggu penilaian uraian' : ($attempt->total_score >= $attempt->passing_score ? 'Lulus' : 'Belum lulus') }}</td><td><a href="{{ route('admin.evaluations.show', $attempt) }}">Periksa</a></td></tr>
@empty<tr><td colspan="6" class="admin-empty">Belum ada hasil evaluasi tersimpan.</td></tr>@endforelse
</tbody></table></div>{{ $finals->links('pagination::bootstrap-5') }}
@endsection
