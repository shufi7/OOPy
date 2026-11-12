# Audit akademis Evaluasi Akhir OOPy

Tanggal: 13 November 2026, WITA. Audit dilakukan pada checkout lokal repository OOPy. Tidak dilakukan pull, commit, push, migrasi skema, atau perubahan autentikasi.

## 1. Ringkasan audit

### Kondisi awal dan temuan

Evaluasi sudah berformat 20 soal: 10 pilihan ganda (PG) dan 10 isian kode. Setiap jawaban benar bernilai 5 poin; ambang lulus 70, durasi 40 menit. Penilaian resmi dan latihan tamu sama-sama melalui Laravel. Jawaban benar tidak dikirim ke HTML/API siswa. Snapshot per percobaan, deadline server, transaksi, pemeriksaan kepemilikan, autosave, dan submit idempoten sudah tersedia dan dipertahankan.

Keseluruhan kunci awal sesuai maksud soal; tidak ditemukan kunci PG yang keliru. Masalah utamanya adalah dominasi hafalan, distraktor seperti CSS/warna editor/nama file yang tidak sebanding dengan kompetensi OOP, pengulangan pewarisan, dan lemahnya konteks isian. q12 hanya berisi property tanpa kelas; q13 tidak menyertakan induk; q14 tidak menyertakan kelas sensor; q15 tidak menyertakan impor ABC. q16, q17, q18, q20 terutama meminta nama kelas/method/decorator. q11, q12, q13, q19 memiliki inti konsep dan jawaban yang benar, sehingga inti tersebut dipertahankan dan konteksnya dilengkapi.

Pencocokan kode sebelumnya toleran terhadap sebagian jarak antartoken, tetapi belum menerima pengelompokan yang sah seperti `return (self._ph)`. Operator aritmatika hanya dapat diterima lewat pembandingan persis, sehingga variasi spasinya dapat ditolak. Publikasi paket menetapkan jumlah 10/10, tetapi validasi umum masih membolehkan PG dengan 2–8 opsi. Ketika PG mulai memakai cuplikan kode, input isian perlu disembunyikan dan placeholder PG harus tetap ditampilkan.

### Cakupan materi yang diperiksa

Sumber utama adalah file konten yang digunakan aplikasi dan mekanisme override `MaterialContentService`, bukan daftar konsep OOP umum.

| BAB | Konten dan kompetensi yang menjadi dasar |
| --- | --- |
| 1 | `dasar-pemrograman-oop.php`: variabel, penugasan, aritmatika, percabangan, list/loop, fungsi/parameter, transisi ke OOP |
| 2 | `kelas-dan-objek.php`: class/object, initializer, atribut instance, self, method, beberapa objek independen |
| 3 | `enkapsulasi.php`: interface, public/non-public, name mangling, getter/setter/property, validasi sebelum penyimpanan, penanganan ValueError |
| 4 | `pewarisan.php`: superclass/subclass, super(), overriding, relasi is-a/has-a, composition StasiunPemantau |
| 5 | `polimorfisme.php`: overriding, duck typing, pemanggilan method seragam, pengurangan percabangan tipe |
| 6 | `kelas-abstrak.php`: ABC/abstractmethod, penolakan instansiasi subclass belum lengkap, method konkret yang diwarisi, pemilihan ABC/duck typing |

Atribut kelas tidak dimasukkan karena belum menjadi kompetensi yang dibahas cukup dalam pada materi. BAB 1 diintegrasikan ke soal penerapan OOP, bukan dijadikan soal definisi tersendiri. Nilai sensor merupakan angka latihan pemrograman, bukan standar kualitas lingkungan.

Override materi dan paket aktif MySQL utama **belum dapat diperiksa**: koneksi `127.0.0.1:3306` ditolak. Karena itu, kesesuaian materi di sini berlaku untuk konten repository; sebelum menerbitkan, periksa apakah Admin telah mengubah materi terbitan. Pengujian penulisan seluruhnya menggunakan SQLite terisolasi. Tidak ada pembaruan soal atau penulisan hasil siswa ke database utama.

### Kondisi setelah perbaikan

Bank sumber baru tetap 10 PG + 10 isian, semuanya memiliki jawaban acuan teruji. PG memakai empat opsi berbeda. Isian meminta satu pernyataan, ekspresi, atau deklarasi superclass yang sesuai input satu baris. Cuplikan isian menyertakan seluruh kelas/impor yang diperlukan. Sebagian besar kompetensi dinilai melalui penerapan dan analisis, bukan nama modul atau sintaks khusus.

Perkiraan kisi-kisi: C2 2 soal (10%), C3 14 soal (70%), C4 4 soal (20%). Penetapan level mempertimbangkan proses yang diminta, bukan hanya verba. Ini merupakan telaah isi dan rancangan instrumen; validitas empiris, reliabilitas, dan kesepakatan penilai ahli belum diuji.

## 2. Perubahan soal

Nomor mengacu pada urutan bank sumber, bukan ID database. A–D adalah label opsi; indeks internal tetap 0–3. Isi lengkap tersedia di [bank sumber](../resources/materi/evaluasi-akhir.php).

| No. | Jenis | Materi | Bloom | Masalah sebelumnya | Perbaikan | Kunci terverifikasi |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | PG | BAB 2, independensi atribut instance | C4 | Definisi atribut; distraktor tidak relevan | Analisis perubahan satu dari dua objek | C, `80 90` |
| 2 | PG | BAB 2, self dan pemanggilan method | C3 | Hafalan rujukan self | Pilih pemanggilan yang memperbarui dan mengembalikan nilai | D, `sensor.tambah(5)` |
| 3 | PG | BAB 3, non-public convention | C2 | Pertanyaan istilah tanpa konteks interface | Bedakan akses yang dimungkinkan Python dan akses yang dianjurkan | A, gunakan property; `_nilai` tetap dapat diakses |
| 4 | PG | BAB 4, initializer induk | C3 | Sekadar mengenali deklarasi inheritance | Lengkapi inisialisasi induk memakai super | C, `super().__init__(nama)` |
| 5 | PG | BAB 4–5, overriding | C4 | Definisi overriding | Analisis implementasi yang dipilih serta keluarannya | A, `7.2`, implementasi subclass |
| 6 | PG | BAB 4, composition | C3 | Pengelompokan relasi tanpa implementasi | Pilih model stasiun yang mengelola objek sensor | B, daftar objek sensor pada atribut instance |
| 7 | PG | BAB 5, duck typing | C2 | Distraktor warna editor/nama file | Identifikasi kontrak pemanggilan pada dua kelas berbeda | D, menyediakan `baca_data()` tanpa argumen tambahan |
| 8 | PG | BAB 6, kontrak ABC | C4 | Hafalan nama modul abc | Analisis subclass yang belum mengimplementasikan kontrak | A, `TypeError` saat instansiasi |
| 9 | PG | BAB 3, setter dan konsistensi data | C4 | Mengulang pengenalan inheritance | Analisis mengapa data lama bertahan setelah validasi gagal | B, penolakan terjadi sebelum assignment |
| 10 | PG | BAB 5, pengembangan polimorfisme | C3 | Slogan desain yang terlalu umum | Terapkan interface seragam untuk jenis sensor baru | C, implementasikan method lalu tambahkan objek ke daftar |
| 11 | Isian | BAB 2, initializer/atribut instance | C3 | Konteks sangat pendek | Pertahankan inti; uji dua objek dengan nilai berbeda | `self.nilai = nilai` |
| 12 | Isian | BAB 3, property getter | C3 | Property berdiri tanpa kelas/atribut awal | Tambahkan kelas, initializer, dan pemakaian property | `return self._ph` |
| 13 | Isian | BAB 4, super dan inisialisasi | C3 | Induk serta kontrak parameternya tidak tersedia | Tampilkan induk, subclass, dan atribut tambahan | `super().__init__(nama, lokasi)` |
| 14 | Isian | BAB 5, pemanggilan polimorfis | C3 | Hanya mengisi nama method; kelas tidak tersedia | Lengkapi pemanggilan objek pada loop dua sensor | `item.baca_data()` |
| 15 | Isian | BAB 6, subclass konkret | C3 | Mengingat decorator; impor tidak tersedia | Lengkapi hubungan subclass yang memenuhi kontrak ABC | `SensorLingkungan` |
| 16 | Isian | BAB 4, composition | C3 | Mengisi nama kelas untuk instansiasi | Simpan referensi objek yang diberikan pada initializer | `self.sensor = sensor` |
| 17 | Isian | BAB 1–2, ekspresi dalam method | C3 | Mengingat nama `__init__` | Gabungkan data instance dan parameter tanpa mutasi | `self.nilai + kenaikan` |
| 18 | Isian | BAB 4–5, perluasan overriding | C3 | Mengisi nama kelas induk | Gunakan pembacaan induk dan koreksi tanpa rekursi | `super().baca_data() + self.koreksi` |
| 19 | Isian | BAB 3, setter/property | C3 | Setter belum memiliki initializer/pemakaian lengkap | Pertahankan inti; sertakan validasi dan pembaruan property | `self._ph = nilai` |
| 20 | Isian | BAB 6, pewarisan method konkret | C3 | Mengingat nama ABC | Gunakan method konkret dari kelas abstrak pada objek subclass | `sensor.sumber()` |

### Verifikasi jawaban dan ekuivalensi

`tests/Fixtures/final-exam-cases.json` berisi keluaran, variasi benar, dan contoh salah untuk setiap isian. Seluruh jawaban acuan dan 21 variasi benar dijalankan melalui Python 3.14. Cuplikan PG yang menampilkan program dijalankan; pilihan pemanggilan method q2 dan initializer q4 diuji satu per satu untuk memastikan hanya satu pilihan memenuhi tujuan. q8 memang memicu TypeError, bukan SyntaxError. Soal konseptual ditelaah terhadap materi dan dokumentasi primer.

Contoh yang diterima: `self . nilai=nilai`, `(self).nilai = (nilai)`, `return(self._ph)`, `super ( ).__init__( nama , lokasi )`, `(super()).__init__((nama), lokasi,)`, `(self.nilai) + (kenaikan)`. Nama dan huruf besar/kecil dipertahankan. Tanda kurung pembentuk tuple tidak disamakan dengan grouping. Penambahan perintah, nama yang dipisah, `==` pengganti assignment, pemanggilan setter berulang, urutan argumen yang salah, atau method yang tidak dipanggil tidak memperoleh poin.

Pemeriksa memakai tata bahasa terbatas untuk nama/atribut, pemanggilan posisi, penjumlahan, assignment, return, dan decorator lama. Ia membandingkan struktur tanpa mengeksekusi jawaban. Ini bukan pembuktian ekuivalensi seluruh bahasa Python. Bentuk lain yang sengaja dikecualikan dinyatakan dalam instruksi: q13 memakai super() dan argumen posisi; q17/q18 meminta urutan ekspresi tertentu; q14/q20 meminta pemanggilan melalui variabel objek yang ditentukan. Jawaban yang hanya menghardcode output tidak diterima.

Kunci Admin di luar tata bahasa tersebut mempertahankan pembandingan persis yang sudah ada. Validasi sintaks Python umum dan ekuivalensi untuk kunci Admin arbitrer belum disediakan; Admin perlu menguji cuplikan beserta kuncinya sebelum publikasi. Jaminan eksekusi Python pada audit ini berlaku untuk bank sumber yang diuji, bukan semua kemungkinan soal buatan Admin.

## 3. Audit penilaian

### Alur dan perhitungan

1. Halaman Blade memuat hanya field publik soal. JavaScript mengambil state dan memulai/resume percobaan melalui `routes/final-evaluation.php` serta `FinalExamAttemptController`.
2. Akun memakai `Quiz`/`Question` terbitan database; tamu memakai paket database jika tersedia, atau bank sumber sebagai fallback. Seeder tidak menimpa soal yang sudah ada.
3. `FinalExamService` mengunci paket dalam `FinalExamAttempt.snapshot`, dengan jawaban kosong, waktu mulai, deadline, dan kebijakan nilai. Latihan tamu disimpan pada sesi, bukan tabel nilai resmi.
4. Jawaban disimpan melalui autosave. Isian dibatasi 500 karakter. ID asing, tipe jawaban salah, indeks opsi tidak sah, dan akses milik akun lain ditolak.
5. Submit menghitung jawaban benar di server: `poin = benar × 5`, maksimal 100. PG kosong atau isian kosong/salah memberi 0. Nilai persentase objektif dan poin akhir sama untuk format baru 20 soal. Ambang 70 berarti minimal 14 benar.
6. Snapshot baru menambahkan `grading_version: 2` pada field internal. Percobaan sebelum perubahan tetap memakai pembandingan lama, termasuk saat menghitung feedback. Field internal/kunci tetap tidak dikirim ke siswa.
7. Submit berulang tidak mengganti jawaban atau nilai yang sudah dikumpulkan. Jawaban yang tiba setelah deadline tidak mengganti jawaban yang diterima sebelumnya. Percobaan baru tetap boleh dilakukan sesuai kebijakan aplikasi.
8. `DashboardProgressService` dan `LearningReportService` memakai nilai tersimpan yang sama. Browser memverifikasi nilai 70 pada hasil, dashboard siswa, halaman Admin, dan CSV; nilai 100 juga diuji. Tes PHP memeriksa agregasi, batas lulus 65/70, dan manipulasi field skor dari frontend.

Riwayat lama dengan lima uraian tetap didukung dan memerlukan penilaian Admin. Totalnya tidak diubah atau dihitung ulang oleh pembaruan bank. Tidak ada perubahan bobot, durasi, ambang lulus, skema database, ataupun alur autentikasi.

### Bug dan perbaikan terbukti

- Normalisasi yang terlalu sempit diperluas menjadi pembandingan struktur terbatas. Varian spacing, grouping, dan koma akhir argumen yang setara diterima; struktur berbeda ditolak.
- Syarat empat opsi berbeda ditambahkan pada publikasi final dan pemeriksaan paket saat start. Paket yang melanggar ditolak sebelum menciptakan percobaan.
- Input isian disembunyikan pada PG. Source PG tetap utuh, termasuk placeholder q4; caption dibedakan dari petunjuk isian.
- Peringatan kegagalan autosave dihapus setelah jawaban berhasil tersimpan kembali. Inspeksi screenshot menemukan pesan gagal lama masih tampil di atas status penyimpanan yang sudah berhasil; tes kini memeriksa pemulihan status secara persis.
- Kebijakan penilaian diberi versi per snapshot agar perbaikan normalisasi tidak mengubah feedback percobaan historis.
- Fixture riwayat lama dipisahkan dari bank baru agar regresi sejarah tidak ikut berubah ketika soal baru direvisi.

### Penerbitan data yang aman

Perintah baru `oopy:refresh-final-exam` secara default hanya preview. Paket pembanding lama berada di `resources/evaluasi/baseline-2026-11-12.php`. Perintah hanya mengenali soal yang identik dengan baseline atau revisi baru. Suntingan Admin, perbedaan urutan yang tidak dapat dicocokkan, atau paket yang tidak lengkap menyebabkan penolakan seluruh operasi.

Untuk apply, fingerprint harus cocok dengan preview terbaru. Fingerprint mencakup ID, urutan, isi/key/versi setiap soal, dan paket tujuan; perubahan isi pada ID yang sama juga membatalkan preview. Publikasi menggunakan `QuestionBankService` dalam satu transaksi. Setiap soal yang berubah dibuat sebagai revisi baru dan soal sebelumnya diarsipkan. Snapshot aktif, jawaban, nilai, dan riwayat tidak dihapus. Kegagalan publikasi me-rollback seluruh draft. Reseed dan apply ulang tidak menambah revisi yang sama.

Database utama belum diterbitkan karena MySQL tidak tersedia. Setelah layanan tersedia, lakukan backup, periksa materi terbitan serta preview, lalu jalankan:

```powershell
php artisan oopy:refresh-final-exam --quiz=ID
php artisan oopy:refresh-final-exam --quiz=ID --apply --revision=FINGERPRINT_DARI_PREVIEW
```

Jangan menebak ID/fingerprint. Jika terdeteksi suntingan Admin, gunakan Bank Soal untuk meninjau dan membuat revisi yang sesuai; jangan menimpa langsung atau menjalankan seeder untuk memaksa pembaruan. Paket lama 10/5/5 memerlukan transisi yang sudah ada (`oopy:upgrade-final-exam`) dan peninjauan tersendiri; perintah revisi akademis akan menolaknya.

## 4. Perubahan kode

| File | Perubahan dan alasan |
| --- | --- |
| `resources/materi/evaluasi-akhir.php` | Revisi akademis 20 soal; konteks kode lengkap dan penjelasan kunci PG |
| `resources/evaluasi/baseline-2026-11-12.php` | Salinan bank sebelumnya sebagai pembanding aman dan fixture sejarah |
| `resources/materi/chapters.php` | Koreksi deskripsi format pada katalog menjadi 10 PG + 10 isian |
| `app/Services/FinalExamCodeAnswer.php` | Parser/pembanding jawaban terbatas tanpa eksekusi kode |
| `app/Services/FinalExamService.php` | Pemakaian pembanding baru, versi penilaian snapshot, penjagaan empat opsi saat start, kompatibilitas snapshot lama |
| `app/Services/Admin/QuestionBankService.php` | Syarat empat opsi berbeda khusus Evaluasi Akhir |
| `app/Console/Commands/RefreshFinalExam.php` | Preview/apply akademis dengan fingerprint, perlindungan suntingan Admin, revisi atomik |
| `public/js/evaluasi/code-answer.js` | Pembanding setara untuk model latihan lama dan tes; nilai resmi tetap dari server |
| `public/js/evaluasi/exam-model.js` | Memakai pembanding terbatas yang sama bentuknya |
| `public/js/evaluasi/exam.js` | Input isian hanya pada code-fill; source/caption PG ditampilkan benar; peringatan autosave pulih setelah penyimpanan berhasil |
| `resources/views/evaluasi/ujian.blade.php` | Hook caption kode sesuai jenis soal; layout tetap |
| `tests/Feature/EvaluasiAkhirTest.php` | Hapus ikatan ke daftar jawaban bank lama; pemeriksaan format tetap |
| `tests/Feature/FinalExamAcademicTest.php` | Varian benar/salah, hasil server, perlindungan kebijakan historis, paket opsi invalid |
| `tests/Feature/Admin/FinalExamAcademicRefreshTest.php` | Preview, fingerprint basi, suntingan Admin, apply, rollback, reseed, riwayat/snapshot |
| `tests/Fixtures/final-exam-cases.json` | Corpus jawaban benar/salah dan output yang diverifikasi Python |
| `tests/Fixtures/legacy-final-exam.php` | Referensi bank historis tetap, bukan bank baru |
| `tests/final_exam_python_test.py` | Eksekusi cuplikan tepercaya, opsi PG, alternatif, dan perilaku pada input lain |
| `tests/js/final-exam.test.js` | Corpus ekuivalensi serta regresi model skor/storage/timer |
| `tests/browser/evaluasi.mjs` | Pemeriksaan cuplikan PG, empat opsi, input tersembunyi, layout seluruh soal |
| `docs/README.md`, laporan ini, `docs/evidence/final-exam-academic-2026-11-13/` | Indeks, hasil audit, dan bukti verifikasi |

Tidak ada dependency baru. CSS, palet, tipografi, dan struktur halaman dipertahankan. Build Vite berhasil; aset `public/js/evaluasi/` juga diuji langsung lewat browser karena tidak bergantung pada bundling Vite.

## 5. Hasil pengujian

Baseline sebelum perubahan: 19 tes PHP terkait evaluasi lulus, 737 assertion.

| Pengujian | Hasil | Bukti |
| --- | --- | --- |
| `php artisan test --compact` | PASS, 252 tes, 5.189 assertion | [Log PHP](evidence/final-exam-academic-2026-11-13/php-tests.txt) |
| `node --test tests/js/*.test.js` | PASS, 24 tes | [Log JavaScript](evidence/final-exam-academic-2026-11-13/javascript-tests.txt) |
| `python tests/final_exam_python_test.py` | PASS, 3 kelompok tes dengan subtest seluruh acuan/variasi | [Log Python](evidence/final-exam-academic-2026-11-13/python-tests.txt) |
| `node tests/browser/evaluasi.mjs` pada fixture SQLite baru | PASS untuk latihan tamu dan akun; nilai 65/70/100, retry jaringan, dashboard/Admin/CSV, keyboard, review, autosave, deadline, layout | [Log browser](evidence/final-exam-academic-2026-11-13/browser-tests.txt) |
| `npm.cmd run build` | PASS, Vite membangun 58 modul | Dijalankan di Windows memakai `npm.cmd` karena kebijakan PowerShell memblokir `npm.ps1` |
| Pint pada file PHP yang ditambah/diubah; `git diff --check` | PASS | Pemeriksaan format dan whitespace dijalankan |
| Materi override, preview/apply pada MySQL utama | BELUM DAPAT DILAKUKAN | Koneksi MySQL 127.0.0.1:3306 ditolak; tidak ada data utama ditulis |

Browser dijalankan pada Microsoft Edge headless dengan aset Bootstrap/font cache lokal, port pengujian terpisah, dan database baru `admin-coding-*.sqlite`. Halaman intro, ujian aktif, isian, hasil, dan dashboard diperiksa pada 320/390/768/1024/1440 px. Semua soal juga diperiksa terhadap overflow halaman pada 320 dan 1440 px. Screenshot tersimpan di `storage/framework/testing/final-exam-academic-2026-11-13/`; screenshot mobile dan desktop diinspeksi. Pengujian ini tidak membuktikan dukungan seluruh browser atau audit screen reader menyeluruh.

### Delivery Gate untuk permukaan yang diubah

- Hard Gate PASS: browser memeriksa kontrol evaluasi, keyboard/Escape, koneksi gagal/retry, hidden/disabled, serta overflow. Tidak ditambahkan aset visual, statistik, testimoni, atau klaim produk baru.
- Purpose Gate PASS: cuplikan kode mendukung analisis soal; penyembunyian input dan caption membedakan PG/isian. Tidak ada dekorasi atau komponen baru.
- Liveliness PASS: mengikuti `DESIGN.md`, ENERGY 2 / RHYTHM 2 / MOTION 1. Fokus tetap soal aktif pada ujian dan nilai pada hasil; motif kode serta aksen mint/teal dipertahankan.
- Craftsmanship PASS: alur resmi dan tamu diperiksa melalui server; skor tersimpan dibandingkan dengan dashboard/Admin/CSV; hasil tes tidak menggantikan keterbatasan produksi yang dicatat di atas.

### Batas dan masalah tersisa

Penerbitan paket pada database utama masih diperlukan. Soal yang sudah tersimpan tidak otomatis berubah ketika file sumber diperbarui. Materi yang mungkin diubah Admin belum dibandingkan karena MySQL offline. Pengujian database MySQL/MariaDB, beban/concurrency produksi, browser selain Edge, layanan Docker Grader live, dan validasi psikometrik belum dijalankan. Coding Grader tidak digunakan untuk Evaluasi Akhir ini, sehingga eksekusi kode mahasiswa tidak ditambahkan.

## 6. Rekomendasi

| Prioritas | Rekomendasi |
| --- | --- |
| Critical | Sebelum memakai revisi untuk penilaian resmi, pulihkan akses MySQL, backup, bandingkan materi Admin, tinjau preview, lalu terbitkan versi yang disetujui. Pastikan UI siswa dan Admin membaca paket/nilai yang sama. Tidak ditemukan bug Critical pada perhitungan 20 soal yang diuji. |
| High | Minta dua penelaah materi/instrumen mengkaji kesesuaian kompetensi, ketunggalan jawaban, dan level Bloom secara independen; lakukan uji coba mahasiswa sebelum menyatakan instrumen tervalidasi. Ulangi regresi terarah pada MySQL/MariaDB. |
| Medium | Hitung tingkat kesukaran, daya pembeda, fungsi distraktor, dan reliabilitas dari data uji coba. Tambahkan validasi sintaks dan perilaku untuk soal Admin arbitrer dalam pipeline tepercaya, tanpa mengeksekusi jawaban mahasiswa langsung di proses web. |
| Low | Tambahkan variasi paket ekuivalen berdasarkan kisi-kisi setelah hasil empiris tersedia; lengkapi pemeriksaan Firefox/Safari dan screen reader. Perbarui panduan lama yang masih menggambarkan format 10/5/5. |

### Acuan primer

Materi lokal BAB 1–6 tetap menjadi dasar cakupan. Pemaknaan perilaku Python dirujuk ke [Python 3.14: Classes](https://docs.python.org/3.14/tutorial/classes.html) dan [modul abc](https://docs.python.org/3.14/library/abc.html). Pengelompokan proses kognitif merujuk [Iowa State University CELT: Bloom's Taxonomy](https://celt.iastate.edu/prepare-and-teach/design-your-course/blooms-taxonomy/). Acuan tersebut mendukung telaah konsep; hasil eksekusi dan pengujian aplikasi adalah bukti lokal yang terpisah.
