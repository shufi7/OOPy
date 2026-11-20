<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $certificate->certificate_number }}</title>
    <style>
        @page { margin: 13mm; }
        body { margin: 0; color: #243b39; font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; }
        .sheet { position: relative; height: 180mm; border: 1.5mm solid #076d71; background: #fff; }
        .brand { position: absolute; left: 12mm; top: 7mm; }
        .brand img { width: 15mm; height: 15mm; vertical-align: middle; }
        .brand strong { font-size: 19pt; vertical-align: middle; }
        .demo { position: absolute; right: 12mm; top: 12mm; color: #076d71; font-weight: bold; font-size: 12pt; }
        .heading { position: absolute; top: 30mm; left: 10mm; right: 10mm; text-align: center; }
        h1 { margin: 0 0 2mm; font-size: 25pt; }
        .recipient { position: absolute; top: 56mm; left: 16mm; right: 16mm; text-align: center; }
        .name { margin: 3mm 0 0; color: #076d71; line-height: 1.25; word-wrap: break-word; font-weight: bold; }
        .achievement { position: absolute; top: 96mm; left: 12mm; right: 12mm; text-align: center; }
        .course { font-size: 15pt; margin-top: 3mm; font-weight: bold; }
        .details { position: absolute; left: 12mm; bottom: 11mm; width: 195mm; font-size: 9pt; line-height: 1.8; }
        .details strong { font-size: 11pt; }
        .qr { position: absolute; right: 10mm; bottom: 9mm; width: 35mm; text-align: center; font-size: 8pt; }
        .qr img { width: 29mm; height: 29mm; display: block; }
        .notice { position: absolute; bottom: 0; left: 12mm; font-size: 8pt; }
    </style>
</head>
<body>
@php($showDemoNotice = $certificate->order?->mode !== 'sandbox')
<div class="sheet">
    <div class="brand"><img src="{{ $logo }}" alt="Logo OOPy"> <strong>OOPy</strong></div>
    @if ($showDemoNotice)
        <div class="demo">{{ $certificate->demoLabel() }}</div>
    @endif
    <div class="heading"><h1>CERTIFICATE OF COMPLETION</h1><div>Sertifikat Penyelesaian Pembelajaran</div></div>
    <div class="recipient">Diberikan kepada
        <div class="name" style="font-size: {{ mb_strlen($certificate->participant_name) > 100 ? 14 : (mb_strlen($certificate->participant_name) > 60 ? 21 : 30) }}pt">{{ $certificate->participant_name }}</div>
    </div>
    <div class="achievement">Atas keberhasilannya menyelesaikan program pembelajaran
        <div class="course">{{ $certificate->course_title }}</div>
    </div>
    <div class="details">
        <strong>OOPy Learning Platform</strong><br>
        Nomor: {{ $certificate->certificate_number }}<br>
        Selesai: {{ $certificate->completed_at->timezone('Asia/Makassar')->locale('id')->translatedFormat('j F Y') }}<br>
        Diterbitkan: {{ $certificate->issued_at->timezone('Asia/Makassar')->locale('id')->translatedFormat('j F Y') }}
    </div>
    <div class="qr"><img src="{{ $qr }}" alt="QR verifikasi"><br><span>Pindai untuk verifikasi</span></div>
    @if ($showDemoNotice)
        <p class="notice">Sertifikat demo penyelesaian pembelajaran OOPy ({{ $certificate->demoLabel() }}). Bukan sertifikasi profesi atau akreditasi.</p>
    @endif
</div>
</body>
</html>
