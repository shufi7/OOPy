# Tahap 3: hasil Coding pada Panel Admin OOPy

5 November 2026, WITA. Baseline: `e905f84e837e6dab43e4f653242c8a1314636dd2` (`progress coding`). Working tree bersih saat audit dimulai; implementasi Tahap 2 sudah tersedia. Panduan antislop digunakan dalam mode **during**, melanjutkan pilihan pengguna dalam sesi ini.

**A. Ringkasan implementasi**

- `/admin`: lima statistik Coding berdasarkan pengguna unik ber-role `user` dan maksimal sepuluh aktivitas Coding terbaru, dengan nama pengguna, latihan, skor, status submission, waktu WITA, dan tautan detail.
- `/admin/users/{user}`: progres dua latihan, nilai terbaik, status, total percobaan, waktu terakhir, progress bar aksesibel, dan riwayat sepuluh submission per halaman.
- `/admin/scores`: nilai/status Coding dikelompokkan per latihan, lalu persentase/jumlah latihan selesai. BAB 1–6, Evaluasi Akhir, rata-rata, dan status kuis dipertahankan.
- Filter Coding terpisah melalui `coding_exercise` dan `coding_status`; filter kuis, Coding, dan pencarian digabung dengan AND sebelum pagination. Pengurutan Coding mendukung aktivitas terbaru/nilai terbaik dengan NULL terakhir dan ID pengguna sebagai tie-breaker.
- CSV mempertahankan 15 kolom lama dan menambahkan enam kolom Coding di akhir. Filter, pengurutan, BOM UTF-8, delimiter `;`, quoting, formula injection defense, dan nilai nol terverifikasi.
- `CodingProgressService` menjadi sumber bersama untuk agregasi, status, nilai, persentase, dan riwayat Admin serta Dashboard Pengguna. Tampilan Dashboard Pengguna tidak diubah.

Hanya `coding-latihan-1` dan `coding-latihan-2` yang dihitung. Selesai berarti pernah memiliki `passed`, termasuk jika retry berikutnya gagal/error. Nilai terbaik berasal dari skor sah 0–100 pada submission `passed`/`failed`/`error`; NULL dan status awal `submitted` belum menjadi nilai resmi. Skor tinggi tanpa `passed` tidak dianggap lulus. Label awal Admin **Belum Dikerjakan** dan Dashboard Pengguna **Belum Dimulai** memiliki definisi data yang sama.

Untuk filter **Semua latihan**, Selesai berarti kedua latihan selesai; Belum Lulus berarti sudah mencoba tetapi belum menyelesaikan keduanya; Belum Dikerjakan berarti belum ada submission pada latihan mana pun. Statistik Dashboard **belum lulus latihan mana pun** menghitung kondisi yang lebih spesifik, yakni sudah mencoba dan belum menyelesaikan satu latihan pun. Perilaku ini dijelaskan di UI dan dokumentasi.

**B. File yang diubah**

| File | Tujuan |
| --- | --- |
| [CodingProgressService.php](../app/Services/CodingProgressService.php), baru | Definisi dua latihan, skor valid, agregat per pengguna, ringkasan, dan riwayat bersama |
| [DashboardProgressService.php](../app/Services/DashboardProgressService.php) | Memakai service Coding bersama, mempertahankan properti dan perhitungan BAB/kuis sebelumnya |
| [LearningReportService.php](../app/Services/Admin/LearningReportService.php) | Join agregat Coding, filter/sort, statistik unik, aktivitas terbaru, dan pembacaan katalog tanpa output whitespace |
| [ReportRequest.php](../app/Http/Requests/Admin/ReportRequest.php) | Allowlist latihan, status Coding, dan dua opsi sort baru |
| [UserController.php](../app/Http/Controllers/Admin/UserController.php) | Pagination riwayat Coding dengan `coding_page`, terpisah dari kuis/evaluasi |
| [ScoreController.php](../app/Http/Controllers/Admin/ScoreController.php) | Menambahkan enam kolom CSV tanpa query tambahan per baris |
| [admin/dashboard/index.blade.php](../resources/views/admin/dashboard/index.blade.php) | Ringkasan dan aktivitas Coding lintas pengguna |
| [admin/users/show.blade.php](../resources/views/admin/users/show.blade.php) | Tabel progres, progress bar, dan riwayat Coding pengguna |
| [admin/scores/index.blade.php](../resources/views/admin/scores/index.blade.php) | Filter terpisah, kolom Coding ringkas, dan URL ekspor dengan filter aktif |
| [admin.css](../public/css/oopy/admin/admin.css) | Status, progress bar, dan lebar tabel dengan scroll lokal; header Coding tetap terbaca |
| [ReportsTest.php](../tests/Feature/Admin/ReportsTest.php) | Menambahkan pemeriksaan statistik/empty state Coding pada tes lama |
| [CodingReportsTest.php](../tests/Feature/Admin/CodingReportsTest.php), baru | 22 kasus baru termasuk data provider, konsistensi akun, filter, CSV, pagination, validasi, dan jumlah query |
| [admin-sandbox.php](../tests/browser/admin-sandbox.php) | Mendukung nama SQLite QA unik dan fixture Coding terpisah dari database aplikasi |
| [admin-coding.mjs](../tests/browser/admin-coding.mjs), baru | QA terarah lima halaman, viewport, kontras, navigasi, pagination, filter, unduhan CSV, dan akses |
| [ADMIN_PANEL.md](ADMIN_PANEL.md) | Menambahkan bagian I tentang penggunaan, definisi, keamanan, performa, CSV, dan pengujian Coding |
| Dokumen ini dan [bukti](evidence/admin-coding-2026-11-05/results.json) | Laporan hasil aktual, screenshot, log baseline/akhir, dan verifikasi MariaDB |

Route, middleware role, login, sidebar, katalog/rubrik latihan, controller submit, grader Docker, Monaco, Pyodide, materi, dan migration tidak diubah.

**C. Perubahan database**

**Tidak ada migration atau perubahan skema database proyek.** Tabel dan indeks existing mencukupi. `.env` dan data pengguna/submission proyek tidak diubah atau dihapus.

Feature tests memakai SQLite `:memory:`. QA browser menggunakan SQLite baru `admin-coding-<UUID>.sqlite` di `storage/framework/testing/`; seluruh akun dan submission contoh hanya berada di database QA tersebut. Untuk memeriksa kompatibilitas SQL, database MariaDB baru `oopy_admin_coding_test_<UUID>` dibuat, diisi fixture, diverifikasi, lalu dihapus. Pemeriksaan ini tidak menjalankan migration pada database proyek.

**D. Hasil pengujian**

| Perintah/pemeriksaan | Hasil aktual |
| --- | --- |
| Baseline `php artisan test --compact` sebelum perubahan aplikasi | **171 lulus, 2 gagal**, 3570 assertions, 180,13 detik; [log](evidence/admin-coding-2026-11-05/baseline.txt) |
| `php artisan test --filter=ReportsTest` pada hasil akhir | **27 lulus**, 472 assertions, 0 gagal; [log](evidence/admin-coding-2026-11-05/reports-test.txt) |
| `php artisan test --filter=DashboardTest` setelah ekstraksi service | **27 lulus**, 314 assertions, 0 gagal; [log](evidence/admin-coding-2026-11-05/dashboard-test.txt) |
| `php artisan test --compact` pada hasil akhir | **193 lulus, 2 gagal**, 3854 assertions, 248,68 detik; [log](evidence/admin-coding-2026-11-05/php-suite.txt) |
| `php artisan route:list --path=admin` | **21 route**, grup Admin existing; [hasil](evidence/admin-coding-2026-11-05/admin-routes.txt) |
| `node --test tests/js/runtime-manager.test.js tests/js/material-checkers.test.js tests/js/final-exam.test.js tests/js/coding-checkers.test.js` | **21 lulus**, 0 gagal; [log](evidence/admin-coding-2026-11-05/javascript-tests.txt) |
| `node tests/browser/admin-coding.mjs` | **PASS: 25 sampel layout**, 209 pengukuran kontras, lima kelompok interaksi, 0 page errors; [hasil](evidence/admin-coding-2026-11-05/results.json) |
| Query pada MariaDB 10.4.32 dengan database QA terpisah | **PASS**: migration/seeder, ringkasan bersama, konsistensi Admin/pengguna, filter, sort, statistik, pagination; [hasil](evidence/admin-coding-2026-11-05/mariadb.json) |
| `vendor/bin/pint --test` pada sembilan file PHP yang diubah/ditambahkan | PASS |
| `node --check tests/browser/admin-coding.mjs` dan `git diff --check` | PASS |
| QA lama `python tests/browser/admin-cdp.py --browser <Chrome/Edge> --base http://127.0.0.1:8041` | **Tidak selesai**: startup/koneksi DevTools gagal sebelum pemeriksaan aplikasi; bukan PASS |

Dua kegagalan full suite sama pada baseline dan hasil akhir:

1. `LearningDatabaseTest.php:276`: ekspektasi rollback memakai jumlah tetap 12 migration sehingga kolom role belum hilang. Tidak diubah dalam Tahap 3.
2. `MateriTest.php:23`: pencocokan literal `<span>Pelajari BAB` gagal terhadap markup/whitespace existing. Tidak diubah dalam Tahap 3.

Tidak ada kegagalan baru pada tes Coding Admin setelah perbaikan selesai. Percobaan awal tes baru memperbaiki assertion tipe integer dan selector pagination yang memilih tombol responsif tersembunyi; keduanya merupakan masalah tes, bukan perubahan aturan data.

Browser menemukan masalah CSV existing yang tidak terlihat dalam assertion `streamedContent()`: empat file data materi memiliki whitespace sebelum `<?php`, sehingga pembacaan katalog mengirim byte sebelum BOM. Output tersebut kini ditahan hanya di batas pembacaan katalog laporan. Assertion backend memeriksa ketiadaan preamble, dan unduhan browser memeriksa BOM sebagai tiga byte pertama. File konten materi tidak diedit.

**E. Pengujian manual dan bukti visual**

1. Login sebagai Admin lalu buka `/admin`. Cocokkan jumlah pengguna unik yang mencoba, lulus tiap latihan, lulus keduanya, dan belum lulus satu pun. Akun Admin serta latihan materi tidak boleh masuk statistik Coding.
2. Buka nama pada Aktivitas Coding Terbaru. Periksa bahwa detail pengguna yang benar terbuka, nilai terbaik bertahan setelah retry lebih rendah, status penyelesaian tetap Selesai setelah gagal, dan waktu menggunakan WITA.
3. Pindah halaman Riwayat Pengerjaan Coding. Parameter `coding_page` harus bekerja tanpa mengubah halaman kuis/evaluasi yang sedang dipilih. Tabel tidak berisi source code/output.
4. Buka Rekap Nilai. Pilih latihan/status Coding, gabungkan dengan filter BAB/status kuis dan pencarian nama/email, lalu klik Terapkan. Kombinasi yang tidak cocok harus menghasilkan empty state.
5. Gunakan pengurutan Coding terbaru/tertinggi, lalu Export CSV. Pastikan 15 kolom lama tetap berurutan, enam kolom baru tersedia, hasil mengikuti filter/urutan, nilai 0 tetap 0, dan nama berawalan formula diberi perlindungan.
6. Gunakan Reset dan navigasi keyboard. Tabel lebar dapat digeser dalam region yang dapat difokuskan, tanpa horizontal overflow halaman. Pada ponsel, menu Admin tetap memakai perilaku existing.
7. Login sebagai mahasiswa yang diperiksa. Cocokkan nilai/status/progres pada `/dashboard`. Akses langsung mahasiswa ke laporan/ekspor Admin harus 403; guest diarahkan ke login.

QA browser terarah menjalankan alur-alur di atas dengan fixture resmi yang terisolasi: mahasiswa memiliki Latihan 1 = 90/Selesai dan Latihan 2 = 70/Belum Lulus, progres 50%; mahasiswa lain memiliki skor 0/Error; akun kosong memiliki progres 0%. Riwayat 23 percobaan diuji sampai halaman ketiga. Unduhan CSV sungguhan diperiksa, termasuk perlindungan nama fixture `=SUM(1;2)`.

| Halaman | Ponsel 390px | Desktop 1440px |
| --- | --- | --- |
| Dashboard Admin | [Screenshot](evidence/admin-coding-2026-11-05/dashboard-390.png) | [Screenshot](evidence/admin-coding-2026-11-05/dashboard-1440.png) |
| Detail Coding | [Screenshot](evidence/admin-coding-2026-11-05/student-390.png) | [Screenshot](evidence/admin-coding-2026-11-05/student-1440.png) |
| Rekap Nilai | [Screenshot](evidence/admin-coding-2026-11-05/scores-390.png) | [Screenshot](evidence/admin-coding-2026-11-05/scores-1440.png) |
| Detail tanpa Coding | [Screenshot](evidence/admin-coding-2026-11-05/empty-student-390.png) | [Screenshot](evidence/admin-coding-2026-11-05/empty-student-1440.png) |
| Daftar pengguna | [Screenshot](evidence/admin-coding-2026-11-05/users-390.png) | [Screenshot](evidence/admin-coding-2026-11-05/users-1440.png) |

**F. Keamanan dan performa**

Grup `auth`, `role:admin`, dan middleware no-store/nosniff dipertahankan. Tes otorisasi lama tetap memeriksa seluruh endpoint Admin, ditambah pemeriksaan khusus laporan Coding. Detail hanya menerima pengguna ber-role `user`. Dashboard Pengguna selalu memakai parameter pengguna sesi, sedangkan Admin memakai pengguna pada route yang sudah diotorisasi. Tidak ada endpoint publik atau form perubahan nilai baru.

Input filter/sort divalidasi dengan allowlist. Nilai latihan dalam agregat memakai parameter binding; nama kolom sort berasal dari pemetaan internal, bukan SQL mentah dari browser. Teks/nama/status di Blade tetap di-escape. CSV melewati `csvCell()` existing dan `fputcsv()` dengan konfigurasi lama. Password, token grader, source code, dan output lengkap tidak masuk select laporan Coding atau hasil ekspor.

Agregat per pengguna di-left-join ke query rekap. Tidak memanggil service per pengguna dalam cursor ekspor. Query log membuktikan:

- Satu query untuk cursor 25 pengguna beserta ringkasan Coding.
- Dua query untuk ekspor, termasuk pembacaan daftar BAB; tidak ada query tambahan per baris.
- Empat query Coding untuk detail dengan 26 submission: agregat, lima terbaru yang dipertahankan oleh kontrak Dashboard, count pagination, dan sepuluh riwayat pada halaman terpilih.
- Dashboard Pengguna tetap memakai delapan query baca keseluruhan, sama dengan Tahap 2.

Aktivitas lintas pengguna mengambil maksimal sepuluh baris dan eager-load hanya identitas/nama pemilik. Riwayat memakai kolom terpilih dan pagination database. Query CASE/MAX/COUNT/COALESCE dan join agregat terbukti berjalan pada SQLite maupun MariaDB lokal. Ini bukan hasil uji beban produksi.

**G. Masalah tersisa dan batas verifikasi**

- Dua tes baseline di bagian D masih gagal. Tidak menghapus atau melonggarkan tes tersebut untuk menghijaukan suite.
- QA CDP lama dicoba dengan Chrome dan Edge. Chrome tidak menyediakan endpoint debug selama percobaan startup; Edge memutus koneksi saat `Page.enable` dengan WinError 10054. Tidak ada hasil PASS untuk alur lama CDP. Suite Playwright Coding Admin terarah dan tes fitur Admin selesai; tidak mengklaim seluruh alur publikasi materi/soal pada browser telah dijalankan ulang.
- Browser terarah memakai Edge headless dan cache aset CDN dari URL asli. Yang diuji adalah render/interaksi aset tersebut, bukan ketersediaan CDN publik saat ini. Belum diuji pada perangkat fisik, Safari/Firefox, screen reader, atau zoom toolbar browser 200%.
- Tidak menjalankan ulang penilaian Docker end-to-end; rubrik, sandbox, dan submit tidak berubah. Hasil sukses Coding pada browser merupakan fixture database QA yang dinyatakan, bukan klaim hasil grader produksi.
- Peringatan `Module "openssl" is already loaded` berasal dari konfigurasi PHP lingkungan dan tetap terlihat pada CLI. Tidak diubah dalam tugas ini.
- Tidak menjalankan build Vite karena Blade menyajikan CSS `public/` secara langsung; hasilnya sudah dijalankan pada browser.

**H. Status Git**

`git status --short` mencatat 12 file tracked yang dimodifikasi, tiga file kode/tes baru (`CodingProgressService.php`, `CodingReportsTest.php`, `admin-coding.mjs`), laporan ini, dan direktori bukti. `git diff --stat` untuk file tracked mencatat **12 files changed, 256 insertions(+), 93 deletions(-)**. File baru yang masih untracked tidak masuk angka stat tersebut; semuanya sengaja dibiarkan untuk ditinjau tanpa staging.

Tidak ada commit, push, merge, atau deployment otomatis. Tidak ada perubahan `.env` atau migration. Database/sesi/view-cache/snapshot QA berada pada direktori testing yang diabaikan Git; database MariaDB sementara sudah dihapus. Server PHP QA dihentikan setelah seluruh verifikasi.

**Arah desain dan Delivery Gate antislop**

Design Read: ruang pengelolaan akademik OOP Python untuk dosen, tenang dan fungsional dengan token mint–teal, tabel/status yang mengikuti Admin existing, **ENERGY 2 / RHYTHM 2 / MOTION 1**. Tidak merombak sidebar, navigasi, atau halaman pembelajaran.

| Keputusan | Alasan |
| --- | --- |
| Token dan Plus Jakarta Sans existing | Mempertahankan identitas OOPy dan kontras antarmuka |
| Lima angka ringkasan dan tabel aktivitas | Memisahkan jumlah pengguna unik dari hasil setiap submission |
| Satu kolom per latihan pada rekap | Menyatukan nilai/status tanpa menambah lima kolom datar yang sulit dibandingkan |
| Scroll lokal pada tabel | Menjaga lebar halaman dan keterbacaan metadata pada ponsel |
| Progress bar berlabel dan status berteks | Penyelesaian dipahami tanpa bergantung pada warna |
| Tanpa grafik, ikon, aset, atau animasi baru | Menjaga perhatian pada rekap akademik dan tindak lanjut pengguna |

Gate berikut **PASS untuk tambahan UI Coding Admin pada cakupan dan kondisi yang diuji**; bukan sertifikasi seluruh aplikasi atau QA CDP lama.

| Hard Gate | Bukti |
| --- | --- |
| R-02 PASS | Judul latihan, tanda nilai kosong, dan header CSV mengikuti teks yang diminta pengguna |
| R-03 PASS | 25 layout pada 360/390/768/1024/1440px tanpa overflow halaman; tabel mempunyai scroll lokal |
| R-17 PASS | Statistik/nilai berasal dari database, bukan angka dummy produksi |
| R-18 PASS | Tidak menambah testimoni |
| R-23 PASS | Logo/navigasi dipertahankan, tanpa aset identitas baru |
| R-24 PASS | Link aktivitas/detail, reset, dan ekspor memakai route nyata dan diuji |
| R-25 PASS | 209 pengukuran kontras, rasio terendah 5,48:1 pada sampel yang diperiksa |
| R-26 PASS | Filter, pagination, link pengguna, dan unduhan CSV dijalankan di browser |
| R-27 PASS | Empty state akun/hasil filter/aktivitas tersedia; render server tidak menambah state async baru |
| R-28 PASS | Tidak menambah FAQ |
| R-32 PASS | Label select, focus-visible, perpindahan Tab, dan keyboard scroll region diuji |
| R-33 PASS | Source ditulis langsung; script QA hanya memakai fixture/database terisolasi |
| R-34 PASS | Tema terang existing, tanpa toggle tema yang tidak selesai |
| R-35 PASS | Aplikasi dijalankan; 25 layout dan lima kelompok interaksi tercatat; screenshot ditinjau |
| R-36 PASS | Tidak menambah klaim produksi/keamanan/performa tanpa batas bukti |
| R-37 PASS | Arah mengikuti DESIGN dan mode during yang telah dipilih pengguna |
| R-38 PASS | Fixture ditandai sebagai QA dan tidak disajikan sebagai data proyek sebenarnya |

| Purpose-Gate | Bukti |
| --- | --- |
| R-01 PASS | Tidak menambah gradient/glow |
| R-04 PASS | Tidak menambah ikon generik |
| R-06 PASS | Font/skala mengikuti sistem Admin untuk kesinambungan |
| R-07 PASS | Tidak menambah pola latar |
| R-08 PASS | Tidak menambah panah dekoratif pada kontrol baru |
| R-09 PASS | Badge menyatakan status akademik aktual, bukan promosi |
| R-10 PASS | Tidak menambah glassmorphism |
| R-12 PASS | Tabel/angka memakai komponen existing tanpa elevasi bertumpuk |
| R-13 PASS | Tidak menambah glow |
| R-14 PASS | Angka setara mewakili hitungan setara; detail memakai tabel dan progress bar sesuai fungsi |
| R-19 PASS | Tidak menambah motion; browser memakai reduced motion |
| R-22 PASS | Tidak menambah ilustrasi |

| Liveliness | Bukti |
| --- | --- |
| Dial PASS | ENERGY 2 / RHYTHM 2 / MOTION 1 eksplisit dan konsisten dengan Admin existing |
| Konsistensi PASS | Hierarki judul, angka, progres, dan tabel berbeda menurut fungsi |
| Fokus PASS | Judul/rekap akademik tetap menjadi fokus setiap halaman |
| Whitespace PASS | Section terpisah melalui heading dan jarak existing |
| Aksen PASS | Teal untuk aksi/progres; warna status memiliki arti akademik |
| Identitas PASS | BAB, latihan Class/Object/Inheritance, dan riwayat submission khusus OOPy |
| Design Read PASS | Arah dinyatakan sebelum implementasi UI dan tercatat pada laporan |

| Craftsmanship dan Quality Locks | Bukti |
| --- | --- |
| C-1 PASS | Alasan font, warna, komposisi, kolom, scroll, dan progres tertulis |
| C-2 PASS | Kontrol baru memakai endpoint/perilaku yang diuji |
| C-3 PASS | Section memenuhi kebutuhan pemantauan dan rekap dalam brief |
| C-4 PASS | Kosong/terisi/error, lima viewport, pagination, dan keyboard terverifikasi dalam cakupan baru |
| C-5 PASS | Angka produksi dari DB; batas fixture dan pengujian dicantumkan |
| R-05 PASS | Memperluas Admin existing, tanpa template landing page/grafik dekoratif |
| R-11 PASS | Radius panel, kontrol, dan status berbeda menurut fungsi |
| R-15 PASS | Terapkan, Reset, Export CSV, dan link pengguna memiliki tujuan konkret |
| R-16 PASS | Copy menjelaskan latihan, nilai, status, dan filter tanpa buzzword |
| R-20 PASS | Isi spesifik pemantauan praktikum OOP Python |
| R-21 PASS | Tema terang mengikuti DESIGN |
| R-29 PASS | Palet memakai token netral/mint–teal dan warna status existing |
| R-30 PASS | Mengikuti komponen OOPy, tidak meniru produk lain |
| R-31 PASS | Setiap keputusan visual utama memiliki alasan pada tabel |
