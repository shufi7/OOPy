@extends('admin.layouts.app')
@section('title', 'Dashboard')
@section('content')
@php($format = \App\Services\Admin\LearningReportService::class)
<div class="admin-heading"><div><h1>Dashboard</h1><p>Aktivitas dan hasil pembelajaran pengguna OOPy.</p></div><a href="{{ route('admin.scores.index') }}" class="btn btn-brand">Buka rekap nilai</a></div>
<dl class="admin-metrics">
    @foreach(['users' => 'Pengguna terdaftar', 'quiz_users' => 'Pernah memulai kuis', 'active_users' => 'Aktif belajar', 'chapters' => 'BAB tersedia', 'questions' => 'Soal dipublikasikan', 'attempts' => 'Percobaan kuis selesai'] as $key => $label)
        <div><dt>{{ $label }}</dt><dd>{{ number_format($stats[$key], 0, ',', '.') }}</dd></div>
    @endforeach
    <div><dt>Rata-rata percobaan kuis</dt><dd>{{ $format::score($stats['average']) }}</dd></div>
    <div><dt>Evaluasi dikumpulkan</dt><dd>{{ $stats['finals'] }}</dd></div>
</dl>
<h2 class="mb-3">Aktivitas kuis terbaru</h2>
<div class="table-responsive" role="region" aria-label="Aktivitas kuis terbaru" tabindex="0"><table class="table"><thead><tr><th scope="col">Pengguna</th><th scope="col">Kuis</th><th scope="col">Nilai</th><th scope="col">Status</th><th scope="col">Dikerjakan</th></tr></thead><tbody>
@forelse($stats['recent'] as $attempt)<tr><td><a href="{{ route('admin.users.show', $attempt->user_id) }}">{{ $attempt->user->name }}</a></td><td>{{ $attempt->quiz->title }}</td><td>{{ $format::score($attempt->score) }}</td><td>{{ $attempt->score >= $attempt->quiz->passing_score ? 'Lulus' : 'Belum lulus' }}</td><td>{{ $format::date($attempt->completed_at) }}</td></tr>
@empty<tr><td colspan="5" class="admin-empty">Belum ada percobaan kuis yang selesai.</td></tr>@endforelse
</tbody></table></div>
<div class="admin-heading"><div><h2>Evaluasi Akhir</h2><p>{{ $stats['pending'] }} menunggu penilaian uraian. Rata-rata nilai lengkap: {{ $format::score($stats['final_average']) }}.</p></div></div>
<div class="table-responsive" role="region" aria-label="Evaluasi akhir terbaru" tabindex="0"><table class="table"><thead><tr><th scope="col">Pengguna</th><th scope="col">Objektif / 75</th><th scope="col">Uraian / 25</th><th scope="col">Total / 100</th><th scope="col">Status</th><th scope="col">Tindakan</th></tr></thead><tbody>
@forelse($stats['recent_finals'] as $attempt)<tr><td>{{ $attempt->user->name }}</td><td>{{ $format::score($attempt->objective_points) }}</td><td>{{ $format::score($attempt->essay_points) }}</td><td>{{ $format::score($attempt->total_score) }}</td><td>{{ $attempt->status === 'pending_review' ? 'Menunggu penilaian' : ($attempt->total_score >= $attempt->passing_score ? 'Lulus' : 'Belum lulus') }}</td><td><a href="{{ route('admin.evaluations.show', $attempt) }}">Periksa jawaban</a></td></tr>
@empty<tr><td colspan="6" class="admin-empty">Belum ada hasil evaluasi tersimpan di server.</td></tr>@endforelse
</tbody></table></div>
@endsection
