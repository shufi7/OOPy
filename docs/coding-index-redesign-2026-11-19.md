# Redesign daftar Latihan Coding, 19 November 2026

Redesign frontend `/coding` diterapkan. Integrasi gambar sudah disiapkan, tetapi
dua SVG dari brief belum tersedia. Hasil saat ini sengaja tidak menampilkan
elemen gambar atau ruang kosong penggantinya. Pekerjaan ilustrasi belum lengkap
sampai kedua file disalin dan pengujian gambar dijalankan ulang.

Audit awal menemukan kartu tanpa visual kontekstual dan kompetensi, pemberitahuan
login berbentuk paragraf dengan tautan panjang, serta aturan kartu dan hero di
stylesheet yang dipakai bersama halaman detail. `DESIGN.md`, katalog soal,
controller, kedua view Coding, CSS dan tes browser sudah diperiksa.
`MASTER_UX_IMPROVEMENT_PLAN.md` tidak ditemukan di repository.

Pilihan visual mengikuti brief dan `DESIGN.md`: ruang praktikum OOP Python
untuk pelajar, ENERGY 2 / RHYTHM 2 / MOTION 1. Antislop digunakan dalam mode
During sesuai jawaban pengguna, bersama panduan UI, komentar, aksesibilitas,
dan layout responsif.

| Perubahan | Alasan |
| --- | --- |
| Hero ringkas, H1 dominan, paragraf dibatasi 72ch | Memperjelas pintu masuk praktikum tanpa hero promosi yang tinggi. |
| Mint lembut dan permukaan putih dengan token OOPy | Menjaga hubungan visual dengan Beranda dan Materi. |
| Plus Jakarta Sans yang sudah tersedia | Mempertahankan identitas dan keterbacaan tanpa dependensi font baru. |
| Dua kartu setara pada desktop, satu kolom di bawah 992px | Kedua latihan adalah pilihan yang setara; teks panjang tetap terbaca di tablet dan ponsel. |
| Nomor 01/02 dan label kompetensi | Menyatakan urutan serta konsep yang benar dari masing-masing latihan. |
| Footer kartu untuk status dan tombol | Memisahkan progres nyata dari ringkasan soal dan menjaga posisi aksi konsisten. |
| Banner dengan ikon kunci dan tombol Masuk | Menjelaskan kebutuhan akun untuk penyimpanan tanpa menghalangi praktik tamu. |
| Border, radius kartu 14px, shadow ringan | Memisahkan tiap latihan dari kanvas tanpa elevasi berlebihan. |
| Hover -2px, active +1px, transisi 160ms | Memberi feedback pada aksi; reduced motion mematikan transisi dan transform. |
| `.coding-index` membatasi semua selector baru | Melindungi halaman detail dan komponen editor yang memakai CSS sama. |

Judul dan deskripsi tetap berasal dari katalog; tanda baca pada judul tetap
mengikuti konten yang diminta pengguna. Status dan label aksi tetap berasal
dari `$completed` dan `$attempted`. Tidak ada perubahan soal, grader,
controller, autentikasi, migration, data utama, navbar, atau editor produksi.
Tidak dilakukan commit atau push.

File implementasi dan pendukung:

| File | Peran |
| --- | --- |
| `resources/views/coding/index.blade.php` | Wrapper khusus, banner guest, kompetensi, metadata ilustrasi, footer status dan aksi. |
| `public/css/oopy/coding.css` | Aturan baru hanya untuk `.coding-index`; aturan awal tetap utuh. |
| `public/images/coding/README.md` | Pemetaan nama SVG sumber ke nama tujuan. |
| `tests/browser/coding-index.mjs` | Tes daftar, responsif, status backend, keyboard, kontras, dan gambar jika tersedia. |
| `tests/browser/quiz-helpers.mjs` | Dukungan manifest cache CDN lokal untuk browser QA yang tidak bisa mengakses jaringan. |
| `tests/browser/live-code.mjs` | Memakai helper cache yang sama, termasuk konteks pengujian failure/retry. |
| `docs/evidence/coding-index-2026-11-19/` | Screenshot sebelum/sesudah dan log tes. |

File ilustrasi yang masih diperlukan:

| File dari pengguna | Tujuan |
| --- | --- |
| `OOPy_Latihan1_SensorAir_Sesuai_Soal.svg` | `public/images/coding/latihan-1-sensorair.svg` |
| `OOPy_Latihan2_Ekosistem_Rawa_Multifile_Sesuai_Soal.svg` | `public/images/coding/latihan-2-ekosistem-rawa.svg` |

Blade memeriksa keberadaan setiap file, lalu memakai `asset()`, alt kontekstual,
lazy loading, decoding async dan `object-fit: contain`. Tidak dibuat ilustrasi
pengganti. Visual SVG dan pemuatan dua ilustrasi: **NOT TESTED**, menunggu file.

Perbandingan screenshot:

| Keadaan | Sebelum | Sesudah |
| --- | --- | --- |
| Tamu, desktop 1440px | [Sebelum](evidence/coding-index-2026-11-19/before-1440.png) | [Sesudah](evidence/coding-index-2026-11-19/after-guest-1440.png) |
| Tamu, ponsel 390px | [Sebelum](evidence/coding-index-2026-11-19/before-390.png) | [Sesudah](evidence/coding-index-2026-11-19/after-guest-390.png) |
| Akun dengan latihan selesai dan belum lulus | Tidak diambil | [Desktop](evidence/coding-index-2026-11-19/after-completed-1440.png), [ponsel](evidence/coding-index-2026-11-19/after-completed-390.png) |

Screenshot status belum dikerjakan dan sudah mencoba juga tersedia di direktori
evidence. Semua screenshot memakai Bootstrap, ikon, dan Plus Jakarta Sans asli
dari cache QA, karena akses CDN langsung ditolak lingkungan eksekusi.
Monaco dan Pyodide juga dijalankan dari aset asli yang tersimpan di cache.

| Pemeriksaan | Hasil aktual |
| --- | --- |
| `php artisan test --filter='CodingTest\|CodingMultifileTest\|LiveCodeTest'` | PASS, 35 tes / 539 assertion. [Log](evidence/coding-index-2026-11-19/php-tests.txt). |
| `node --test tests/js/coding-checkers.test.js tests/js/runtime-manager.test.js` | PASS, 8 tes. [Log](evidence/coding-index-2026-11-19/javascript-tests.txt). |
| `node tests/browser/coding-index.mjs --auth` | PASS, dua latihan; guest dan ketiga status dari backend; enam viewport 320/390/768/1024/1440/1920px. [Log](evidence/coding-index-2026-11-19/index-browser.txt). |
| `node tests/browser/coding.mjs --auth` | PASS, Run, Submit, source, multi-file, error import/sintaks, Stop, Reset, tab keyboard dan shortcut. [Log](evidence/coding-index-2026-11-19/coding-browser.txt). |
| `node tests/browser/live-code.mjs` | PASS, editor umum, isolasi model/runtime, antrean, timeout, failure/retry CDN, keyboard dan latihan BAB 4–6. [Log](evidence/coding-index-2026-11-19/live-code-browser.txt). |
| Perbandingan detail dengan stylesheet awal dari Git | PASS, screenshot seluruh halaman kedua latihan identik secara piksel pada 390/1440px. [Log](evidence/coding-index-2026-11-19/detail-visual.txt). |
| `npm.cmd run build` | PASS, Vite membangun 58 modul. |
| `git diff --check` dan `node --check` pada berkas tes yang diubah | PASS. |

Database browser adalah SQLite baru yang dibuat dengan fixture
`tests/browser/admin-sandbox.php`, terpisah dari database aplikasi. Tes daftar
memakai akun fixture `empty`, `zero`, dan `student` untuk memeriksa label
Mulai Latihan, Lanjutkan Latihan, dan Kerjakan Lagi. Suite PHP memakai SQLite
in-memory. Tidak ada fixture yang ditulis ke database utama.

Grader resmi sengaja diarahkan ke endpoint lokal yang tidak tersedia selama tes
browser Coding. Respons 503 serta respons UI terkontrol menguji perilaku akun,
bukan membuktikan grading Docker nyata. Pemeriksaan resmi dan persistensi tetap
diverifikasi oleh suite PHP dengan respons grader terkontrol.

Delivery Gate untuk bagian yang tersedia:

| Gate | Bukti |
| --- | --- |
| Hard Gate: konten dan aset | PASS, katalog asli, label kompetensi sesuai brief, tidak ada angka/identitas/ilustrasi buatan. Kedua SVG masih NOT TESTED. |
| Hard Gate: responsif | PASS, tidak ada overflow; susunan kartu dan batas tombol diperiksa pada enam lebar layar untuk empat keadaan akun. |
| Hard Gate: kontras | PASS, seluruh sampel teks daftar minimal 5,10:1; termasuk normal, hover, active, banner dan tiga status. |
| Hard Gate: keyboard | PASS, Tab mencapai Masuk lalu kedua aksi sesuai urutan; outline minimal 3px; Enter membuka `/login`, `/coding/latihan-1`, `/coding/latihan-2`. |
| Hard Gate: kontrol dan feedback | PASS, menu ponsel membuka/menutup; tombol latihan menuju route nyata; reduced motion dan pembesaran teks 200% diperiksa. |
| Hard Gate: state | PASS, banner hanya guest, tiga status backend benar; aset yang tidak tersedia dihilangkan. Katalog ini dirender server dari dua latihan tetap, tanpa fetch/loading asinkron baru. |
| Hard Gate: console | PASS, tidak ada page error atau console error pada daftar selama pengujian. |
| Purpose Gate | PASS, alasan warna, layout, font, spacing, kartu, ikon kunci dan motion tercatat pada tabel keputusan. |
| Liveliness | PASS, nomor praktikum dan kompetensi OOP Python memberi identitas; hero, banner dan daftar memiliki bobot berbeda sesuai ENERGY 2 / RHYTHM 2 / MOTION 1. |
| Craftsmanship | PASS pada implementasi yang tersedia, alur browser nyata dan regresi detail diverifikasi; keterbatasan ilustrasi disebutkan secara terbuka. |

Untuk menjalankan ulang browser dengan cache, set `OOPY_ASSET_MANIFEST` ke JSON
pemetaan URL CDN ke path file cache, `OOPY_BASE_URL` ke server QA terisolasi, dan
opsional `OOPY_SCREENSHOT_DIR` untuk screenshot. Argumen `--auth` pada tes daftar
memerlukan akun dari fixture admin-sandbox; pada tes Coding argumen ini membuat
akun uji melalui registrasi.

Batas verifikasi: ilustrasi asli belum tersedia, Docker grader nyata tidak
dijalankan, serta belum ada audit screen reader atau browser selain Edge.
Tambahkan kedua SVG lalu jalankan kembali tes daftar dan periksa screenshot
untuk menyelesaikan verifikasi ilustrasi.
