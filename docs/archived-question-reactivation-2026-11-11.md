# Mengaktifkan kembali soal arsip kuis BAB

Perbaikan 11 November 2026 untuk soal nomor 6 yang tidak dapat dicentang.

Sebelumnya, Blade mengganti checkbox soal arsip dengan tanda kosong dan service publikasi menolak status arsip. Kuis BAB sekarang menampilkan checkbox untuk soal aktif, draft, dan arsip. Preview menghitung seluruh soal terpilih dan backend mengaktifkan kembali soal arsip hanya setelah paket diterbitkan.

Cara menggunakan:

1. Muat ulang Bank Soal, gunakan Ctrl+F5 jika halaman lama masih terlihat.
2. Centang soal nomor 6 bersama lima soal aktif yang ingin dipertahankan.
3. Periksa preview 6 soal dengan bobot 16,67 poin, lalu tekan **Terbitkan paket terpilih**.

Riwayat jawaban, ID/isi soal, nilai selesai, dan soal yang sudah dikunci pada percobaan aktif tidak diubah. ID duplikat, urutan bentrok, soal kuis lain, soal tidak valid, batas jumlah, pemeriksaan versi paket, dan transaksi tetap berlaku. Jika memilih dua versi yang sama-sama berurutan 1, salah satunya perlu dibatalkan agar urutan tidak bentrok. Evaluasi Akhir mempertahankan pembatasan publikasi sebelumnya.

File utama: `app/Services/Admin/QuestionBankService.php` dan `resources/views/admin/questions/index.blade.php`. Regresi diperbarui pada `tests/Feature/Quiz/DynamicQuizTest.php` dan `tests/Feature/Admin/QuestionBankTest.php`; alur browser tersedia di `tests/browser/archived-question.mjs`.

Pengujian menggunakan database terpisah; data aplikasi utama tidak diubah atau diterbitkan otomatis. Pemeriksaan browser meliputi paket 6 soal, pengarsipan soal 6, memilihnya kembali dengan Space, preview 6 soal, publikasi ulang, dan penyelesaian percobaan siswa yang sudah berjalan.

Hasil akhir:

- PASS: `php artisan test --compact`, 237 tes dan 4.499 assertion.
- PASS: browser Edge/Playwright, 3 skenario interaksi, tampilan 390/1440 px, tanpa exception JavaScript. Siswa mendapat 83,33 untuk 5 jawaban benar dari paket 6 soal semula.
- PASS: Laravel Pint, pemeriksaan sintaks skrip browser, dan `git diff --check`.
- Delivery Gate PASS: checkbox arsip dan publikasi berfungsi; komponen OOPy yang ada dipertahankan; pemilihan dengan Space dan gulir tabel tetap bekerja; riwayat diuji. ENERGY 2 / RHYTHM 2 / MOTION 1 mengikuti arah proyek.

[Screenshot dan hasil browser](evidence/archived-question-2026-11-11/).
