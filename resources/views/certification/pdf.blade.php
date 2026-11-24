<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $certificate->certificate_number }}</title>
    <style>
        @page { margin: 12mm; }
        body { margin: 0; color: #243b39; font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; }
        .sheet { position: relative; height: 183mm; border: .65mm solid #076d71; background: #fff; }
        .masthead { position: absolute; top: 7mm; left: 10mm; right: 10mm; padding-bottom: 4mm; border-bottom: .35mm solid #a8cfc7; }
        .masthead img { width: 10mm; height: 10mm; vertical-align: middle; }
        .masthead strong { font-size: 14pt; vertical-align: middle; }
        .completion { position: absolute; top: 32mm; left: 12mm; right: 12mm; text-align: center; }
        h1 { margin: 0 0 1mm; font-size: 24pt; }
        .subtitle { margin: 0; font-size: 10pt; color: #40514e; }
        .recipient { margin: 8mm 0 0; font-size: 10pt; }
        .name { margin: 2mm 0 0; color: #065f63; line-height: 1.18; word-wrap: break-word; font-weight: bold; }
        .achievement { margin: 5mm 0 0; font-size: 10pt; }
        .course { font-size: 14pt; margin-top: 1mm; font-weight: bold; }
        .details { position: absolute; left: 10mm; bottom: 18mm; font-size: 9pt; line-height: 1.7; }
        .details strong { font-size: 10pt; }
        .qr { position: absolute; right: 10mm; bottom: 15mm; width: 37mm; text-align: center; font-size: 8pt; }
        .qr img { width: 27mm; height: 27mm; }
        .qr-caption { margin-top: 1mm; }
        .footer { position: absolute; left: 10mm; right: 10mm; bottom: 5mm; border-top: .35mm solid #a8cfc7; padding-top: 2mm; font-size: 8pt; }
        .page-number { float: right; font-weight: bold; }
        .transcript { page-break-before: always; }
        .transcript-body { padding: 23mm 10mm 0; }
        .transcript-body h1 { font-size: 18pt; }
        .identity { margin: 2.5mm 0; font-size: 9pt; line-height: 1.35; word-wrap: break-word; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 9pt; line-height: 1.3; }
        th, td { padding: .65mm 2mm; border-bottom: .22mm solid #c5dcd7; text-align: left; line-height: 1.17; }
        thead th { background: #e4f9f5; }
        tbody th { font-weight: normal; }
        .position, .score { text-align: center; }
        .academic-summary { margin: 2mm 0 1.5mm; font-size: 9pt; }
        .academic-summary td { padding: 1.5mm 1mm; background: #e4f9f5; border: none; vertical-align: top; }
        .academic-summary strong { display: block; font-size: 10pt; }
    </style>
</head>
<body>
@php
    $transcript = $certificate->transcript_snapshot;
    $archivedCaptureDate = \App\Services\CertificateTranscriptService::archivedCaptureDate($transcript);
    $completionDate = $certificate->completed_at->copy()->timezone('Asia/Makassar')->locale('id')->translatedFormat('j F Y');
    $issueDate = $certificate->issued_at->copy()->timezone('Asia/Makassar')->locale('id')->translatedFormat('j F Y');
    $participantName = $certificate->currentParticipantName();
    $correctedDate = $certificate->currentNameRevision()?->created_at->copy()->timezone('Asia/Makassar')->locale('id')->translatedFormat('j F Y');
    $nameLength = mb_strlen($participantName);
@endphp
<div class="sheet">
    <div class="masthead">
        <img src="{{ $logo }}" alt="Logo OOPy"> <strong>OOPy</strong>
    </div>
    <div class="completion">
        <h1 lang="en">CERTIFICATE OF COMPLETION</h1>
        <p class="subtitle">Sertifikat Penyelesaian Pembelajaran</p>
        <p class="recipient">Diberikan kepada</p>
        <div class="name" style="font-size: {{ $nameLength > 100 ? 14 : ($nameLength > 60 ? 19 : 29) }}pt">{{ $participantName }}</div>
        <p class="achievement">Atas keberhasilannya menyelesaikan program pembelajaran</p>
        <div class="course">{{ $certificate->course_title }}</div>
    </div>
    <div class="details">
        <strong>OOPy Learning Platform</strong><br>
        Nomor sertifikat: {{ $certificate->certificate_number }}<br>
        Pembelajaran selesai: {{ $completionDate }}<br>
        Diterbitkan: {{ $issueDate }}
        @if ($correctedDate)<br>Koreksi nama: {{ $correctedDate }}@endif
    </div>
    <div class="qr"><div><img src="{{ $qr }}" alt="QR verifikasi"></div><div class="qr-caption">Pindai untuk verifikasi</div></div>
    <div class="footer">
        @if ($transcript)<span class="page-number">1/2</span>@endif
        Sertifikat demo penyelesaian pembelajaran OOPy
    </div>
</div>
@if ($transcript)
    <div class="sheet transcript">
        <div class="masthead">
            <img src="{{ $logo }}" alt="Logo OOPy"> <strong>OOPy</strong>
        </div>
        <div class="transcript-body">
            <h1>TRANSKRIP NILAI</h1>
            <p class="subtitle" lang="en">Learning Achievement Transcript</p>
            <div class="identity">
                <strong>Nama peserta:</strong> {{ $participantName }}<br>
                <strong>Program:</strong> {{ $certificate->course_title }}<br>
                <strong>Nomor sertifikat:</strong> {{ $certificate->certificate_number }} &nbsp; <strong>Diterbitkan:</strong> {{ $issueDate }}
                @if ($archivedCaptureDate)
                    <br>Nilai dicatat pada {{ $archivedCaptureDate }} setelah sertifikat diterbitkan.
                @endif
            </div>
            @include('certification.partials.transcript-table')
            <table class="academic-summary">
                <tr>
                    <td>BAB diselesaikan<strong>{{ implode(' / ', $transcript['summary']['chapters']) }}</strong></td>
                    <td>Kuis lulus<strong>{{ implode(' / ', $transcript['summary']['quizzes']) }}</strong></td>
                    <td>Latihan coding lulus<strong>{{ implode(' / ', $transcript['summary']['coding']) }}</strong></td>
                    <td>Nilai Evaluasi Akhir<strong>{{ \App\Services\CertificateTranscriptService::score($transcript['summary']['final_score']) }}</strong></td>
                    <td>{{ $archivedCaptureDate ? 'Status saat pencatatan' : 'Status kelulusan' }}<strong>{{ $transcript['summary']['graduation'] }}</strong></td>
                </tr>
            </table>
        </div>
        <div class="footer"><span class="page-number">2/2</span>Transkrip demo OOPy · {{ $transcript['policy_version'] }}@if ($transcript['captured_at_checkout'] ?? false) · Nilai resmi saat pesanan dibuat @endif @if ($correctedDate) · Koreksi nama {{ $correctedDate }}@endif</div>
    </div>
@endif
</body>
</html>
