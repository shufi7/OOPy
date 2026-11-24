@extends('certification.layout')
@section('title', 'Verifikasi Sertifikat')

@section('certification-content')
<p class="certification-mode">{{ $sandboxMode ? 'Mode Sandbox · Pembayaran pengujian' : 'Mode Simulasi · Dokumen pengujian' }}</p>
<header class="certification-header"><h1>Verifikasi Sertifikat</h1><p>Periksa penerbitan dan status sertifikat penyelesaian OOPy.</p></header>
<section class="certification-verification">
    @if ($certificate)
        <h2>{{ $sandboxMode ? 'Sertifikat Sandbox OOPy' : 'Sertifikat Simulasi OOPy' }}</h2>
        <p class="{{ $certificate->isDownloadable() ? 'certification-success' : 'certification-error' }}" role="status">{{ $certificate->status === 'revoked' ? 'Dibatalkan. Sertifikat ini tidak berlaku.' : ($certificate->isDownloadable() ? ($sandboxMode ? 'Valid sebagai sertifikat Sandbox penyelesaian OOPy.' : 'Valid sebagai sertifikat simulasi penyelesaian OOPy.') : 'Tidak aktif. Sertifikat ini belum dapat divalidasi.') }}</p>
        <dl class="certification-metadata">
            <div><dt>Nama peserta</dt><dd>{{ $certificate->currentParticipantName() }}</dd></div>
            <div><dt>Program</dt><dd>{{ $certificate->course_title }}</dd></div>
            <div><dt>Nomor sertifikat</dt><dd>{{ $certificate->certificate_number }}</dd></div>
            <div><dt>Tanggal penerbitan</dt><dd>{{ $certificate->issued_at->timezone('Asia/Makassar')->locale('id')->translatedFormat('j F Y') }}</dd></div>
            <div><dt>Penerbit</dt><dd>OOPy Learning Platform</dd></div>
        </dl>
        @if ($certificate->currentNameRevision())
            <p>Nama pada sertifikat ini telah dikoreksi. Cocokkan dengan nama pada PDF terbaru.</p>
        @endif
        <p>Dokumen ini berlabel {{ $certificate->demoLabel() }}. Bukan bukti pembayaran uang nyata, sertifikasi profesi, atau akreditasi.</p>
    @else
        <h2>Sertifikat tidak ditemukan</h2><p>Periksa kembali tautan atau pindai ulang QR Code pada sertifikat.</p>
    @endif
    <a href="{{ route('certification.index') }}">Kembali ke Sertifikasi</a>
</section>
@endsection
