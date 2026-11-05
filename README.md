# OOPy

OOPy adalah aplikasi belajar pemrograman berorientasi objek dengan Python. Pengguna membaca materi BAB 1–7, mengerjakan kuis dan Evaluasi Akhir, mencoba kode di browser, serta mengirim latihan Coding untuk dinilai server.

Dashboard Pengguna merangkum progres akun sendiri. Panel Admin menyediakan rekap pengguna, nilai, progres Coding, detail siswa, filter, dan export CSV. Hasil resmi Coding berasal dari grader server; pemeriksaan lokal di browser membantu latihan.

## Teknologi dan kebutuhan

- PHP 8.2+ dan Composer; Laravel 12 dengan Blade.
- SQLite atau MariaDB, dengan ekstensi PDO yang sesuai.
- Bootstrap 5.3.3, Plus Jakarta Sans, dan aset aplikasi dalam `public/`.
- Monaco Editor 0.52.2 dan Pyodide 0.27.7 untuk eksekusi Python di browser.
- Python 3.11+ dan Docker dengan Linux Engine untuk Coding Grader lokal.
- Node.js untuk pengujian JavaScript atau pengembangan Vite. Vite 6 menerima Node `^18.0.0 || ^20.0.0 || >=22.0.0`; pilih versi yang juga sesuai dengan dependensi lokal.

Versi dan dependensi proyek tercatat pada [composer.json](composer.json), [composer.lock](composer.lock), dan [package.json](package.json). Sebagian aset browser dimuat dari CDN. Akses CDN diperlukan pada penggunaan awal; cache aset QA bukan bukti ketersediaan CDN publik.

## Instalasi lokal baru

Jalankan dari root repository. Langkah ini ditujukan untuk lingkungan baru dengan database lokal tersendiri.

```powershell
composer install
if (-not (Test-Path -LiteralPath .env)) {
    Copy-Item -LiteralPath .env.example -Destination .env
}
```

Sesuaikan konfigurasi privat pada `.env`: `APP_URL`, koneksi database, session, cache, dan konfigurasi grader. Contoh konfigurasi tersedia dalam [.env.example](.env.example). Buat database lokal baru dan arahkan konfigurasi ke sana. Untuk SQLite, buat file kosong pada path yang dikonfigurasi; untuk MariaDB, buat database lokal melalui pengelola database.

Pada instalasi baru yang belum mempunyai `APP_KEY`, jalankan:

```powershell
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

`DatabaseSeeder` memanggil katalog materi/latihan dan seeder Coding. Akun pengguna dibuat melalui registrasi. Untuk workspace existing, pertahankan `.env`, key, dan database yang sudah ada; tinjau migration sebelum menjalankannya. Jangan menimpa konfigurasi atau melakukan reset data sebagai bagian instalasi ulang.

Promosi akun lokal menjadi admin tersedia melalui `php artisan oopy:promote-admin alamat-email-akun`. Rincian otorisasi dan seeder development terpisah ada pada [panduan Admin](docs/ADMIN_PANEL.md). Nilai kredensial lokal tidak dicantumkan dalam panduan ini.

Halaman utama menggunakan aset `public/`. Jika mengerjakan entry Vite yang sudah ada, gunakan `npm install` lalu `npm run dev` atau `npm run build`. Entry Vite dan dependensinya tetap merupakan bagian proyek.

## Menjalankan Coding Grader

Grader berjalan sebagai gateway privat yang membuat sandbox Docker untuk submission. Aktifkan Docker Linux Engine dan siapkan Python terlebih dahulu.

```powershell
docker info --format '{{.OSType}}'
docker build -t oopy-coding-sandbox:1 services/coding-grader
```

Atur `CODING_GRADER_URL` dan `CODING_GRADER_TOKEN` secara privat pada `.env`, sesuai [panduan Coding](docs/coding.md). Gunakan token acak lokal; simpan hanya di konfigurasi server. Launcher membaca konfigurasi tanpa menampilkannya.

Jalankan pada terminal PowerShell tersendiri:

```powershell
.\services\coding-grader\start.ps1
```

Launcher development memakai listener loopback; Laravel berkomunikasi dengan gateway tersebut. Jika konfigurasi lokal berubah, jalankan `php artisan config:clear`. Hentikan gateway dengan Ctrl+C. Jangan menjalankan `sandbox.py` langsung pada host untuk menilai source pengguna.

Detail kontrak API, batas sandbox, kegagalan grader, dan troubleshooting ada di [docs/coding.md](docs/coding.md). [Laporan aktivasi](docs/coding-grader-activation-2026-10-22.md) dan [laporan rubrik](docs/coding-grader-rubric-2026-10-22.md) memuat bukti pengujian pada tanggal masing-masing.

## Pengujian

```powershell
php artisan test --compact
node --test tests/js/*.test.js
php artisan route:list --except-vendor
composer validate
git diff --check
```

Suite PHP menggunakan konfigurasi [phpunit.xml](phpunit.xml). JavaScript mencakup pemeriksaan latihan Python dan memerlukan interpreter Python yang dapat ditemukan oleh harness. Tes grader Python dan opt-in integrasi Docker dijelaskan pada [panduan Coding](docs/coding.md).

Regresi browser tersedia di [tests/browser](tests/browser): Coding, Dashboard Pengguna, dan Admin. Suite membutuhkan Playwright serta browser yang sesuai; dependensi QA tersebut belum dideklarasikan pada `package.json`. Ikuti pengaturan fixture dalam laporan fitur dan gunakan database testing terpisah. Suite browser yang menulis fixture tidak ditujukan ke database pengguna.

Baseline cleanup 5 November 2026: PHP **193 lulus, 2 gagal**, JavaScript **21 lulus**. Dua kegagalan existing berada pada `LearningDatabaseTest` dan `MateriTest`; rinciannya tersedia pada [laporan cleanup](docs/maintenance/cleanup-2026-11-05.md).

## Struktur proyek

| Direktori / file | Isi |
| --- | --- |
| `app/` | Controller, model, middleware, dan service termasuk agregasi progres |
| `bootstrap/`, `config/`, `routes/` | Bootstrap Laravel, konfigurasi, dan route |
| `database/` | Migration, factory, dan seeder |
| `resources/` | Blade, katalog materi, dan entry Vite |
| `public/` | Entry HTTP dan aset aplikasi |
| `services/coding-grader/` | Gateway dan sandbox Docker untuk penilaian Coding |
| `tests/` | Pengujian PHP, JavaScript, Python pendukung, dan browser |
| `docs/` | Panduan fitur, laporan integrasi, evidence, dan pemeliharaan |
| `anti-slop/` | Arsip audit desain, implementasi fondasi, dan evidence |
| `storage/`, `bootstrap/cache/` | Data runtime/cache lokal; pertahankan aturan ignore per direktori |
| `vendor/`, `node_modules/` | Dependensi lokal yang diabaikan Git |
| `DESIGN.md` | Pedoman desain proyek |

## Dokumentasi

Mulai dari [indeks dokumentasi](docs/README.md). Integrasi terbaru dibahas dalam [Coding ke Dashboard Pengguna](docs/coding-dashboard-2026-11-05.md) dan [Coding ke Panel Admin](docs/admin-coding-2026-11-05.md). Arah visual tetap mengikuti [DESIGN.md](DESIGN.md).

[Audit cleanup](docs/maintenance/cleanup-2026-11-05-audit.md) menjelaskan klasifikasi SAFE/REVIEW/KEEP. [Manifest screenshot regresi](anti-slop/evidence/stage-2/foundation-regression/README.md) memetakan salinan yang dideduplikasi ke bukti kanonis.
