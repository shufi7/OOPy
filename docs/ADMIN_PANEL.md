# Laporan implementasi Admin OOPy

Tanggal: 29 Oktober 2026 (WITA).

## A. Ringkasan implementasi

Panel `/admin` memakai autentikasi lama, middleware `auth` dan `role:admin`, serta navigasi Dashboard, Manajemen Pengguna, Rekap Nilai, Bank Soal, dan Manajemen Materi. Semua endpoint Admin, termasuk perubahan data, menolak pengguna biasa dengan 403 sebelum model binding. Guest diarahkan ke login. Respons Admin tidak disimpan dalam cache browser.

Dashboard mengambil statistik asli dari database. Daftar pengguna menyediakan pencarian, pagination, detail progres enam BAB, nilai terbaik, riwayat kuis, serta evaluasi. Password dan token tidak ditampilkan. Tidak ada penghapusan akun atau promosi role melalui antarmuka.

Rekap mengambil nilai terbaik percobaan BAB yang selesai, mempunyai waktu selesai sah, dan nilai 0–100. Nilai nol dibedakan dari belum mengerjakan. Tersedia filter BAB/status, pencarian, pengurutan, pagination, serta CSV UTF-8 dengan BOM, delimiter titik koma, quoting, dan perlindungan formula injection. CSV mengikuti filter. Rata-rata rekap dihitung dari nilai terbaik BAB yang tersedia; rata-rata dashboard memakai seluruh percobaan selesai.

Bank Soal menyediakan draft, revisi, pratinjau, urutan tampilan, pengarsipan, dan publikasi paket secara atomik. Paket wajib berisi 3 pilihan ganda + 2 isian kode untuk BAB, atau 10 pilihan ganda + 5 kode + 5 uraian untuk evaluasi. Perubahan soal aktif dibuat sebagai versi baru. Percobaan BAB mengunci identitas/urutan soal saat dimulai; nilai historis tetap memakai versi lama. Tidak ada penghapusan permanen. Seeder tidak menimpa soal yang sudah tersedia.

Materi memiliki draft dan publikasi berbentuk JSON terstruktur dengan fallback file asli. Teks, tujuan, rangkuman, refleksi, paragraf, judul bagian, contoh Python, output, dan catatan bisa diedit. Bagian baru bisa ditambahkan dan diurutkan. Pratinjau tersedia sebelum penyimpanan dan sebelum publikasi. Tabel, contoh kompleks, live coding, serta checker lama dipertahankan sebagai konfigurasi tepercaya yang tidak bisa ditimpa form. Konten form dirender sebagai teks ter-escape, bukan PHP/HTML yang dieksekusi.

Evaluasi Akhir kini memakai penilaian server dan snapshot soal per percobaan. Identitas akun, deadline, autosave, kepemilikan, serta submission idempoten diperiksa pada backend. Kunci tidak dikirim ke HTML atau API pengguna. Objektif bernilai maksimal 75 poin; lima uraian masing-masing maksimal 5 poin. Total tetap kosong sampai Admin memberikan seluruh skor uraian. Persentase objektif, poin objektif, poin uraian, dan total disimpan terpisah. Praktik tamu memakai sesi browser dan tidak masuk rekap resmi. Riwayat localStorage lama tetap dapat dibaca sebagai latihan belum diverifikasi.

## B. File baru

Setiap path di bawah merupakan file baru.

| File | Kegunaan |
| --- | --- |
| `app/Http/Controllers/Admin/DashboardController.php` | Dashboard statistik asli. |
| `app/Http/Controllers/Admin/UserController.php` | Daftar/detail pengguna. |
| `app/Http/Controllers/Admin/ScoreController.php` | Rekap dan streaming CSV. |
| `app/Http/Controllers/Admin/QuestionController.php` | CRUD draft, revisi, arsip, publikasi paket soal. |
| `app/Http/Controllers/Admin/MaterialController.php` | Editor, preview, draft, publikasi materi. |
| `app/Http/Controllers/Admin/FinalExamController.php` | Pemeriksaan jawaban dan penilaian uraian. |
| `app/Http/Controllers/FinalExamAttemptController.php` | API state/start/autosave/submit evaluasi. |
| `app/Http/Middleware/PreventAdminCaching.php` | Header no-store dan nosniff untuk Admin. |
| `app/Http/Requests/Admin/ReportRequest.php` | Validasi filter/pencarian/sort. |
| `app/Http/Requests/Admin/QuestionRequest.php` | Validasi jenis soal, indeks nol, kode, rubrik. |
| `app/Http/Requests/Admin/MaterialRequest.php` | Validasi struktur materi yang boleh diedit. |
| `app/Http/Requests/Admin/GradeFinalExamRequest.php` | Validasi lima skor uraian dan feedback. |
| `app/Http/Requests/FinalExamAnswerRequest.php` | Validasi payload jawaban evaluasi. |
| `app/Models/MaterialContent.php` | Draft/publikasi serta versi perubahan materi. |
| `app/Models/FinalExamAttempt.php` | Snapshot, jawaban, hasil, deadline, penilai. |
| `app/Services/Admin/LearningReportService.php` | Query statistik/agregasi dan format laporan. |
| `app/Services/Admin/QuestionBankService.php` | Revisi dan publikasi transaksional. |
| `app/Services/Admin/MaterialContentService.php` | Konversi struktur aman, fallback, optimistic locking. |
| `app/Services/FinalExamService.php` | Penilaian resmi dan praktik tamu. |
| `database/seeders/AdminSeeder.php` | Pembuatan akun Admin lokal yang dapat dijalankan setelah Git pull, tanpa menimpa akun lama. |
| `database/migrations/2026_10_29_000001_add_question_publication_and_attempt_versions.php` | Metadata publikasi soal dan penguncian set soal percobaan. |
| `database/migrations/2026_10_29_000020_create_material_contents_table.php` | Penyimpanan draft/publikasi materi. |
| `database/migrations/2026_10_29_000300_create_final_exam_attempts_table.php` | Penyimpanan hasil evaluasi terpusat. |
| `routes/admin.php` | Grup route Admin dan laporan. |
| `routes/admin-questions.php` | Route bank soal dalam grup Admin. |
| `routes/admin-materials.php` | Route materi dalam grup Admin. |
| `routes/admin-evaluations.php` | Route penilaian dalam grup Admin. |
| `routes/final-evaluation.php` | API evaluasi, pembatasan request dan session lock. |
| `resources/views/admin/layouts/app.blade.php` | Layout Admin, feedback, skip link. |
| `resources/views/admin/partials/sidebar.blade.php` | Menu, kembali ke OOPy, POST logout. |
| `resources/views/admin/dashboard/index.blade.php` | Ringkasan dan aktivitas terbaru. |
| `resources/views/admin/users/index.blade.php` | Tabel/pencarian pengguna. |
| `resources/views/admin/users/show.blade.php` | Progres, percobaan, hasil evaluasi pengguna. |
| `resources/views/admin/scores/index.blade.php` | Matriks nilai/filter/export. |
| `resources/views/admin/questions/index.blade.php` | Daftar soal dan pemilihan paket publikasi. |
| `resources/views/admin/questions/form.blade.php` | Form tambah/revisi soal. |
| `resources/views/admin/questions/show.blade.php` | Pratinjau soal dan arsip draft. |
| `resources/views/admin/materials/index.blade.php` | Daftar enam BAB. |
| `resources/views/admin/materials/edit.blade.php` | Editor teks/bagian/kode/output. |
| `resources/views/admin/materials/section-form.blade.php` | Field bagian pembelajaran. |
| `resources/views/admin/materials/preview.blade.php` | Pratinjau menggunakan komponen materi asli. |
| `resources/views/admin/evaluations/show.blade.php` | Jawaban snapshot dan form skor uraian. |
| `public/css/oopy/admin/admin.css` | Gaya Admin dengan token OOPy. |
| `public/js/admin/admin.js` | Menu responsif dan pengembalian fokus via Escape. |
| `public/js/admin/materials.js` | Menambah/membatalkan bagian baru dalam editor. |
| `tests/Feature/Admin/ReportsTest.php` | Otorisasi seluruh endpoint, statistik, filter, CSV, evaluasi pending. |
| `tests/Feature/Admin/AdminSeederTest.php` | Login Admin hasil seed, password hashed, seeder berulang, konflik akun, dan pembatasan lingkungan. |
| `tests/Feature/Admin/QuestionBankTest.php` | Validasi, publikasi, versi, seeder, payload malformed. |
| `tests/Feature/Admin/MaterialsTest.php` | Draft/preview/publish, XSS, kode, live coding, konflik perubahan. |
| `tests/Feature/Admin/FinalEvaluationTest.php` | Deadline, IDOR, skor server, snapshot, uraian, guest. |
| `tests/Feature/Admin/LearningMigrationLifecycleTest.php` | Rollback/reapply skema kosong pada SQLite terisolasi. |
| `tests/browser/admin-sandbox.php` | Router/fixture SQLite browser yang terpisah dari database aplikasi. |
| `tests/browser/admin-cdp.py` | Browser QA tanpa dependensi npm/pip, viewport, kontras, screenshot. |
| `tests/browser/final_evaluation_cdp.py` | Alur evaluasi resmi sampai penilaian Admin di browser. |
| `tests/browser/questions_cdp.py` | Revisi/publikasi soal dan tampilan nyata bagi siswa. |
| `tests/browser/materials_cdp.py` | Tambah bagian, preview, publish, keyboard dan tampilan nyata. |
| `docs/ADMIN_PANEL.md` | Laporan ini dan petunjuk penggunaan. |

## C. File lama yang diubah

| File | Perubahan dan alasan |
| --- | --- |
| `routes/web.php` | Memuat grup Admin/API evaluasi, mempertahankan route lama. |
| `bootstrap/app.php` | Role diperiksa sebelum model binding; field kode dikecualikan dari TrimStrings agar indentasi utuh. |
| `resources/views/components/navbar.blade.php` | Tautan Panel Admin hanya untuk role Admin. |
| `app/Models/Question.php` | Metadata publikasi, revisi, urutan, rubrik; proteksi soal berjawaban diperluas ke soal percobaan aktif. |
| `app/Models/Quiz.php` | Relasi soal terbit dan semua versi dipisahkan. |
| `app/Services/ChapterQuizService.php` | Menyimpan/membaca set soal yang dikunci saat start. |
| `app/Services/QuizGradingService.php` | Grading memakai soal percobaan, tanpa mengganti aturan perbandingan kode. |
| `app/Http/Controllers/QuizController.php` | Respons start menambahkan soal versi percobaan. |
| `public/js/oopy-quiz.js` | Mengadopsi soal versi start secara aman, membersihkan isian jika versi berubah. |
| `database/seeders/OopyContentSeeder.php` | Soal/kuis existing tidak ditimpa pada reseed. |
| `app/Http/Controllers/MateriController.php` | Overlay materi terbit dan soal terbit/versi aktif; fallback file tetap tersedia. |
| `app/Http/Controllers/EvaluasiAkhirController.php` | Config publik tanpa kunci, URL API, identitas mode akun/tamu. |
| `public/js/evaluasi/exam.js` | API otoritatif, autosave/draft, deadline server, tampilan skor lengkap/pending. |
| `resources/views/evaluasi/layout.blade.php` | Label resmi atau praktik sesi. |
| `resources/views/evaluasi/index.blade.php` | Petunjuk penyimpanan akun/tamu, riwayat lama belum diverifikasi. |
| `resources/views/evaluasi/hasil.blade.php` | Penjelasan bahwa total menunggu uraian. |

Tidak ada perubahan pada file konten `resources/materi/*.php`, desain beranda, Monaco Editor, runtime Pyodide, password, kredensial, atau `.env`. Tes lama tidak dihapus atau diedit.

## D. Perubahan database

Tiga migration tambahan telah dijalankan pada MySQL lokal. Migration soal menambah metadata ke `questions` dan tabel `quiz_attempt_questions`, lalu mengaitkan percobaan lama ke set soal saat migrasi. Migration materi menambah `material_contents`. Migration evaluasi menambah `final_exam_attempts` dengan snapshot, jawaban, hasil terpisah, dan penilai.

Tidak ada tabel lama yang dihapus. Tidak ada akun uji/dummy yang dibuat pada database aktif. Data browser QA berada di `storage/framework/testing/admin-browser.sqlite` dan memakai akun fixture tersendiri. Rollback data pembelajaran berisi riwayat diblokir; rollback skema kosong diuji terpisah. `deadline_at` memakai DATETIME agar kompatibel dengan aturan timestamp MariaDB lokal.

## E. Pengujian

| Pemeriksaan | Hasil aktual |
| --- | --- |
| `php artisan route:list --except-vendor` | PASS: 44 route, 21 di bawah Admin. |
| `php artisan migrate --force` dan `migrate:status` | PASS: ketiga migration tambahan sudah Ran di MySQL lokal. |
| Query dashboard/filter laporan pada MySQL aktif, read-only | PASS. |
| `php artisan test --compact --filter='Admin\|EvaluasiAkhirTest'` | PASS: 39 tes, 648 assertions. |
| `php artisan test --compact` | 158 PASS, 2 FAIL, 3375 assertions pada run penuh terakhir. |
| Browser Chrome headless, SQLite fixture | PASS: 90 pemeriksaan, termasuk publikasi soal/materi dan penilaian uraian sampai tampil ke pengguna. |
| Pint pada file PHP baru | PASS. |
| AdminSeeder dan regresi autentikasi (penambahan 30 Oktober 2026) | PASS: 30 tes, 326 assertions; seeder di MySQL lokal juga mempertahankan akun dan password existing. |
| Syntax JavaScript yang berubah | PASS dengan Node 16 melalui mode module. |
| Unit JavaScript `node --test tests/js/*.test.js` | NOT TESTED: Node yang tersedia v16.13.2 tidak mendukung runner `--test`; npm/Playwright tidak terpasang. |
| Browser suite Playwright lama | NOT TESTED; pengujian CDP baru dijalankan sebagai pemeriksaan browser aktual. |

Dua kegagalan full suite dijelaskan terpisah:

1. `MateriTest::material_list_links_only_to_the_available_chapters` sudah gagal sebelum implementasi (baseline 131 PASS/1 FAIL). Tes mencari substring `<span>Pelajari BAB`, sedangkan view asli mempunyai newline setelah `<span>`. View tersebut tidak diubah.
2. `LearningDatabaseTest::empty_schema_rollback_and_reapply_preserve_existing_users` mengasumsikan tepat 12 migration pembelajaran. Setelah migration tambahan, rollback 12 langkah memang belum mencapai kolom role. Tes lifecycle baru memakai jumlah migration aktual dan membuktikan rollback/reapply aman pada database kosong terisolasi.

Bukti browser: `storage/framework/testing/admin-browser-results/results.json`, beserta screenshot `admin-390.png`, `admin-1440.png`, `admin-scores-390.png`, `admin-scores-1440.png`, dan editor materi pada dua ukuran tersebut. Folder ini diabaikan Git. Screenshot sebelum implementasi tidak tersedia karena Admin belum ada pada baseline.

Menjalankan ulang browser QA (menggunakan executable terpasang):

```powershell
php tests/browser/admin-sandbox.php
php -S 127.0.0.1:8035 -t public tests/browser/admin-sandbox.php
# Jalankan di terminal kedua:
& 'C:\Users\MyBook Hype AMD\AppData\Local\Programs\Python\Python313\python.exe' tests/browser/admin-cdp.py --browser 'C:\Program Files\Google\Chrome\Application\chrome.exe'
```

Fixture hanya dibuat jika file SQLite uji belum ada, tanpa menghapus isi sebelumnya. Pengulangan QA menambah riwayat/draft pada database uji tersebut.

## F. Review Anti-Slop

Desain memakai logo asli, Plus Jakarta Sans, warna mint–teal dan token `base.css`. Sidebar dan tabel mengikuti tugas pengelolaan. Tidak ada angka contoh, grafik dekoratif, gradient, glow, glassmorphism, atau framework UI baru. Radius, jarak, fokus, dan area sentuh memakai token yang tersedia.

Browser memeriksa lebar 360, 390, 768, 1024, 1440 piksel; tabel/kode mempunyai scroll lokal. Menu mobile dapat ditutup dengan Escape dan fokus dikembalikan. Form memiliki label. Reduced motion dihormati. Reflow 720 CSS px diperiksa sebagai ekuivalen viewport 1440 pada zoom 200%; zoom melalui toolbar browser tidak diuji terpisah.

Kontras hasil pembacaan computed styles: tombol utama 6,11:1, menu aktif 5,58:1, header tabel 10,89:1, isi tabel 8,38:1. Ini memenuhi WCAG AA untuk teks normal pada komponen yang diperiksa. Audit screen reader menyeluruh dan seluruh pasangan warna bukan bagian pengujian otomatis ini.

Skill Anti-Slop tidak tersedia pada katalog skill sesi. Prinsip yang tertulis dalam `DESIGN.md` digunakan sebagai acuan langsung.

## G. Cara menggunakan Admin

1. Pada instalasi lain, jalankan `php artisan migrate --force`. Instalasi lokal ini sudah dimigrasikan.
2. Jika konten awal belum pernah diimpor, jalankan `php artisan db:seed --class=OopyContentSeeder`. Seeder konten tidak membuat akun contoh.
3. Buat akun melalui `/register` seperti biasa; role default tetap `user`.
4. Jalankan `php artisan oopy:promote-admin nama@email.test`, lalu jawab konfirmasi command. Tidak ada promosi role lewat HTTP.
5. Login melalui `/login`, lalu buka `/admin` atau menu akun → Panel Admin.
6. Bank Soal: pilih kuis → tambah/revisi → simpan draft → pratinjau → pilih tepat satu paket dengan jenis/jumlah dan urutan yang valid → Terbitkan paket terpilih. Soal aktif yang tidak dipilih diarsipkan; percobaan lama tetap valid.
7. Materi: buka BAB → edit/tambah bagian/ubah urutan → pratinjau → simpan draft → Periksa draf tersimpan → Publikasikan draf ini.
8. Rekap Nilai: atur pencarian/filter/sort; Export CSV menggunakan filter yang sama. Tanda — berarti belum ada nilai, bukan nol.
9. Penilaian uraian: buka evaluasi dari Dashboard atau detail pengguna → isi seluruh lima skor 0–5 sesuai rubrik → simpan. Total sah kemudian muncul pada rekap dan halaman hasil pengguna.

### Membuat akun Admin langsung setelah Git pull

`AdminSeeder` tersedia sebagai seeder terpisah untuk pengembangan lokal. Seeder ini membuat `admin@gmail.com` dengan password `admin123` dan role `admin` sejak awal, sehingga tidak membutuhkan registrasi atau promosi.

Setelah perubahan kode di-push dan teman melakukan pull:

1. Pastikan dependensi terpasang (`composer install` jika belum), `.env` miliknya memakai `APP_ENV=local`, dan koneksi database mengarah ke database di komputernya sendiri. Untuk instalasi baru, buat `.env` dari `.env.example` dan jalankan `php artisan key:generate` jika `APP_KEY` masih kosong.
2. Jalankan migration dan konten awal, kemudian seeder Admin:

   ```bash
   php artisan migrate
   php artisan db:seed
   php artisan db:seed --class=AdminSeeder
   ```

3. Login melalui `/login` menggunakan email `admin@gmail.com` dan password `admin123`, lalu buka `/admin`.

`AdminSeeder` tidak dipanggil otomatis oleh `DatabaseSeeder`. Seeder dapat dijalankan ulang: Admin yang sudah ada tetap menggunakan password, nama, dan data sebelumnya. Jika email tersebut sudah dipakai akun biasa, seeder menolak tanpa mengubah akun itu. Seeder ini hanya berjalan pada lingkungan `local` atau `testing`; kredensial bawaan tidak dibuat pada server produksi.

## H. Batasan dan catatan

Full suite belum seluruhnya hijau karena dua tes yang dijelaskan di atas. Tidak ada klaim semua pemeriksaan PASS. Tes unit JS modern dan suite Playwright lama belum berjalan pada runtime yang tersedia; browser aktual diuji memakai Chrome/CDP tanpa dependensi tambahan.

Komponen materi kompleks hanya dapat dilihat/dipertahankan; editor berfokus pada teks dan contoh sederhana. Enam BAB tetap menjadi ruang lingkup. Riwayat praktik browser lama tidak bisa dipercaya sebagai hasil resmi, sehingga hanya ditampilkan dengan label belum diverifikasi dan tidak diimpor ke rekap.

Penilaian uraian membutuhkan keputusan manusia. Tidak ada nilai uraian buatan. API pengguna menampilkan paling banyak 100 riwayat evaluasi terbaru, sementara seluruh hasil resmi tetap tersimpan dan detail Admin memakai pagination. Praktik tamu dibatasi sepuluh percobaan per sesi.

Belum dilakukan uji beban banyak pengguna atau pengujian pada mesin produksi. Uji browser memakai data terisolasi; kueri laporan serta migration juga sudah diverifikasi pada MySQL lokal.
