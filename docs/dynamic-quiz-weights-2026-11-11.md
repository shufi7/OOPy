# Bobot nilai dinamis kuis BAB OOPy

Tanggal verifikasi: 11 November 2026, WITA.

Pembaruan pemilihan soal arsip dan hasil regresi terbaru tersedia pada [catatan pengaktifan kembali soal](archived-question-reactivation-2026-11-11.md).

## Ringkasan implementasi

Admin dapat menerbitkan paket Kuis BAB 1–6 berisi 1–30 soal. Pilihan ganda dan isian kode memiliki bobot setara; komposisinya bebas, termasuk paket yang hanya berisi salah satu jenis. Draft tidak mengubah soal aktif. Paket baru berlaku pada percobaan baru, sedangkan percobaan yang sudah berjalan tetap menggunakan daftar soal yang dikunci ketika dimulai.

Aturan ada di `config/quiz.php`: `minimum_questions = 1`, `maximum_questions = 30`, `passing_score = 80`, dan `maximum_answer_length = 1000`. Batas jumlah dapat dikonfigurasi. Ambang kelulusan pekerjaan ini tetap 80/100; tidak ada pengaturan Admin untuk menggantinya.

Tidak ada perubahan skema, migrasi baru, perubahan `.env`, penghitungan ulang nilai lama, commit, atau push.

## Hasil audit sebelum implementasi

Pembatasan sebelumnya terdapat di beberapa lapisan:

1. `config/quiz.php` menetapkan 5 soal, minimal 4 benar, 3 pilihan ganda, dan 2 isian kode.
2. `QuestionBankService` menolak komposisi selain 3+2 untuk kuis BAB. Controller publikasi juga memiliki batas umum 20 soal.
3. `ChapterQuizService` menolak paket dengan jumlah atau komposisi berbeda.
4. `SubmitQuizRequest` mewajibkan tepat 5 jawaban.
5. `QuizGradingService` memakai jumlah soal global sebagai pembagi dan membulatkan nilai ke bilangan bulat.
6. Controller materi dan JavaScript membawa jumlah/minimal jawaban benar tetap. Dashboard juga menampilkan petunjuk kelulusan 4 dari 5.
7. `quiz_attempt_questions` sudah menyimpan ID dan posisi soal per percobaan. Kolom `quiz_attempts.score` sudah bertipe desimal dua angka, sehingga kebutuhan ini tidak memerlukan kolom bobot atau jumlah soal tambahan.
8. Evaluasi Akhir mempunyai jalur percobaan dan penilaian tersendiri, dengan 20 soal berkomposisi 10 pilihan ganda, 5 isian kode, dan 5 uraian.

`Quiz`, `QuizAttempt`, `QuestionRequest`, seeder, aturan integritas database, serta alur progres dan laporan turut diperiksa. Tipe kolom nilai dan relasi pada `Quiz`/`QuizAttempt` dipertahankan.

## File yang dibuat atau diubah

| File | Perubahan |
| --- | --- |
| `app/Services/ChapterQuizScoring.php` | Baru: aturan nilai, rasio kelulusan, minimal jawaban benar, ringkasan bobot, dan format maksimal dua desimal. |
| `config/quiz.php` | Mengganti jumlah/komposisi tetap dengan batas jumlah dan ambang nilai. |
| `app/Services/Admin/QuestionBankService.php` | Publikasi fleksibel, validasi semua soal, transaksi, penguncian percobaan lama, serta pemeriksaan versi paket dan draft. |
| `app/Models/Question.php` | Validasi ulang isi dan kunci soal sebelum publikasi. |
| `app/Http/Requests/Admin/QuestionRequest.php` | Memvalidasi versi formulir revisi agar penyimpanan tidak menimpa perubahan Admin lain. |
| `app/Http/Controllers/Admin/QuestionController.php` | Batas publikasi sesuai tipe kuis, pesan validasi, dan pemeriksaan versi formulir. |
| `resources/views/admin/questions/index.blade.php` | Informasi paket aktif, preview pilihan, bobot, komposisi, dan token versi paket. |
| `resources/views/admin/questions/form.blade.php` | Token versi soal pada formulir edit/revisi. |
| `public/js/admin/question-publication.js` | Baru: preview langsung dan penonaktifan tombol untuk pilihan kosong/tidak valid. |
| `public/css/oopy/admin/admin.css` | Area pilih checkbox 44 px, indikator fokus, dan lebar tabel Bank Soal dengan gulir lokal. |
| `app/Services/ChapterQuizService.php` | Memvalidasi jumlah fleksibel dan mengembalikan soal dari percobaan yang tepat. |
| `app/Http/Requests/Quiz/SubmitQuizRequest.php` | Jumlah jawaban wajib mengikuti paket percobaan. |
| `app/Services/QuizGradingService.php` | Pembagi dari paket aktual, nilai dua desimal, validasi duplikasi, dan progres berdasarkan rasio. |
| `app/Http/Controllers/QuizController.php` | Respons mulai memuat soal dan informasi penilaian percobaan yang sama. |
| `app/Http/Controllers/MateriController.php` | Konfigurasi halaman mengikuti jumlah soal yang benar-benar ditampilkan. |
| `public/js/oopy-quiz.js` | Menerima perubahan jumlah dari API, mengosongkan jawaban jika paket berganti sebelum mulai, serta memperbarui petunjuk dan format nilai. |
| `resources/views/materi/partials/quiz.blade.php` | Petunjuk jenis soal mengikuti paket yang ditampilkan. |
| `app/Services/DashboardProgressService.php` | Ambang nilai terpusat dan format nilai dua desimal. |
| `resources/views/dashboard/index.blade.php` | Mengganti petunjuk tetap 4/5 dengan ketentuan nilai 80/100. |
| `app/Services/Admin/LearningReportService.php` | Ambang nilai terpusat dan format nilai dua desimal, termasuk CSV. Filter sebelumnya dipertahankan. |
| `resources/views/admin/users/show.blade.php` | Status nilai memakai ambang yang sama. |
| `database/seeders/OopyContentSeeder.php` | Ambang awal berasal dari kebijakan terpusat; komposisi konten awal tetap tersedia. |
| `tests/Feature/Quiz/DynamicQuizTest.php` | Baru: nilai dinamis, paket aktif/historis, publikasi, rollback, progres, dan laporan. |
| `tests/Feature/Admin/QuestionBankTest.php` | Paket kosong ditolak dan perubahan draft dari formulir kedaluwarsa dilindungi. |
| `tests/js/question-publication.test.js` | Baru: preview jumlah, komposisi, presisi, jenis, serta batas pilihan. |
| `tests/browser/dynamic-quiz.mjs` | Baru: alur Admin/siswa, pergantian paket, CSV, layout, kontras, dan keyboard pada browser. |
| `tests/integration/dynamic-quiz-mariadb.php` | Baru: integrasi pada database MariaDB sementara yang dihapus setelah pengujian. |
| `docs/evidence/dynamic-quiz-2026-11-11/` | Screenshot dan hasil verifikasi browser/MariaDB. |

## Cara menghitung nilai dan kelulusan

Untuk paket dengan `N` soal dan `C` jawaban benar:

```text
bobot setiap soal = 100 / N
nilai tersimpan = round(C × 100 / N, 2)
lulus = C × 100 >= N × 80
minimal benar = ceil(N × 80 / 100)
```

Bobot setiap soal tidak dibulatkan dahulu lalu dijumlahkan. Contohnya, 6 soal bukan berarti menjumlahkan enam nilai 16,67. Sistem menghitung rasio sekali, sehingga semua jawaban benar tetap menghasilkan 100.

| Jumlah soal | Benar | Nilai | Status |
| --- | --- | --- | --- |
| 5 | 4 | 80 | Lulus |
| 6 | 5 | 83,33 | Lulus |
| 6 | 4 | 66,67 | Belum lulus |
| 10 | 8 | 80 | Lulus |
| 20 | 15 | 75 | Belum lulus |
| 30 | 30 | 100 | Lulus |
| 10 | 0 | 0 | Belum lulus |

Laravel menentukan nilai resmi dan memeriksa seluruh ID/jawaban. Jumlah, nilai, identitas, atau status yang dikirim browser tidak dijadikan sumber penilaian. Respons soal tetap menggunakan daftar field publik tanpa kunci atau hasil benar/salah per soal.

## Publikasi dan penyuntingan bersamaan

Publikasi mengunci baris kuis dan soal terpilih dalam satu transaksi. Backend menolak paket kosong, jumlah di luar batas, ID duplikat/tidak sah, soal kuis lain, urutan tampilan bentrok, isi/kunci tidak valid, dan uraian pada kuis BAB. Soal arsip kuis BAB dapat dipilih untuk diaktifkan kembali, dengan isi dan ID yang sama agar riwayat tetap utuh. Soal arsip Evaluasi Akhir tetap tidak dapat diterbitkan langsung. Kegagalan validasi atau database membatalkan seluruh perubahan, termasuk pengarsipan dan penguncian soal legacy.

Formulir membawa sidik versi paket aktif. Jika Admin lain sudah menerbitkan paket berbeda, backend menolak formulir lama dan meminta peninjauan ulang. Formulir edit soal juga membawa versi isi; penyimpanan dari formulir kedaluwarsa tidak dapat menimpa perubahan draft yang lebih baru. Revisi soal terbit tetap membuat ID draft baru.

## Paket yang sedang dikerjakan dan riwayat lama

- Percobaan baru menyimpan ID dan posisi soal di `quiz_attempt_questions` saat mulai. Semua validasi, pembagi nilai, dan hitungan benar/salah memakai daftar ini.
- Paket 5 soal yang sedang dikerjakan tetap 5 soal setelah publikasi paket 10 soal. Percobaan baru mendapat 10 soal.
- Percobaan aktif lama tanpa daftar soal dikunci ke paket terbit yang tersedia saat dilanjutkan, atau sebelum paket tersebut diganti oleh Admin.
- Percobaan selesai tanpa daftar soal membaca rincian dari `quiz_answers` miliknya sendiri. Sistem tidak mengambil paket terbaru untuk merekonstruksi percobaan selesai.
- Nilai tersimpan tidak dihitung ulang. Pengulangan submit dengan paket yang sah mengembalikan hasil tersimpan dan tidak menambah jawaban atau mengubah waktu selesai.
- Progres yang sudah selesai dan waktu kelulusan pertama tetap dipertahankan meskipun percobaan berikutnya gagal. Nilai terbaik tetap berasal dari nilai percobaan selesai yang tersimpan.
- Data impor yang tidak memiliki daftar soal maupun rincian jawaban tidak menyediakan informasi yang cukup untuk memulihkan paket asli. Nilai historisnya tetap tersedia pada laporan; rincian soal tidak ditebak dari paket terbaru. Data demikian memerlukan pemeriksaan manual jika rinciannya dibutuhkan.

Tidak ada migrasi baru. Kolom nilai, foreign key, pembatasan penghapusan soal, dan data riwayat existing tetap digunakan.

## Hasil pengujian otomatis

| Pemeriksaan | Hasil akhir |
| --- | --- |
| `php artisan test --compact` | PASS: 236 tes, 4.473 assertion, SQLite terisolasi. |
| `node --test tests/js/*.test.js` | PASS: 23 tes, termasuk preview baru, Coding, materi, runtime, dan Evaluasi Akhir. |
| `php artisan route:list --except-vendor` | PASS: 50 route terdaftar. |
| `composer validate` | PASS: `composer.json` valid. |
| Laravel Pint pada file PHP yang berubah | PASS. |
| `git diff --check` | PASS. |
| `php tests/integration/dynamic-quiz-mariadb.php` | PASS: 14 pemeriksaan pada database MariaDB 10.4.32 sementara; database uji dihapus pada akhir proses. |

Suite PHP mencakup login/registrasi, Google OAuth dengan mock, akses materi, progres Dashboard, Rekap Nilai dan filter/CSV, Coding, Evaluasi Akhir, penghapusan, serta otorisasi route. Tes baru mencakup paket 1/5/6/10/20/30 soal, paket satu jenis, validasi jawaban, penggantian paket saat aktif, nilai historis, submit berulang, konfigurasi batas, perubahan Admin yang kedaluwarsa, dan rollback publikasi saat database gagal.

## Hasil pengujian browser dan Delivery Gate

Browser: Microsoft Edge headless dengan Playwright. Database SQLite browser terpisah dari database aplikasi. Aset CDN dilayani dari salinan cache lokal yang tersedia agar pemeriksaan tidak bergantung pada jaringan CDN.

Alur baru diperiksa pada lebar 360, 390, 768, 1024, dan 1440 px: 15 pemeriksaan layout, 43 pemeriksaan pasangan warna, 6 kelompok interaksi, dan tidak ada exception JavaScript. Alur ini mencakup membuat lima draft dari formulir, preview komposisi 6 pilihan ganda + 4 isian kode, pemilihan dengan keyboard, penolakan paket kosong dan formulir kedaluwarsa, penerbitan 10 lalu 6 soal, penyelesaian paket lama 5/10 soal, retry dengan paket 6 soal, nilai 83,33, filter, serta unduhan CSV.

Regresi Admin/Coding yang sudah tersedia juga dijalankan: 25 pemeriksaan layout, 209 pemeriksaan pasangan warna, 6 kelompok interaksi, tanpa exception JavaScript.

- Hard Gate PASS: formulir publikasi, pilihan kosong, pesan validasi, kuis, filter, Reset, dan CSV diuji melalui browser; tabel bergulir lokal tanpa overflow halaman.
- Purpose Gate PASS: ringkasan menggunakan komponen dan warna OOPy yang sudah ada untuk membandingkan paket aktif dengan pilihan sebelum publikasi. Tidak ada aset atau dekorasi baru.
- Liveliness PASS: arah `DESIGN.md` dipertahankan, ENERGY 2 / RHYTHM 2 / MOTION 1. Judul BAB, jenis soal, dan tindakan publikasi menjadi penanda tugas.
- Craftsmanship PASS: pemilihan memakai label dengan area 44 px dan fokus terlihat; checkbox dapat diubah dengan Space; tabel dapat digulir dengan tombol panah. Pasangan warna yang diuji memenuhi ambang teks normal/besar.

Bukti tersimpan di [folder evidence](evidence/dynamic-quiz-2026-11-11/): screenshot Bank Soal sebelum/sesudah publikasi pada desktop/seluler, hasil kuis 83,33, serta hasil JSON browser dan MariaDB. Screenshot sebelum/sesudah membandingkan keadaan paket 5 dan 10 soal; bukan perbandingan revisi kode lama dengan kode baru.

## Kendala, risiko, dan batas verifikasi

1. Login Google diuji dengan mock sesuai suite yang tersedia; login ke akun Google nyata tidak dijalankan.
2. Perlindungan perubahan bersamaan diuji melalui versi formulir kedaluwarsa, termasuk dua tab browser dan MariaDB. Uji beban dengan banyak proses Admin/siswa yang benar-benar simultan belum dijalankan.
3. Suite PHP lengkap dijalankan pada SQLite. MariaDB menjalankan skenario integrasi khusus, bukan seluruh 236 tes; sebagian tes kegagalan memakai sintaks trigger SQLite.
4. Tidak ada reset, migrasi, seeding, atau perubahan data pada database utama. Pengujian browser memakai data sintetis pada database terpisah.
5. Ambang pekerjaan ini tetap 80. Mengubah kebijakan ambang secara historis atau memberi bobot berbeda per soal merupakan pengembangan lain dan memerlukan aturan versi kebijakan tersendiri.

## Panduan penggunaan Admin

1. Buka **Bank Soal**, pilih kuis BAB, lalu tekan **Tampilkan**.
2. Tambahkan soal melalui **Tambah soal**. Pilih pilihan ganda atau isian kode, lengkapi isi/kunci, dan gunakan urutan tampilan yang berbeda.
3. Simpan draft. Jumlah soal aktif dan bobot aktif belum berubah.
4. Kembali ke Bank Soal. Centang semua soal yang akan menjadi paket baru, termasuk soal aktif yang masih ingin dipakai.
   Soal BAB yang berstatus **Diarsipkan** juga dapat langsung dicentang untuk diaktifkan kembali, tanpa membuat revisi apabila isinya tidak berubah.
5. Periksa jumlah, komposisi, dan bobot pada **Preview paket terpilih**.
6. Tekan **Terbitkan paket terpilih**. Soal aktif yang tidak dipilih menjadi arsip. Jika ada pesan formulir kedaluwarsa, tinjau ulang perubahan Admin lain sebelum menerbitkan kembali.

Contoh: BAB 1 berisi 5 soal. Admin membuat 5 draft baru dengan urutan 6–10, lalu memilih kesepuluh soal dan menerbitkannya. Preview menunjukkan bobot 10 poin per soal dan nilai maksimal 100. Siswa yang baru memulai mendapat 10 soal; 8 jawaban benar menghasilkan 80 dan lulus. Siswa yang sudah memulai paket 5 soal sebelumnya tetap mendapat nilai 80 untuk 4 jawaban benar.

Evaluasi Akhir tetap memakai paket 20 soal dan penilaian uraian manual. Formulir tersebut tidak mendapat pembagian bobot dinamis kuis BAB.
