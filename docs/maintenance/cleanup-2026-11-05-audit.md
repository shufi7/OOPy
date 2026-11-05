# Audit struktur dan cleanup OOPy

Audit 5 November 2026 (WITA), sebelum perubahan dokumentasi dan deduplikasi. Baseline Git `94d85fe` (`progress coding di admin`), working tree bersih. Tahap 2 dan 3 sudah tersimpan di workspace. Laporan hasil akhir: [cleanup-2026-11-05.md](cleanup-2026-11-05.md).

**Metode dan batas audit**

Seluruh direktori, termasuk `.git`, file tersembunyi, `vendor`, `node_modules`, dan `storage`, dienumerasi tanpa mengikuti symlink/junction. Setiap file yang dapat dibaca dihitung ukuran dan SHA-256-nya: **16.410 file, 523.269.080 byte**, tanpa error baca atau symlink/junction yang ditemukan. Pengukuran workspace merupakan snapshot lokal; log/cache dan artefak QA dapat berubah selama pengujian.

Git melacak **544 file, 36.902.622 byte** pada awal audit. SHA-256 penuh dipakai untuk identifikasi duplikat, bukan nama atau kemiripan visual. Ada **51 kelompok duplikat tracked**: 50 kelompok PNG dan satu kelompok `.gitignore`. Inventaris lengkap lokal disimpan dalam `storage/framework/testing/cleanup-2026-11-05/`; hash/isi `.env` tidak disalin ke dokumentasi publik. Daftar duplikat tracked beserta ukuran, hash, risiko, alasan, dan tindakan tersedia dalam [duplicates-2026-11-05.json](duplicates-2026-11-05.json).

Pemeriksaan 250 referensi lokal Markdown/HTML pada baseline menemukan **0 target file hilang**. Pemeriksaan ini mencakup keberadaan target file; bukan pengujian layanan eksternal atau setiap anchor heading.

**Kandidat dan keputusan**

| Kategori | Path / ukuran awal | Bukti dan alasan | Risiko / rekomendasi |
| --- | --- | --- | --- |
| SAFE | 38 PNG pada `anti-slop/evidence/stage-2/foundation-regression/`, 5.345.861 byte | Masing-masing identik ukuran/SHA-256 dengan PNG bernama sama di `anti-slop/evidence/stage-1/`. Tidak ada inbound link individual di 250 link baseline. `tests/browser/foundation.mjs:80`/`:109`/`:198` menulis screenshot; pembacaan pada `:14`/`:25` hanya JSON hasil/aset. `foundation-results.json` tetap diperlukan dan dipertahankan. | Rendah setelah verifikasi ulang hash dan pembuatan manifest asal → kanonis. Hapus hanya salinan redundan; simpan metadata regresi dan semua capture unik. Daftar setiap path dan ukuran ada di JSON audit. |
| SAFE | `README.md`, 4.109 byte | Masih berupa README Laravel: logo framework, sponsor, dan petunjuk kontribusi Laravel; belum menjelaskan OOPy. | Rendah. Ganti dengan petunjuk proyek berdasarkan kode/dokumen existing, tanpa mengubah aplikasi. |
| SAFE | `.gitignore`, 286 byte | `.env` terabaikan tetapi varian selain `.env.backup`/`.env.production` belum dicakup umum; Python dipakai oleh grader/QA, dengan aturan pycache hanya pada grader. | Rendah. Lindungi `.env.*` dengan pengecualian `.env.example`, serta cache Python; periksa bahwa file tracked aktif tidak ikut terabaikan. |
| SAFE | `docs/ADMIN_PANEL.md`, 26.495 byte | Bagian panduan AdminSeeder menampilkan kredensial development bawaan. Kredensial bukan token produksi, tetapi tidak perlu disalin ke panduan tim. | Rendah. Ganti nilai literal dengan rujukan ke seeder lokal; jangan mengubah seeder atau login. |
| SAFE | `docs/authentication.md` 11.582 byte, `docs/database.md` 22.649 byte, `docs/dashboard.md` 13.793 byte, `docs/quiz-progress.md` 16.109 byte | Ada angka hasil pengujian/route lama dan rencana integrasi yang sudah dilaksanakan pada Tahap 2/3. | Rendah. Beri penanda cakupan historis dan rujukan terbaru; pertahankan isi hasil pengujian lama. |
| SAFE | `docs/` dan `anti-slop/` | Panduan fitur bercampur dengan laporan bertanggal; belum ada indeks navigasi proyek. | Rendah. Buat `docs/README.md` yang mengelompokkan tautan. Hindari pemindahan dokumen agar path lama tetap sah. |
| REVIEW | 12 pasangan PNG identik di luar salinan regresi fondasi yang dipilih | Termasuk capture before/initial, authenticated/initial, forged-results/failed, classes-only/missing-output, dan completed-after-login lintas laporan. Nama menggambarkan skenario/provenance berbeda walaupun piksel sama. Setiap path, ukuran, dan hash tercatat di JSON audit. | Sedang. Pertahankan. Penggabungan lebih lanjut memerlukan keputusan pemilik mengenai arsip historis dan referensi skenario. |
| REVIEW | `resources/views/welcome.blade.php`, 82.540 byte | Tampilan Laravel bawaan; tidak ditemukan pemanggilan route saat pencarian source. Tetap memuat `@vite`, dan ketidakhadiran referensi teks tidak membuktikan tidak dipakai. | Sedang. Jangan hapus atau pindahkan tanpa persetujuan dan kajian pemakaian dinamis. |
| KEEP | `resources/css/app.css` 413 byte, `resources/js/app.js` 22 byte, `resources/js/bootstrap.js` 127 byte, `vite.config.js`, `package.json` 355 byte | Input Vite disebut eksplisit di `vite.config.js:8`; `app.js` memuat bootstrap yang mengimpor Axios. Konfigurasi mengimpor plugin Laravel/Tailwind. | Tinggi jika dihapus. Pertahankan entry/dependensi walau halaman utama menggunakan aset `public/`. Tidak menjalankan uninstall/update. |
| KEEP | `public/favicon.ico`, 0 byte | Tidak terlihat sebagai referensi eksplisit aplikasi, tetapi browser/server dapat meminta `/favicon.ico` secara implisit. | Rendah untuk ukuran, tetap tidak cukup bukti penggunaan. Pertahankan; penggantian ikon merupakan perubahan aset/UI di luar tugas. |
| KEEP | `public/images/oopy-logo.png`, `public/images/materi/bab1.jpeg` sampai `bab7.jpeg`, seluruh `public/css`/`public/js` | Thumbnail dipilih melalui `resources/materi/chapters.php`; logo/aset dirujuk view dan runtime dinamis. | Tinggi. Pertahankan seluruh aset aplikasi dan lisensi Prism. |
| KEEP | Delapan `.gitignore` identik, masing-masing 14 byte | `bootstrap/cache/.gitignore`, `storage/app/private/.gitignore`, `storage/app/public/.gitignore`, `storage/framework/cache/data/.gitignore`, `storage/framework/sessions/.gitignore`, `storage/framework/testing/.gitignore`, `storage/framework/views/.gitignore`, `storage/logs/.gitignore`. Isi sama tetapi batas direktori berbeda. | Tinggi. Jangan deduplikasi aturan berdasarkan hash saja. |
| KEEP | `.env` lokal 1.247 byte; `.env.example` tracked 1.145 byte | `git check-ignore` mengonfirmasi `.env` diabaikan; contoh berisi field konfigurasi kosong/placeholder. | Tinggi. Tidak membaca isi rahasia ke laporan, tidak mengubah atau menghapus `.env`; contoh tetap tersedia. |
| KEEP | `.git/` 49.481.463 byte, 1.870 file | Metadata dan object Git. Tidak ada garbage menurut `git count-objects -v`. | Tinggi. Tidak menyentuh index, object, reflog, history, remote, atau melakukan GC/rewrite. |
| KEEP | `vendor/` 66.601.951 byte; `node_modules/` 260.013.519 byte | Dependensi lokal, sudah diabaikan Git; 12.928 file gabungan. Ukurannya bukan bukti bahwa paket tidak diperlukan. | Tinggi. Tidak dihapus, diinstal ulang, atau diubah. |
| KEEP | `storage/` 110.081.417 byte, 1.070 file; `.phpunit.result.cache` 26.914 byte | Berisi log, view/session/cache, database QA, snapshot baseline, dan bukti lokal terdahulu. Sudah diabaikan sesuai direktori. Sebagian dapat berkaitan dengan sesi/pekerjaan berjalan. | Sedang/tinggi. Tidak mengosongkan cache/log, menghapus database, atau menghilangkan bukti lokal. Artefak audit/QA baru tetap di direktori testing yang diabaikan. |
| KEEP | `app`, `bootstrap`, `config`, `database`, `resources`, `routes`, `services`, `tests`, `composer.json`/`.lock`, `DESIGN.md` | Bagian aplikasi, skema, runtime, grading, pengujian aktif, atau arah desain. | Tinggi. Hash before/after wajib tetap sama. Tidak memindahkan struktur Laravel atau menyentuh algoritma/perilaku. |

**Pemeriksaan file sensitif**

Tidak ada `.env` aktual, file private key, `auth.json`, atau file kredensial lokal yang terlacak pada pemeriksaan nama/path. Pemeriksaan pola token berkeyakinan tinggi dilakukan tanpa mencetak nilai yang cocok. Kredensial development pada panduan AdminSeeder menjadi kandidat dokumentasi SAFE di atas; source fixture/seeder aktif tidak diubah. Hasil ini merupakan audit repository lokal dengan cakupan pemeriksaan yang disebutkan, bukan jaminan seluruh jenis rahasia atau Git history telah dipindai.

**Baseline sebelum perubahan**

- PHP: **193 lulus, 2 gagal, 3854 assertions**. Dua kegagalan berada pada ekspektasi rollback jumlah tetap di `LearningDatabaseTest` dan literal markup di `MateriTest`.
- JavaScript: **21 lulus, 0 gagal**.
- `composer validate`: valid; peringatan OpenSSL ganda berasal dari lingkungan.
- Browser Admin, Coding, dan Dashboard dijalankan pada database SQLite QA terpisah. Rincian perbandingan akhir akan dicatat di laporan hasil cleanup.

Audit ini menetapkan tindakan yang boleh dilaksanakan: dokumentasi, aturan ignore, dan 38 salinan screenshot yang sudah dibuktikan identik. Kandidat REVIEW tetap utuh. Tidak ada tindakan terhadap source, test aktif, database proyek, atau konfigurasi rahasia.
