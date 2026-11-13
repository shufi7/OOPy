# Dokumentasi OOPy

Petunjuk instalasi, teknologi, dan menjalankan aplikasi ada di [README proyek](../README.md). Gunakan panduan fitur untuk pengoperasian; laporan bertanggal menjelaskan hasil pada snapshot tertentu.

## Panduan fitur dan integrasi terbaru

| Topik | Dokumen |
| --- | --- |
| Coding, gateway privat, sandbox, dan pengujian | [Coding](coding.md) |
| Latihan Coding 2 multi-file, versi latihan, dan QA | [Implementasi multi-file, 13 November 2026](coding-multifile-2026-11-13.md) |
| Rubrik penilaian Coding | [Laporan rubrik, 22 Oktober 2026](coding-grader-rubric-2026-10-22.md) |
| Aktivasi grader development | [Laporan aktivasi, 22 Oktober 2026](coding-grader-activation-2026-10-22.md) |
| Dashboard Pengguna dan agregasi Coding, Tahap 2 | [Integrasi Dashboard, 5 November 2026](coding-dashboard-2026-11-05.md) |
| Panel Admin, promosi role, dan rekap | [Panduan Admin](ADMIN_PANEL.md) |
| Audit akademis, revisi soal, dan penilaian Evaluasi Akhir | [Evaluasi Akhir, 13 November 2026](final-exam-academic-2026-11-13.md) |
| Coding pada detail siswa, filter rekap, dan CSV, Tahap 3 | [Integrasi Admin, 5 November 2026](admin-coding-2026-11-05.md) |
| Materi BAB 1–7 | [Materi](materials.md) |
| Editor Python | [Editor](editor.md) |
| Fondasi eksekusi Python di browser | [Live Coding](live-coding.md) |

## Dokumen fondasi dan riwayat

Dokumen berikut mencatat tahap awal. Angka tes/route dan pernyataan integrasi yang belum dilakukan berlaku pada saat dokumen ditulis. Baca laporan Tahap 2/3 di atas untuk perkembangan Dashboard dan Admin.

- [Autentikasi](authentication.md)
- [Database](database.md)
- [Dashboard fondasi](dashboard.md)
- [Progres kuis](quiz-progress.md)
- [Audit desain awal](../anti-slop/audit-001-2026-10-16.md)
- [Implementasi fondasi UI Tahap 1](../anti-slop/stage-1-2026-10-16.md)
- [Implementasi beranda Tahap 2 UI](../anti-slop/stage-2-2026-10-16.md)
- [Pemetaan screenshot regresi fondasi](../anti-slop/evidence/stage-2/foundation-regression/README.md)

Penomoran tahap pada arsip UI Oktober berbeda dari tahapan integrasi Coding November. Nama/path dokumen lama dipertahankan agar tautan dan provenance evidence tetap tersedia. Pedoman desain berada di [DESIGN.md](../DESIGN.md).

## Pemeliharaan repository

- [Cleanup lanjutan kode bawaan, 5 November 2026](maintenance/cleanup-2026-11-05-followup.md): menghapus template yang tidak dirutekan, import tidak terpakai, dan command contoh; mencatat verifikasi serta kode lama yang tetap diperlukan tes.
- [Audit cleanup 5 November 2026](maintenance/cleanup-2026-11-05-audit.md): inventaris, bukti, risiko, dan kategori SAFE/REVIEW/KEEP.
- [Daftar duplikat beserta hash](maintenance/duplicates-2026-11-05.json): path, ukuran, salinan kanonis, dan keputusan untuk 51 kelompok duplikat tracked.
- [Hasil cleanup 5 November 2026](maintenance/cleanup-2026-11-05.md): perubahan, perbandingan ukuran/jumlah file, serta verifikasi sebelum dan sesudah.

Evidence fitur tetap berada di `docs/evidence/`; evidence desain tetap di `anti-slop/evidence/`. Log, database QA, dan capture baru untuk cleanup disimpan lokal pada direktori testing yang diabaikan Git.
