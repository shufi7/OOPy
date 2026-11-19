<div class="certificate-preview" aria-label="{{ $certificate ? 'Pratinjau sertifikat terbit' : 'Contoh desain sertifikat' }}">
    <div class="certificate-preview-top"><span><img src="{{ asset('images/oopy-logo.png') }}" width="48" height="48" alt="Logo OOPy"> <strong>OOPy</strong></span><strong>{{ $certificate?->demoLabel() ?? 'SANDBOX / DEMO' }}</strong></div>
    <p class="certificate-preview-heading" lang="en">CERTIFICATE OF COMPLETION</p>
    <p>Sertifikat Penyelesaian Pembelajaran</p>
    <p class="mt-4 mb-1">Diberikan kepada</p>
    <p class="certificate-preview-name">{{ $certificate?->participant_name ?? '[Nama Peserta]' }}</p>
    <p>Atas keberhasilannya menyelesaikan program pembelajaran</p>
    <p class="fw-bold">{{ $certificate?->course_title ?? \App\Services\CertificateIssuanceService::COURSE }}</p>
    @if ($certificate)
        <p class="certificate-preview-number">{{ $certificate->certificate_number }}</p>
        <p>Selesai {{ $certificate->completed_at->timezone('Asia/Makassar')->locale('id')->translatedFormat('j F Y') }} · Terbit {{ $certificate->issued_at->timezone('Asia/Makassar')->locale('id')->translatedFormat('j F Y') }}</p>
        @isset($qr)<img class="certificate-preview-qr" src="{{ $qr }}" width="112" height="112" alt="QR Code menuju verifikasi sertifikat">@endisset
    @else
        <p class="certificate-preview-number">CONTOH DESAIN · BELUM DITERBITKAN</p>
    @endif
    <p class="mb-0">OOPy Learning Platform</p>
</div>
