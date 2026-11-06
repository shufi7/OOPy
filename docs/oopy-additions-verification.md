# Verifikasi tambahan OOPy, 6 November 2026

Empat perubahan diterapkan pada layout materi, autentikasi Google, penghapusan pada panel Admin, dan warna Logout. Tidak ada commit atau push otomatis.

## Perubahan dan batas perilaku

- Materi: container desktop memakai lebar viewport dengan margin 24px; jarak sidebar dan artikel menjadi 24px. Pada layar kecil, daftar isi tetap berada di atas artikel. Batas panjang paragraf dipertahankan agar tetap mudah dibaca.
- Google: tombol tersedia pada Masuk dan Daftar. Laravel Socialite menangani OAuth dengan `state` dan PKCE. Identitas Google disimpan pada kolom nullable `users.google_id` yang unik. Panduan aktivasi ada di [google-login.md](google-login.md).
- Bank Soal: Hapus membuka halaman konfirmasi. Draft/arsip yang tidak digunakan dapat dihapus permanen. Soal aktif, soal yang dipakai percobaan/jawaban kuis, dan sumber revisi lain tetap dilindungi agar paket kuis serta riwayat tidak rusak. Snapshot Evaluasi Akhir menyimpan kontennya sendiri.
- Pengguna: Hapus hanya tersedia untuk akun pembelajar. Konfirmasi harus berisi email akun target. Akun, progres, percobaan/jawaban kuis, Coding, Evaluasi Akhir, token reset password, dan sesi database milik akun tersebut dihapus dalam satu transaksi. Akun Admin dilindungi. Pengguna lain tetap utuh.
- Logout Admin memakai token merah `--oopy-danger`, dengan state hover dan fokus yang tetap terbaca.

## Hasil eksekusi

| Pemeriksaan | Hasil |
| --- | --- |
| `php artisan test --compact` | PASS: 216 tes, 4.127 assertion, tanpa kegagalan atau skip |
| Tes penghapusan setelah penyesuaian pesan validasi | PASS: 9 tes, 87 assertion |
| Empat file JavaScript regression | PASS: 21 tes, tanpa skip |
| `tests/browser/admin-coding.mjs` | PASS: statistik, riwayat, filter, pagination, CSV, otorisasi, dan 25 pemeriksaan layout |
| `tests/browser/additions.mjs` | PASS: layout materi, fallback Google, Hapus soal, Hapus pengguna, sesi lama, dan Logout lewat keyboard |
| Pemeriksaan target konfirmasi tambahan | PASS: label checkbox minimal 44px pada desktop/ponsel; submit berhasil |
| PHP lint dan Laravel Pint pada file PHP yang berubah | PASS |
| `composer validate --strict` | PASS |
| `git diff --check` dan syntax JavaScript browser test | PASS |
| Route | PASS: 44 route lama tetap tersedia dengan middleware semula; 6 route baru, total 50 |

Tes OAuth menggunakan implementasi Socialite dengan respons HTTP Google terisolasi. State palsu, callback berulang, pembatalan, profil tidak lengkap, email belum terverifikasi, kegagalan Google, benturan identitas, role, password existing, regenerasi sesi, dan redirect eksternal diperiksa. Penghapusan diuji terhadap tamu, pengguna biasa, akun Admin, CSRF, serta rollback transaksi ketika database menolak penghapusan.

Pengujian PHP memakai SQLite `:memory:`. Browser memakai fixture baru `admin-coding-*.sqlite`, bukan database aplikasi. Hash `.env` dan file database existing sama dengan baseline. Migration Google hanya dijalankan pada database pengujian; aktivasi pada database aplikasi masih perlu dilakukan sesuai panduan.

## Delivery Gate UI

- Hard Gate: PASS untuk perubahan ini. Browser memeriksa lebar 320, 390, 768, 1024, 1440, dan 1920px; tidak ada overflow halaman. Tautan, konfirmasi, pembatalan, submit, error, dan logout dijalankan. Screenshot sebelum/sesudah tersedia pada artefak QA lokal.
- Purpose Gate: PASS. Pelebaran memberi ruang pada materi/kode; sidebar dekat tepi membantu navigasi. Merah membedakan penghapusan dan Logout. Tombol Google menjadi alternatif autentikasi sekunder. Tidak ada aset visual baru.
- Identitas dan ritme: PASS. Mengikuti DESIGN.md dengan ENERGY 2 / RHYTHM 2 / MOTION 1; mempertahankan logo, tipografi, mint/teal, hierarki BAB, serta pola artikel OOPy.
- Aksesibilitas dan kualitas: PASS untuk kontrol yang berubah. Kontras teks tombol Google, Hapus, dan Logout dihitung di browser dan memenuhi 4,5:1. Fokus keyboard, Enter, label formulir, target konfirmasi 44px, dan scroll lokal tabel diverifikasi.

Artefak screenshot, pengukuran, dan hasil JSON disimpan secara lokal di `storage/framework/testing/additions-20261106-*`. Log PHP tersedia di `storage/logs/additions-full-php.log`.

## Batas verifikasi dan temuan dependensi

Login langsung ke Google: NOT TESTED karena Client ID/Client Secret belum tersedia. Isi environment dan jalankan migration tambahan mengikuti panduan Google, kemudian uji dengan akun nyata. Login password tetap tersedia selama konfigurasi Google belum lengkap.

`composer audit --locked` menemukan 10 advisory pada enam dependensi existing: `league/commonmark`, `symfony/http-foundation`, `symfony/http-kernel`, `symfony/mime`, `symfony/routing`, dan `symfony/yaml`. Versi keenam paket identik dengan baseline. Tidak ada advisory pada enam paket baru yang dipasang untuk Socialite. Dependensi lama tidak diperbarui dalam pekerjaan ini; temuan ini memerlukan penanganan terpisah. Detail eksekusi tersedia di `storage/logs/additions-composer-audit.json`.
