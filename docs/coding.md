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
`passed` bernilai 90 atau 100; `failed` mendapat skor parsial dari pemeriksaan
yang berhasil dan `error` bernilai 0. Satu submission passed milik akun tersebut cukup untuk status
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

Rubrik perilaku `services/coding-grader/checks.py` dipakai bersama oleh Pyodide
dan image grader. Server menambahkan data acak yang tidak dikirim ke browser.
Ia memeriksa batas pH, atribut beberapa object, perubahan atribut, constructor,
inheritance, dan override. Enam pemeriksaan inti masing-masing bernilai 15 poin
dan semuanya wajib berhasil. Contoh object dan output bernilai tambahan 10 poin;
kegagalannya tidak membatalkan kelulusan. Kapitalisasi dan whitespace string
status/output dinormalisasi; makna status, nama class/method/atribut, nilai data,
serta batas pH tetap diperiksa. Pemeriksaan memakai object dan pemanggilan method,
tanpa mencari pola source code. Python dasar, `super()`, class, if/loop, fungsi dan print didukung.
Import, decorator, async, akses filesystem, manipulasi builtins dan introspeksi
interpreter tidak didukung. Validator AST dan builtins terbatas melindungi
objek checker dari manipulasi; batas keamanan eksekusi tetap container.
Pembatasan ini khusus dua latihan dasar ini, bukan grader Python serbaguna.
Pyodide menyediakan feedback latihan; pembatasan keamanan eksekusi resmi tetap
diterapkan di server. Respons grader memakai protokol versi 2. Laravel memeriksa
ID, kelengkapan, urutan, bobot dan penanda wajib rubrik sebelum menghitung status
dan skor. Grader versi lama, pemeriksaan hilang/duplikat dan perubahan bobot ditolak
tanpa menyimpan submission. Setelah memperbarui kode, bangun ulang image dan mulai
ulang gateway agar keduanya memakai protokol yang sama.

Jalankan gateway pada host sandbox Linux/VM terpisah untuk produksi. Lindungi
Docker daemon, gunakan firewall/private network dan TLS bila gateway tidak di
localhost. Runtime `runsc`/gVisor dapat dipilih lewat CODING_GRADER_RUNTIME
setelah dipasang pada host. Jangan membuka port gateway atau socket Docker
ke browser. Lihat juga [Docker Engine security](https://docs.docker.com/engine/security/).

### Menjalankan service

Docker Desktop harus aktif dengan Linux containers pada Windows; gunakan
Docker Engine pada host Linux. Python 3.11+ cukup untuk gateway, tanpa pip dependencies.

```powershell
docker version
docker info --format '{{.OSType}}'
docker build -t oopy-coding-sandbox:1 services/coding-grader
```

Hasil OSType harus `linux`. Jika named pipe Docker tidak ditemukan, jalankan
Docker Desktop dan tunggu Engine siap, lalu ulangi pemeriksaan. Tidak perlu
mengubah service, firewall, WSL, atau pengaturan virtualisasi jika Engine sudah
dapat dijalankan. Jangan mengaktifkan Docker API publik.

Gunakan secret acak berbeda per lingkungan. Untuk menghasilkan 32 byte acak
sebagai 64 karakter hex tanpa menampilkannya di terminal:

```powershell
$graderSecret = (& python -c "import secrets; print(secrets.token_hex(32))").Trim()
```

Simpan nilainya hanya pada konfigurasi privat Laravel dan gateway, bukan log,
source, atau Git. `.env` Laravel perlu memiliki dua nilai berikut:

```dotenv
CODING_GRADER_URL=http://127.0.0.1:8020
CODING_GRADER_TOKEN=<secret-acak-yang-sama-dengan-gateway>
```

Pada aktivasi 22 Oktober 2026, kedua nilai sudah ditambahkan ke `.env` lokal
yang diabaikan Git, dengan token acak 64 karakter; konfigurasi lain dipertahankan.
Tidak perlu membuat ulang token yang sudah benar. Jangan menimpa seluruh `.env`.

Di Windows, jalankan dari root repository:

```powershell
php artisan config:clear
.\services\coding-grader\start.ps1
```

Launcher hanya membaca kunci grader dari `.env`, tanpa menjalankan isi file
sebagai script. Ia memeriksa Linux Engine/image, mengambil token ke environment
proses, dan membatasi listener development pada `127.0.0.1`. Environment proses
yang sudah diisi memiliki prioritas; gunakan terminal baru jika sebelumnya
memakai token pengujian yang berbeda. Hentikan gateway dengan Ctrl+C.

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

Pada pemeriksaan 15 Oktober tersebut, daemon Docker belum berjalan sehingga
integrasi container belum terverifikasi. Status ini sudah diperbarui melalui
aktivasi berikut.

## Aktivasi dan integrasi nyata, 22 Oktober 2026

**PASS untuk lingkungan development yang diuji.** Docker Desktop 4.75.0,
Engine 29.5.2 Linux, image `oopy-coding-sandbox:1`, gateway privat, dan konfigurasi
Laravel berhasil diaktifkan. Pengujian browser menggunakan MariaDB 10.4.32
melalui driver mysql dengan database `oopy_grader_test_<32 karakter hex>` yang
terpisah. Tidak ada akun atau submission uji yang ditambahkan ke database asli.

Dua bug transport diperbaiki tanpa mengubah checker atau kontrak soal:

- Batas pembacaan stdin sandbox 65.000 byte memotong JSON Unicode yang masih
  sah menurut batas source 30.000 karakter/60.000 byte. Sandbox kini membaca
  payload hingga 200.000 byte, sesuai batas gateway, dan menolak kelebihan.
- TrimStrings Laravel memangkas whitespace source sebelum grading/penyimpanan.
  Field `files.*` kini dikecualikan sehingga snapshot source tetap persis;
  pengecualian jawaban kuis yang sudah ada dipertahankan.

Gateway juga menolak respons container non-object secara eksplisit. Tidak ada
perubahan UI, route, model, schema, materi, kuis, atau algoritma penilaian.

| Tes | Hasil |
| --- | --- |
| Laravel | 130 tes, 2776 assertions, PASS; SQLite in-memory |
| Node Test Runner | 21 tes, PASS |
| Python tanpa Docker opt-in | 12 PASS, 2 integrasi SKIP |
| Python dengan `OOPY_TEST_DOCKER=1` | 14 PASS, termasuk dua tes container nyata |
| `coding.mjs --auth` | PASS: guest, Monaco/Pyodide, Reset, error, loading, ukuran layar; sebagian respons UI disimulasikan sebagaimana desain tes lama |
| `coding-grader.mjs` | PASS: browser sampai Docker dan MariaDB nyata, kedua latihan passed/failed/error, snapshot, waktu, pemilik, refresh/login, isolasi akun, CSRF, rate limit, token salah; hasil sukses tidak dimock |
| Inspeksi container hidup | PASS: non-root, read-only, network none, cap drop, no-new-privileges, CPU/RAM/PID/ulimit/tmpfs, tanpa bind mount/socket, cleanup |

Bukti dan batas pengujian: [laporan aktivasi](coding-grader-activation-2026-10-22.md),
[hasil browser](evidence/coding-grader-2026-10-22/browser-real-grader.json),
[inspeksi runtime](evidence/coding-grader-2026-10-22/sandbox-runtime.json).

### Menjalankan ulang tes browser integrasi

Gunakan database testing baru, bukan database OOPy yang menyimpan akun asli.
Tetapkan nama unik dengan pola yang diwajibkan oleh probe:

```powershell
$env:OOPY_TEST_DATABASE = 'oopy_grader_test_' + [guid]::NewGuid().ToString('N')
$env:DB_CONNECTION = 'mysql'
$env:DB_DATABASE = $env:OOPY_TEST_DATABASE
$env:DB_URL = ''
$env:APP_ENV = 'local'
$env:APP_DEBUG = 'false'
$env:SESSION_DRIVER = 'database'
$env:CACHE_STORE = 'database'
```

Buat database bernama tersebut pada MariaDB testing, lalu jalankan migration
dan seeder hanya setelah memastikan DB_DATABASE menunjuk database testing itu.
`APP_ENV=local` mempertahankan CSRF pada HTTP nyata; `testing` akan melewati CSRF.
Cache database mempertahankan hitungan rate limit antarreques HTTP.

Jalankan server Laravel testing di 8027 dengan grader yang benar, dan server
testing kedua di 8028 dengan token grader sengaja salah. Kedua server harus
mengarah ke database testing yang sama dan memakai environment terpisah.
Gunakan `php -d display_errors=0 -S 127.0.0.1:8027 -t public` untuk server pertama,
dengan APP_URL sesuai port. Tampilan error PHP ke response dinonaktifkan agar
notice startup tidak mengubah status/header JSON. Periksa log CLI untuk notice;
pastikan direktori temporary PHP dapat ditulis oleh akun proses.

Di terminal pengujian, isi nama database testing yang sama, kemudian:

```powershell
$env:OOPY_BASE_URL = 'http://127.0.0.1:8027'
$env:OOPY_UNAVAILABLE_BASE_URL = 'http://127.0.0.1:8028'
node tests/browser/coding-grader.mjs
```

Tes membuat akun sementara dan membaca hasil melalui `coding-db-probe.php`.
Probe menolak nama database selain pola testing dan alamat akun selain
`@example.test`. Tidak ada endpoint inspeksi DB yang ditambahkan ke aplikasi.
Opsional `OOPY_ASSET_MANIFEST` menunjuk peta URL CDN ke cache aset lokal.

### Memeriksa penyimpanan tanpa mengubah data

Jalankan query read-only berikut pada database yang sedang Anda tinjau:

```sql
SELECT s.id, s.user_id, e.exercise_key, s.status, s.score, s.submitted_at
FROM exercise_submissions AS s
JOIN exercises AS e ON e.id = s.exercise_id
ORDER BY s.id DESC
LIMIT 20;
```

Jangan memasukkan source pengguna atau token ke log publik. Riwayat lama tidak
dihapus; status Selesai tetap berasal dari keberadaan submission passed milik
akun dan latihan yang sama.

### Troubleshooting

- Named pipe Docker tidak tersedia: jalankan Docker Desktop, pastikan konteks
  `desktop-linux`, dan tunggu `docker info --format '{{.OSType}}'` berhasil.
- Pesan grader belum dikonfigurasi/401 gateway: cocokkan token Laravel dan
  gateway, bersihkan cache konfigurasi, lalu restart gateway pada terminal baru.
- Image tidak tersedia: build ulang tag yang sama; source sandbox yang berubah
  juga memerlukan rebuild. Gateway memakai `--pull=never` saat submission.
- Port 8020 sudah dipakai: periksa proses pemilik, jangan hentikan proses yang
  tidak dikenal. Gunakan gateway yang benar atau port lokal lain dengan URL
  Laravel yang sesuai.
- 429: tunggu jendela rate limit; login ulang tidak menghapus pembatasan akun.
- Response JSON tercampur notice PHP: periksa temporary directory dan log PHP;
  matikan `display_errors` pada server HTTP, tanpa menyembunyikan log error.
- Setelah reboot, Docker Desktop dan gateway perlu dijalankan lagi. Aktivasi
  ini tidak memasang Windows service/autostart dan tidak melakukan deployment.

## Rubrik konsep inti, 22 Oktober 2026

Rubrik kini memisahkan enam konsep inti wajib (90 poin) dari penyajian output
tambahan (10 poin). Kelulusan tidak bergantung pada format output; backend tetap
memerlukan semua konsep inti berhasil. Implementasi, hasil tes terkini dan bukti
UI tersedia di [laporan perbaikan rubrik](coding-grader-rubric-2026-10-22.md).
