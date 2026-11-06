@extends('admin.layouts.app')
@section('title', 'Hapus Pengguna')
@section('content')
<div class="admin-heading"><div><h1>Hapus Pengguna</h1><p>Periksa akun yang akan dihapus.</p></div></div>
<section class="admin-panel" aria-labelledby="delete-user-title">
    <h2 id="delete-user-title">{{ $user->name }}</h2>
    <p>{{ $user->email }}</p>
    <p class="alert alert-warning">Akun, progres belajar, jawaban kuis, riwayat Coding, dan Evaluasi Akhir pengguna ini akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.</p>
    <form class="admin-delete-form" method="POST" action="{{ route('admin.users.destroy', $user) }}">
        @csrf
        @method('DELETE')
        <div><label class="form-label" for="email_confirmation">Ketik email pengguna untuk mengonfirmasi</label><input class="form-control" id="email_confirmation" name="email_confirmation" type="email" value="{{ old('email_confirmation') }}" autocomplete="off" required @error('email_confirmation') aria-invalid="true" aria-describedby="email-confirmation-error" @enderror>@error('email_confirmation')<p class="text-danger" id="email-confirmation-error">Email konfirmasi harus sesuai dengan akun yang akan dihapus.</p>@enderror</div>
        <div class="admin-row-actions"><a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">Batal</a><button class="btn admin-danger" type="submit">Hapus pengguna permanen</button></div>
    </form>
</section>
@endsection
