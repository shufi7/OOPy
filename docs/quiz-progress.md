# Progres kuis BAB 1–6

Kuis resmi memakai authentication session Laravel, penilaian server dan database
existing. Tidak ada tabel/model duplikat, best_score column, perubahan
`.env`, akun default, atau perubahan BAB 7/Live Coding.
Satu migration kompatibilitas MariaDB memperbaiki timestamp started_at; lihat
catatan perbaikan penyimpanan di bawah.

## Alur dan endpoint

Semua endpoint berada di `routes/web.php` dengan `web`/`auth`; POST memakai CSRF.
Untuk JSON, kirim `Accept: application/json`, cookie session, dan `X-CSRF-TOKEN`.

| Method | URL | Nama | Fungsi |
| --- | --- | --- | --- |
| POST | /materi/{slug}/kuis/attempts | quiz.start | Mulai atau gunakan kembali attempt aktif milik akun. |
| POST | /materi/{slug}/kuis/attempts/{attempt}/submit | quiz.submit | Validasi, nilai, simpan, kembalikan ringkasan. |
| GET | /materi/{slug}/kuis/progress | quiz.progress | Status, kelulusan, waktu selesai, nilai terbaik akun. |

Hanya enam slug kuis BAB 1–6 yang diterima. `evaluasi-akhir` dan slug tidak
dikenal menghasilkan 404. `ChapterQuizService` mengambil `chapter_quiz` melalui
chapter.slug serta material dengan slug yang sama. Bank soal harus memiliki
tepat 3 multiple_choice + 2 code_fill, dengan ambang DB yang konsisten; katalog
hilang/tidak konsisten menghasilkan 503, tanpa mengubah data lama otomatis.

Halaman dibuka → server merender soal aman dan progres akun → JavaScript
menyimpan jawaban di memori → interaksi pertama memulai attempt → Selesai Kuis
mengirim lima jawaban → Laravel menghitung hasil → transaction menyimpan lima
jawaban, attempt dan progres → UI menampilkan ringkasan agregat.

Guest tetap membaca materi publik dan melihat **Masuk untuk Mengerjakan Kuis**.
Form kuis tidak diaktifkan untuk guest. Endpoint JSON tanpa login menghasilkan
401; request HTML diarahkan ke login. Token CSRF hilang/salah menghasilkan 419.
Tidak ada kelulusan resmi untuk tamu.

## Payload dan validasi

Start tidak menerima identitas/nilai dari client. Response berisi `attempt_id`
dan `submit_url`. Submit menerima:

```json
{
  "answers": [
    {"question_id": 1, "answer": 0},
    {"question_id": 2, "answer": 1},
    {"question_id": 3, "answer": 2},
    {"question_id": 4, "answer": "teks isian pengguna"},
    {"question_id": 5, "answer": "teks isian pengguna"}
  ]
}
```

ID pada contoh hanyalah ilustrasi; gunakan ID dari JSON soal pada halaman.
`SubmitQuizRequest` memerlukan list lima jawaban, question ID distinct, serta
field nested hanya question_id/answer. `QuizGradingService` memastikan seluruh
soal berasal dari kuis attempt, masing-masing tepat sekali. Pilihan ganda wajib
indeks integer yang ada di options (string/boolean/out-of-range ditolak).
Code-fill wajib string tidak kosong, maksimal 1000 karakter. Hanya whitespace
awal/akhir dibuang: casing dan spasi internal dipertahankan seperti engine lama.
`Return` berbeda dengan `return`; assignment dengan dua spasi internal tidak
sama dengan assignment satu spasi. Tidak ada eksekusi Python untuk menilai kuis.
Normalisasi mengikuti `String.trim()` engine lama, termasuk Unicode whitespace;
NUL/zero-width tidak dibuang menjadi jawaban benar. `bootstrap/app.php`
mengecualikan `answers.*.answer` dari TrimStrings global agar normalisasi Laravel
yang lebih luas tidak mengubah jawaban sebelum penilaian.

Field client `score`, `passed`, `is_correct`, `best_score`, `correct_count`,
`user_id`, `role`, dan `quiz_id` dilarang. Field nilai tidak dipakai untuk grading.
User berasal dari session; quiz berasal dari slug/relasi database. Payload
invalid menghasilkan 422, tanpa completion/jawaban/progres baru.

## Aturan penilaian dan kunci

`config/quiz.php` menjadi sumber aturan: total lima, minimal empat benar,
komposisi 3 PG/2 code-fill dan panjang isian. Seeder menghitung passing_score
chapter_quiz dari aturan itu (80); konfigurasi Evaluasi Akhir tidak diubah.

`QuizGradingService` mengambil `questions.correct_answer` dari database.
Multiple-choice dibandingkan dengan string indeks canonical; code-fill memakai
trim dan perbandingan string case-sensitive yang mempertahankan whitespace
internal. Nilai = `round(correctCount / 5 * 100)`, lulus = `correctCount >= 4`.
Jumlah benar 0/1/2/3/4/5 menghasilkan nilai 0/20/40/60/80/100.

JSON soal menggunakan whitelist question_id/question_order/type/question/options/code.
Tidak ada correct, correct_answer, answer, is_correct, atau explanation.
Guest menerima metadata publik yang disaring dari sumber PHP, tanpa ID penilaian;
akun login menerima ID/soal dari DB. Kunci authoring tetap di PHP/seeder/database,
tidak di HTML tersembunyi, attribute atau JavaScript quiz.

Response submit hanya berisi `result` (attempt_id, score, correct_count,
incorrect_count, passed) dan `progress` (status, completed_at, passed,
next_unlocked, best_score). Tidak ada soal mana benar/salah, jawaban user per
soal, kunci, pembahasan, atau explanation. Hasil UI tetap kartu agregat existing.
Halaman materi akun dan response sukses memakai `Cache-Control: no-store, private`.

## Persistence, ownership dan idempotensi

Relasi yang dipakai: Quiz.chapter/questions, Chapter.materials,
User.quizAttempts/progress, QuizAttempt.answers, QuizAnswer.attempt/question.
`QuizAnswer` menurunkan quiz_id dari attempt. FK gabungan dan unique
attempt_id/question_id existing tetap aktif. Tidak ada constraint yang dilepas.

`QuizAttemptPolicy::submit` hanya mengizinkan pemilik attempt, termasuk ketika
akun lain ber-role admin. Service memeriksa ownership lagi pada row terkunci
dan memastikan attempt.quiz_id sesuai chapter. Akun lain mendapat 403;
attempt dipakai di chapter lain mendapat 404. GET progress selalu memakai akun
session sendiri; parameter user_id tidak mengubah scope.

Start/submit memakai `DB::transaction(..., 3)`. Keduanya mengunci row User lebih
dulu agar pembuatan attempt aktif maupun progress yang belum ada tidak berlomba.
Submit kemudian mengunci attempt dan soal. Pada MySQL/MariaDB, `lockForUpdate`
mengambil row lock sampai transaksi selesai. SQLite memakai locking transaksi
engine; feature tests tidak membuktikan stress concurrency pada MariaDB.

Start mengembalikan attempt `in_progress` yang sudah ada untuk user/quiz.
Halaman/refresh tidak membuat attempt; JavaScript memanggil start hanya setelah
interaksi kuis. Submit menggunakan ID attempt sebagai identitas idempotensi.
Setelah completed, request ulang mengembalikan ringkasan tersimpan tanpa
mengubah jawaban, nilai atau waktu completion. Payload baru tetap divalidasi,
tetapi tidak menilai ulang attempt selesai. Status expired menghasilkan 409.
Double-click dicegah di UI; database tetap melindungi request ulang/tab lain.

Percobaan baru setelah selesai dibuat melalui start berikutnya (misalnya setelah
Coba Lagi dan mulai menjawab). Riwayat sebelumnya tidak dihapus/ditimpa.
Jika koneksi submit terputus, ID attempt dan jawaban tetap di memori untuk retry
ke endpoint yang sama. Jika server sudah commit tetapi response hilang, retry
mendapat hasil lama. Refresh tidak mengirim ulang jawaban otomatis; draft lokal
tidak bertahan refresh. Attempt aktif yang ditinggalkan dapat digunakan kembali.

Lima jawaban, completion attempt, dan update progress berada dalam satu transaksi.
Error di tengah penyimpanan me-rollback semuanya; attempt aktif yang telah dibuat
sebelumnya tetap in_progress, sehingga bisa dicoba lagi. Error SQL dilaporkan
ke log dan HTTP mendapat 503 generik, tanpa detail query atau kunci jawaban.

## Progres dan navigasi

Gagal sebelum pernah lulus → in_progress/completed_at null.
Lulus → completed/completed_at waktu kelulusan pertama.
Gagal atau lulus lagi setelah completed → progres dan waktu pertama tetap.
Unique user_id/material_id mencegah row progres ganda. Nilai terbaik diperoleh
melalui MAX(score) attempt completed, bukan kolom tambahan atau localStorage.

HTML awal membaca progress akun dari DB. JavaScript membuka Next dan CTA hasil
berdasarkan `progress.next_unlocked` response server, tanpa reload. Refresh,
logout/login kembali dan perangkat lain membaca progres yang sama. Urutan Next
akun login berasal dari chapter_order dan registry server, tidak di-hardcode JS.
BAB 6 tetap menuju halaman Evaluasi Akhir; engine BAB 7 tidak diintegrasikan.

Navigasi ini bukan pembatasan URL materi: halaman BAB tetap publik, dan quiz
akun login tidak menerapkan prasyarat akses BAB sebelumnya. Kebijakan course
gating server/URL langsung adalah pekerjaan terpisah. Mengubah DOM sendiri
tidak memberikan kelulusan database atau hak mengubah attempt akun lain.

`oopy.quiz.progress` dan key kelulusan legacy tidak lagi dibaca/ditulis/diimpor.
Data lama tidak dihapus; forged passed/skor lokal tidak membuka progres resmi.
Namespace localStorage Live Coding/BAB 7 tidak disentuh. Akun yang berbagi
browser tidak mewarisi progres karena seluruh endpoint scoped ke session.

## File dan verifikasi

File baru: config/quiz.php; QuizController; StartQuizRequest/SubmitQuizRequest;
ChapterQuizService/QuizGradingService; QuizAttemptPolicy; empat suite feature
Quiz beserta QuizTestCase; tests/browser/quiz-helpers.mjs; dokumen ini.
File berubah: bootstrap/app.php, MateriController, routes/web.php, OopyContentSeeder, Blade quiz/show,
oopy-quiz.js, quiz.css, MateriTest, browser quiz/material dan tiga dokumen existing.
Model, migration, bank materi, engine Live Coding dan BAB 7 tidak diubah.

```powershell
php artisan test --compact
node --test tests/js/*.test.js
node --check public/js/oopy-quiz.js
php artisan route:list
php artisan migrate:status
php vendor/bin/pint --test bootstrap/app.php app/Services app/Policies app/Http/Requests/Quiz app/Http/Controllers/QuizController.php app/Http/Controllers/MateriController.php config/quiz.php database/seeders/OopyContentSeeder.php routes/web.php tests/Feature/Quiz tests/Feature/MateriTest.php
git diff --check
node tests/browser/quiz.mjs
node tests/browser/material.mjs
node tests/browser/chapters.mjs
```

Browser scripts harus diarahkan ke server pengujian terpisah yang telah migrate
dan seed katalog. Jangan arahkan ke database produksi/akun nyata: script membuat
akun dengan credential acak dan riwayat kuis. Feature tests memakai SQLite
in-memory sesuai phpunit.xml. Browser memakai SQLite file terpisah dengan env
proses; `.env` aplikasi tidak diubah. Fixture jawaban dibaca lewat PHP CLI lokal
oleh test runner, bukan dari page/HTTP. `chapters.mjs` merupakan modul fixture,
bukan suite assertion mandiri. `OOPY_ASSET_CACHE` opsional dapat menunjuk cache
Bootstrap/font asli ketika akses CDN browser dibatasi.

### Hasil verifikasi — 9 Oktober 2026

| Pemeriksaan | Hasil |
| --- | --- |
| Seluruh `php artisan test --compact` | 97 test lulus, 2448 assertions; 24 kasus feature kuis baru, authentication/regresi existing tetap lulus. |
| Node unit/regression | 19 test lulus, termasuk runtime/checker Live Coding dan Evaluasi Akhir. |
| Sintaks oopy-quiz.js dan browser scripts | Lulus. |
| Pint seluruh PHP pekerjaan | Lulus. |
| `git diff --check` | Lulus. |
| Route list | 18 route, termasuk tiga endpoint kuis `auth`; route belajar tetap publik. |
| Migration status koneksi lokal | Semua 14 migration existing Ran, tanpa migration baru. |
| Katalog koneksi lokal, baca-saja | Driver mysql; enam chapter_quiz masing-masing lima soal dengan 3 PG/2 code-fill, material valid, passing_score 80. Tidak ada seeding/write pada DB lokal. |
| Browser quiz.mjs, Edge headless | Keenam BAB: nilai 0/20/40/60/80/100, batas 3 gagal/4 lulus, payload publik tanpa kunci, immutable duplicate replay, retry/refresh, kelulusan monotonic, nilai terbaik, isian inline. |
| Browser responsif | Form, hasil gagal/lulus dan isian keenam BAB lulus pada 320/390/768/1024/1440px tanpa overflow. Screenshot mobile/desktop diperiksa. |
| Browser akun/perangkat | Register, logout/login, session browser lain, akun kedua di browser sama, forged localStorage dan endpoint guest lulus. |
| Browser resilience | Koneksi submit terputus tidak menampilkan kelulusan; retry mempertahankan jawaban; loading/double-submit lulus. Snapshot progres lama yang datang setelah completion dan Coba Lagi tidak mengunci ulang Next. Dijalankan ulang setelah normalisasi kode terakhir. |
| Browser material.mjs | Lulus penuh: BAB 1–6, sidebar/manual collapse/anchor/history/focus/no-JS, Monaco/Pyodide asli, submit/reset latihan, satu worker/loader, BAB 6 menuju intro BAB 7. |
| Browser material.mjs --sidebar-only | Keenam BAB pada seluruh lima ukuran lulus tanpa page error. |
| chapters.mjs | Modul kontrak/fixture berhasil dimuat; assertion memakai fixture tersebut di quiz/material tests. |

Feature tests memakai SQLite in-memory. Akun/riwayat browser hanya dibuat pada
SQLite testing terpisah. Bootstrap/font asli memakai cache pengujian; regresi
materi penuh memperoleh akses CDN Monaco/Pyodide melalui eksekusi browser yang
diizinkan. Tidak ada perubahan dependensi/CDN aplikasi, `.env`, data DB lokal,
materi Python, checker, ataupun engine BAB 7. Peringatan PHP OpenSSL dimuat dua
kali tetap berasal dari lingkungan dan tidak menggagalkan pengujian.
Stress concurrency native MariaDB/MySQL belum dijalankan; penguncian/transaction
implementasi diuji melalui idempotent replay, ownership, constraint existing,
dan rollback kegagalan insert di SQLite.

## Perbaikan penyimpanan pada MariaDB — 9 Oktober 2026

Pada MariaDB lokal, kolom started_at dari migration lama mendapat implicit
`DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`. Saat completion,
waktu mulai otomatis diganti waktu sistem DB. Dengan aplikasi UTC dan DB
SYSTEM (+08), waktu selesai yang dikirim aplikasi terlihat lebih awal daripada
waktu mulai baru sehingga `oopy_quiz_attempts_check` menolak update (4025).
SQLite tidak mempunyai perilaku implicit tersebut, sehingga pengujian awal
tidak menangkap masalah ini.

Migration `2026_10_09_000012_fix_quiz_attempt_started_at_timestamp` menetapkan
DEFAULT CURRENT_TIMESTAMP secara eksplisit tanpa ON UPDATE. Constraint
completed_at >= started_at tetap aktif, nilai timestamp existing tetap, dan
tidak ada perubahan timezone server/.env atau penghapusan data. Migration
diterapkan pada DB lokal, batch 3; kini 15 migration Ran. Down sengaja tidak
mengembalikan perilaku implicit yang merusak waktu mulai.

Error direproduksi dengan fixture transaksi pada MariaDB sebelum migration.
Sesudahnya, nilai 0/20/40/60/80/100, lima jawaban, waktu mulai tetap, dan duplicate
replay lulus pada MariaDB asli. Seluruh fixture di-rollback; tidak ada akun,
attempt, jawaban atau progres pengujian yang ditinggalkan. Test regression
completion-preserves-start ditambahkan, dan test rollback SQLite disesuaikan
dengan 12 migration pembelajaran. Catatan hasil awal di atas adalah hasil
sebelum migration kompatibilitas ini, bukan klaim stress concurrency MariaDB.
Verifikasi akhir perbaikan: **98 test PHP lulus, 2452 assertions**; Pint dan
git diff --check lulus. Seluruh enam rentang nilai juga lulus pada MariaDB asli.
Referensi perilaku implicit:
[TIMESTAMP MariaDB](https://mariadb.com/docs/server/reference/data-types/date-and-time-data-types/timestamp#automatic-values).

## Tahap lanjutan

Dashboard Siswa dapat membaca relasi attempt dan progres existing dengan policy
ownership dan pagination, tanpa menambah kolom nilai terbaik. Berikutnya rancang
versioning/snapshot soal sebelum mengubah bank yang sudah dijawab; model existing
menolak perubahan grading fields pada soal yang sudah memiliki jawaban.
Tambahkan cleanup/expiry draft bila diperlukan, uji konkurensi native MariaDB
dan MySQL dalam CI, lalu integrasikan submission Live Coding serta BAB 7 secara
terpisah dengan aturan masing-masing. Tidak ada migrasi skor legacy localStorage
ke kelulusan resmi dan tidak ada commit/push otomatis pada implementasi ini.

Referensi:
[transaksi Laravel 12](https://laravel.com/docs/12.x/database#database-transactions),
[policies](https://laravel.com/docs/12.x/authorization#creating-policies),
[Form Request](https://laravel.com/docs/12.x/validation#form-request-validation).
Normalisasi mengikuti [ECMAScript String.trim](https://tc39.es/ecma262/multipage/text-processing.html#sec-string.prototype.trim).
