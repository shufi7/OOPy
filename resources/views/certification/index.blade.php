@extends('certification.layout')
@section('title', 'Sertifikasi OOPy')

@section('certification-content')
@php
    $ready = $certificate?->isDownloadable() ?? false;
    $canIssue = $eligibility && ($eligibility['eligible'] || $identity['locked']) && ! $certificate;
    $archivedCaptureDate = \App\Services\CertificateTranscriptService::archivedCaptureDate($transcript);
@endphp
<header class="certification-header">
    <a class="certification-back" href="{{ auth()->check() ? route('dashboard') : route('home') }}">{{ auth()->check() ? 'Kembali ke Dashboard' : 'Kembali ke Beranda' }}</a>
    <h1>Sertifikasi OOPy</h1>
    <p>Sertifikat penyelesaian untuk pembelajaran <strong>Pemrograman Berorientasi Objek dengan Python.</strong></p>
    @unless ($certificate)
        <p class="certification-header-note">Periksa hasil belajar dan konfirmasi nama Anda sebelum melanjutkan ke pembayaran Sandbox.</p>
    @endunless
</header>

@if ($certificate)
    <section id="penerbitan" class="certification-issuance certification-issued" aria-labelledby="issuance-title">
        <div class="certification-issued-heading">
            <div>
                <h2 id="issuance-title">{{ $ready ? 'Siap diunduh' : 'Sertifikat tidak aktif' }}</h2>
                @unless ($ready)
                    <p class="certification-state is-unavailable">{{ $certificate->status === 'revoked' ? 'Dibatalkan' : 'Tidak aktif' }}</p>
                @endunless
            </div>
            @if ($ready)
                <a class="certification-press" href="{{ route('certificates.download', $certificate->public_id) }}" download>Unduh Sertifikat PDF</a>
            @endif
        </div>
        <dl class="certification-receipt">
            <div class="certification-receipt-wide"><dt>Nama peserta</dt><dd>{{ $certificate->currentParticipantName() }}</dd></div>
            <div class="certification-receipt-wide"><dt>Nomor sertifikat</dt><dd class="certificate-reference">{{ $certificate->certificate_number }}</dd></div>
            <div><dt>Pembelajaran selesai</dt><dd>{{ $certificate->completed_at->timezone('Asia/Makassar')->locale('id')->translatedFormat('j F Y') }}</dd></div>
            <div><dt>Diterbitkan</dt><dd>{{ $certificate->issued_at->timezone('Asia/Makassar')->locale('id')->translatedFormat('j F Y') }}</dd></div>
            <div class="certification-receipt-wide"><dt>Transaksi</dt><dd>{{ $payment['label'] ?? 'Status belum tersedia' }}</dd></div>
        </dl>
        @unless ($ready)
            <p class="certification-action-note">Hubungi pengelola OOPy untuk informasi status sertifikat ini.</p>
        @endunless
        <div class="certification-issued-footer">
            <p class="certification-issuer">Diterbitkan oleh <strong>OOPy Learning Platform</strong></p>
            @if ($paymentAvailable && $payment)
                <div data-midtrans-payment data-status-url="{{ $payment['status_url'] }}" data-current-ready="{{ $ready ? '1' : '0' }}">
                    <button type="button" class="certification-secondary" data-payment-check>Cek Status Pembayaran</button>
                    <p class="certification-action-note" data-payment-feedback role="status"></p>
                </div>
            @endif
        </div>
        @if ($ready)
            @include('certification.partials.issued-name')
        @endif
    </section>
@else
    @include('certification.partials.academic')
    @if ($identity)
        @include('certification.partials.identity')
    @endif
@endif

<div class="certification-workspace {{ $certificate ? 'certification-workspace-issued' : 'certification-workspace-checkout' }}">
        <section id="sertifikat" class="certification-document" aria-labelledby="document-title">
            <div class="certification-section-heading">
                <h2 id="document-title">{{ $certificate ? 'Sertifikat dan transkrip Anda' : 'Pratinjau sertifikat' }}</h2>
            </div>
            @if ($certificate && ! $ready)
                <p class="certification-section-note">{{ $certificate->status === 'revoked' ? 'Dibatalkan. Sertifikat ini tidak dapat diunduh.' : 'Sertifikat ini tidak aktif dan belum dapat diunduh.' }}</p>
            @elseif (! $certificate)
                <p class="certification-section-note">Nomor sertifikat, tanggal terbit, dan QR verifikasi tersedia setelah pembayaran Sandbox terverifikasi.</p>
            @endif
            <div class="certification-tabs" data-preview-tabs>
                <div class="certification-tab-links" aria-label="Pratinjau dokumen">
                    <a id="certificate-tab" href="#certificate-panel">Sertifikat</a>
                    <a id="transcript-tab" href="#transcript-panel">Transkrip Nilai</a>
                </div>
                <div id="certificate-panel">@include('certification.partials.preview')</div>
                <div id="transcript-panel" class="certificate-transcript">
                    @unless ($certificate)
                        <p class="certification-section-label">PRATINJAU</p>
                    @endunless
                    <h3>Transkrip Nilai</h3>
                    <p lang="en">Learning Achievement Transcript</p>
                    @if ($transcript)
                        <p class="certificate-transcript-name">{{ $certificate?->currentParticipantName() ?? $identity['name'] }}</p>
                        @if ($certificate)
                            <p>{{ $certificate->certificate_number }} · {{ $certificate->issued_at->copy()->timezone('Asia/Makassar')->locale('id')->translatedFormat('j F Y') }}</p>
                        @endif
                        <p>{{ $certificate?->course_title ?? \App\Services\CertificateIssuanceService::COURSE }}</p>
                        @if ($archivedCaptureDate)
                            <p>Nilai dicatat pada {{ $archivedCaptureDate }} setelah sertifikat diterbitkan.</p>
                        @endif
                        @include('certification.partials.transcript-table')
                        <p class="certification-action-note">Skala {{ $transcript['scale']['min'] }}–{{ $transcript['scale']['max'] }}. Tanda – menunjukkan nilai belum tersedia.</p>
                        <ul class="certificate-transcript-policy">@foreach ($transcript['policy'] as $rule)<li>{{ $rule }}</li>@endforeach</ul>
                    @elseif ($certificate)
                        <p class="certification-action-note">Transkrip belum disimpan saat sertifikat ini diterbitkan.</p>
                        @if ($canCaptureTranscript)
                            <p>Gunakan nilai resmi yang tersedia saat ini untuk melengkapi PDF Anda.</p>
                            <form method="post" action="{{ route('certification.transcript.capture', $certificate->public_id) }}" data-identity-form>
                                @csrf
                                <button type="submit" class="certification-press" data-busy-label="Mencatat nilai…">Lengkapi Transkrip Nilai</button>
                            </form>
                        @endif
                    @else
                        <p class="certification-action-note">Masuk untuk melihat nilai resmi akun Anda.</p>
                    @endif
                </div>
            </div>
            @if ($certificate)
                <div class="certification-document-actions">
                    <a href="{{ route('certificates.verify', $certificate->public_id) }}">Verifikasi sertifikat</a>
                    <button type="button" data-copy-verification="{{ route('certificates.verify', $certificate->public_id) }}">Salin tautan verifikasi</button>
                </div>
                <p role="status" data-copy-status class="certification-copy-status"></p>
            @endif
            @if ($legacyCertificate)
                <details class="certification-missing">
                    <summary>Sertifikat simulasi lama{{ $legacyCertificate->status === 'revoked' ? ' · Dibatalkan' : '' }}</summary>
                    <p>{{ $legacyCertificate->participant_name }} · {{ $legacyCertificate->certificate_number }}</p>
                    <img class="certificate-preview-qr" src="{{ $legacyQr }}" width="92" height="92" alt="QR Code menuju verifikasi sertifikat simulasi lama">
                    <a class="certification-next-step" href="{{ route('certificates.verify', $legacyCertificate->public_id) }}">Verifikasi sertifikat simulasi lama</a>
                    @if ($legacyCertificate->isDownloadable())
                        <a class="certification-next-step" href="{{ route('certificates.download', $legacyCertificate->public_id) }}" download>Unduh sertifikat simulasi lama</a>
                    @endif
                </details>
            @endif
        </section>

    @if ($certificate)
        @include('certification.partials.academic')
    @else
        <aside id="penerbitan" class="certification-issuance" aria-labelledby="issuance-title">
            <h2 id="issuance-title">Pembayaran Sandbox</h2>
            <p class="certification-price">Rp24.999 <span>/ sertifikat</span></p>
            <ul class="certification-benefits"><li>Sertifikat dan transkrip dalam satu PDF A4 landscape</li><li>Nomor unik dan QR verifikasi</li><li>Unduh ulang dari halaman ini</li></ul>
            @if ($canIssue && $paymentAvailable)
                @include('certification.partials.payment-form')
            @elseif ($canIssue)
                <p class="certification-error" role="status">Pembayaran Sandbox belum tersedia. Hubungi pengelola OOPy untuk menyiapkan layanan.</p>
            @elseif ($eligibility)
                <button class="certification-press" type="button" disabled aria-describedby="checkout-reason">Ambil Sertifikat</button>
                <p id="checkout-reason" class="certification-action-note">Penerbitan terbuka setelah seluruh persyaratan akademik terpenuhi.</p>
                @if ($eligibility['missing'])<a class="certification-next-step" href="{{ $eligibility['missing'][0]['url'] }}">Lanjutkan pembelajaran</a>@endif
            @else
                <a class="certification-press" href="{{ route('login') }}">Masuk untuk Ambil Sertifikat</a>
            @endif
            <p class="certification-issuer">Diterbitkan oleh <strong>OOPy Learning Platform</strong></p>
        </aside>
    @endif
</div>

<section id="panduan" class="certification-guide" aria-labelledby="guide-title">
    <h2 id="guide-title">Tentang sertifikat ini</h2>
    <details><summary>Belajar tetap gratis, sertifikat bersifat opsional</summary><p>Materi, kuis, Coding, dan Evaluasi Akhir dapat digunakan gratis. Penerbitan menggunakan Midtrans Sandbox dan tidak memproses uang sungguhan. Jangan gunakan aplikasi bank sungguhan untuk QRIS Sandbox.</p></details>
    <details><summary>Unduh ulang dan verifikasi</summary><p>Setelah diterbitkan, kembali melalui Dashboard → Ambil Sertifikat untuk melihat dan mengunduh PDF Anda. QR Code dan tautan verifikasi menampilkan nama peserta, program, nomor, tanggal, dan status sertifikat secara publik. Email dan data transaksi privat tidak ditampilkan.</p></details>
</section>
@endsection

@if ($paymentAvailable && auth()->check())
    @push('scripts')
        <script src="{{ \App\Services\MidtransGateway::SCRIPT_URL }}" data-client-key="{{ config('midtrans.client_key') }}" defer></script>
    @endpush
@endif
