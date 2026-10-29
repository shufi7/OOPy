@extends('admin.layouts.app')
@section('title', 'Rekap Nilai')
@section('content')
<div class="admin-heading"><div><h1>Rekap Nilai</h1><p>Nilai terbaik dari percobaan selesai. Tanda — berarti belum ada nilai. Ambang lulus kuis BAB: {{ $reports::score($reports->passingScore()) }}.</p></div><a href="{{ route('admin.scores.export', request()->only('q', 'chapter', 'status', 'sort')) }}" class="btn btn-brand">Export CSV</a></div>
<form method="GET" action="{{ route('admin.scores.index') }}" class="admin-filters">
    <div class="admin-search"><label for="q">Nama atau email</label><input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="150" class="form-control">@error('q')<p class="text-danger">{{ $message }}</p>@enderror</div>
    <div><label for="chapter">BAB untuk filter status</label><select id="chapter" name="chapter" class="form-select"><option value="">Semua BAB</option>@foreach($chapters as $chapter)<option value="{{ $chapter->chapter_order }}" @selected(($filters['chapter'] ?? '') == $chapter->chapter_order)>BAB {{ $chapter->chapter_order }}</option>@endforeach</select></div>
    <div><label for="status">Status kuis</label><select id="status" name="status" class="form-select"><option value="">Semua status</option>@foreach(['attempted' => 'Sudah mengerjakan', 'not_started' => 'Belum mengerjakan', 'passed' => 'Lulus', 'not_passed' => 'Ada nilai belum lulus'] as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div><label for="sort">Urutkan</label><select id="sort" name="sort" class="form-select">@foreach(['name' => 'Nama pengguna', 'latest' => 'Kuis terbaru', 'best' => 'Nilai terbaik tertinggi'] as $value => $label)<option value="{{ $value }}" @selected(($filters['sort'] ?? 'name') === $value)>{{ $label }}</option>@endforeach</select></div>
    <button type="submit" class="btn btn-brand">Terapkan</button><a href="{{ route('admin.scores.index') }}" class="btn btn-outline-secondary">Reset</a>
</form>
<p>{{ $users->total() }} pengguna. Rata-rata dihitung dari nilai terbaik BAB yang tersedia. Total evaluasi tersedia setelah uraian dinilai.</p>
<div class="table-responsive" role="region" aria-label="Rekap nilai BAB dan Evaluasi Akhir" tabindex="0"><table class="table admin-scores-table"><thead><tr><th scope="col">Nama Pengguna</th>@for($number = 1; $number <= 6; $number++)<th scope="col">BAB {{ $number }}</th>@endfor<th scope="col">Evaluasi Akhir</th><th scope="col">Kuis selesai</th><th scope="col">Rata-rata</th><th scope="col">Status kuis</th><th scope="col">Pengerjaan terakhir</th></tr></thead><tbody>
@forelse($users as $user)
@php($row = $reports->row($user))
<tr><td><a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a></td>@foreach($row['scores'] as $score)<td>{{ $reports::score($score) }}</td>@endforeach<td>{{ $reports::score($user->final_score) }}@if($user->final_pending > 0)<br><small>Menunggu uraian: {{ $user->final_pending }}</small>@endif</td><td>{{ $row['completed_quizzes'] }} / 6</td><td>{{ $reports::score($row['average']) }}</td><td>{{ $row['status'] }}</td><td>{{ $reports::date($row['last_at']) }}</td></tr>
@empty<tr><td colspan="12" class="admin-empty">Tidak ada pengguna yang sesuai filter.</td></tr>@endforelse
</tbody></table></div>
{{ $users->links('pagination::bootstrap-5') }}
@endsection
