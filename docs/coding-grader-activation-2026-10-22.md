# Aktivasi Coding Grader OOPy, 22 Oktober 2026

## A. Ringkasan

**Tahap 1: PASS pada lingkungan development yang diuji.** Docker Desktop dan
Linux Engine aktif, image dibangun, gateway privat berjalan pada
`127.0.0.1:8020`, dan Laravel dikonfigurasi dengan token acak lokal.
Alur Browser → Laravel → Gateway → Docker → Laravel → MariaDB benar-benar
dijalankan untuk kedua latihan, tanpa respons grading sukses yang dimock.

Pengujian memakai database MariaDB khusus testing, bukan database OOPy asli.
Database asli tetap memiliki 3 pengguna, 9 latihan, dan 0 submission, sama
seperti sebelum pekerjaan. Dua starter Coding tetap kosong. Tidak membuat
akun uji pada database asli, menghapus data, menambah migration/tabel produksi,
atau melakukan commit/push/deployment.

## B. Hasil audit awal

| Temuan | Bukti dan tindakan |
| --- | --- |
| Engine Docker berhenti | CLI tersedia, konteks `desktop-linux`, named pipe Engine tidak ditemukan. Docker Desktop dijalankan; Engine Linux kemudian merespons |
| URL/token grader belum ada | Kedua kunci ditambahkan ke `.env` ignored; token 32 byte acak dalam 64 karakter hex. Byte konfigurasi yang sebelumnya ada dipertahankan |
| JSON Unicode terpotong | Fixture 18.306 karakter/36.306 byte menghasilkan JSON stdin 108.386 byte, diterima gateway tetapi image lama menghasilkan error karena membaca hanya 65.000 byte |
| Snapshot whitespace berubah | Tes regresi awal gagal: newline awal/akhir source hilang sebelum grading dan penyimpanan. Field `files.*` sekarang dikecualikan dari TrimStrings |
| Respons container non-object | Pemanggilan `.get()` pada JSON list/null sebelumnya tidak divalidasi. Gateway kini menolaknya secara eksplisit |
| Cakupan browser lama | `coding.mjs --auth` sengaja mengharapkan grader tidak tersedia dan mensimulasikan beberapa respons UI. Ditambahkan suite integrasi terpisah untuk jalur Docker dan MariaDB nyata |
| Notice startup PHP pengujian | Request besar sempat menerima Notice sebelum JSON sehingga status/header salah. `display_errors=0` diterapkan hanya pada server HTTP pengujian; php.ini Windows tidak diubah. Request besar kemudian terverifikasi 422 |

Routing, controller, request validation, model, seeder, checker resmi/lokal,
dan mekanisme progres existing sudah menyediakan arsitektur yang diperlukan.
Tidak dibuat sistem Coding baru.

## C. Perubahan source code

| File | Perubahan |
| --- | --- |
| [bootstrap/app.php](../bootstrap/app.php) | Mempertahankan whitespace `files.*`; aturan jawaban kuis tetap |
| [sandbox.py](../services/coding-grader/sandbox.py) | Membaca stdin JSON maksimal 200.000 byte dengan pemeriksaan kelebihan; checker tetap |
| [server.py](../services/coding-grader/server.py) | Menolak JSON container non-object |
| [start.ps1](../services/coding-grader/start.ps1) | Launcher development membaca hanya konfigurasi grader, tidak mengevaluasi `.env`, memeriksa Engine/image dan listener loopback |
| [test_grader.py](../services/coding-grader/test_grader.py) | Regresi Unicode, payload, autentikasi HTTP, response invalid, variasi solusi, output limit dan Docker cleanup |
| [CodingTest.php](../tests/Feature/CodingTest.php) | Tes snapshot source dan body request grader yang identik |
| [coding-grader.mjs](../tests/browser/coding-grader.mjs) | Integrasi browser, Docker dan MariaDB nyata; loading, duplikasi, hasil, keamanan dan persistensi |
| [coding-db-probe.php](../tests/browser/coding-db-probe.php) | Probe read-only yang mewajibkan database testing bernama khusus dan akun `@example.test` |
| [coding.md](coding.md) | Menjalankan gateway, token, pengujian, inspeksi DB dan troubleshooting |

Perubahan lokal di luar Git: hanya penambahan dua kunci grader ke `.env`.
Tidak ada token asli dalam laporan, source, screenshot, atau JSON bukti.
Source UI, dashboard, materi, kuis, evaluasi, route, schema dan algoritma nilai
tidak berubah, dibuktikan melalui diff area terkait dan regresi.

## D. Hasil pengujian

| Pengujian | Status | Jumlah dan batas bukti |
| --- | --- | --- |
| Docker version/info | PASS | Desktop 4.75.0; Engine 29.5.2; linux/amd64 |
| Build image | PASS | `oopy-coding-sandbox:1`, dibangun ulang setelah perbaikan |
| `php artisan test --compact` | PASS | 130 tes, 2776 assertions, 23,00 detik; SQLite in-memory |
| `node --test tests/js/*.test.js` | PASS | 21 tes, 0 gagal |
| Python unittest tanpa opt-in | PASS | 12 lulus, 2 tes integrasi dilewati |
| Python unittest dengan `OOPY_TEST_DOCKER=1` | PASS | 14 lulus, 0 dilewati, 25,301 detik; container nyata |
| Browser `coding.mjs --auth` | PASS | Kedua latihan: Monaco/Pyodide nyata, editor kosong, Run, error, checker lokal, Reset, responsivitas, loading/duplikasi; error/ack tertentu disimulasikan |
| Browser `coding-grader.mjs` | PASS | 10 catatan interaksi; 6 submission akun A dan 2 submission salah akun B terverifikasi; 0 page errors |
| Inspect container aktif | PASS | Seluruh 12 kondisi isolasi yang direkam bernilai true; infinite loop error dan tidak ada container sisa |
| Source/syntax | PASS | `git diff --check`, `node --check`, `php -l`; token scan source dan bukti |
| MariaDB asli | PASS, read-only | Jumlah pengguna/latihan/submission sebelum dan sesudah tetap 3/9/0 |

Browser menguji 360/390/768/1024/1440px pada enam keadaan hasil; suite lama
menambahkan 320px. Font, Bootstrap, Monaco dan Pyodide memakai cache URL CDN
yang tersedia dari QA sebelumnya. Ini tidak membuktikan ketersediaan CDN publik
saat ini. Tes HTTP berjalan dengan `APP_ENV=local`, CSRF aktif, session dan
rate limit tersimpan pada database testing.

Bukti: [hasil browser nyata](evidence/coding-grader-2026-10-22/browser-real-grader.json),
[inspeksi sandbox hidup](evidence/coding-grader-2026-10-22/sandbox-runtime.json),
[ringkasan verifikasi](evidence/coding-grader-2026-10-22/verification.json).

## E. Verifikasi Coding

| Keadaan | Latihan 1 | Latihan 2 |
| --- | --- | --- |
| Editor awal | main.py kosong | main.py kosong |
| Run Code | Pyodide; output sesuai; source tetap | Pyodide; output sesuai; source tetap |
| Jawaban benar | passed, skor 100, tersimpan | passed, skor 100, tersimpan |
| Jawaban salah | failed, tidak memberi kelulusan baru | failed, tidak memberi kelulusan baru |
| Runtime error | error, skor 0, tersimpan | error, skor 0, tersimpan |
| Snapshot | Source termasuk newline awal/akhir identik | Source termasuk newline awal/akhir identik |
| Persistensi | Selesai setelah refresh dan login ulang | Selesai setelah refresh dan login ulang |
| Gagal setelah lulus | Selesai tetap ada | Selesai tetap ada |
| Akun lain | Mulai Belum Dikerjakan; tidak menerima progres A | Mulai Belum Dikerjakan; tidak menerima progres A |

Setiap submission diverifikasi dengan pembacaan MariaDB: pemilik, identitas
latihan, status, skor, source snapshot, output resmi dan timestamp. Pemeriksaan
loading menunda pengiriman request lalu meneruskannya ke Laravel asli;
respons sukses tidak diganti fixture. Percobaan klik saat Submit disabled tidak
menambah request atau baris database.

Screenshot: [Latihan 1 passed](evidence/coding-grader-2026-10-22/latihan-1-passed-1440.png),
[Latihan 1 failed](evidence/coding-grader-2026-10-22/latihan-1-failed-390.png),
[Latihan 1 error](evidence/coding-grader-2026-10-22/latihan-1-error-390.png),
[Latihan 2 passed](evidence/coding-grader-2026-10-22/latihan-2-passed-1440.png),
[sesudah login ulang](evidence/coding-grader-2026-10-22/completed-after-login-1440.png),
[grader tidak tersedia](evidence/coding-grader-2026-10-22/unavailable-grader-1440.png).

## F. Keamanan

- Gateway teramati mendengarkan hanya `127.0.0.1:8020`. Token acak 256-bit
  tidak dikirim ke browser dan tidak di-commit. Endpoint grader/latihan berasal
  dari konfigurasi dan katalog server.
- Autentikasi, CSRF, rate limit 6/menit dan validasi sumber berjalan. Akun B
  ditolak saat mencoba menyuplai user_id A, nilai, passed dan status. Path
  traversal, kelebihan 30.000 karakter, dan CSRF hilang ditolak. Guest mendapat 401.
- Token salah pada gateway sungguhan menghasilkan 503 Laravel, pesan gangguan
  yang jelas, 0 submission pada akun uji itu, serta progres 0%. Tidak ada fallback
  yang menganggap skor lokal sebagai kelulusan resmi.
- Container aktif diperiksa: user `65534:65534`, network none, rootfs read-only,
  privileged false, ALL capabilities dibuang, no-new-privileges, RAM/swap 128 MB,
  0,5 CPU, PID 16, CPU time 3 detik, nofile 32, fsize/core 0, tmpfs 8 MB.
  Tidak ada bind mount host atau Docker socket.
- Wall timeout gateway 8 detik dan cleanup terdapat pada `finally`. Infinite
  loop, output sangat panjang, syntax error, import terlarang, respons container
  rusak, request oversized dan autentikasi salah diuji. Container sisa tidak ada
  setelah pengujian. Batas dua concurrent sandbox diuji melalui semaphore HTTP
  unit, bukan diklaim sebagai load test produksi.
- Source pengguna tidak dieksekusi pada Laravel, gateway, atau host Windows.
  Unit test host hanya menjalankan fixture tetap tepercaya; source dari jalur
  browser dikirim ke disposable Docker sandbox.
- Anti-Slop dalam cakupan ini: tidak ada redesign/aset/kontrol baru; UI existing
  dipertahankan, status resmi dan error diverifikasi, keyboard/fokus serta reflow
  tidak mengalami regresi pada sampel browser. Tidak mengklaim audit kontras baru.

## G. Pekerjaan yang belum selesai

Tidak ada kriteria aktivasi development yang tersisa pada jalur yang diuji.
Hal berikut berada di luar verifikasi:

- NOT RUN: deployment publik, host produksi Linux terpisah, gVisor/runsc, TLS
  antarhost, pengujian beban, penetration test dan seluruh kemungkinan sandbox
  escape. Container tidak diklaim sebagai jaminan keamanan absolut.
- NOT RUN: akun uji atau submission ke database OOPy asli, sesuai batasan pemilik.
  Persistensi dibuktikan pada MariaDB testing dengan schema aplikasi yang sama.
- NOT RUN: perangkat fisik, screen reader, Safari/Firefox. Regresi runtime/kuis
  backend dan JavaScript lulus; tidak mengulang semua suite browser halaman lain.
- Peringatan OpenSSL ganda berasal dari PHP CLI setempat. Tidak mengubah php.ini
  atau konfigurasi sistem Windows. Notice temporary-directory pada server tes
  ditangani melalui konfigurasi proses pengujian.
- Setelah reboot, Docker Desktop dan gateway perlu dijalankan kembali. Tidak
  memasang service Windows atau autostart. Database testing dipertahankan untuk
  peninjauan; tidak ada perintah penghapusan database.

## H. Instruksi pengguna

Gateway lokal sudah aktif pada akhir verifikasi. Untuk menjalankannya kembali,
buka Docker Desktop, tunggu Linux Engine siap, lalu gunakan PowerShell baru di
root proyek:

```powershell
docker info --format '{{.OSType}}'
.\services\coding-grader\start.ps1
```

Jika source sandbox diubah, build ulang sebelum menjalankan gateway:

```powershell
docker build -t oopy-coding-sandbox:1 services/coding-grader
```

Laravel/XAMPP memakai dua nilai grader yang sudah ditambahkan pada `.env`
lokal. Jangan menyalin token ke browser atau Git. Setelah perubahan konfigurasi,
jalankan `php artisan config:clear`. Hentikan gateway dengan Ctrl+C pada terminal
yang menjalankannya. Jangan menjalankan gateway kedua pada port yang sama.

Panduan instalasi baru, query inspeksi database, dan troubleshooting tersedia
pada [docs/coding.md](coding.md).
