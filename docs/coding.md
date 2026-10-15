# Fitur Coding OOPy

Halaman `/coding` menyediakan dua latihan mandiri: class/object SensorAir dan
inheritance/overriding Ekosistem–Rawa. Guest dapat membaca soal, memakai Monaco,
Run Code dan memperoleh feedback lokal Pyodide. Akun yang masuk dapat meminta
grading resmi. Komponen, worker, runtime manager, timeout, antrean, Stop dan
Reset memakai Live Coding existing. Materi dan `/editor` tetap memeriksa lokal.

Latihan 1 dan 2 dimulai dengan `main.py` benar-benar kosong. Tidak ada template,
komentar TODO, contoh jawaban, atau pemulihan otomatis kode awal. Reset dengan
konfirmasi mengosongkan kode kembali; membatalkan konfirmasi mempertahankan kode.
Run Code dan Submit menolak editor kosong atau hanya spasi dengan pemberitahuan,
tanpa menjalankan worker atau mengirim submission. Menjalankan kode tidak
mengubah isi editor. Kode awal latihan materi dan `/editor` tetap mengikuti
konfigurasi masing-masing.

Panel **Soal Latihan** berada sebelum editor dan memuat judul spesifik, deskripsi,
langkah pengerjaan, kontrak class/atribut/method, contoh output dan kriteria
keberhasilan. Rentang pH inklusif 6.5–8.5 dan teks status mengikuti grader resmi.
Panel hanya menjelaskan tugas dan output; tidak menyediakan kode penyelesaian.

## Penerapan database dan route

Tidak ada tabel atau migration baru. Pada instalasi dengan schema/konten existing:

```powershell
php artisan db:seed --class=CodingExerciseSeeder --force
```

Pada instalasi baru, jalankan `php artisan migrate` lalu `php artisan db:seed`.
`DatabaseSeeder` memanggil OopyContentSeeder kemudian CodingExerciseSeeder.
Seeder Coding meng-upsert dua key `coding-latihan-1` dan `coding-latihan-2`,
terhubung ke materi `kelas-dan-objek` dan `pewarisan`, tanpa menghapus latihan
atau submission lama. Tidak menggunakan migrate:fresh.
Kolom `starter_code` kedua latihan diisi string kosong. Validasi model Exercise
mengizinkan string kosong yang tetap hadir; kolom tidak diubah menjadi nullable.
Menjalankan ulang seeder memperbarui kode awal tanpa mengubah riwayat submission.

| Method | URL | Nama | Akses |
| --- | --- | --- | --- |
| GET | /coding | coding.index | Publik |
| GET | /coding/{slug} | coding.show | Publik; latihan-1 atau latihan-2 |
| POST | /coding/{slug}/submit | coding.submit | auth, CSRF, 6 request/menit |

Request JSON hanya membutuhkan `{"files":{"main.py":"...kode..."}}`.
Satu file, maksimal 30.000 karakter dan 60.000 byte. `user_id`, `role`, `score`,
`passed`, `status` dan identitas latihan dari request ditolak. Server memilih
latihan dari slug terdaftar dan pengguna dari session.

Snapshot disimpan di `exercise_submissions.code` sebagai JSON berisi
`entry_file` dan `files`; output resmi, status, skor dan submitted_at memakai
kolom existing. Setiap percobaan resmi yang valid membuat baris baru.
`passed` bernilai 100; `failed` mengikuti proporsi pemeriksaan dan `error`
bernilai 0. Satu submission passed milik akun tersebut cukup untuk status
Selesai, sehingga percobaan gagal berikutnya tidak menghilangkan progres.
Tidak tersedia endpoint pembacaan atau perubahan submission akun lain.

## Verifikasi resmi dan service sandbox

Run Code tidak mengirim submission. Submit menjalankan checker publik untuk
feedback, kemudian mengirim snapshot yang sama ke Laravel untuk akun login,
termasuk ketika pemeriksaan lokal gagal. Nilai lokal tidak menentukan nilai DB.
Kelulusan dan skor resmi dihitung Laravel dari hasil pemeriksaan service privat.
UI menunggu respons penyimpanan Laravel sebelum menampilkan progres tersimpan.

Laravel hanya memakai HTTP ke grader; tidak mengeksekusi Python pengguna.
`services/coding-grader/server.py` adalah gateway privat dengan bearer token,
batas request dan maksimal dua sandbox bersamaan. Gateway meluncurkan satu
container sekali pakai per submission, tanpa shell interpolation, host mount,
Docker socket dalam container, atau akses jaringan container. Batas: CPU 0,5,
CPU time 3 detik, wall time 8 detik, RAM/swap 128 MB, 16 PID, filesystem read-only,
tmpfs 8 MB, user non-root, capability dihapus, no-new-privileges. Container
dibersihkan termasuk setelah timeout. Image harus sudah tersedia (`--pull=never`).
Konfigurasi batas mengikuti [dokumentasi Docker](https://docs.docker.com/engine/containers/run/).

Checker resmi hanya terdapat pada image grader, tidak disisipkan ke halaman.
Ia memeriksa batas pH, nilai acak dalam/luar rentang, atribut beberapa object,
perubahan atribut, constructor, inheritance, override, pembuatan object dan
output. Python dasar, `super()`, class, if/loop, fungsi dan print didukung.
Import, decorator, async, akses filesystem, manipulasi builtins dan introspeksi
interpreter tidak didukung. Validator AST dan builtins terbatas melindungi
objek checker dari manipulasi; batas keamanan eksekusi tetap container.
Pembatasan ini khusus dua latihan dasar ini, bukan grader Python serbaguna.

Jalankan gateway pada host sandbox Linux/VM terpisah untuk produksi. Lindungi
Docker daemon, gunakan firewall/private network dan TLS bila gateway tidak di
localhost. Runtime `runsc`/gVisor dapat dipilih lewat CODING_GRADER_RUNTIME
setelah dipasang pada host. Jangan membuka port gateway atau socket Docker
ke browser. Lihat juga [Docker Engine security](https://docs.docker.com/engine/security/).

### Menjalankan service

Docker Desktop harus aktif dengan Linux containers pada Windows; gunakan
Docker Engine pada host Linux. Python 3.11+ cukup untuk gateway, tanpa pip dependencies.

```powershell
docker build -t oopy-coding-sandbox:1 services/coding-grader
$env:CODING_GRADER_TOKEN = '<rahasia-acak-minimal-32-karakter>'
python services/coding-grader/server.py
```

Gunakan secret acak berbeda per lingkungan; misalnya hasil
`python -c "import secrets; print(secrets.token_hex(32))"`. Di `.env` Laravel:

```dotenv
CODING_GRADER_URL=http://127.0.0.1:8020
CODING_GRADER_TOKEN=<secret-yang-sama-dengan-gateway>
```

Jalankan `php artisan config:clear` setelah mengubah konfigurasi. Gateway default
mendengarkan `127.0.0.1:8020`. Opsional: CODING_GRADER_BIND, CODING_GRADER_PORT,
CODING_GRADER_IMAGE dan CODING_GRADER_RUNTIME adalah environment gateway.
Timeout Laravel 15 detik dan timeout browser 20 detik memberi ruang untuk cleanup.
Jangan menjalankan sandbox.py langsung pada host untuk jawaban pengguna.

Ketika URL/token belum diisi, Docker/image tidak tersedia, gateway gagal,
respons tidak valid, atau HTTP timeout: Laravel mengembalikan 503 tanpa membuat
submission passed. Runtime error/timeout yang dilaporkan sandbox disimpan sebagai
error bernilai 0. Pyodide tetap tersedia. Jika koneksi browser putus setelah
penyimpanan, UI menyatakan penyimpanan belum terkonfirmasi dan menyarankan
refresh; status DB menjadi sumber kebenaran.

## Pengembangan dan pengujian

Konten, konfigurasi editor kosong dan checker lokal: `resources/coding/exercises.php`.
Pemeriksaan resmi terpisah: `services/coding-grader/sandbox.py`.
Perubahan checker resmi membutuhkan rebuild image. Request saat ini sengaja
hanya mengizinkan main.py; komponen existing masih mendukung banyak file untuk materi.

```powershell
php artisan test --compact
node --test tests/js/*.test.js
python -m unittest discover -s services/coding-grader -p test_grader.py -v
```

Tes PHP memakai SQLite memory; tes grader unit hanya mengeksekusi fixture tetap
yang ditulis pengembang dan memock Docker. Aktifkan integrasi container setelah
image dibangun dan Docker tersedia:

```powershell
$env:OOPY_TEST_DOCKER = '1'
python -m unittest discover -s services/coding-grader -p test_grader.py -v
```

Tes Playwright membutuhkan browser/Playwright existing, akses CDN Monaco/Pyodide,
dan server Laravel (default 127.0.0.1:8017):

```powershell
node tests/browser/coding.mjs
node tests/browser/live-code.mjs
```

`node tests/browser/coding.mjs --auth` membuat akun uji: gunakan server dengan
database SQLite sementara dan grader tidak tersedia. OOPY_BASE_URL memilih server.
Tes ini memeriksa 503 backend nyata dan mensimulasikan respons jaringan serta
konfirmasi tertunda untuk menguji UI; bukan bukti grading Docker nyata.
Tes PHP memverifikasi persistensi, login ulang, isolasi akun, anti-pemalsuan,
CSRF, rate limit dan retry; tes browser memverifikasi Monaco/Pyodide nyata,
output, checker lokal, Reset dan ukuran layar 320–1440 px.

## Berkas implementasi

| Bagian | Berkas baru atau diubah |
| --- | --- |
| Route dan controller | `routes/web.php`, `app/Http/Controllers/CodingController.php` |
| Validasi dan service Laravel | `app/Http/Requests/Coding/SubmitCodingRequest.php`, `app/Services/CodingExerciseCatalog.php`, `app/Services/CodingGraderService.php` |
| Konfigurasi | `config/coding.php`, `.env.example` |
| Model | `app/Models/Exercise.php` (mengizinkan kode awal kosong tanpa perubahan schema) |
| Konten dan seeder | `resources/coding/exercises.php`, `database/seeders/CodingExerciseSeeder.php`, `database/seeders/DatabaseSeeder.php` |
| Tampilan | `resources/views/coding/index.blade.php`, `resources/views/coding/show.blade.php`, `resources/views/components/navbar.blade.php`, `public/css/oopy/coding.css` |
| Integrasi komponen existing | `resources/views/components/live-code.blade.php`, `public/js/live-code/live-code-instance.js` |
| Grader terisolasi | `services/coding-grader/Dockerfile`, `sandbox.py`, `server.py`, `.dockerignore`, `.gitignore` |
| Tes | `tests/Feature/CodingTest.php`, `tests/Feature/LearningDatabaseTest.php`, `tests/browser/coding.mjs`, `tests/browser/coding-fixtures.mjs`, `tests/js/coding-checkers.test.js`, `services/coding-grader/test_grader.py` |
| Dokumentasi | `docs/coding.md`, `docs/database.md` |

## Hasil pemeriksaan lokal (15 Oktober 2026)

- Suite Laravel: **129 tes lulus, 2.771 assertion**. Termasuk 13 tes Coding serta
  regresi autentikasi, Materi, Kuis, Evaluasi Akhir, Dashboard dan Live Coding.
- Suite JavaScript: **21 tes lulus**, termasuk kedua checker Coding dengan kode
  kosong, jawaban salah, beberapa bentuk solusi benar, dan runtime manager.
- Grader Python: **7 tes lulus, 1 tes integrasi Docker dilewati**. Unit test
  memakai fixture tetap dan Docker mock; bukan eksekusi submission pada host.
- Playwright `coding.mjs --auth`: lulus pada database SQLite sementara. Monaco
  dan Pyodide nyata menjalankan kedua solusi dari editor kosong. Program salah
  gagal, kedua solusi benar diterima checker, Reset dapat
  dibatalkan/dikonfirmasi dan kembali kosong, editor kosong/spasi tidak dikirim,
  source tetap utuh setelah Run maupun error, dan layout tidak
  overflow pada 320/390/768/1440 px. Respons 503 grader nyata tidak mengubah
  progres, termasuk setelah refresh. Respons error dan konfirmasi tertunda
  disimulasikan untuk memeriksa UI dan pencegahan duplicate Submit.
- Playwright `live-code.mjs`: lulus untuk `/editor` dan latihan BAB 4–6,
  termasuk multi-file, antrean, Stop, timeout, isolasi model, keyboard, output,
  penanganan kegagalan CDN, dan pemulihan runtime.
- Pint, pemeriksaan syntax JS dan `git diff --check`: lulus.
- CodingExerciseSeeder berhasil diterapkan ke DB aplikasi existing. Pengujian
  tidak memakai DB aplikasi; tidak ada penghapusan data atau push GitHub.

Docker CLI terpasang, tetapi daemon Docker Desktop Linux tidak berjalan.
Image dan grading dalam container belum bisa diuji pada mesin ini. Karena
`.env` aplikasi belum dikonfigurasi untuk grader aktif, Submit resmi saat ini
menampilkan verifikasi belum tersedia dan tidak menyimpan kelulusan. Ikuti
langkah service di atas lalu jalankan tes Docker opt-in sebelum memakai
grading resmi. Akses CDN tes browser memerlukan eksekusi di luar sandbox IDE.
