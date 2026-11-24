# Sistem Sertifikasi OOPy

## Upgrade 24 November 2026

Halaman `/sertifikasi` menampilkan persyaratan akademik dan ringkasan nilai, diikuti konfirmasi nama, dua tab pratinjau, dan pembayaran. Setelah sertifikat terbit, tombol unduh berada tepat di bawah judul halaman. Pratinjau dokumen dan informasi akademik berdampingan di desktop. Di ponsel, dokumen tampil lebih dahulu. Panel ringkasan berwarna mint mengikuti token OOPy, tabel memakai nama komponen dari katalog, dan tab bisa diakses dengan tombol panah. Harga backend tetap Rp24.999 per sertifikat Sandbox.

### Nama dan batas akses

- Nama awal berasal dari profil. Nama yang sudah disimpan untuk sertifikat menjadi pilihan berikutnya. Konfirmasi mengharuskan checkbox persetujuan dan mencocokkan nama yang ditampilkan dengan pilihan terkini di server.
- Perubahan nama memakai password akun OOPy, CSRF, dan batas lima percobaan per menit per akun. Nama dinormalisasi menjadi 2–100 karakter. Perubahan mengosongkan konfirmasi tanpa mengubah nama profil. Laravel menyimpan hash password pada tabel `users` dan mengecualikan field password dari input lama ketika validasi gagal.
- Untuk akun Google, **Tetapkan password OOPy dengan Google** memulai OAuth ber-state dan PKCE melalui callback existing. Subject akun Google yang terverifikasi harus cocok dengan akun OOPy yang sedang login. Izin menetapkan password berlaku lima menit, terikat pada akun dan sesi, dan dikonsumsi saat penyimpanan. Google dapat memakai sesi Google yang sudah aktif saat pemilihan akun. [Referensi Google OpenID Connect](https://developers.google.com/identity/openid-connect/reference)
- Checkout menyimpan nama pada order dan referensi Midtrans sebelum menghubungi Snap. Referensi yang masih bisa dibayar atau berstatus respons API ambigu mengunci nama. Pengguna dapat mengganti nama setelah backend memverifikasi status terminal atau memastikan sesi Snap kedaluwarsa dan metode belum dipilih. Notifikasi yang terlambat menggunakan nama milik referensi yang betul-betul berhasil.
- Transkrip dan unduhan PDF memerlukan sesi pemilik. Verifikasi publik menampilkan nama pada dokumen terbaru, program, nomor, tanggal terbit, penerbit, dan status Sandbox atau simulasi.
- Pemilik sertifikat Sandbox aktif dapat membuka **Periksa atau ganti nama sertifikat** pada panel unduh. Formulir menampilkan nama saat ini dan meminta konfirmasi serta password OOPy sebelum menyimpan koreksi. Untuk akun Google, pengaturan password OOPy tersedia melalui verifikasi Google tertaut.
- Setiap koreksi menambah revisi nama bertanggal dalam transaksi yang mengunci akun dan sertifikat. Nama penerbitan awal, nomor, URL verifikasi, order, dan snapshot nilai disimpan seperti saat terbit. PDF dan halaman verifikasi memakai nama revisi terakhir. Verifikasi publik menampilkan keterangan koreksi dan mengarahkan pengguna ke PDF terbaru. Sertifikat yang dibatalkan atau dikembalikan tidak menerima koreksi.

| Route tambahan | Fungsi |
| --- | --- |
| `POST /sertifikasi/nama/konfirmasi` | Konfirmasi nama pilihan |
| `POST /sertifikasi/nama` | Verifikasi password dan ganti nama |
| `POST /sertifikasi/{publicId}/koreksi-nama` | Verifikasi password pemilik dan simpan revisi nama sertifikat Sandbox aktif |
| `POST /sertifikasi/password/google` | Mulai verifikasi Google untuk akun tertaut |
| `POST /sertifikasi/password` | Simpan password OOPy setelah verifikasi Google |

### Snapshot akademik dan PDF

`CertificateTranscriptService` memakai ringkasan penilaian resmi yang dibaca `CertificateEligibilityService`. Kuis memakai nilai resmi terbaik yang valid dan ambang yang dikonfigurasi. Latihan memakai nilai terbaik dari grader dan status kelulusan resmi. Evaluasi Akhir memakai nilai tertinggi dari percobaan lulus, atau percobaan selesai dengan nilai tertinggi jika belum lulus. Ambang percobaan tersebut ditampilkan pada transkrip. Nilai resmi kosong ditampilkan dengan tanda `–`. Transkrip memakai skala 0–100 dan mencatat versi kebijakan `oopy-transkrip-v1`.

Settlement yang terverifikasi menyimpan nama, transkrip, dan status penerbitan di dalam satu transaksi. Perubahan nilai, profil, atau katalog setelah terbit tidak mengubah dokumen. Jika hasil atau kebijakan akademik berubah sampai kelayakan saat checkout tidak bisa direproduksi, penerbitan memakai data resmi yang dicatat saat checkout dan memberi keterangan di transkrip. Pratinjau sebelum penerbitan berlabel **PRATINJAU**, dengan tab sertifikat dan transkrip. Setelah penerbitan, kedua tab memakai identitas dan nilai tersimpan.

PDF memakai Dompdf dan aset lokal. Halaman 1 memuat identitas OOPy, nama peserta, program, tanggal selesai dan terbit, nomor, dan QR verifikasi. Halaman 2 memuat transkrip sembilan komponen dan ringkasan kelulusan. Kedua halaman berukuran A4 landscape dengan keterangan demo di bagian bawah. Skala dan aturan penilaian tersedia di tab **Transkrip Nilai**. Untuk sertifikat Sandbox lama, pemilik dapat memilih **Lengkapi Transkrip Nilai**. Nilai resmi dicatat satu kali dan diberi tanggal pencatatan setelah penerbitan. Unduhan berikutnya menyertakan transkrip tersebut. Transaksi yang sudah dimulai sebelum upgrade dapat diselesaikan dengan nama akun saat penerbitan bila belum ada nama terkunci. Halaman menandai kondisi tersebut.

### Skema dan prosedur penerapan

| Tabel | Penambahan |
| --- | --- |
| `certificate_identities` | Satu pilihan nama dan waktu konfirmasi per pengguna, FK ke `users` |
| `certificate_orders` | Nama yang dikunci untuk pesanan |
| `certificate_payment_attempts` | Nama per referensi dan data akademik saat checkout sebagai cadangan |
| `certificates` | JSON `transcript_snapshot`, berisi label, nilai, status, ambang, skala, dan versi kebijakan |
| `certificate_name_revisions` | Riwayat nama pengganti dan waktu koreksi, FK ke sertifikat |

Kolom tambahan bersifat nullable. Migration tidak melakukan backfill atas sertifikat atau transaksi lama. Rollback skema ditolak setelah data identitas atau snapshot tersimpan. Dalam rencana rollback aplikasi, pertahankan skema dan data baru.

1. Pastikan database tujuan telah memiliki tabel sertifikasi dan pembayaran Sandbox. Buat cadangan database dan periksa SQL migration.
2. Setelah persetujuan eksplisit pemilik, terapkan migration aditif sebelum menerbitkan versi aplikasi yang baru. Jalankan langkah ini pada jendela deploy yang mencakup pemrosesan webhook.
3. Gunakan konfigurasi Sandbox yang sudah ada. Jalankan pemeriksaan localhost atau staging dari nama, pembayaran, sampai PDF dan verifikasi.

```powershell
php artisan migrate --pretend --path=database/migrations/2026_11_24_000001_add_certificate_identity_and_transcript.php
# Setelah disetujui dan database tujuan sudah dicadangkan
php artisan migrate --path=database/migrations/2026_11_24_000001_add_certificate_identity_and_transcript.php
# Migration koreksi nama terbit
php artisan migrate --pretend --path=database/migrations/2026_11_24_000002_create_certificate_name_revisions.php
php artisan migrate --path=database/migrations/2026_11_24_000002_create_certificate_name_revisions.php
php artisan serve --host=127.0.0.1 --port=8000
```

Untuk mencoba di localhost, buka `/sertifikasi` dari Dashboard akun yang sudah memenuhi syarat. Coba Ganti Nama dengan password salah lalu benar, lalu periksa bahwa profil tetap sama. Periksa kedua tab dan konfirmasi nama. Ikuti [langkah QRIS Midtrans Sandbox](midtrans-sandbox-integration.md#mencoba-qris-sandbox). Saat pending, periksa bahwa nama terkunci dan tombol unduh belum muncul. Sesudah settlement, periksa kedua halaman PDF dan pindai QR. Buka URL verifikasi tanpa login untuk memeriksa informasi publik, lalu coba URL unduh dengan akun berbeda untuk memeriksa otorisasi. Gunakan akun Google tertaut untuk mencoba pengaturan password melalui callback yang sudah terdaftar.

### Pengujian upgrade

| Pemeriksaan | Sebelum | Sesudah |
| --- | --- | --- |
| Laravel `php artisan test --compact` | 310 lulus, 53 gagal | 328 lulus, 53 gagal |
| Sertifikasi, Midtrans, dan OAuth dalam suite Laravel | 97 lulus | 115 lulus |
| `node --test tests/js/*.test.js` | 24 lulus | 24 lulus |
| `composer validate` dan `git diff --check` | Lulus | Lulus |

Perbandingan JUnit menunjukkan 53 tes gagal yang sama pada Admin, Dashboard, Kuis, Materi, dan LearningDatabase. Log baseline dan akhir tersedia di `storage/logs/certification-upgrade-before.log` dan `storage/logs/certification-upgrade-final.log`. Pint pada file yang berubah lulus.

Browser memakai SQLite disposable, Snap SDK tiruan, dan respons provider tiruan. QA memeriksa tujuh keadaan pada lebar 320, 360, 390, 768, 1024, dan 1440 piksel. Pemeriksaan mencakup konfirmasi nama, pemulihan setelah password salah, keyboard, pembayaran pending dan retry, penerbitan, refund, unduh PDF lewat HTTP terautentikasi, serta penolakan PDF milik akun lain. Empat proses verifikasi paralel menghasilkan satu sertifikat. Penyimpanan file oleh browser otomatis dibatalkan lingkungannya. OAuth diuji dengan respons Google tiruan, dan alur Google serta merchant Sandbox langsung masih perlu diuji pada target deploy.

PyMuPDF memeriksa ukuran A4 landscape dan teks PDF, termasuk nama Latin beraksen dengan apostrof Unicode, nama panjang dengan spasi, dan satu nama 100 karakter tanpa spasi. Empat PDF baru memiliki dua halaman, dan satu PDF simulasi lama memiliki satu halaman. OpenCV mendekode QR yang tertanam di PDF. Kedua halaman diperiksa secara visual. Cakupan karakter PDF mengikuti font DejaVu Sans yang dibundel Dompdf. Gunakan direktori evidence yang dicetak runner untuk membuka screenshot, PDF, dan `pdf-results.json`.

```powershell
$env:XDEBUG_MODE = 'off'
php artisan test --compact
node --test tests/js/*.test.js
composer validate
git diff --check
node tests/browser/certification.mjs
# Gunakan jalur direktori evidence yang dicetak runner
$evidence = 'storage/framework/testing/<direktori-evidence-browser>'
$env:PYTHONPATH = (Resolve-Path storage/framework/testing/certification-tools).Path
python tests/browser/midtrans-pdf-check.py $evidence
```

Migration diuji pada salinan SQLite sebelum upgrade. Jumlah baris dan hash dari kolom existing cocok di delapan tabel yang diperiksa, meliputi pengguna, sertifikat, order, attempt pembayaran, progres, kuis, Coding, dan Evaluasi Akhir. Kolom baru tetap kosong untuk catatan lama. Pratinjau SQL dan hasil audit berada di `storage/framework/testing/certification-upgrade-migration-1ac3b4ffc6c943048af84305981bd130/`. Verifikasi pada target MariaDB masih tertunda. Penerapan pada database aplikasi, push, dan deploy memerlukan persetujuan.

**Pemeriksaan desain.** ENERGY 2 / RHYTHM 2 / MOTION 1 mengikuti `DESIGN.md`. Mint menandai ringkasan dan status, ruang antarseksi menyatakan urutan kerja, dan garis sampul menjadi bingkai dokumen. Kontrol yang ditambahkan bisa dijangkau lewat keyboard. Kontras teks utama yang diperiksa mencapai 6,11:1 pada tombol, 6,79:1 pada status mint, 5,78:1 pada catatan, dan 9,56:1 pada pesan kesalahan.

### Inventaris perubahan

**File baru**

- `app/Http/Controllers/Auth/GoogleCertificatePasswordController.php` dan `app/Http/Controllers/CertificateIdentityController.php`
- `app/Models/CertificateIdentity.php`
- `app/Services/CertificateIdentityService.php` dan `app/Services/CertificateTranscriptService.php`
- `database/migrations/2026_11_24_000001_add_certificate_identity_and_transcript.php`
- `resources/views/certification/partials/identity.blade.php`, `transcript-summary.blade.php`, dan `transcript-table.blade.php`
- `tests/Feature/CertificateIdentityTest.php`

**File yang diperbarui**

- Controller `Auth/GoogleLoginController.php`, `CertificationController.php`, dan `MidtransPaymentController.php` di `app/Http/Controllers/`
- Model `Certificate.php` dan `CertificatePaymentAttempt.php` di `app/Models/`
- Service `CertificateEligibilityService.php`, `CertificateIssuanceService.php`, `MidtransGateway.php`, dan `MidtransPaymentService.php` di `app/Services/`
- `bootstrap/app.php`, `routes/certification.php`, `routes/payments.php`, dan `routes/web.php`
- View `index.blade.php`, `layout.blade.php`, `pdf.blade.php`, `partials/payment-form.blade.php`, dan `partials/preview.blade.php` di `resources/views/certification/`
- `public/css/oopy/certification.css` dan `public/js/oopy-certification.js`
- Feature test `Auth/GoogleLoginTest.php`, `CertificationTest.php`, dan `MidtransPaymentTest.php` di `tests/Feature/`
- `tests/browser/midtrans.mjs`, `tests/browser/midtrans-sandbox.php`, dan `tests/browser/midtrans-pdf-check.py`
- `README.md`, `docs/certification-system.md`, dan `docs/midtrans-sandbox-integration.md`

## Riwayat implementasi simulasi sebelum upgrade

Bagian berikut menyimpan konfigurasi dan hasil QA tahap simulasi awal. Pembayaran terkini memakai [Midtrans Snap Sandbox](midtrans-sandbox-integration.md), dan endpoint penerbitan simulasi lama mengembalikan HTTP 410.

Tanggal implementasi dan QA: 20 November 2026, WITA. Snapshot awal lokal: `e17363d`. Fitur ini **hanya simulasi**, tanpa payment gateway atau perpindahan uang. Pembelajaran, kuis, Coding, dan Evaluasi Akhir tetap gratis. Sesuai revisi alur, seluruh ketentuan, konfirmasi simulasi, pratinjau, dan download berada pada satu halaman `/sertifikasi`.

## Arsitektur dan audit awal

Modul memakai autentikasi/session Laravel existing. `CertificateEligibilityService` membaca `DashboardProgressService`, `ChapterQuizScoring`, `CodingProgressService`, dan catatan `FinalExamAttempt`. Tidak ada jalur penilaian baru. Route web tetap memakai CSRF, auth, dan throttle existing. Navbar dan layout Blade digunakan bersama halaman lain; CSS sertifikasi hanya dimuat pada halaman modul.

Audit mencakup model User/Chapter/Material/UserProgress/QuizAttempt/ExerciseSubmission/FinalExamAttempt, service progres dan penilaian, migration/FK, layout/navbar, `Admin/UserDeletionService`, suite PHPUnit/JavaScript, serta harness Playwright. Acuan desain: `DESIGN.md`, `MASTER_UX_IMPROVEMENT_PLAN.md`, laporan Dashboard 19 November, audit multi-file, dan audit akademis Evaluasi Akhir 13 November 2026.

Working tree awal bersih. Baseline aktual sebelum integrasi: **205 tes PHP lulus, 54 gagal**, **24 tes JavaScript lulus**, `composer validate` lulus. Angka pada README lama bukan baseline sesi ini. Tes existing banyak mengasumsikan lima soal per kuis dan total 50 soal, sementara katalog saat ini berbeda. Satu tes Dashboard juga memiliki sensitivitas timestamp fixture. Tidak ada soal, rubrik, atau nilai yang diubah untuk meluluskan tes tersebut.

## Kelayakan akademik

Semua syarat wajib terpenuhi dan selalu terikat pada pengguna yang login:

1. Enam BAB inti dari registry Dashboard mempunyai `user_progress.status = completed` dan `completed_at` terisi.
2. Keenam BAB memiliki percobaan kuis resmi selesai dengan skor valid 0–100 dan waktu selesai yang sah. Skor terbaik setiap BAB harus mencapai `ChapterQuizScoring::passingScore()`; konfigurasi saat implementasi adalah **80**. Percobaan `in_progress` dan `expired` tidak dihitung. Progres materi saja tidak cukup.
3. Dua latihan dalam `CodingProgressService::EXERCISES` masing-masing pernah mempunyai submission `passed`. Key saat implementasi: `coding-latihan-1` dan `coding-latihan-2-multifile-v1`. Kelulusan `coding-latihan-2` lama tidak dihitung. Percobaan gagal setelah lulus tidak mencabut kelulusan yang masih sah.
4. Ada `FinalExamAttempt` milik akun, berstatus `completed`, sudah dikumpulkan, waktu valid, dan skor mencapai `passing_score` percobaan tersebut. Ambang existing adalah **70**. `pending_review`, percobaan aktif, serta latihan tamu di session tidak dihitung.

Service mengembalikan jumlah BAB/kuis/Coding, skor terbaik Evaluasi Akhir selesai, status kelulusan, `eligible`, daftar syarat yang belum dipenuhi beserta URL tindakan, dan tanggal penyelesaian. Registry yang belum di-seed tetap menunjukkan persyaratan sebenarnya, tanpa membuat progres atau nilai.

Tanggal penyelesaian adalah waktu ketika seluruh prasyarat telah tercapai: waktu progres BAB, kelulusan kuis pertama per BAB, submission lulus pertama per latihan aktif, serta kelulusan Evaluasi Akhir pertama. Untuk Evaluasi Akhir dengan penilaian manual, waktu penilaian turut diperhitungkan. Mengulang latihan gagal tidak menggeser tanggal ini. Tanggal penerbitan merupakan waktu transaksi penerbitan tersendiri.

## Route

| Metode dan path | Nama route | Akses dan perilaku |
| --- | --- | --- |
| GET `/sertifikasi` | `certification.index` | Publik; tamu melihat informasi dan login, akun melihat kelayakan sendiri |
| POST `/sertifikasi` | `certification.confirm` | Auth, CSRF, throttle 10/menit; konfirmasi simulasi dari form pada halaman yang sama |
| GET `/sertifikasi/{publicId}/pdf` | `certificates.download` | Auth, kepemilikan, sertifikat aktif dan transaksi simulasi selesai |
| GET `/sertifikasi/checkout` | `certification.checkout` | Kompatibilitas tautan lama; kembali ke `/sertifikasi`, bagian penerbitan untuk akun layak atau dokumen untuk akun yang sudah mempunyai sertifikat |
| POST `/sertifikasi/checkout` | `certification.confirm.legacy` | Kompatibilitas request lama; validator, CSRF, throttle, dan service penerbitan yang sama |
| GET `/sertifikat-saya` | `certificates.index` | Auth; redirect ke `/sertifikasi#sertifikat`, tanpa halaman terpisah |
| GET `/sertifikat-saya/{publicId}` | `certificates.show` | Auth dan kepemilikan; redirect ke `/sertifikasi#sertifikat` |
| GET `/sertifikat-saya/{publicId}/pdf` | `certificates.download.legacy` | Alias download lama dengan pemeriksaan kepemilikan yang sama |
| GET `/sertifikat/verifikasi/{publicId}` | `certificates.verify` | Publik, throttle 60/menit; verifikasi tidak mengubah data |

Di bagian paling bawah Dashboard, setelah Aktivitas & Informasi Belajar, hanya ada satu tombol **Ambil Sertifikat**. Tombol membuka `/sertifikasi` dan memakai efek kedalaman/tekan 3D dengan state hover, active, focus, serta reduced motion. Tidak ada menu sertifikasi pada navbar atau dropdown akun. View Sertifikat Saya, view checkout terpisah, dan view detail terpisah telah dihapus; URL lama hanya menjaga kompatibilitas bookmark.

## Database

Migration baru: `database/migrations/2026_11_20_000001_create_certification_tables.php`. Migration hanya membuat dua tabel; tidak mengubah tabel belajar.

| Tabel | Data |
| --- | --- |
| `certificate_orders` | `id`, `user_id`, `program_key`, `order_number`, `amount`, `currency`, `payment_method`, `mode`, `status`, `confirmed_at`, timestamps |
| `certificates` | `id`, `user_id`, `program_key`, `public_id`, `certificate_number`, `participant_name`, `course_title`, `completed_at`, `issued_at`, `order_id`, `status`, timestamps |

Kedua tabel memiliki unique constraint `(user_id, program_key)`. Nomor order, nomor sertifikat, dan UUID publik masing-masing unik; satu order hanya boleh terhubung ke satu sertifikat. Nomor sertifikat berawalan `OOPY-SIM-` diikuti 96 bit acak, sedangkan public ID adalah UUID acak. Program saat ini `oopy-python-oop-v1`.

Satu pengguna/program menyimpan satu sertifikat termasuk bila dibatalkan. Reissue sertifikat dibatalkan memerlukan kebijakan baru yang eksplisit, sehingga checkout ulang mengembalikan 409. Status sertifikat yang digunakan adalah `active` dan `revoked`; order memakai `pending` atau `simulation_completed`. Tidak ada endpoint browser untuk mengubah status tersebut.

FK pengguna memakai cascade; FK order pada sertifikat memakai restrict. `Admin/UserDeletionService` secara eksplisit menghapus sertifikat lalu order di dalam transaksi penghapusan akun yang sudah ada. Ini menghindari konflik FK serta menghapus nama yang dipublikasikan oleh halaman verifikasi. URL sertifikat akun yang dihapus menjadi 404. Modul tidak menyimpan file PDF permanen yang memerlukan pembersihan terpisah. Rollback migration menolak penghapusan tabel jika masih berisi catatan.

## Checkout dan penerbitan

1. GET `/sertifikasi` membaca kelayakan dan sertifikat akun. Form simulasi hanya ditampilkan bila akun layak dan belum mempunyai sertifikat. Identitas peserta berasal dari akun, bukan form.
2. Pengguna memilih E-Wallet, Virtual Account, QR Payment, atau Kartu Debit/Kredit. Semua pilihan hanya representasi simulasi; tidak ada input nomor kartu, rekening, PIN, OTP, atau SDK gateway.
3. Dialog menjelaskan simulasi Rp24.999. Checkbox persetujuan wajib diterima, baik oleh browser maupun validator backend. Tombol mempunyai loading state dan menolak submit ulang selama request berjalan.
4. Backend memvalidasi metode dan persetujuan. `amount`, `user_id`, `status`, `payment_status`, `mode`, dan `program_key` dari request ditolak. Nilai transaksi selalu **24999 IDR**, biaya layanan nol.
5. `CertificateIssuanceService` membuka transaksi, mengunci baris pengguna, memeriksa sertifikat existing, lalu mengevaluasi kembali kelayakan sebelum membuat data baru. Pola lock akun selaras dengan submit kuis dan penghapusan akun existing. Database unique constraint menjadi lapisan terakhir terhadap duplikasi. SQLite memakai retry transaksi; MariaDB memakai row lock.
6. Order simulasi dan satu sertifikat dibuat atomik. Order pending yang sudah ada dipakai kembali. Kegagalan insert sertifikat me-rollback order. Retry sertifikat aktif mengembalikan sertifikat yang sama tanpa mengubah nama, nomor, metode order, atau tanggal.
7. Pengguna kembali ke `/sertifikasi#sertifikat` dengan pesan **Simulasi Selesai**, tanpa klaim pembayaran bank. Form penerbitan berubah menjadi ringkasan sertifikat dan tombol download. Pratinjau, copy tautan, dan verifikasi tersedia di halaman yang sama.

Halaman menampilkan empat kelompok persyaratan dari catatan akun sendiri. Rincian syarat yang belum terpenuhi tersedia dalam disclosure tertutup agar daftar panjang tidak mendominasi halaman. Tidak ada statistik/filter sertifikat dekoratif. Pratinjau berlabel contoh sampai sertifikat akun benar-benar diterbitkan. Pada mobile, persyaratan diikuti area penerbitan/download lalu pratinjau; desktop menggunakan dua kolom dengan area dokumen di kiri dan aksi penerbitan di kanan.

## PDF dan QR

Dependensi produksi baru: `dompdf/dompdf` 3.1.6 dan `bacon/bacon-qr-code` 3.1.1, beserta lima dependensi transitif. Persyaratan ekstensi `ext-gd` dinyatakan pada Composer untuk renderer QR PNG. DOM, mbstring, iconv, serta GD tersedia pada PHP lokal. Composer mempertahankan seluruh versi dependensi existing; hanya tujuh paket baru ditambahkan ke lockfile.

Alasan: Dompdf menghasilkan PDF langsung dari Blade tanpa browser/service eksternal; BaconQrCode menghasilkan QR lokal. Keduanya sesuai PHP 8.2 proyek. Rujukan kompatibilitas dan API: [Dompdf](https://github.com/dompdf/dompdf), [BaconQrCode](https://github.com/Bacon/BaconQrCode).

PDF menggunakan A4 landscape, logo resmi `public/images/oopy-logo.png`, identitas mint–teal, nama snapshot saat penerbitan, nomor, tanggal dalam WITA, penerbit OOPy Learning Platform, dan label **SIMULASI / DEMO**. DejaVu Sans lokal digunakan agar PDF tidak bergantung pada font jaringan; UI tetap memakai Plus Jakarta Sans existing. Ukuran nama menyesuaikan panjangnya. Tidak ada tanda tangan atau klaim akreditasi.

QR berisi URL `certificates.verify`, tanpa mengirim nama atau data lainnya ke layanan QR pihak ketiga. Pastikan `APP_URL` pada lingkungan yang menjalankan aplikasi sudah benar dan dapat dijangkau pemindai. URL loopback pada fixture hanya untuk pengujian lokal.

PDF dibangkitkan saat download, tidak disimpan dalam `public/`. Dompdf menonaktifkan remote resource, PHP, dan JavaScript. Direktori cache/font/temp dibatasi ke `storage/framework/cache/certificates`; cache itu tidak berisi sertifikat permanen. Akses resource lokal dibatasi ke direktori gambar dan cache.

## Privasi dan keamanan

- Route privat memakai auth dan query kepemilikan berdasarkan session. Public ID yang benar pun tidak membuka pratinjau privat/PDF milik akun lain.
- Halaman akun, respons checkout, PDF, dan error dari handler sertifikasi menggunakan `Cache-Control: no-store, private` serta `nosniff`.
- Verifikasi publik hanya menampilkan nama peserta, program, nomor, tanggal terbit, penerbit, dan status simulasi/validitas. Tidak menampilkan email, ID internal, source Coding, snapshot soal, order privat, atau session.
- Verifikasi memakai `no-store`, `noindex, nofollow`, dan `no-referrer`. UUID yang tidak ditemukan menghasilkan halaman 404 aman; sertifikat revoked menampilkan Dibatalkan.
- Nama di-escape oleh Blade pada halaman dan PDF. Parameter query tidak dapat memilih akun atau menaikkan kelayakan.
- Tidak ada credential gateway, webhook, rekening pembayaran nyata, log input pembayaran, atau redirect berdasarkan URL dari pengguna.

## Menjalankan pada instalasi lokal

Pada implementasi awal, migration belum dijalankan terhadap database aplikasi. Saat memperbaiki error tabel belum tersedia pada 20 November 2026, migration khusus sertifikasi sudah diterapkan pada database development lokal setelah memeriksa `APP_ENV=local`, koneksi MySQL loopback, status pending, dan preview SQL. Hanya `certificate_orders` dan `certificates` yang ditambahkan; jumlah catatan pengguna dan pembelajaran existing tetap sama. Kedua halaman kemudian diperiksa melalui HTTP kernel dan mengembalikan 200. Tidak ada fixture atau sertifikat yang dibuat pada database aplikasi. Pada instalasi lain, tinjau dan backup database tujuan sebelum menerapkan migration. Jangan menjalankan `migrate:fresh`, `db:wipe`, atau reseed pada database pengguna untuk mencoba fitur.

```powershell
composer install
php artisan migrate --pretend --path=database/migrations/2026_11_20_000001_create_certification_tables.php
# Setelah meninjau SQL pada database lokal tujuan:
php artisan migrate --path=database/migrations/2026_11_20_000001_create_certification_tables.php
php artisan serve
```

Pastikan katalog pembelajaran existing sudah tersedia. Pengguna baru membuka `/sertifikasi`, login/registrasi melalui alur lama, lalu melihat syarat yang belum terpenuhi. Semua tautan persyaratan mengarah ke materi/kuis, dua latihan aktif, atau Evaluasi Akhir. Nilai akun tidak boleh dipalsukan untuk membuka checkout.

Pengguna membuka Dashboard lalu memilih **Ambil Sertifikat**. Jika belum layak, ringkasan dan rincian persyaratan menjelaskan tindakan berikutnya. Jika layak, pengguna memilih metode simulasi pada halaman yang sama, memilih Ambil Sertifikat, mencentang persetujuan dalam dialog, lalu memilih Ya, Selesaikan Simulasi. Sesudah penerbitan, halaman yang sama menampilkan sertifikat akun, status transaksi, tombol **Unduh Sertifikat PDF**, copy tautan, dan verifikasi. Kembali melalui Dashboard untuk mengunduh ulang tanpa checkout ulang.

## Pengujian dan evidence

Tes fitur memakai SQLite `:memory:` yang dipaksa oleh `phpunit.xml`, bukan database `.env`. `Tests\Support\CertificationFixture` hanya dipakai suite dan sandbox browser; fixture menuntaskan kuis dan Evaluasi Akhir melalui service penilaian resmi. Submission Coding fixture berada hanya pada database disposable, tanpa mengubah grader.

```powershell
$env:XDEBUG_MODE = 'off'
php artisan test --compact --filter=CertificationTest
php artisan test --compact
node --test tests/js/*.test.js
composer validate
git diff --check
```

Browser memerlukan Playwright, Microsoft Edge/Chrome, Python/OpenCV untuk decoding QR, serta PyMuPDF untuk pemeriksaan/render PDF. Ini dependensi QA, bukan dependensi runtime aplikasi. Untuk parser terisolasi:

```powershell
python -m pip install --target storage/framework/testing/certification-tools pymupdf
node tests/browser/certification.mjs
```

Harness membuat direktori `storage/framework/testing/certification-<acak>/` baru dan database SQLite baru; tidak boleh diarahkan ke database aplikasi. Router `certification-sandbox.php` memaksakan lokasi tersebut, menolak overwrite fixture, dan menggunakan CSRF web sungguhan. Pengujian membuat screenshot pada 320, 360, 390, 768, 1024, dan 1440px, menguji checkout, modal/keyboard, consent, empty state, copy/panduan, verifikasi, IDOR, dan empat worker PHP bersamaan.

`OOPY_ASSET_CACHE` dapat menunjuk cache Bootstrap/font QA existing, atau `OOPY_ASSET_MANIFEST` memakai pemetaan aset existing. Default harness pada workspace ini adalah `storage/framework/testing/auth-verification`. Cache tidak membuktikan ketersediaan CDN publik. Browser dapat dipilih melalui `OOPY_BROWSER`.

Browser otomatis di lingkungan sesi ini membatalkan download file, juga saat menggunakan Chrome dan eksekusi di luar sandbox. GET PDF melalui HTTP terautentikasi tetap menghasilkan PDF valid. Karena itu percobaan akhir memakai fallback QA eksplisit:

```powershell
$env:OOPY_PDF_HTTP_FALLBACK = '1'
node tests/browser/certification.mjs
```

Fallback tetap mengklik tautan, mencatat pembatalan sebagai **NOT TESTED untuk penyimpanan file oleh browser**, lalu menyimpan respons HTTP terautentikasi untuk diperiksa parser independen. Fallback tidak mengubah kode aplikasi dan tidak melabeli penyimpanan browser sebagai PASS.

Evidence browser alur satu halaman: `storage/framework/testing/certification-e72f25935137a31db33f717521f97dfc/`. `results.json` mencatat lima state halaman pada enam lebar layar, tombol Dashboard 3D, redirect tautan lama, checkout inline, konfirmasi keyboard, satu keterbatasan penyimpanan browser, serta hasil empat worker. `pdf-results.json`, empat PDF, teks ekstraksi, PNG hasil render, screenshot halaman, dan QR PNG tersimpan pada direktori yang sama. Direktori ini diabaikan Git dan hanya berisi akun fixture.

## Hasil QA aktual dan batasan

| Pemeriksaan yang dijalankan | Hasil aktual |
| --- | --- |
| Baseline PHP sebelum integrasi | 205 PASS, 54 FAIL, 4103 assertions |
| PHP sesudah revisi satu halaman | 245 PASS, 54 FAIL, 4394 assertions; exit 1 karena kegagalan existing |
| Tes sertifikasi pada suite final | 40 PASS; tes fitur terfokus 289 assertions, tanpa kegagalan sertifikasi |
| Auth | 45 PASS |
| Coding 1 / Coding multi-file | 16 PASS / 6 PASS |
| Halaman Evaluasi Akhir / audit akademis | 4 PASS / 3 PASS |
| Admin | 64 PASS, 14 FAIL existing |
| Dashboard | 13 PASS, 14 FAIL existing |
| Kuis | 19 PASS, 22 FAIL existing |
| Materi / LearningDatabase | 12 PASS + 2 FAIL / 8 PASS + 2 FAIL existing |
| Live Coding | 13 PASS |
| JavaScript `node --test tests/js/*.test.js` | 24 PASS sebelum dan sesudah perubahan |
| Browser sertifikasi satu halaman | PASS alur/CSRF/modal/consent/IDOR/QR/30 kombinasi screenshot state-lebar, satu tombol 3D Dashboard, redirect bookmark, dan download inline; NOT TESTED penyimpanan file browser otomatis |
| Browser navbar Dashboard existing | PASS satu komponen pada Beranda, Materi, Coding, Dashboard; delapan lebar, dropdown, collapse, dialog logout dan pembatalan |
| Konkurensi nyata SQLite | PASS empat proses PHP terpisah mengembalikan public ID sama untuk satu akun/program |
| Parser/render PDF independen | PASS empat file, masing-masing 1 halaman 841.89 × 595.28 pt; teks, gambar, nama panjang dan batas halaman valid |
| `composer validate`, Pint `--dirty --test`, sintaks JS, `git diff --check` | PASS |
| `composer audit` | FAIL: 11 advisori existing pada tujuh paket, tidak mengenai paket PDF/QR baru |
| Database development lokal, tindak lanjut error tabel belum tersedia | PASS migration tambahan melalui driver MySQL; `/sertifikasi` dan `/sertifikat-saya` HTTP 200; jumlah catatan existing tetap sama |
| Konkurensi MariaDB dan database production | NOT TESTED / tidak dimigrasi |

Log awal: `storage/logs/certification-baseline-php.log` dan `certification-baseline-js.log`. Regresi revisi satu halaman: `storage/logs/certification-unified-regression.log` dan `certification-unified-regression.xml`; fitur terfokus: `certification-unified-features.log`. Kumpulan tes gagal existing tetap sama; tes statistik Dashboard yang sama kadang berhenti pada validasi timestamp fixture dan kadang pada ekspektasi nilai lama. Tidak ada kegagalan fitur baru pada suite final.

Delivery Gate antislop untuk perubahan UI:

- **Hard Gate PASS:** 30 kombinasi state/lebar tanpa overflow, kontrol menuju route/aksi nyata, empty/error/loading state tersedia, contoh diberi label, dan keyboard/modal diuji. Frasa mode simulasi mempertahankan tanda baca yang diminta pengguna.
- **Purpose Gate PASS:** mint membedakan pemberitahuan simulasi, panel harga memisahkan keputusan penerbitan, border sertifikat menyatakan format dokumen, shadow dialog menandai elevasi, dan kedalaman tombol 3D memberi umpan balik tekan sesuai permintaan pengguna. Logo existing dipertahankan.
- **Liveliness PASS:** ENERGY 2 / RHYTHM 2 / MOTION 1 dari DESIGN.md; fokus utama status kelayakan/checkout, ritme daftar persyaratan dan preview dokumen berbeda sesuai konten, identitas OOP Python tetap terlihat.
- **Craftsmanship PASS:** data berasal dari akun, font UI existing, CSS terisolasi, route navigasi teruji, dan pasangan kontras teks diuji dengan checker antislop-human: 6.92:1 (tombol disabled), 5.78:1 (teks pendukung/kanvas), 6.11:1 (putih/tombol), 6.79:1 (status selesai/mint). Batasan pengujian unduh browser tetap dilaporkan terpisah, tanpa klaim PASS. Komentar baru yang menarasikan kode tidak ditambahkan; komentar tentang aturan akademik, concurrency, dan data historis dipertahankan.

Keterbatasan yang sudah diketahui:

- Penyimpanan file lewat browser otomatis dibatalkan lingkungan pengujian; unduh HTTP dan isi PDF sudah diuji terpisah.
- MariaDB production dan balapan transaksi pada MariaDB belum diuji. Skema memakai Schema Builder/FK Laravel; tes konkurensi aktual memakai empat proses PHP terpisah pada SQLite.
- Paket dependensi existing mempunyai 11 advisori pada tujuh paket: Laravel framework, league/commonmark, dan lima paket Symfony. `composer audit` gagal atas advisori tersebut; tidak ada advisori yang dilaporkan untuk paket PDF/QR baru. Tidak ada versi paket existing yang dinaikkan diam-diam. Pembaruan dependensi existing perlu pekerjaan terpisah.
- Verifikasi publik sengaja menerbitkan nama peserta kepada pemegang URL. Halaman checkout dan panduan menjelaskannya. PDF yang sudah disimpan pengguna tidak bisa ditarik dari perangkat; halaman verifikasi menunjukkan status terbaru.
- Tidak ada UI Admin baru untuk revoke/reissue. Status revoked didukung model, endpoint, dan tes; operasi administrasinya memerlukan kebijakan tersendiri.
- Satu sertifikat dipertahankan per akun/program, termasuk setelah revoked. Nama terbit berupa snapshot; perubahan nama akun tidak mengubah dokumen lama.

## Arah integrasi pembayaran nyata

Implementasi ini tidak memproses pembayaran nyata. Integrasi kelak harus memakai mode dan kebijakan penerbitan terpisah dari simulasi: provider reference, idempotency key provider, callback/webhook bertanda tangan, verifikasi nominal/mata uang di server, status pending/settled/failed/refunded, serta audit perubahan status. Jangan memperlakukan `simulation_completed` sebagai pembayaran nyata dan jangan menerima status pembayaran dari browser. Tinjau ulang kebijakan harga, persetujuan publikasi nama, refund, pencabutan, dan reissue sebelum mengaktifkan provider. Unique constraint dan transaksi penerbitan tetap harus dipertahankan.

## Inventaris perubahan

File baru:

- `app/Http/Controllers/CertificationController.php`
- `app/Http/Middleware/CertificateNoStore.php`
- `app/Models/Certificate.php`, `app/Models/CertificateOrder.php`
- `app/Services/CertificateEligibilityService.php`, `CertificateIssuanceService.php`, `CertificatePdfService.php`
- `database/migrations/2026_11_20_000001_create_certification_tables.php`
- `routes/certification.php`
- `public/css/oopy/certification.css`, `public/js/oopy-certification.js`
- `resources/views/certification/layout.blade.php`, `index.blade.php`, `verify.blade.php`, `pdf.blade.php`, `partials/preview.blade.php`, `partials/payment-form.blade.php`. View checkout, daftar Sertifikat Saya, dan detail terpisah telah dihapus pada revisi satu halaman.
- `tests/Feature/CertificationTest.php`, `tests/Support/CertificationFixture.php`
- `tests/browser/certification-sandbox.php`, `certification.mjs`, `certification-pdf-check.py`
- `docs/certification-system.md`

File existing yang dimodifikasi: `routes/web.php`, `resources/views/dashboard/index.blade.php`, `public/css/oopy/dashboard/dashboard.css`, `app/Services/Admin/UserDeletionService.php`, `composer.json`, `composer.lock`, `README.md`, dan `docs/README.md`. Navbar sempat mendapat tautan sertifikasi pada implementasi awal; tautan itu sudah dihapus sesuai revisi navigasi.

Database development lokal mendapat dua tabel sertifikasi dan catatan migration saat tindak lanjut error tabel belum tersedia. Tabel/data pembelajaran existing, `.env`, autentikasi/OAuth, Coding Grader, key latihan, rubrik, soal/materi, nilai kuis/Evaluasi Akhir, dan riwayat pengguna tidak diubah. Integrasi Admin hanya menambahkan pembersihan sertifikat/order pada proses penghapusan akun yang sudah ada. Tidak ada perubahan database production, commit, push, merge, atau deployment.
