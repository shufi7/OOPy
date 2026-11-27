<div class="certificate-preview" aria-label="{{ $certificate ? 'Pratinjau sertifikat terbit' : 'Pratinjau sebelum penerbitan' }}">
    <div class="certificate-preview-top">
        <span class="certificate-preview-brand"><img src="{{ asset('images/oopy-logo.png') }}" width="36" height="36" alt=""> <strong>OOPy</strong></span>
        @unless ($certificate)
            <strong>PRATINJAU</strong>
        @endunless
    </div>
    <div class="certificate-preview-body">
        <h3 class="certificate-preview-heading" lang="en">Certificate of Completion</h3>
        <p class="certificate-preview-caption">Diberikan kepada</p>
        <p class="certificate-preview-name">{{ $certificate?->currentParticipantName() ?? $identity['name'] ?? '[Nama Peserta]' }}</p>
        <p>Atas penyelesaian program pembelajaran</p>
        <p class="certificate-preview-program">{{ $certificate?->course_title ?? \App\Services\CertificateIssuanceService::COURSE }}</p>
    </div>
    <footer class="certificate-preview-footer">
        <div>
            <p class="certificate-preview-issuer">OOPy Learning Platform</p>
            @if ($certificate)
                <p class="certificate-reference">{{ $certificate->certificate_number }}</p>
                <p>Selesai {{ $certificate->completed_at->timezone('Asia/Makassar')->locale('id')->translatedFormat('j F Y') }}</p>
                <p>Terbit {{ $certificate->issued_at->timezone('Asia/Makassar')->locale('id')->translatedFormat('j F Y') }}</p>
            @else
                <p>Belum diterbitkan</p>
            @endif
        </div>
        @isset($qr)
            <img class="certificate-preview-qr" src="{{ $qr }}" width="92" height="92" alt="QR Code menuju verifikasi sertifikat">
        @endisset
    </footer>
</div>
