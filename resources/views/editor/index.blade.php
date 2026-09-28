@extends('layouts.app')

@section('title', 'Live Coding Component Demo')

@section('content')
<div class="container py-5">
    <header class="mb-4">
        <h1>Live Coding Component Demo</h1>
        <p>Development Playground — tiga latihan independen dengan satu runtime Python bersama.</p>
        <p class="text-muted">Run menjalankan entry file. Submit memeriksa perilaku program. Skor hanya menunjukkan progres latihan coding, bukan progres kursus. Kode belum disimpan setelah halaman ditutup.</p>
    </header>
    @foreach ($exercises as $exercise)
        <x-live-code :config="$exercise" />
    @endforeach
</div>
@endsection
