# Perbaikan rubrik Coding OOPy, 22 Oktober 2026

## Hasil audit

1. Grader sebelumnya mensyaratkan semua pemeriksaan lulus, termasuk pencetakan
   contoh output. Pemahaman OOP yang benar dapat dianggap gagal karena presentasi.
2. Perbandingan string persis menolak variasi kapitalisasi dan whitespace.
3. Checker lokal memeriksa lebih sedikit kondisi daripada server: perubahan
   atribut, constructor, isolasi object, dan beberapa batas belum selaras.
4. Laravel sudah menghitung status sendiri, tetapi belum memverifikasi identitas
   dan kelengkapan setiap pemeriksaan. Respons berisi sebagian pemeriksaan tidak
   boleh cukup untuk memperoleh kelulusan.
5. UI menampilkan semua kegagalan dengan perlakuan sama, tanpa membedakan konsep
   wajib dan saran presentasi.

## Aturan yang diterapkan

| Latihan | Konsep inti, masing-masing 15 poin | Tambahan 10 poin |
| --- | --- | --- |
| 1 | Constructor SensorAir; atribut setiap object; batas 6.5/8.5; rentang normal; di luar rentang; perubahan atribut | Penyajian output lokasi dan status |
| 2 | Constructor Ekosistem; atribut induk; status induk; inheritance; atribut subclass; overriding | Penyajian output status Rawa |

Kelulusan memerlukan eksekusi tanpa error dan keenam konsep inti berhasil.
Skor 90 dapat lulus; skor 85 akibat satu konsep inti gagal tetap belum lulus.
Syntax error, pelanggaran kontrak bahasa sandbox, runtime error dan timeout
menghasilkan status error serta skor 0. Kegagalan saat pemanggilan method dalam
suatu pemeriksaan menghasilkan feedback spesifik dan poin parsial dari
pemeriksaan lain yang berhasil.

String status dibandingkan setelah casefold dan normalisasi whitespace. Makna
status harus tetap sesuai: mencetak saja tanpa return, teks berbeda, data tetap,
batas eksklusif, atau hasil yang disimpan saat constructor lalu tidak mengikuti
perubahan ph tetap gagal. Nama class, atribut dan method tidak dinormalisasi.
Tidak ada pencocokan source untuk penilaian; AST tetap digunakan untuk validasi
keamanan bahasa yang sudah berlaku.

`services/coding-grader/checks.py` menjadi satu implementasi rubrik yang dipakai
server dan disisipkan sebagai checker lokal. Server menambahkan parameter acak
untuk menguji generalisasi. Pyodide menangkap output program sambil tetap
meneruskannya ke panel output. Pemeriksaan tambahan membaca output yang sudah
dihasilkan, sehingga solusi dengan object di dalam fungsi main() juga diterima.

Protokol grader versi 2 menyertakan ID, penanda wajib, bobot, hasil, dan feedback.
Laravel memeriksa daftar ID lengkap, urutan, keunikan, tipe data, penanda wajib
serta bobot yang diharapkan. Nilai score/passed tambahan dari grader tidak dipakai.
Identitas akun dan latihan tetap berasal dari session dan route. Browser hanya
mengirim snapshot main.py. Grader lama atau respons tidak lengkap menghasilkan
503 dan tidak disimpan sebagai submission.

UI menampilkan kategori serta poin tiap pemeriksaan. Kegagalan tambahan memakai
ikon informasi; kegagalan inti tetap memakai ikon gagal dan petunjuk perbaikan.
Poin lokal tidak menaikkan progres akun. Respons backend setelah penyimpanan
tetap menjadi sumber status resmi. Percobaan gagal berikutnya tidak mencabut
kelulusan yang sudah tersimpan.

Tidak ada perubahan schema, model, migration, soal pemrograman, kode awal,
materi atau kuis. Teks ketentuan/kriteria Coding hanya diklarifikasi agar aturan
format dan kelulusan tidak bertentangan dengan rubrik baru. Riwayat submission
lama tidak dinilai ulang. Perubahan aktivasi grader yang sudah ada dipertahankan.

## Pengujian

| Pemeriksaan | Hasil |
| --- | --- |
| Laravel, `php artisan test` | PASS: 132 tes, 2843 assertions; SQLite memory |
| Coding feature tests | PASS: 16 tes, 244 assertions; kategori wajib/tambahan, seluruh konsep inti, skor palsu, pemeriksaan hilang/duplikat, bobot/penanda diubah, protokol lama, autentikasi, CSRF, rate limit, snapshot dan progres |
| Node, `node --test tests/js/*.test.js` | PASS: 21 tes, termasuk checker Coding, materi dan runtime manager |
| Python dengan `OOPY_TEST_DOCKER=1` | PASS: 17 tes, termasuk dua integrasi Docker nyata; format, output opsional, constructor, atribut, update, inheritance, overriding, manipulasi, Unicode, batas sumber daya dan cleanup |
| Kesetaraan rubrik | PASS: 16 pasangan fixture membandingkan ID, hasil, kategori dan bobot checker lokal dengan server |
| Browser `coding-grader.mjs` | PASS: 22 interaksi, 20 submission resmi termasuk dua percobaan salah akun lain; browser, Pyodide, Laravel, Docker dan MariaDB nyata, respons sukses tidak dimock |
| Browser `live-code.mjs` | PASS: editor bersama, multi-file, antrean, pembatalan, Stop, timeout, isolasi, checker BAB 4–6, error CDN/retry dan keyboard |
| Pint, syntax JS, `git diff --check` | PASS |

Browser Coding menguji kedua latihan dengan format bervariasi (100), output
belum lengkap (90, lulus), class tanpa program utama (90, lulus), kesalahan konsep
(70/75, gagal), syntax error (0), dan variabel hasil palsu (0, gagal). Skor lokal
dan resmi dibandingkan sebelum dan sesudah respons backend. Setiap submission
diperiksa melalui probe database untuk source persis, pemilik, latihan, skor,
status dan waktu. Pengujian akun lain, login ulang, kegagalan token gateway,
CSRF, rate limit dan progres lama tetap dilakukan.

Database browser terpisah: `oopy_grader_test_633cd46e682343a09d8f49d66e4d3b58`.
Database aplikasi asli tidak dipakai untuk akun/submission pengujian. Pemeriksaan
baca saja menemukan 3 pengguna, 9 latihan dan 4 submission existing; data tersebut
dipertahankan. Kedua starter_code Coding tetap kosong.

Batas Docker tetap: user non-root, network none, read-only, cap-drop ALL,
no-new-privileges, CPU 0.5, CPU time 3 detik, wall time 8 detik, RAM/swap 128 MB,
16 PID, tmpfs 8 MB, tanpa host mount atau socket Docker. Tes memeriksa flag,
timeout, output terbatas, kegagalan daemon dan pembersihan container.

Peringatan modul OpenSSL PHP yang dimuat dua kali berasal dari konfigurasi lokal
existing; tidak menghalangi pengujian. Saat integrasi pertama ditemukan gateway
lama masih aktif. Kedua proses OOPy yang teridentifikasi dimulai ulang menjadi
satu gateway, lalu pengujian diulang dan lulus. Penolakan protokol lama tidak
menghasilkan kelulusan atau submission palsu.

Bukti: [hasil browser](evidence/coding-rubric-2026-10-22/browser-real-grader.json),
[skor 90 di ponsel](evidence/coding-rubric-2026-10-22/latihan-1-missing-output-390.png),
[kesalahan konsep](evidence/coding-rubric-2026-10-22/latihan-1-core-error-1440.png).

## Antislop delivery gate

Cakupan gate adalah perubahan feedback Coding. Arah desain mengikuti DESIGN.md:
ruang praktikum OOP Python, ENERGY 2, RHYTHM 2, MOTION 1. Ikon informasi menjelaskan
saran yang tidak menggagalkan kelulusan; label teks dan poin menjelaskan kategori
tanpa mengandalkan warna. Tidak ada aset atau struktur navigasi baru.

- R-02 PASS: teks feedback baru tanpa em dash; judul soal existing dipertahankan.
- R-03 PASS: browser memeriksa overflow pada 360/390/768/1024/1440 px.
- R-17 PASS: skor berasal dari hasil grader dan perhitungan Laravel, bukan angka contoh.
- R-18 PASS: tidak menambahkan testimonial.
- R-23 PASS: tidak membuat logo, foto, navigasi, atau aset visual baru.
- R-24 PASS: tautan kembali tetap menuju daftar Coding yang diuji lewat browser.
- R-25 PASS: feedback #986174 di putih 4.89:1; ikon saran #40514e di putih 8.38:1, dihitung contrast-check.py.
- R-26 PASS: Run, Submit, Reset, logout dan login dijalankan dalam tes browser.
- R-27 PASS: keadaan kosong, verifikasi tertunda, syntax/runtime error dan 503 diuji.
- R-28 PASS: tidak menambahkan FAQ.
- R-32 PASS: Tab dan fokus Reset diperiksa browser; regresi keyboard editor lulus.
- R-33 PASS: perubahan UI ditulis melalui patch source, tanpa skrip rewrite.
- R-34 PASS: tidak ada perubahan atau penambahan tema.
- R-35 PASS: aplikasi dijalankan; browser memakai Pyodide, Docker dan DB nyata.
- R-36 PASS: batas keamanan yang dilaporkan mengikuti flag sandbox dan pengujian.
- R-37 PASS: arah serta dials mengikuti DESIGN.md, tanpa redesign.
- R-38 PASS: tidak ada fitur, klaim pelanggan atau konten realistis fiktif baru.
- R-01 PASS: tidak menambahkan gradient/glow.
- R-04 PASS: ikon informasi membedakan saran output dari kesalahan konsep.
- R-06 PASS: tipografi existing dipertahankan; label kategori membantu membaca hasil.
- R-07 PASS: tidak menambahkan grid latar.
- R-08 PASS: tidak menambahkan panah dekoratif.
- R-09 PASS: kategori ditulis sebagai teks pemeriksaan, tanpa badge dekoratif.
- R-10 PASS: tidak menambahkan glassmorphism.
- R-12 PASS: tidak menambahkan shadow.
- R-13 PASS: tidak menambahkan glow.
- R-14 PASS: daftar mengikuti jumlah konsep yang diuji, bukan kartu seragam baru.
- R-19 PASS: tidak menambahkan animasi; browser memakai reduced motion.
- R-22 PASS: tidak menambahkan ilustrasi.
- Dials PASS: ENERGY 2 / RHYTHM 2 / MOTION 1 dinyatakan sesuai arah proyek.
- Konsistensi dials PASS: panel feedback dan ritme editor tetap existing.
- Fokus PASS: hasil inti, skor resmi dan langkah perbaikan berada dalam panel hasil.
- Whitespace PASS: daftar memisahkan setiap konsep, tanpa baris feedback sukses berulang.
- Aksen PASS: aksen progres existing dipertahankan, saran memakai warna teks.
- Identitas PASS: pemeriksaan constructor, pH, inheritance dan overriding spesifik OOPy.
- Design Read PASS: arah praktikum OOP Python dari DESIGN.md diterapkan sebelum UI diubah.
- C-1 PASS: kategori, poin dan ikon mempunyai tujuan pembelajaran yang tertulis di atas.
- C-2 PASS: kontrol yang terlibat dijalankan melalui tes integrasi.
- C-3 PASS: tidak menambah bagian di luar kebutuhan penilaian.
- C-4 PASS: tampilan error, loading, hasil parsial, skor 90 dan ukuran layar diuji.
- C-5 PASS: skor, status dan klaim tes ditopang hasil nyata.
- R-05 PASS: tata letak praktikum existing dipertahankan.
- R-11 PASS: tidak mengubah bentuk kontrol atau menambah pill.
- R-15 PASS: Run Code, Submit dan Reset tetap menyatakan aksi nyata.
- R-16 PASS: teks feedback tanpa jargon promosi.
- R-20 PASS: daftar konsep OOP dan rentang pH spesifik latihan tetap menjadi isi utama.
- R-21 PASS: tidak memaksakan mode tema baru.
- R-29 PASS: tidak menambah warna; saran memakai warna teks existing.
- R-30 PASS: tidak melakukan redesign atau meniru produk lain.
- R-31 PASS: perubahan visual terbatas pada pembeda wajib/tambahan yang tujuannya tertulis.

## Menjalankan pembaruan

Bangun ulang image dan mulai ulang gateway privat agar protokol versi 2 dipakai:

```powershell
docker build -t oopy-coding-sandbox:1 services/coding-grader
.\services\coding-grader\start.ps1
```

Image lokal sudah dibangun ulang dan satu gateway development dijalankan untuk
verifikasi ini. Tidak ada migrasi, seeder ulang atau perubahan konfigurasi DB.
Pyodide tetap memberi feedback lokal; keamanan resmi dan status akun ditentukan
backend. Grader dasar ini tetap membatasi bahasa Python sesuai kontrak sandbox
existing, bukan mendukung seluruh fitur Python.
