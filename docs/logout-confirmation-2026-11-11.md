# Konfirmasi logout OOPy

Tanggal: 11 November 2026.

## Audit sebelum perubahan

Logout ditemukan pada tiga formulir: dropdown akun navbar, sidebar Dashboard User (`dashboard/partials/navigation.blade.php`), dan bagian bawah sidebar Admin. Ketiganya memakai `method="POST"`, `route('logout')`, dan CSRF. Navbar tetap memuat nama, role, Dashboard, serta Panel Admin untuk administrator.

`AuthenticatedSessionController::destroy()` melakukan logout guard `web`, invalidasi sesi, regenerasi token CSRF, dan redirect ke route `home`. Route POST logout dilindungi middleware `auth`. Controller, route, middleware, Socialite, konfigurasi sesi, dan database aplikasi tidak diubah.

Audit juga mencakup kedua layout, `public/js/admin/admin.js`, `public/js/oopy-auth.js`, `public/js/oopy-dashboard.js`, CSS base/layout/navbar/Admin, tes autentikasi, seluruh referensi logout, dan `DESIGN.md`. Admin tidak memuat Bootstrap JS; sidebar Admin dan Dashboard User sama-sama memiliki handler Escape. Halaman materi menggunakan navigasi pembelajaran sendiri tanpa tombol logout.

## Implementasi dan penggunaan

Satu komponen Blade `<x-logout-confirmation />` dirender pada tingkat layout, di luar navbar/sidebar. `@once` mencegah dialog dan script ganda. Layout umum merender komponen hanya untuk akun terautentikasi. Dialog memakai native `showModal()`, sehingga Admin tidak bergantung pada Bootstrap JS.

Semua formulir asli diberi `data-logout-confirmation`. JavaScript mencegat event submit, termasuk aktivasi tombol dengan Enter. Pembukaan modal tidak mengirim request logout.

- User: buka akun di navbar lalu pilih **Logout**, atau pilih **Keluar** di sidebar Dashboard.
- Admin: pilih tombol merah **Logout** di bagian bawah sidebar; pada layar kecil buka **Menu** terlebih dahulu.
- **Batal** dan **Escape** menutup dialog, mempertahankan sesi, dan mengembalikan fokus.
- **Ya, Logout** menonaktifkan kedua tombol, lalu menjalankan formulir asli tepat sekali dengan `HTMLFormElement.prototype.submit.call(form)`. Pemanggilan ini melewati interceptor submit sehingga tidak terjadi rekursi. POST dan token CSRF tetap berasal dari form semula.

Tidak ada endpoint, request API, library, migration, atau mekanisme autentikasi baru. Tidak ada commit atau push.

## Keputusan desain

Design Read: dialog keputusan untuk pelajar dan administrator di ruang belajar OOPy, dengan bahasa visual mint–teal yang tenang. ENERGY 2 / RHYTHM 2 / MOTION 1 mengikuti `DESIGN.md`.

| Keputusan | Alasan |
| --- | --- |
| Plus Jakarta Sans existing | Menjaga kesinambungan tipografi dengan navigasi dan konten. |
| Permukaan putih, garis teal, overlay berwarna ink | Memusatkan perhatian pada keputusan dan mempertahankan identitas OOPy. |
| Batal netral, konfirmasi `--oopy-danger` | Membedakan tindakan aman dan tindakan keluar secara jelas. |
| Token radius, spacing, border, shadow existing | Menyamakan ukuran dan elevasi User/Admin tanpa merombak layout. |
| Lebar maksimal 30rem, margin 16px, tombol dapat membungkus | Menjaga keterbacaan pada desktop dan reflow pada 320px serta pembesaran teks. |
| Tanpa animasi baru atau dekorasi | Keputusan logout tidak membutuhkan gerakan atau ilustrasi tambahan. |

## Aksesibilitas dan ketahanan

- `aria-labelledby` menghubungkan judul; `aria-describedby` menghubungkan deskripsi.
- Fokus awal pada Batal; native modal membuat konten belakang inert dan membatasi navigasi fokus.
- Tab/Shift+Tab, Enter, Escape, dan indikator fokus terlihat diuji di browser.
- Escape tetap menggunakan pembatalan native. Handler capture menghentikan propagasinya agar sidebar dan dropdown tidak ikut menerima Escape tersebut.
- Ketika batal dari navbar, dropdown dibuka kembali menggunakan instance Bootstrap yang tersedia agar tombol pemicu kembali terlihat dan dapat difokuskan.
- Jika perubahan viewport menutup sidebar saat dialog terbuka, fokus pembatalan kembali ke tombol Menu yang terlihat.
- Listener dipasang sekali. Script yang dimuat ulang tidak memasang interceptor tambahan. Event close lama tidak menghapus state dialog yang telah dibuka kembali.
- Klik berulang dan konfirmasi berulang tidak mengirim POST tambahan. `pageshow` memulihkan kontrol ketika dokumen dikembalikan dari cache browser.
- Tidak ada transisi baru; preferensi reduced motion tetap terpenuhi.

Kontras teks modal yang diukur oleh browser: judul sekitar 11,93:1, deskripsi/Batal sekitar 8,38:1, dan rasio minimum seluruh 90 pemeriksaan teks sebesar 7,50:1, termasuk hover. Seluruh 15 kombinasi navigasi/viewport lulus dan tidak ada error JavaScript. Semua pasangan aktual dan hasil layout tersedia pada [results.json](evidence/logout-confirmation-2026-11-11/results.json).

## Fallback

Jika JavaScript dinonaktifkan, script logout gagal dimuat, atau `showModal()` tidak tersedia, form POST asli langsung logout tanpa modal. Jika `showModal()` melempar exception saat dibuka, event submit tidak dibatalkan.

Aturan `noscript` pada layout umum menampilkan navigasi dan menu akun agar tombol logout navbar tetap bisa dijangkau tanpa JavaScript, termasuk pada 320px. Sidebar Dashboard dan Admin tetap menyediakan form asli. Fallback ini sengaja tidak menampilkan konfirmasi; validasi CSRF dan middleware backend tetap berlaku.

Dokumen yang sudah kedaluwarsa dapat menerima HTTP 419 saat konfirmasi. Ini adalah respons validasi existing; modal tidak mengganti atau melewati pemeriksaan sesi/CSRF.

## File yang dibuat atau diubah

| File | Perubahan |
| --- | --- |
| `resources/views/components/logout-confirmation.blade.php` | Komponen native dialog dan satu pemuatan script. |
| `public/js/oopy-logout.js` | Interceptor, pembatalan, fokus, proteksi submit, dan fallback. |
| `public/css/oopy/logout-confirmation.css` | Desain bersama, reflow, hover, dan fokus. |
| `resources/views/components/navbar.blade.php` | Penanda pada form logout asli. |
| `resources/views/dashboard/partials/navigation.blade.php` | Penanda pada form Keluar Dashboard User. |
| `resources/views/admin/partials/sidebar.blade.php` | Penanda pada form logout merah Admin. |
| `resources/views/layouts/app.blade.php` | CSS, komponen untuk akun login, dan navigasi tanpa JS. |
| `resources/views/admin/layouts/app.blade.php` | CSS dan komponen bersama. |
| `tests/Feature/Auth/LogoutTest.php` | Tambahan pemeriksaan rendering, POST langsung Admin, dan akses setelah logout. Assertion lama dipertahankan. |
| `tests/browser/logout-confirmation.mjs` | Pengujian browser baru untuk tiga navigasi dan kondisi khusus. |
| `tests/browser/quiz-helpers.mjs` | Helper logout mengikuti langkah konfirmasi. |
| `tests/browser/home.mjs` | Logout navbar melewati konfirmasi. |
| `tests/browser/admin-coding.mjs` | Logout Admin melewati konfirmasi. |
| `tests/browser/additions.mjs` | Logout keyboard Admin melewati konfirmasi. |
| `docs/logout-confirmation-2026-11-11.md` | Audit, hasil, batasan, dan petunjuk pengujian. |
| `docs/evidence/logout-confirmation-2026-11-11/` | Screenshot modal serta laporan QA. |

## Lingkungan pengujian

PHPUnit memakai konfigurasi existing `sqlite :memory:` dengan `force=true`. Browser memakai router `tests/browser/admin-sandbox.php` dan dua file SQLite QA baru dengan nama `admin-coding-<uuid>.sqlite` di `storage/framework/testing`. Router menetapkan database dan cookie sesi QA sendiri sebelum bootstrap. Seeder/migrasi hanya berjalan pada database QA tersebut, bukan database aplikasi. `.env` tidak diubah.

Playwright memakai Microsoft Edge headless. Dependensi CDN tidak dapat dimuat langsung di lingkungan QA; pengujian yang memerlukan Bootstrap/font/runtime memakai cache aset QA existing. Tidak ada dependensi baru yang diinstal.

Regresi `additions.mjs` dijalankan pada server QA dengan `GOOGLE_CLIENT_ID=(null)` di lingkungan proses agar skenario OAuth belum dikonfigurasi dapat diuji. Ini tidak mengubah `.env` atau konfigurasi aplikasi di disk. Percobaan awal dengan konfigurasi OAuth existing berhenti menunggu pesan OAuth belum tersedia; pengulangan dengan kondisi QA yang sesuai lulus.

## Hasil PHP dan pemeriksaan statis

| Perintah | Hasil |
| --- | --- |
| `php artisan test --compact` | PASS: 239 tes, 4.584 assertion. |
| `php artisan test --compact --filter=LogoutTest` | PASS: 6 tes, 114 assertion setelah formatting dan perubahan fallback. |
| `node --test tests/js/*.test.js` | PASS: 23 tes. |
| `php artisan route:list --except-vendor` | PASS: 50 route; logout tetap hanya POST. |
| `composer validate` | PASS. |
| PHP lint pada file PHP/Blade yang disentuh | PASS. |
| Laravel Pint pada `tests/Feature/Auth/LogoutTest.php` | PASS setelah formatting. |
| `node --check` pada script baru/yang diubah | PASS. |
| `git diff --check` | PASS. |

Suite PHP mencakup Login, Logout, GoogleLogin dengan mock, role/otorisasi, registrasi, Dashboard User/Admin, materi, kuis/bobot dinamis, evaluasi, Coding 1/2, progres, filtering/CSV, manajemen pengguna/materi, publikasi dan penghapusan soal. Tes CSRF existing tetap dijalankan dengan verifikasi middleware sesungguhnya; tes logout menguji invalidasi sesi, rotasi token, dan replay cookie lama.

Percobaan awal tes rendering baru gagal 503 karena fixture konten belum diseed; diperbaiki dengan seeder pada tes baru. Suite penuh setelah perbaikan lulus. Tidak ada assertion lama yang dilonggarkan.

## Hasil browser

| Script | Hasil |
| --- | --- |
| `logout-confirmation.mjs` | PASS: navbar, Dashboard User, Admin pada 320/390/768/1024/1440px; sesi tetap aktif saat dialog terbuka/Batal/Escape; fokus, keyboard, kontras, overflow, refresh, klik berulang, satu POST, akses terproteksi, fallback, CSRF, dan dokumen kedaluwarsa. |
| `home.mjs` | PASS: tautan, akun, logout melalui modal, keyboard, reduced motion, dan lima viewport. |
| `dashboard.mjs` | PASS: nilai/progres nyata, enam BAB, hasil terbaik, isolasi akun, nama panjang, logout/login, dan pembacaan tanpa JS. |
| `admin-coding.mjs` | PASS pada fixture tersendiri: Dashboard, pengguna, detail, rekap, filter gabungan, CSV, akses role, lima viewport, dan logout melalui modal. |
| `foundation.mjs` | PASS: BAB 1–7, Coding 1/2, editor, login/registrasi, dashboard, kontras, keyboard, Monaco/Python Stop, kuis, dan alur evaluasi. |
| `dynamic-quiz.mjs` | PASS: draft/publikasi, tab usang, snapshot percobaan, retry paket berbeda, nilai pecahan, filter/CSV, serta pemisahan Evaluasi Akhir. |
| `additions.mjs` | PASS: materi responsif, pesan Google belum dikonfigurasi, kontrol merah Admin, perlindungan soal aktif, pembatalan/penghapusan draft soal, konfirmasi email penghapusan pengguna, pencabutan sesi pengguna terhapus, dan logout keyboard Admin melalui modal. |
| `evaluasi.mjs` | FAIL: baris 110 mengharapkan timer awal `40:00`, aktual `39:59`. Tes berhenti pada assertion tersebut; skenario setelahnya dalam script ini NOT TESTED. |

Percobaan awal browser tanpa cache CDN gagal pada tampilan navbar yang kehilangan Bootstrap. Pengulangan dengan cache aset lulus. Percobaan awal `admin-coding.mjs` pada database yang dipakai uji registrasi gagal jumlah baris 4 vs 3; pengulangan pada fixture baru tersendiri lulus. Kedua percobaan ini tidak diatasi dengan mengubah assertion.

`evaluasi.mjs` menjalankan evaluasi sebagai guest, ketika komponen/CSS logout tidak dirender. Hasil ini menunjukkan masalah assertion waktu pada alur evaluasi; penyebab pastinya belum diselesaikan dalam pekerjaan antarmuka logout. Timer dan penilaian evaluasi tidak diubah. Suite PHP dan skenario evaluasi pada `foundation.mjs` tetap lulus.

## Screenshot

| Navigasi | Desktop 1440px | Mobile 390px |
| --- | --- | --- |
| Navbar User | [Lihat](evidence/logout-confirmation-2026-11-11/navbar-1440.png) | [Lihat](evidence/logout-confirmation-2026-11-11/navbar-390.png) |
| Dashboard User | [Lihat](evidence/logout-confirmation-2026-11-11/dashboard-1440.png) | [Lihat](evidence/logout-confirmation-2026-11-11/dashboard-390.png) |
| Sidebar Admin | [Lihat](evidence/logout-confirmation-2026-11-11/admin-1440.png) | [Lihat](evidence/logout-confirmation-2026-11-11/admin-390.png) |

## Gate antislop untuk modal

- PASS, Hard Gate: 15 kombinasi navigasi/viewport tanpa overflow, semua kontrol modal berfungsi, keyboard dan kontras diuji, teks mengikuti permintaan tanpa klaim buatan.
- PASS, Purpose Gate: alasan warna, font, ukuran, ruang, dan elevasi tercatat; tidak ada dekorasi atau animasi tambahan.
- PASS, Liveliness: ENERGY 2 / RHYTHM 2 / MOTION 1 mengikuti OOPy; hierarki judul, penjelasan, dan keputusan jelas.
- PASS, Craftsmanship: satu komponen/CSS/handler untuk semua navigasi; state pembatalan, submit, fallback, pembesaran teks, sidebar, dan pemuatan script ulang diperiksa di browser.

## Batasan dan risiko tersisa

- Assertion timer awal pada script evaluasi lama masih gagal seperti dilaporkan di atas; regresi browser keseluruhan tidak dinyatakan seluruhnya lulus.
- Google OAuth langsung: NOT TESTED, memerlukan akun/kredensial eksternal. Pengujian mock Google pada suite PHP lulus.
- Screen reader fisik, browser Safari/Firefox, dan perangkat sentuh fisik: NOT TESTED. Pemeriksaan ini memakai DOM/keyboard dan emulasi viewport Edge.
- Tanpa JavaScript atau dukungan native dialog, logout langsung memakai POST tanpa konfirmasi, sesuai strategi fallback.
- Tidak ada perubahan database aplikasi, `.env`, controller autentikasi, route, mekanisme penilaian, atau fitur pembelajaran.

## Menjalankan ulang QA modal

Di PowerShell, gunakan dua terminal dengan nilai `OOPY_ADMIN_TEST_DATABASE` dan `OOPY_BASE_URL` yang sama:

```powershell
$env:XDEBUG_MODE = 'off'
$env:OOPY_ADMIN_TEST_DATABASE = 'admin-coding-' + [guid]::NewGuid().ToString('N') + '.sqlite'
$env:OOPY_BASE_URL = 'http://127.0.0.1:8061'
php tests/browser/admin-sandbox.php
php -d display_errors=0 -S 127.0.0.1:8061 -t public tests/browser/admin-sandbox.php
```

Terminal kedua:

```powershell
# Salin nama database QA dan URL dari terminal pertama.
$env:OOPY_ADMIN_TEST_DATABASE = 'admin-coding-<uuid-dari-terminal-pertama>.sqlite'
$env:OOPY_BASE_URL = 'http://127.0.0.1:8061'
$env:OOPY_SCREENSHOT_DIR = 'storage/framework/testing/logout-confirmation-review'
# Opsional jika CDN tidak tersedia: OOPY_ASSET_MANIFEST menunjuk JSON URL -> file cache aset.
node tests/browser/logout-confirmation.mjs
```

Gunakan fixture tersendiri saat menjalankan tes Admin yang mengharapkan jumlah akun tertentu. Jangan arahkan browser QA ke database aplikasi utama.
