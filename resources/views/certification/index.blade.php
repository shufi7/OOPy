@extends('certification.layout')
@section('title', 'Sertifikasi OOPy')

@section('certification-content')
@php
    $ready = $certificate?->isDownloadable() ?? false;
    $canIssue = $eligibility && $eligibility['eligible'] && ! $sandboxCertificate;
    $chapters = collect($eligibility['chapters'] ?? []);
    $coding = collect($eligibility['coding'] ?? []);
    $groups = $eligibility ? [
        ['title' => 'Materi BAB 1–6', 'note' => 'Menuntaskan enam BAB inti.', 'value' => $eligibility['completed_chapters'].' / '.$eligibility['total_chapters'], 'done' => $eligibility['completed_chapters'] === $eligibility['total_chapters'], 'status' => $chapters->contains(fn ($chapter) => $chapter['status'] !== 'not_started') ? 'Dalam Proses' : 'Belum Dimulai'],
        ['title' => 'Kuis setiap BAB', 'note' => 'Lulus enam kuis dengan nilai minimal '.\App\Services\ChapterQuizScoring::format(\App\Services\ChapterQuizScoring::passingScore()).'.', 'value' => $eligibility['passed_quizzes'].' / '.$eligibility['total_quizzes'], 'done' => $eligibility['passed_quizzes'] === $eligibility['total_quizzes'], 'status' => $chapters->contains(fn ($chapter) => $chapter['best_score'] !== null && $chapter['best_score'] < \App\Services\ChapterQuizScoring::passingScore()) ? 'Belum Lulus' : ($chapters->contains(fn ($chapter) => $chapter['status'] !== 'not_started') ? 'Dalam Proses' : 'Belum Dimulai')],
        ['title' => 'Latihan Coding', 'note' => 'Lulus Latihan 1 dan Latihan 2 multi-file versi aktif.', 'value' => $eligibility['passed_coding'].' / '.$eligibility['total_coding'], 'done' => $eligibility['passed_coding'] === $eligibility['total_coding'], 'status' => $coding->contains('status', 'in_progress') ? 'Belum Lulus' : ($eligibility['passed_coding'] > 0 ? 'Dalam Proses' : 'Belum Dimulai')],
        ['title' => 'Evaluasi Akhir', 'note' => 'Percobaan resmi selesai dinilai dan mencapai ambang kelulusan.', 'value' => $eligibility['best_final_score'] === null ? 'Belum ada nilai' : \App\Services\ChapterQuizScoring::format($eligibility['best_final_score']).' / 100', 'done' => $eligibility['final_passed'], 'status' => $eligibility['final_status']],
    ] : [];
@endphp
<header class="certification-header">
    <a class="certification-back" href="{{ auth()->check() ? route('dashboard') : route('home') }}">{{ auth()->check() ? 'Kembali ke Dashboard' : 'Kembali ke Beranda' }}</a>
    <h1>Sertifikasi OOPy</h1>
    <p>Sertifikat penyelesaian untuk pembelajaran <strong>Pemrograman Berorientasi Objek dengan Python.</strong></p>
    <p class="certification-header-note">Periksa ketentuan, bayar melalui Midtrans Sandbox, lalu unduh PDF Anda di halaman ini. Semua kegiatan belajar tetap gratis.</p>
</header>

<div class="certification-workspace">
    <div class="certification-main-column">
        <section class="certification-eligibility" aria-labelledby="eligibility-title">
            <div class="certification-section-heading">
                <div><p class="certification-section-label">Ketentuan akademik</p><h2 id="eligibility-title">Selesaikan pembelajaran Anda</h2></div>
                @if ($eligibility)<span class="certification-state {{ $eligibility['eligible'] ? 'is-complete' : '' }}">{{ $eligibility['eligible'] ? 'Semua syarat terpenuhi' : 'Belum memenuhi syarat' }}</span>@endif
            </div>
            @if ($eligibility)
                <p class="certification-section-note">Status di bawah berasal dari progres dan hasil penilaian resmi akun Anda.</p>
                <dl class="certification-checklist">
                    @foreach ($groups as $group)
                        <div class="{{ $group['done'] ? 'is-complete' : '' }}">
                            <dt><span>{{ $group['title'] }}</span><small>{{ $group['note'] }}</small></dt>
                            <dd><strong>{{ $group['value'] }}</strong><span>{{ $group['done'] ? ($loop->first ? 'Selesai' : 'Lulus') : $group['status'] }}</span></dd>
                        </div>
                    @endforeach
                </dl>
                @if ($eligibility['missing'])
                    <details class="certification-missing">
                        <summary>Lihat yang perlu diselesaikan <span>({{ count($eligibility['missing']) }})</span></summary>
                        <ul>@foreach ($eligibility['missing'] as $requirement)<li><a href="{{ $requirement['url'] }}">{{ $requirement['label'] }}</a></li>@endforeach</ul>
                    </details>
                @else
                    <p class="certification-completion-note">Anda sudah memenuhi seluruh persyaratan akademik untuk sertifikat penyelesaian OOPy.</p>
                @endif
            @else
                <ol class="certification-guest-requirements">
                    <li><strong>Materi dan kuis BAB 1–6.</strong> Selesaikan enam BAB dan lulus setiap kuis, minimal {{ \App\Services\ChapterQuizScoring::format(\App\Services\ChapterQuizScoring::passingScore()) }}.</li>
                    <li><strong>Dua latihan Coding.</strong> Lulus Latihan 1 dan Latihan 2 multi-file versi aktif.</li>
                    <li><strong>Evaluasi Akhir resmi.</strong> Minimal {{ config('evaluasi.pass_threshold') }} atau ambang resmi percobaan, dan sudah selesai dinilai.</li>
                </ol>
                <p class="certification-section-note">Masuk untuk melihat kelayakan akun Anda. Latihan Evaluasi Akhir sebagai tamu tidak dihitung.</p>
            @endif
        </section>

        <section id="sertifikat" class="certification-document" aria-labelledby="document-title">
            <div class="certification-section-heading">
                <div><p class="certification-section-label">{{ $certificate ? 'Dokumen Anda' : 'Pratinjau dokumen' }}</p><h2 id="document-title">{{ $certificate ? 'Sertifikat penyelesaian Anda' : 'Contoh sertifikat OOPy' }}</h2></div>
            </div>
            @if ($certificate)
                <p class="certification-section-note">{{ $ready ? 'Diterbitkan untuk akun Anda. Unduh ulang kapan saja dari halaman ini.' : ($certificate->status === 'revoked' ? 'Dibatalkan. Sertifikat ini tidak dapat diunduh.' : 'Sertifikat ini tidak aktif dan belum dapat diunduh.') }}</p>
            @else
                <p class="certification-section-note">Hanya contoh tampilan. Sertifikat dengan nama Anda tersedia setelah semua syarat terpenuhi dan pembayaran Sandbox terverifikasi.</p>
            @endif
            @include('certification.partials.preview')
            @if ($certificate)
                <div class="certification-document-actions">
                    <a href="{{ route('certificates.verify', $certificate->public_id) }}">Verifikasi sertifikat</a>
                    <button type="button" data-copy-verification="{{ route('certificates.verify', $certificate->public_id) }}">Salin tautan verifikasi</button>
                </div>
                <p role="status" data-copy-status class="certification-copy-status"></p>
            @endif
            @if ($legacyCertificate && $legacyCertificate->isDownloadable())
                <p class="certification-action-note">Sertifikat simulasi sebelumnya tetap tersedia, terpisah dari transaksi Midtrans Sandbox.</p>
                <a class="certification-next-step" href="{{ route('certificates.download', $legacyCertificate->public_id) }}" download>Unduh sertifikat simulasi lama</a>
            @endif
        </section>
    </div>

    <aside id="penerbitan" class="certification-issuance" aria-labelledby="issuance-title">
        @if ($sandboxCertificate)
            <p class="certification-section-label">Sertifikat terbit</p>
            <h2 id="issuance-title">{{ $ready ? 'Siap diunduh' : 'Sertifikat tidak aktif' }}</h2>
            <p class="certification-state {{ $ready ? 'is-complete' : 'is-unavailable' }}">{{ $certificate->status === 'revoked' ? 'Dibatalkan' : ($ready ? 'Aktif · SANDBOX / DEMO' : 'Tidak aktif') }}</p>
            <dl class="certification-receipt">
                <div><dt>Nama peserta</dt><dd>{{ $certificate->participant_name }}</dd></div>
                <div><dt>Nomor sertifikat</dt><dd>{{ $certificate->certificate_number }}</dd></div>
                <div><dt>Pembelajaran selesai</dt><dd>{{ $certificate->completed_at->timezone('Asia/Makassar')->locale('id')->translatedFormat('j F Y') }}</dd></div>
                <div><dt>Diterbitkan</dt><dd>{{ $certificate->issued_at->timezone('Asia/Makassar')->locale('id')->translatedFormat('j F Y') }}</dd></div>
                <div><dt>Transaksi</dt><dd>{{ $payment['label'] ?? 'Status belum tersedia' }}</dd></div>
            </dl>
            @if ($ready)
                <a class="certification-press" href="{{ route('certificates.download', $certificate->public_id) }}" download>Unduh Sertifikat PDF</a>
                <p class="certification-action-note">Tidak perlu mengulang pembayaran Sandbox. Anda dapat mengunduh PDF ini kembali kapan saja.</p>
            @else
                <p class="certification-action-note">Hubungi pengelola OOPy untuk informasi status sertifikat ini.</p>
            @endif
            @if ($paymentAvailable && $payment)
                <div data-midtrans-payment data-status-url="{{ $payment['status_url'] }}" data-current-ready="{{ $ready ? '1' : '0' }}">
                    <button type="button" class="certification-secondary" data-payment-check>Cek Status Pembayaran</button>
                    <p class="certification-action-note" data-payment-feedback role="status"></p>
                </div>
            @endif
        @else
            <p class="certification-section-label">Penerbitan opsional</p>
            <h2 id="issuance-title">Ambil sertifikat Anda</h2>
            <p class="certification-price">Rp24.999 <span>/ sertifikat</span></p>
            <ul class="certification-benefits"><li>PDF A4 landscape dengan nama akun Anda</li><li>Nomor unik dan QR verifikasi</li><li>Unduh ulang dari halaman ini</li></ul>
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
        @endif
        <p class="certification-issuer">Diterbitkan oleh <strong>OOPy Learning Platform</strong></p>
    </aside>
</div>

<section id="panduan" class="certification-guide" aria-labelledby="guide-title">
    <h2 id="guide-title">Tentang sertifikat ini</h2>
    <details><summary>Belajar tetap gratis, sertifikat bersifat opsional</summary><p>Materi, kuis, Coding, dan Evaluasi Akhir dapat digunakan gratis. Penerbitan menggunakan Midtrans Sandbox dan tidak memproses uang sungguhan. Jangan gunakan aplikasi bank sungguhan untuk QRIS Sandbox.</p></details>
    <details><summary>Unduh ulang dan verifikasi</summary><p>Setelah diterbitkan, kembali melalui Dashboard → Ambil Sertifikat untuk melihat dan mengunduh PDF Anda. QR Code dan tautan verifikasi menampilkan nama peserta, program, nomor, tanggal, dan status sertifikat secara publik. Email dan data transaksi privat tidak ditampilkan.</p></details>
    <p class="certification-disclaimer">Sertifikat dari transaksi Sandbox berlabel <strong>SANDBOX / DEMO</strong>. Sertifikat simulasi lama tetap berlabel SIMULASI / DEMO. Keduanya bukan sertifikat produksi, sertifikasi profesi, atau akreditasi.</p>
</section>
@endsection

@if ($paymentAvailable && auth()->check())
    @push('scripts')
        <script src="{{ \App\Services\MidtransGateway::SCRIPT_URL }}" data-client-key="{{ config('midtrans.client_key') }}" defer></script>
    @endpush
@endif
