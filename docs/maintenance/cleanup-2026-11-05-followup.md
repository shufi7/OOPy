# Cleanup lanjutan kode bawaan OOPy

5 November 2026, WITA. Melanjutkan audit file/kode yang tidak dipakai setelah [cleanup dokumentasi dan evidence](cleanup-2026-11-05.md). Pengguna meminta pembersihan setelah daftar kandidat disampaikan. Laporan dan JSON cleanup pertama tetap merupakan snapshot tahap tersebut; laporan ini mencatat perubahan tambahan.

## Perubahan dan bukti

| File | Tindakan | Bukti dan batas dampak |
| --- | --- | --- |
| `app/Http/Controllers/HomeController.php` | Hapus import `Illuminate\Http\Request` | Identifier tidak dipakai oleh controller. Method `index()` tetap mengembalikan `home.index`; controller tidak dihapus. |
| `resources/views/welcome.blade.php` | Hapus template bawaan Laravel, 82.540 byte | Tidak ada route/controller, include, extends, registrasi view alternatif, atau import aplikasi yang memilih template ini pada audit. Route beranda menggunakan `HomeController@index` dan `home.index`. View Evaluasi Akhir yang dipilih secara dinamis tetap dipertahankan. |
| `routes/console.php` | Hapus blok command contoh `inspire` dan dua import terkait | Command contoh tidak terkait alur pembelajaran atau administrasi. File tetap ada karena direferensikan bootstrap Laravel. Perubahan CLI yang disengaja: `php artisan inspire` tidak lagi tersedia; command promosi admin tetap terdaftar. |
| `docs/README.md` | Tambahkan tautan laporan lanjutan | Panduan tim dapat menemukan alasan, batas perubahan, dan hasil verifikasi terbaru. |

Struktur standar Laravel tetap utuh. Tidak ada pemindahan controller, service, model, migration, route HTTP, atau view aktif. Penghapusan template dan import mengurangi isi file existing sebesar 82.774 byte sebelum penambahan laporan/navigasi dokumentasi.

## Kode yang tetap dipertahankan

- `public/js/evaluasi/exam-storage.js`: tidak diimpor halaman Evaluasi Akhir saat ini, tetapi masih dipakai `tests/js/final-exam.test.js`.
- Bagian penilaian/penyimpanan lokal pada `exam-model.js`: terhubung dengan modul lama dan tes aktif. File yang sama masih menyediakan `hasAnswer`, `remainingSeconds`, dan `formatSeconds` bagi halaman ujian. Tidak menghapus algoritma atau menyesuaikan tes untuk memaksakan cleanup.
- `tests/Unit/ExampleTest.php`: manfaat pengujiannya kecil, tetapi masih masuk suite aktif. `tests/Feature/ExampleTest.php` tetap memeriksa respons halaman utama.
- Entry Vite, Tailwind, Axios, lockfile, dan dependensi lokal: tetap terhubung dalam konfigurasi build.
- Policy kuis, komponen Live Coding, dan command `oopy:promote-admin`: ditemukan/dimuat oleh mekanisme Laravel, bukan kode yatim.
- Relasi `Material::userProgress()`: dipakai tes database dan merupakan bagian antarmuka model existing.
- Seluruh source Dashboard, Admin, agregasi Coding, grading, autentikasi, materi, kuis, Evaluasi Akhir, serta aset aktif.
- Semua file database, `.env`, `DESIGN.md`, runtime cache/log existing, dan bukti historis yang sebelumnya dikategorikan REVIEW.

Perubahan source dibatasi pada dua penghapusan import/blok CLI di atas. Tidak ada perubahan test aktif, schema, data pengguna, konfigurasi privat, dependensi, atau tampilan halaman OOPy.

## Verifikasi

Baseline langsung sebelum cleanup lanjutan adalah hasil sebelumnya: PHP 193 lulus dan 2 gagal (3854 assertions), JavaScript 21 lulus, 44 route HTTP. Snapshot hash file existing dan daftar route/command diambil ulang sebelum perubahan; tidak mengulang suite penuh hanya untuk mengambil baseline yang sama.

| Pemeriksaan | Hasil sesudah cleanup lanjutan |
| --- | --- |
| `php artisan test --compact` | 193 lulus, 2 kegagalan baseline, 3854 assertions; 152,82 detik; tidak ada kegagalan baru |
| `node --test tests/js/*.test.js` | 21 lulus, 0 gagal |
| `php artisan route:list --except-vendor --json` | 44 route; seluruh definisi identik sebelum/sesudah |
| `php artisan list --raw` | Hanya `inspire` dihapus; tidak ada command baru, promosi admin tetap tersedia |
| `php -l` controller dan route console | Lulus |
| `composer validate` | Valid; peringatan OpenSSL ganda dari lingkungan |
| `git diff --check` | Bersih |
| Target tautan lokal Markdown/HTML | 355 referensi; 0 target file hilang |
| Integritas file di luar daftar cleanup lanjutan | 508 file identik; tidak ada perubahan di luar daftar yang diizinkan |
| Konfigurasi privat dan database | 9 file pada snapshot privat lanjutan identik; 10 file database snapshot cleanup pertama juga tetap identik |

Tes penuh tetap menjalankan pemeriksaan halaman utama, autentikasi, materi, kuis, Evaluasi Akhir, Coding, Dashboard, laporan Admin, dan export CSV. Tes JavaScript mencakup modul Evaluasi Akhir lama yang dipertahankan. Tidak menambahkan tes yang hanya meniru penghapusan import/template.

Regresi browser Coding/Dashboard/Admin sudah dijalankan sebelum cleanup lanjutan pada tahap sebelumnya. Tidak diklaim sebagai pengujian browser baru pada tahap ini; source dan aset ketiga fitur tersebut harus tetap identik pada pemeriksaan hash. Jalur Docker grading sukses dan MariaDB existing juga tidak dijalankan ulang.

Dua kegagalan PHP baseline pada `LearningDatabaseTest.php:276` (jumlah rollback migration tetap) dan `MateriTest.php:23` (literal markup/whitespace) tetap dipisahkan dari hasil cleanup. Tidak mengubah tes atau UI untuk menyembunyikan kegagalan lama.

Pemeriksaan Anti-Slop untuk cakupan cleanup ini: PASS, seluruh tindakan memiliki bukti audit; PASS, 355 tautan lokal memiliki target; PASS, tidak membuat UI, aset, atau klaim fitur baru. Dua kegagalan tes baseline tetap dilaporkan.

Log lokal dan snapshot audit lanjutan tersedia di `storage/framework/testing/cleanup-2026-11-05/followup/`, yang diabaikan Git. Metadata/hash konfigurasi privat tidak disalin ke laporan publik.

## Status dan batas

| Pengukuran working tree | Sebelum lanjutan | Sesudah lanjutan |
| --- | ---: | ---: |
| File tracked yang masih ada | 506 | 505 |
| Byte file tracked yang masih ada | 31.560.858 | 31.478.084 |
| Dokumen baru belum tracked, kumulatif | 6 | 7 |
| Total file usulan, termasuk dokumen baru | 512 | 512 |

Jumlah usulan tetap karena satu template dihapus dan satu laporan ditambahkan. Index Git masih 544 file karena tidak melakukan staging. Secara kumulatif dari commit baseline `94d85fe`, statusnya 10 file tracked dimodifikasi, 39 dihapus (38 PNG sebelumnya dan satu template), serta 7 dokumen baru. `git diff --stat` untuk file existing: 49 file berubah, 99 baris ditambahkan, 335 baris dihapus; dokumen baru belum masuk statistik diff.

Perubahan cleanup sebelumnya dipertahankan. Tidak ada staging, commit, push, merge, uninstall/update dependensi, pengosongan cache/log, atau penghapusan database. Ukuran Git history tidak diklaim berkurang. Kandidat kode Evaluasi Akhir lama membutuhkan peninjauan cakupan tes sebelum pembersihan berikutnya; seluruhnya tetap tersedia pada akhir tahap ini.
