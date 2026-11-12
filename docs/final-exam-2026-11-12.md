# Evaluasi Akhir OOPy, 12 November 2026

Implementasi paket baru menggunakan 10 pilihan ganda dan 10 isian kode Python: 20 soal, 5 poin per soal, maksimal 100, durasi 2.400 detik, dan batas lulus 70. Semua jawaban dinilai oleh server dan percobaan baru langsung berstatus `completed` setelah dikumpulkan.

Paket baru telah diaktifkan pada database utama pada 12 November 2026 pukul 18.29 WITA. Kuis #7 kini berisi tepat 10 pilihan ganda + 10 isian kode aktif, tanpa uraian aktif. Lima uraian lama diarsipkan, bukan dihapus. Koneksi MySQL yang sempat ditolak pada implementasi awal sudah tersedia saat tindak lanjut pengguna. Seluruh pengujian yang membuat atau mengumpulkan percobaan tetap memakai SQLite testing terisolasi.

Backup database sebelum aktivasi tersimpan di `storage/app/private/backups/final-exam-before-20261112-182951.sql` (101.764 byte). Fingerprint sebelum/sesudah memastikan seluruh data pada `final_exam_attempts` (1), `quiz_attempts` (8), `quiz_answers` (36), `user_progress` (4), `exercise_submissions` (9), dan `users` (6) tetap identik. Aktivasi memakai perintah transisi dengan versi paket hasil preview; tidak ada migration atau seeder pada database utama.

## Audit dan batas perubahan

Alur yang diperiksa meliputi `FinalExamService`, controller peserta dan Admin, request jawaban dan penilaian, model `FinalExamAttempt`, `QuestionBankService`, `LearningReportService`, relasi soal aktif pada `Quiz`, bank PHP, seeder, route, seluruh view evaluasi, modul JavaScript evaluasi, dashboard, rekap/CSV, dan pengujian terkait.

Temuan utama:

- `finish()` sebelumnya selalu menetapkan `pending_review`, sekalipun seluruh soal dapat diperiksa otomatis.
- Validasi katalog dan publikasi masih mensyaratkan 10/5/5; jumlah uraian di payload hasil selalu 5.
- Pengantar, hasil, detail pengguna, dan Dashboard Admin masih menyebut batas objektif 75 poin untuk semua percobaan.
- Dashboard pengguna sebelumnya hanya menampilkan nilai kuis dan Coding, sehingga ditambahkan ringkasan Evaluasi Akhir melalui satu query agregat. Perhitungan progres dan nilai kuis tetap sama.
- Seeder memakai `firstOrCreate` berdasarkan kuis dan urutan. Seeder ulang mempertahankan soal existing, sehingga tidak dapat dipakai untuk mengganti paket lama secara otomatis.
- Tes browser evaluasi lama masih mengasumsikan kunci jawaban dan nilai berada di localStorage. Tes diperbarui mengikuti penyimpanan dan penilaian server.

## Bank soal

Sepuluh pilihan ganda dan lima isian kode pertama dipertahankan, termasuk kunci jawaban yang telah diperiksa. Lima uraian pada bank default diganti dengan soal berikut.

| ID default | Soal dan bagian yang diisi | Kunci server | Konsep |
| --- | --- | --- | --- |
| q16 | Isi nama class pada `stasiun = __________()` setelah deklarasi `class Stasiun` | `Stasiun` | Class dan object |
| q17 | Isi nama method khusus pada `def __________(self, nilai)` | `__init__` | Constructor |
| q18 | Isi class induk pada `class SensorPH(__________)` | `SensorLingkungan` | Inheritance |
| q19 | Isi penugasan atribut internal pada setter `ph(self, nilai)` | `self._ph = nilai` | Encapsulation dan property |
| q20 | Isi abstract base class yang diimpor dari `abc` | `ABC` | Abstract class |

Setiap pertanyaan membatasi bentuk jawaban yang diminta. Polimorfisme tetap diwakili soal isian q14 dan pilihan ganda existing. Kelima potongan kode baru, setelah diisi kunci, diperiksa dengan `ast.parse` Python dan semuanya valid secara sintaks.

Database testing yang di-seed dari kondisi kosong menghasilkan 10 PG + 10 kode untuk Evaluasi Akhir. Total bank seluruh aplikasi tetap 50 soal: 28 PG + 22 kode, tanpa uraian pada instalasi baru. Soal kuis BAB 1–6 tidak diubah.

## Penilaian, penyimpanan, dan riwayat

Server menggunakan kunci dalam snapshot percobaan, bukan nilai/kunci yang dikirim peserta. Nilai paket baru adalah `benar / 20 * 100`, setara `benar * 5`, disimpan sebagai `objective_score`, `objective_points`, dan `total_score`. Hasil 70 dinyatakan lulus; 65 belum lulus. Tidak ada eksekusi Python peserta, `eval`, atau `exec` dalam penilaian isian. Pembanding exact dan toleransi token/spasi existing dipertahankan.

Kepemilikan akun/sesi, validasi input, transaksi, locking, batas request, deadline server, autosave, penanda, navigasi, dan retry submit tetap digunakan. Submit ulang tidak mengganti jawaban atau nilai. Payload yang terlambat tidak dapat mengganti jawaban yang telah tersimpan sebelum deadline. Kebijakan percobaan baru diambil dari konfigurasi Evaluasi Akhir; percobaan lama mempertahankan durasi dan ambang lulus dalam record-nya.

Penanganan ditentukan oleh snapshot:

- Snapshot tanpa uraian: langsung `completed`, total resmi tersedia, tanpa kontrol penilaian manual.
- Snapshot lama dengan uraian: tetap `pending_review` setelah submit, lalu dapat diselesaikan Admin dengan mekanisme lama.
- Record historis yang sudah selesai tidak diubah saat dibaca, disubmit ulang, atau saat paket baru diterbitkan.

Kolom essay, rubrik, feedback, dan route penilaian Admin tetap tersedia. Tidak ada migration atau tabel baru. Jumlah uraian dan batas poin pada hasil/detail diambil dari snapshot. Dashboard Admin, rekap, filter existing, dan CSV tetap menggunakan `total_score` resmi yang sudah tersimpan. Dashboard pengguna kini menampilkan nilai evaluasi terbaik, jumlah percobaan terkumpul, dan jumlah riwayat yang masih menunggu uraian.

## Aktivasi aman pada database existing

Prosedur berikut sudah digunakan untuk aktivasi database utama. Pada paket yang telah menggunakan 10/10, menjalankan ulang preview hanya menampilkan status dan tidak mengubah data. Gunakan prosedur ini jika menerapkan perubahan pada database existing lain.

1. Buat backup database dan verifikasi salinannya. Jangan reset database atau memakai `migrate:fresh`.
2. Jalankan preview tanpa penulisan:

   ```bash
   php artisan oopy:upgrade-final-exam
   ```

   Bila ada lebih dari satu kuis Evaluasi Akhir, pilih `--quiz=ID`. Preview menampilkan ID kuis, versi paket, komposisi aktif, dan lima pengganti beserta kunci server untuk ditinjau operator.

3. Untuk paket lama tepat 10/5/5, setelah meninjau preview dan mendapat persetujuan publikasi, jalankan:

   ```bash
   php artisan oopy:upgrade-final-exam --quiz=ID --apply --package-version=HASH_DARI_PREVIEW
   ```

   Perintah mengunci kuis dan soal aktif, memverifikasi versi paket, mempertahankan 15 soal objektif, membuat lima revisi sebagai draft, lalu menerbitkan paket melalui `QuestionBankService`. Lima uraian diarsipkan, tidak dihapus atau ditimpa. Percobaan dan nilai historis tidak diperbarui. Kegagalan validasi membatalkan seluruh transaksi.

4. Jalankan preview lagi dan periksa Bank Soal Admin: 10 PG + 10 kode aktif. Pengulangan perintah pada paket baru tidak menambah revisi atau mengubah data. Seeder ulang juga tidak mengaktifkan kembali uraian lama.
5. Verifikasi satu percobaan baru dan satu riwayat lama melalui UI. Percobaan lama yang masih aktif harus melanjutkan snapshot sebelumnya.

Paket dengan komposisi selain 10/5/5 atau 10/10 ditolak oleh perintah transisi agar paket khusus ditinjau operator melalui Bank Soal Admin. Publikasi Admin tetap hanya menerima tepat 10 PG + 10 kode. Mengedit draft/revisi, arsip, dan proteksi versi tetap memakai mekanisme existing. Jangan memakai seeder umum sebagai pengganti prosedur ini pada database utama.

## Pengujian yang dijalankan

| Pemeriksaan | Hasil |
| --- | --- |
| `php artisan test --compact` | Lulus: 245 tes, 5.056 assertion |
| `node --test tests/js/*.test.js` | Lulus: 23 tes |
| `php artisan route:list --except-vendor` | Berhasil: 50 route |
| `composer validate` | Valid |
| `git diff --check` | Bersih |
| Sintaks Python lima soal baru menggunakan `ast.parse` | q16–q20 lulus |
| `node tests/browser/evaluasi.mjs` | Lulus, Edge headless, database SQLite disposable |
| `node tests/browser/quiz.mjs` | Lulus untuk BAB 1–6, rentang nilai 0–100, retry/refresh, akun terpisah, dan guest |
| QA visual sesudah perbaikan kontras | Lulus: judul panel teal 6,11:1; judul panel mint 7,65:1; lebar 320–1440 px |

Suite PHP mencakup seluruh nilai 100/90/80/70/65/50/0, ambang lulus, penolakan nilai client, snapshot, pengumpulan ulang, kepemilikan, validasi, deadline, guest practice, manual grading historis, nilai historis yang tetap, penolakan komposisi bank, serta konsistensi dashboard/Admin/CSV. Uji transisi memeriksa preview tanpa penulisan, versi basi, pengarsipan, revisi, idempotensi, seeder ulang, snapshot historis, dan kelanjutan attempt lama. Suite penuh juga mencakup regresi kuis, autentikasi, dan integrasi Coding existing.

Browser evaluasi dijalankan dengan dan tanpa cache aset lokal yang sudah tersedia. Cakupannya: semua 20 soal, nilai 65/70/100, keyboard, navigasi, penanda, autosave, refresh dan deadline sama, peringatan lima/satu menit, kegagalan koneksi autosave/submit dan retry, riwayat, review yang aman dari HTML, dashboard, Admin tanpa input grading baru, CSV, serta lebar 320/390/768/1024/1440 px. Screenshot tersedia di `storage/framework/testing/final-exam-browser-cached/`.

Log run akhir tersedia di `storage/framework/testing/final-exam-phpunit.log`, `final-exam-js.log`, dan `final-exam-routes.log`. QA visual tambahan berada di `storage/framework/testing/final-exam-visual.mjs`; screenshot sesudah perbaikan warna memakai nama `intro-final-*` dan `results-final-*`.

Assertion awal timer sebelumnya terlalu kaku: pemuatan halaman dapat menghabiskan satu detik, sehingga `39:59` adalah nilai sah. Tes sekarang memastikan durasi snapshot tepat 2.400.000 ms dan waktu tampilan dekat deadline server. Durasi aplikasi dan aturan deadline tidak diperpendek atau diperpanjang. Deadline serta penolakan jawaban terlambat tetap diuji secara deterministik pada server.

## File yang berubah

Backend dan transisi:

- `app/Services/FinalExamService.php`
- `app/Services/Admin/QuestionBankService.php`
- `app/Http/Requests/Admin/GradeFinalExamRequest.php`
- `app/Services/DashboardProgressService.php`
- `app/Console/Commands/UpgradeFinalExam.php` (baru)

Konten dan antarmuka:

- `resources/materi/evaluasi-akhir.php`
- `public/js/evaluasi/exam.js`
- `public/css/oopy/evaluasi/evaluasi.css`
- `resources/views/evaluasi/index.blade.php`
- `resources/views/evaluasi/layout.blade.php`
- `resources/views/evaluasi/ujian.blade.php`
- `resources/views/evaluasi/hasil.blade.php`
- `resources/views/admin/evaluations/show.blade.php`
- `resources/views/admin/questions/index.blade.php`
- `resources/views/admin/users/show.blade.php`
- `resources/views/admin/scores/index.blade.php`
- `resources/views/admin/dashboard/index.blade.php`
- `resources/views/dashboard/index.blade.php`

Pengujian dan dokumentasi:

- `tests/Feature/Admin/FinalEvaluationTest.php`
- `tests/Feature/Admin/FinalExamTransitionTest.php` (baru)
- `tests/Feature/Admin/QuestionBankTest.php`
- `tests/Feature/Admin/ReportsTest.php`
- `tests/Feature/EvaluasiAkhirTest.php`
- `tests/Feature/DashboardTest.php`
- `tests/Feature/LearningDatabaseTest.php`
- `tests/Fixtures/legacy-final-exam.php` (baru)
- `tests/js/final-exam.test.js`
- `tests/browser/evaluasi.mjs`
- `tests/browser/final_evaluation_cdp.py`
- `docs/final-exam-2026-11-12.md` (baru)

## Desain dan batas verifikasi

Arah desain mengikuti `DESIGN.md`, ENERGY 2 / RHYTHM 2 / MOTION 1. Tidak ada aset, dependency, atau komponen visual baru. Palet, tipografi, spacing, fokus, dan navigasi existing dipertahankan. Judul panel evaluasi diberi `color: inherit` karena pemeriksaan dengan Bootstrap aktif menemukan warna heading global mengalahkan teks putih pada panel teal.

Delivery Gate untuk perubahan UI yang dikerjakan:

- Hard Gate PASS: pengujian browser dan inspeksi screenshot tidak menemukan overflow pada 320–1440 px; kode review di-escape; heading panel terukur 6,11:1/7,65:1; kontrol baru memakai link route yang tersedia. Tidak ditambahkan aset, statistik, atau klaim produk tanpa sumber.
- Purpose Gate PASS: perubahan terbatas pada isi, kondisi penilaian historis, ringkasan nilai pada dashboard, dan warna heading yang mengikuti panel untuk keterbacaan. Komponen, dekorasi, dan motion existing dipertahankan.
- Liveliness PASS: ENERGY 2 / RHYTHM 2 / MOTION 1 dari `DESIGN.md`; nilai akhir menjadi fokus halaman hasil; aksen teal dan motif praktikum kode OOPy tetap dipakai.
- Craftsmanship PASS: dialog mulai/submit, navigasi, penanda, review, autosave, retry koneksi, serta tautan dashboard/Admin diuji; hasil resmi diverifikasi terhadap server dan CSV. Batas pengujian tetap dijelaskan di bawah.

Aktivasi pada MySQL utama berhasil dan preview ulang memverifikasi paket 10/10. Pemeriksaan browser read-only pada `http://127.0.0.1:8000/materi/evaluasi-akhir` memastikan HTTP 200, API state siap, tombol mulai aktif, dan dialog konfirmasi dapat dibuka tanpa membuat percobaan di database utama. Pada tindak lanjut, 15 tes PHP terarah lulus dengan 607 assertion. Pengujian penulisan percobaan tetap memakai SQLite terisolasi. Suite CDP Python diperbarui mengikuti format otomatis tetapi tidak dijalankan tersendiri. Tidak ada klaim pengujian beban produksi, screen reader menyeluruh, atau layanan Docker Grader live.

Tidak ada perubahan `.env`, kredensial, autentikasi/Google OAuth, logika kuis BAB 1–6, Coding Grader, atau schema database. Tidak ada commit, push, merge, atau PR.
