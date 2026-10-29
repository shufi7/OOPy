@extends('admin.layouts.app')
@section('title', 'Manajemen Pengguna')
@section('content')
<div class="admin-heading"><div><h1>Manajemen Pengguna</h1><p>Progres BAB 1–6 dan riwayat kuis dari akun pembelajar.</p></div></div>
<form action="{{ route('admin.users.index') }}" method="GET" class="admin-filters"><div class="admin-search"><label for="q">Cari nama atau email</label><input id="q" name="q" type="search" value="{{ request('q') }}" maxlength="150" class="form-control">@error('q')<p class="text-danger">{{ $message }}</p>@enderror</div><button class="btn btn-brand" type="submit">Cari pengguna</button><a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Reset</a></form>
<p>{{ $users->total() }} pengguna ditemukan.</p>
<div class="table-responsive" role="region" aria-label="Daftar pengguna" tabindex="0"><table class="table admin-users-table"><thead><tr><th scope="col">Nama dan email</th><th scope="col">Registrasi</th><th scope="col">BAB selesai</th><th scope="col">Percobaan selesai</th><th scope="col">Nilai terbaik</th><th scope="col">Status belajar</th></tr></thead><tbody>
@forelse($users as $user)<tr><td><a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a><br><span>{{ $user->email }}</span></td><td>{{ $reports::date($user->created_at) }}</td><td>{{ $user->completed_chapters }} / 6</td><td>{{ $user->completed_attempts }}</td><td>{{ $reports::score($user->best_score) }}</td><td>{{ $user->completed_chapters >= 6 ? 'Pembelajaran selesai' : ($user->completed_chapters || $user->completed_attempts ? 'Dalam proses' : 'Belum selesai') }}</td></tr>
@empty<tr><td colspan="6" class="admin-empty">Tidak ada pengguna yang sesuai pencarian.</td></tr>@endforelse
</tbody></table></div>
{{ $users->links('pagination::bootstrap-5') }}
@endsection
