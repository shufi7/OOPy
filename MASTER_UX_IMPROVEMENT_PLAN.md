# MASTER UX IMPROVEMENT PLAN
## OOPy — Media Pembelajaran Interaktif Python dan Object-Oriented Programming

**Versi:** 1.0  
**Tanggal:** 15 November 2026  
**Cakupan:** Materi BAB 1–6  
**Target:** Penyempurnaan UX/UI tanpa merusak fungsi backend yang sudah berjalan  
**Acuan desain:** `DESIGN.md` dan prinsip Anti-Slop

---

# 1. LATAR BELAKANG

OOPy merupakan media pembelajaran interaktif berbasis web yang dirancang untuk membantu pengguna mempelajari dasar Python dan Object-Oriented Programming (OOP).

Materi pembelajaran disusun secara bertahap, mulai dari dasar pemrograman Python pada BAB 1, pengenalan kelas dan objek pada BAB 2, enkapsulasi pada BAB 3, pewarisan pada BAB 4, polimorfisme pada BAB 5, hingga kelas abstrak pada BAB 6.

Berdasarkan evaluasi struktur kode dan tampilan halaman materi, OOPy telah memiliki beberapa komponen pembelajaran utama:

- Tujuan pembelajaran.
- Apersepsi.
- Penjelasan materi.
- Contoh kode Python dan output.
- Catatan dan penjelasan kode.
- Live Coding.
- Latihan.
- Rangkuman dan refleksi.
- Kuis setiap BAB.

Fondasi tersebut sudah cukup baik. Namun, masih terdapat peluang untuk meningkatkan pengalaman pengguna, terutama dalam hal navigasi, keterlibatan selama belajar, kemudahan memahami konsep, panduan latihan, umpan balik kesalahan, dan evaluasi pemahaman.

Oleh karena itu, dokumen ini disusun sebagai pedoman pengembangan UX/UI untuk seluruh materi BAB 1–6 agar pengalaman belajar lebih konsisten, mudah dipahami, dan sesuai dengan karakter OOPy sebagai media pembelajaran interaktif.

# 2. TUJUAN PENGEMBANGAN

Pengembangan UX ini mempunyai tujuan:

1. Membuat pengguna lebih mudah memahami materi Python dan OOP, khususnya pengguna pemula.
2. Mengurangi pengalaman belajar yang terlalu berorientasi pada membaca teks.
3. Meningkatkan interaksi antara pengguna dengan materi pembelajaran.
4. Membuat navigasi antar-submateri lebih mudah digunakan.
5. Memberikan panduan praktik coding yang jelas dan bertahap.
6. Memberikan umpan balik yang informatif ketika pengguna menjalankan atau mengirim kode.
7. Membantu pengguna mengetahui perkembangan dan pemahaman belajarnya.
8. Menjaga konsistensi desain dan interaksi pada seluruh BAB 1–6.
9. Mempertahankan seluruh fungsi backend, database, autentikasi, dan sistem penilaian yang sudah berjalan.

# 3. PRINSIP UTAMA PENGEMBANGAN UX

Seluruh perubahan harus mengikuti prinsip berikut.

### 3.1 User-Centered Learning

Desain harus mengutamakan kebutuhan pengguna yang baru belajar Python.

Pengguna harus dapat memahami:
- Apa yang sedang dipelajari.
- Mengapa konsep tersebut penting.
- Bagaimana contoh kode bekerja.
- Bagaimana mencoba konsep secara langsung.
- Bagaimana mengetahui kesalahan yang dilakukan.

### 3.2 Learn by Doing

Pembelajaran tidak hanya berisi penjelasan teori. Setiap konsep penting sebaiknya memberikan kesempatan kepada pengguna untuk memprediksi, mencoba, menjalankan, atau menerapkan kode.

### 3.3 Progressive Learning

Tingkat kesulitan harus meningkat secara bertahap. Jangan langsung memberikan tugas kompleks tanpa memberikan contoh dan petunjuk yang cukup.

### 3.4 Consistency

Seluruh BAB harus menggunakan standar navigasi, tipografi, warna, komponen kode, editor, feedback, dan kuis yang konsisten.

### 3.5 Minimal but Functional

Jangan menambahkan animasi, dekorasi, kartu, atau interaksi yang tidak mempunyai manfaat terhadap proses pembelajaran.

Setiap komponen harus memiliki tujuan yang jelas.

# 4. STANDAR ALUR PEMBELAJARAN BAB 1–6

Seluruh BAB harus mengikuti pola pembelajaran yang konsisten tanpa memaksakan isi materi menjadi identik.

Urutan yang menjadi standar:

1. Identitas BAB dan pengenalan materi.
2. Tujuan pembelajaran.
3. Apersepsi.
4. Penjelasan konsep.
5. Contoh kode dan output.
6. Interaksi pemahaman pada konsep penting.
7. Live Coding atau latihan praktik.
8. Rangkuman.
9. Refleksi.
10. Kuis BAB.

Catatan:
- Tidak setiap subbab wajib mempunyai interaksi tambahan.
- Interaksi harus diterapkan pada konsep yang memang membutuhkan pemeriksaan pemahaman.
- Pertahankan urutan dan isi kurikulum yang sudah ada.
- Jangan menghapus contoh, penjelasan, tabel, latihan, atau bagian penting hanya untuk mempersingkat halaman.

# 5. STANDAR NAVIGASI DAN PROGRES BELAJAR

## 5.1 Navigasi submateri

Pertahankan sidebar daftar isi yang sudah tersedia.

Tingkatkan fungsinya agar:

- Pengguna dapat mengetahui subbab yang sedang dibaca.
- Link navigasi menuju bagian yang benar.
- Bagian aktif mempunyai penanda visual yang jelas.
- Pengguna dapat membuka dan menutup kelompok navigasi.
- Navigasi tetap nyaman digunakan ketika halaman panjang.
- Pada perangkat mobile, daftar isi dapat dilipat agar tidak mengambil terlalu banyak ruang.

Gunakan struktur navigasi yang sudah tersedia pada:

`resources/views/materi/partials/navigation.blade.php`

Jangan membuat sidebar baru jika komponen lama dapat ditingkatkan.

## 5.2 Penanda posisi membaca

Tambahkan penanda posisi membaca yang membantu pengguna mengetahui lokasi mereka dalam halaman.

Contoh informasi:

- Sedang membaca: 2.3 Input dan Output.
- Posisi: Subbab 3 dari 7.
- Indikator posisi membaca pada halaman.

Penanda posisi membaca tidak boleh dianggap sebagai bukti bahwa pengguna sudah menguasai atau menyelesaikan materi.

## 5.3 Progres penyelesaian

Jika memungkinkan tanpa perubahan database, tampilkan progres berdasarkan aktivitas yang memang sudah diketahui oleh frontend.

Namun, jangan mengklaim bahwa subbab sudah dikuasai hanya karena pengguna menggulir halaman.

Progres permanen lintas sesi, penyimpanan status setiap subbab, dan sinkronisasi antarperangkat memerlukan audit backend terlebih dahulu.

Jangan menambahkan tabel database atau endpoint baru tanpa persetujuan.

## 5.4 Navigasi antar-BAB

Pertahankan:

- BAB Sebelumnya.
- Kembali ke Daftar Materi.
- Lanjut ke BAB Berikutnya.

Pertahankan mekanisme penguncian BAB berikutnya berdasarkan kelulusan kuis yang sudah berlaku.

Jangan menghilangkan atau melemahkan aturan kelulusan tersebut demi kemudahan navigasi.

# 6. STANDAR INTERAKSI PEMBELAJARAN

## 6.1 Micro-interaction

Tambahkan interaksi pembelajaran singkat pada konsep yang membutuhkan pemeriksaan pemahaman.

Jenis interaksi yang direkomendasikan:

**A. Prediksi Output**

Pengguna melihat potongan kode dan memilih output sebelum melihat hasil sebenarnya.

**B. Pertanyaan Pemahaman**

Pertanyaan singkat setelah konsep penting untuk memastikan pengguna memahami penjelasan.

**C. Pilih Konsep yang Tepat**

Pengguna memilih konsep atau pendekatan yang sesuai dengan suatu permasalahan.

**D. Lengkapi Kode Sederhana**

Pengguna mengisi bagian kode yang kosong untuk menerapkan konsep yang baru dipelajari.

**E. Identifikasi Kesalahan**

Pengguna mengidentifikasi kesalahan sederhana dalam contoh kode.

### Ketentuan implementasi

- Gunakan komponen interaksi yang dapat digunakan ulang.
- Berikan feedback langsung setelah pengguna menjawab.
- Jelaskan alasan jawaban benar atau salah.
- Jangan menampilkan jawaban sebelum pengguna mencoba.
- Jangan menyimpan nilai interaksi sebagai nilai resmi kuis.
- Jangan mencampurkan micro-interaction dengan mekanisme penilaian backend.
- Pastikan interaksi dapat digunakan dengan keyboard.

Tidak perlu menambahkan interaksi pada setiap paragraf. Utamakan konsep yang mempunyai risiko kesalahpahaman.

# 7. STANDAR PENYAJIAN MATERI DAN CONTOH KODE

## 7.1 Hierarki konten

Gunakan struktur visual yang membedakan:

| Komponen | Fungsi |
|---|---|
| Penjelasan materi | Menyampaikan konsep. |
| Contoh Python | Memperlihatkan penerapan konsep. |
| Output | Menunjukkan hasil eksekusi. |
| Bedah Kode | Menjelaskan bagian penting kode. |
| Catatan Belajar | Memberikan informasi tambahan. |
| Interaksi | Memeriksa pemahaman. |
| Latihan | Memberikan tugas praktik. |

## 7.2 Blok kode

Pertahankan tampilan editor kode gelap yang sudah menjadi identitas OOPy.

Standar yang harus dipenuhi:

- Font monospace.
- Syntax highlighting yang jelas.
- Kontras yang nyaman.
- Ukuran teks kode mudah dibaca.
- Scroll horizontal hanya pada blok kode jika diperlukan.
- Label Contoh Python dan Output.
- Tombol salin kode jika dapat diimplementasikan dengan feedback yang benar.

Jangan menyamakan tampilan kode contoh dengan editor Live Coding.

## 7.3 Variasi penyajian

Untuk mengurangi kejenuhan membaca, gunakan variasi yang sesuai dengan konsep:

- Tabel perbandingan.
- Diagram hubungan class dan object.
- Diagram inheritance.
- Ilustrasi alur eksekusi.
- Prediksi output.
- Contoh sebelum dan sesudah.
- Perbandingan implementasi kode.

Jangan menggunakan gambar dekoratif yang tidak membantu pemahaman.

# 8. STANDAR LIVE CODING DAN LATIHAN

Live Coding merupakan salah satu fitur utama OOPy dan harus dipertahankan.

Komponen yang harus konsisten:

1. Judul latihan.
2. Tujuan latihan.
3. Instruksi pengerjaan.
4. File editor.
5. Tombol Run Code.
6. Tombol Reset jika tersedia.
7. Tombol Submit.
8. Area output.
9. Area hasil penilaian.
10. Petunjuk atau bantuan pengerjaan.

## 8.1 Panduan latihan bertahap

Untuk latihan yang mempunyai beberapa persyaratan, tampilkan petunjuk pengerjaan secara bertahap.

Contoh:

- Langkah 1: Buat class.
- Langkah 2: Tambahkan atribut.
- Langkah 3: Buat method.
- Langkah 4: Terapkan konsep yang dipelajari.
- Langkah 5: Jalankan dan periksa hasilnya.

Panduan ini berfungsi sebagai bantuan belajar, bukan pengganti pemeriksaan kode oleh sistem.

## 8.2 Feedback Run Code

Ketika pengguna menjalankan kode, tampilkan status yang sesuai.

| Kondisi | Feedback |
|---|---|
| Kode sedang dijalankan | Menjalankan kode... |
| Eksekusi berhasil | Kode berhasil dijalankan. |
| Syntax error | Terdapat kesalahan sintaks. Periksa baris yang ditunjukkan. |
| Runtime error | Program mengalami kesalahan saat dijalankan. |
| Runtime belum tersedia | Lingkungan Python belum siap. Coba kembali. |
| Proses gagal | Jelaskan kegagalan berdasarkan informasi yang benar-benar tersedia. |

Jangan menampilkan pesan sukses sebelum proses benar-benar berhasil.

## 8.3 Feedback Submit

Hasil pemeriksaan harus mudah dipahami.

Contoh:

**Jika berhasil:**
- Pemeriksaan berhasil.
- Tampilkan kriteria yang sudah terpenuhi.
- Berikan penjelasan singkat mengenai keberhasilan.

**Jika belum berhasil:**
- Tampilkan bagian yang belum sesuai.
- Berikan petunjuk perbaikan.
- Jangan langsung memberikan seluruh jawaban akhir.
- Pertahankan kode pengguna agar tidak hilang.

Jangan mengubah sistem grading, test case tersembunyi, skor, atau aturan kelulusan.

## 8.4 Latihan multi-file

Khusus latihan seperti BAB 4 yang menggunakan beberapa file:

- Pastikan nama file mudah dikenali.
- Berikan petunjuk fungsi setiap file.
- Tandai file yang sedang aktif.
- Pastikan pergantian file tidak menghilangkan kode.
- Berikan instruksi mengenai file yang perlu diubah.
- Pastikan proses Run dan Submit tetap bekerja seperti sebelumnya.

# 9. STANDAR RANGKUMAN DAN REFLEKSI

## 9.1 Rangkuman

Pertahankan rangkuman yang sudah tersedia.

Tingkatkan keterbacaan dengan:
- Poin-poin singkat.
- Pengelompokan konsep.
- Penekanan istilah penting.
- Referensi ke subbab terkait.

## 9.2 Refleksi

Refleksi harus membantu pengguna menilai pemahamannya sendiri.

Jika sesuai, tambahkan pertanyaan seperti:

- Konsep apa yang sudah dipahami?
- Bagian mana yang masih sulit?
- Apakah pengguna dapat menerapkan konsep tanpa melihat contoh?
- Kesalahan apa yang pernah dilakukan ketika latihan?

Jangan mengubah refleksi menjadi evaluasi resmi yang memengaruhi nilai pengguna.

# 10. STANDAR KUIS DAN EVALUASI

Pertahankan sistem kuis yang sudah berjalan.

Format kuis BAB saat ini:

- 3 soal pilihan ganda.
- 2 soal isian kode.

Jumlah dan komposisi soal tidak boleh diubah dalam pekerjaan redesign UX.

## 10.1 Tampilan pengerjaan

Pastikan pengguna dapat mengetahui:

- Nomor soal.
- Jumlah soal.
- Jawaban yang sudah diisi.
- Soal yang belum dijawab.
- Tombol Soal Sebelumnya.
- Tombol Soal Selanjutnya.
- Tombol Selesai Kuis.

## 10.2 Validasi

Jika pengguna belum menjawab semua soal, berikan peringatan yang jelas.

Jangan menghapus jawaban pengguna ketika mereka berpindah soal.

## 10.3 Hasil kuis

Pertahankan informasi hasil yang sudah tersedia:

- Nilai.
- Jumlah benar.
- Jumlah salah.
- Status kelulusan.
- Pesan hasil.
- Tombol Coba Lagi.
- Tombol Lanjut ke BAB berikutnya jika memenuhi persyaratan.

Peningkatan yang direkomendasikan:

- Berikan pembahasan jawaban jika mekanisme dan data pembahasannya tersedia.
- Berikan rekomendasi subbab berdasarkan kesalahan jika pemetaan soal ke subbab sudah tersedia.
- Bedakan status lulus dan belum lulus secara visual serta teks.

Jangan mengarang pembahasan jawaban atau menghubungkan soal dengan subbab tanpa dasar data.

## 10.4 Perlindungan mekanisme kuis

Pertahankan:

- Penilaian server.
- CSRF protection.
- Penyimpanan hasil.
- Riwayat pengerjaan.
- Ambang kelulusan yang berlaku.
- Penguncian BAB berikutnya.
- Validasi jawaban.
- Kontrak request dan response.

Perubahan visual tidak boleh mengubah hasil penilaian.

# 11. STANDAR VISUAL DAN DESIGN SYSTEM

Seluruh halaman harus mengikuti `DESIGN.md`.

### 11.1 Identitas visual

Pertahankan:

- Mint–teal.
- Plus Jakarta Sans.
- Logo OOPy yang sudah ada.
- Bahasa visual praktikum pemrograman.
- Tema terang.
- Editor kode dengan permukaan gelap.

### 11.2 Prinsip visual

Gunakan:

- ENERGY 2 — Balanced.
- RHYTHM 2 — Varied.
- MOTION 1 — Calm.

Hindari:

- Gradient biru-ungu yang tidak mempunyai tujuan.
- Glow dan glassmorphism dekoratif.
- Shadow besar pada semua komponen.
- Terlalu banyak kartu seragam.
- Badge yang tidak memberikan informasi.
- Animasi berlebihan.
- Ilustrasi AI generik yang tidak berkaitan dengan materi.

### 11.3 Tipografi

- Font utama: Plus Jakarta Sans.
- Font kode: monospace.
- Isi materi: target 16–18px.
- Line-height isi materi: sekitar 1.65–1.8.
- Heading menggunakan hierarki yang konsisten.
- Jangan menggunakan teks terlalu kecil untuk informasi penting.

### 11.4 Layout

- Pertahankan artikel utama yang nyaman dibaca.
- Pertahankan sidebar daftar isi.
- Hindari paragraf terlalu lebar.
- Gunakan jarak antarseksi yang konsisten.
- Jangan membungkus setiap paragraf dalam card.
- Pastikan editor memiliki ruang yang cukup.

### 11.5 Responsivitas

Desktop:
- Sidebar dan artikel ditampilkan secara proporsional.
- Editor memiliki ruang kerja yang cukup.
- Navigasi mudah ditemukan.

Tablet:
- Sidebar dapat disederhanakan.
- Artikel tetap mudah dibaca.
- Tabel dan kode memiliki scroll lokal jika diperlukan.

Mobile:
- Daftar isi dapat dilipat.
- Artikel menjadi satu kolom.
- Tombol memiliki ukuran sentuh yang memadai.
- Editor tidak melampaui layar.
- Tidak ada horizontal overflow pada seluruh halaman.
- Keyboard virtual tidak menutupi kontrol penting.

# 12. PENERAPAN BERDASARKAN KARAKTER BAB

Setiap BAB harus mengikuti standar yang sama, tetapi interaksi dan visualisasi disesuaikan dengan materi.

## BAB 1 — Dasar Pemrograman Python dan OOP

Fokus pembelajaran:
- Variabel dan tipe data.
- Operator.
- Input dan output.
- Percabangan.
- Perulangan dan list.
- Fungsi.
- Dari prosedural ke OOP.

Peningkatan UX:
1. Prediksi output sederhana.
2. Latihan identifikasi tipe data.
3. Interaksi menentukan hasil percabangan.
4. Latihan melengkapi fungsi sederhana.
5. Visualisasi transisi pemrograman prosedural ke OOP.

Tujuan UX: membuat pengguna memahami dasar Python sebelum masuk ke konsep OOP.

## BAB 2 — Kelas dan Objek

Fokus pembelajaran:
- Class dan object.
- Constructor `__init__`.
- `self`.
- Atribut dan method.
- Membuat beberapa object dari satu class.

Peningkatan UX:
1. Visualisasi class sebagai cetak biru.
2. Visualisasi satu class menghasilkan beberapa object.
3. Interaksi membedakan atribut dan method.
4. Prediksi hasil pembuatan object.
5. Panduan latihan Class Spesies dan Sensor Air.

Tujuan UX: membantu pengguna memahami hubungan class, object, atribut, dan method.

## BAB 3 — Enkapsulasi

Fokus pembelajaran:
- Public attribute.
- Konvensi non-public.
- Name mangling.
- Getter dan setter.
- `@property`.

Peningkatan UX:
1. Tabel perbandingan public, `_attribute`, dan `__attribute`.
2. Contoh sebelum dan sesudah menggunakan property.
3. Interaksi menentukan nilai valid dan tidak valid.
4. Visualisasi proses validasi setter.
5. Panduan latihan secara bertahap.

Tujuan UX: mengurangi kebingungan antara atribut, getter, setter, dan property.

## BAB 4 — Pewarisan

Fokus pembelajaran:
- Superclass dan subclass.
- `super()`.
- Method overriding.
- Inheritance dan composition.

Peningkatan UX:
1. Diagram hubungan superclass dan subclass.
2. Visualisasi penggunaan `super()`.
3. Perbandingan inheritance dan composition.
4. Panduan editor multi-file.
5. Penanda langkah pengerjaan latihan.

Tujuan UX: membantu pengguna memahami hubungan antarkelas dan penggunaan ulang kode.

## BAB 5 — Polimorfisme

Fokus pembelajaran:
- Polimorfisme melalui inheritance.
- Duck typing.
- Mengurangi pemeriksaan tipe.

Peningkatan UX:
1. Perbandingan overriding dan polymorphism.
2. Contoh beberapa object dengan method yang sama.
3. Visualisasi duck typing.
4. Interaksi memilih object yang dapat diproses oleh suatu fungsi.
5. Panduan implementasi latihan multi-object.

Tujuan UX: membuat pengguna memahami bahwa berbagai object dapat diproses melalui pola pemanggilan method yang sama.

## BAB 6 — Kelas Abstrak

Fokus pembelajaran:
- Abstract Base Class.
- Abstract method.
- Method konkret.
- Subclass konkret.
- Perbandingan ABC dan duck typing.

Peningkatan UX:
1. Visualisasi abstract class sebagai kontrak.
2. Diagram abstract class dan subclass.
3. Perbandingan abstract method dan method konkret.
4. Penjelasan visual TypeError yang memang diharapkan.
5. Panduan implementasi abstract class secara bertahap.

Tujuan UX: membantu pengguna memahami alasan menggunakan abstract class, bukan sekadar menghafal `ABC` dan `@abstractmethod`.

# 13. ARSITEKTUR IMPLEMENTASI DAN BATASAN BACKEND

Pengembangan harus memanfaatkan struktur repository yang sudah tersedia.

File utama yang perlu diperiksa:

```text
DESIGN.md

resources/materi/
    chapters.php
    dasar-pemrograman-oop.php
    kelas-dan-objek.php
    enkapsulasi.php
    pewarisan.php
    polimorfisme.php
    kelas-abstrak.php

resources/views/materi/
    show.blade.php
    partials/
        navigation.blade.php
        section.blade.php
        quiz.blade.php

public/js/
    oopy-material.js
    oopy-quiz.js
    live-code/
        live-code-instance.js
        runtime-manager.js
        python-worker.js
        project-runner.py

app/Http/Controllers/
    MateriController.php
    QuizController.php
    CodingController.php
```

CSS materi yang sudah digunakan:

```text
public/css/oopy/material/
    material.css
    navigation.css
    code.css
    quiz.css
```

Komponen Live Coding yang sudah ada harus diperiksa sebelum membuat komponen pengganti.

### Aturan backend

DILARANG melakukan perubahan yang tidak diperlukan pada:

- Controller.
- Model.
- Service penilaian.
- Database.
- Migration.
- Seeder.
- Route.
- Middleware.
- Autentikasi.
- Hak akses.
- Sistem kelulusan.
- Sistem penyimpanan hasil.

Jangan:
- Menghapus tabel.
- Menjalankan `migrate:fresh`.
- Mengubah struktur database demi kebutuhan tampilan.
- Mengganti endpoint.
- Mengubah format payload.
- Mengganti mekanisme grading.
- Menghapus test case.
- Mengubah aturan penilaian.
- Mengubah kontrak antara JavaScript dan backend.

Jika suatu fitur UX membutuhkan perubahan backend, Codex harus menjelaskan:
1. Mengapa perubahan backend diperlukan.
2. File apa saja yang akan terdampak.
3. Risiko terhadap fitur yang sudah ada.
4. Alternatif implementasi frontend-only.
5. Meminta persetujuan sebelum mengerjakannya.

# PERLINDUNGAN BACKEND DAN SISTEM ADMIN

Bagian ini merupakan aturan wajib selama pengembangan UX/UI OOPy.

## A. Perlindungan Sistem Admin

Seluruh fitur Admin yang sudah tersedia harus tetap berfungsi, termasuk:

- Dashboard Admin.
- Manajemen pengguna.
- Rekap nilai dan ekspor data.
- Manajemen bank soal.
- Manajemen materi.
- Pembuatan dan pengeditan materi.
- Penyimpanan draft.
- Preview materi.
- Revisi dan publikasi materi.

Jangan menghapus, mengganti, atau menyederhanakan fungsi tersebut.

## B. Perlindungan Manajemen Materi

Halaman materi yang dilihat pengguna merupakan hasil penyajian konten yang dikelola melalui sistem Admin dan sumber materi yang sudah tersedia.

Oleh karena itu:

1. Jangan mengubah struktur data materi tanpa persetujuan.
2. Jangan mengubah format penyimpanan materi.
3. Jangan mengubah mekanisme pengambilan materi dari database atau file sumber.
4. Jangan menghapus kemampuan Admin membuat dan mengedit materi.
5. Jangan merusak mekanisme draft, preview, revisi, dan publikasi.
6. Pastikan perubahan komponen frontend tetap dapat menampilkan materi yang sudah tersimpan.
7. Pastikan perubahan desain tidak menyebabkan konten yang dibuat Admin hilang atau tidak terbaca.
8. Jangan mengganti konten dinamis dengan konten HTML statis yang ditulis langsung pada template.

## C. Pemisahan Frontend dan Backend

Utamakan perubahan pada:

- Blade View.
- CSS.
- JavaScript frontend.
- Komponen presentasi yang tidak mengubah kontrak data.

Jangan mengubah:

- Controller.
- Model.
- Service.
- Migration.
- Database.
- Route.
- Middleware.
- Sistem autentikasi dan otorisasi.
- API dan kontrak request/response.

Jika perubahan pada backend benar-benar diperlukan, hentikan implementasi pada bagian tersebut dan buat laporan alasan, risiko, serta alternatifnya. Tunggu persetujuan sebelum melanjutkan.

## D. Kompatibilitas Konten Admin

Setiap perubahan komponen tampilan materi harus tetap mendukung:

- Materi lama yang sudah tersimpan.
- Materi baru yang dibuat Admin.
- Materi yang sedang berstatus draft.
- Materi yang telah dipublikasikan.
- Konten kode Python.
- Output kode.
- Tabel dan catatan materi.
- Komponen pembelajaran yang digunakan dalam sistem.

Jangan mengasumsikan seluruh konten materi memiliki struktur yang identik. Periksa struktur aktual sebelum membuat perubahan.

## E. Pengujian Regresi Admin

Sebelum menyatakan pekerjaan selesai, lakukan pengujian:

1. Admin dapat membuka halaman manajemen materi.
2. Admin dapat membuat atau mengedit materi.
3. Draft dapat disimpan.
4. Preview materi tetap berfungsi.
5. Publikasi materi tetap berfungsi.
6. Materi yang dipublikasikan dapat ditampilkan pada halaman pengguna.
7. Materi lama tetap dapat dibaca.
8. Bank soal dan rekap nilai tetap berfungsi.

Jika pengujian tidak dapat dilakukan, tandai sebagai NOT TESTED. Jangan mengklaim sistem Admin aman tanpa pengujian.

# 14. STRATEGI IMPLEMENTASI BERTAHAP

Jangan mengerjakan seluruh perubahan dalam satu proses besar.

## Tahap 0 — Audit dan pemetaan

- Baca `DESIGN.md`.
- Periksa seluruh halaman materi.
- Inventaris komponen Blade.
- Inventaris CSS dan JavaScript.
- Periksa mekanisme Live Coding dan kuis.
- Identifikasi komponen yang dapat digunakan bersama.
- Catat potensi regresi.

Output: laporan audit dan daftar file yang akan diubah.

## Tahap 1 — Fondasi UX materi

- Perbaiki hierarki visual.
- Perbaiki navigasi daftar isi.
- Tambahkan penanda posisi membaca.
- Optimalkan spacing.
- Pastikan responsivitas.

Output: fondasi tampilan konsisten untuk BAB 1–6.

## Tahap 2 — Interaksi pembelajaran

- Implementasikan komponen micro-interaction.
- Tambahkan prediksi output pada konsep terpilih.
- Tambahkan pertanyaan pemahaman.
- Tambahkan feedback jawaban.

Output: pengalaman belajar lebih interaktif.

## Tahap 3 — Live Coding

- Optimalkan instruksi.
- Perbaiki hierarki editor dan output.
- Tingkatkan feedback.
- Tambahkan panduan bertahap.
- Periksa editor multi-file.

Output: praktik coding lebih mudah dipahami.

## Tahap 4 — Rangkuman dan evaluasi

- Optimalkan rangkuman.
- Perjelas refleksi.
- Perbaiki tampilan kuis.
- Optimalkan feedback hasil.
- Pertahankan aturan penilaian dan kelulusan.

Output: evaluasi lebih mudah dipahami.

## Tahap 5 — Pengujian dan validasi

- Uji seluruh BAB.
- Uji navigasi.
- Uji editor.
- Uji kuis.
- Uji responsivitas.
- Uji aksesibilitas.
- Periksa kemungkinan regresi.

Output: laporan pengujian dan bukti hasil.

# 15. DEFINITION OF DONE

Perubahan UX baru dianggap selesai jika seluruh kriteria yang relevan terpenuhi.

### Navigasi
- [ ] Sidebar berfungsi.
- [ ] Link subbab menuju bagian yang benar.
- [ ] Penanda bagian aktif bekerja.
- [ ] Navigasi BAB sebelumnya dan berikutnya bekerja.
- [ ] Aturan penguncian BAB tidak berubah.

### Materi
- [ ] Semua materi tetap tersedia.
- [ ] Tidak ada isi penting yang terhapus.
- [ ] Contoh kode dan output tetap benar.
- [ ] Hierarki heading jelas.
- [ ] Konten mudah dipindai.
- [ ] Micro-interaction memberikan feedback yang benar.

### Live Coding
- [ ] Monaco Editor tetap berjalan.
- [ ] Runtime Python tetap berjalan.
- [ ] Run Code bekerja.
- [ ] Submit bekerja.
- [ ] Reset bekerja jika tersedia.
- [ ] Output ditampilkan dengan benar.
- [ ] Kode pengguna tidak hilang secara tidak sengaja.
- [ ] Grading tidak berubah.
- [ ] Latihan multi-file tetap berfungsi.

### Kuis
- [ ] Soal tetap tampil.
- [ ] Navigasi soal bekerja.
- [ ] Jawaban dapat diubah.
- [ ] Validasi bekerja.
- [ ] Penilaian server tetap berjalan.
- [ ] Nilai tersimpan.
- [ ] Kelulusan tetap sesuai aturan.
- [ ] Penguncian BAB tetap bekerja.

### Desain
- [ ] Sesuai `DESIGN.md`.
- [ ] Warna konsisten.
- [ ] Font konsisten.
- [ ] Tidak ada elemen dekoratif berlebihan.
- [ ] Tidak ada horizontal overflow.
- [ ] Tampilan mobile nyaman.
- [ ] Kontras memenuhi WCAG AA untuk komponen yang diuji.
- [ ] Keyboard focus terlihat.
- [ ] Status tidak hanya dibedakan dengan warna.

# 16. SKENARIO USABILITY TESTING

Lakukan pengujian dengan 5–8 pengguna pemula Python jika memungkinkan.

Berikan tugas:

1. Temukan materi yang menjelaskan class dan object.
2. Jelaskan perbedaan atribut dan method.
3. Jalankan contoh kode.
4. Ubah kode sesuai instruksi.
5. Temukan dan perbaiki kesalahan kode.
6. Buat subclass menggunakan `super()`.
7. Terapkan polymorphism pada beberapa object.
8. Buat abstract class.
9. Selesaikan kuis BAB.
10. Temukan kembali materi yang belum dipahami.

Catat:

- Task Success Rate.
- Time on Task.
- Error Rate.
- Assistance Rate.
- User Satisfaction.

Jangan memberikan bantuan sebelum pengguna mencoba menyelesaikan tugas sendiri. Catat bagian yang menyebabkan kebingungan.

# 17. INSTRUKSI MASTER UNTUK CODEX

Gunakan instruksi berikut sebagai pedoman tetap ketika melakukan revisi UX OOPy.

---

**PERAN:**

Bertindak sebagai Senior UI/UX Engineer dan Frontend Developer yang memahami pengembangan media pembelajaran interaktif berbasis Laravel dan Blade.

**TUGAS:**

Lakukan penyempurnaan UX/UI materi BAB 1–6 OOPy berdasarkan dokumen MASTER UX IMPROVEMENT PLAN dan `DESIGN.md`.

**ATURAN WAJIB:**

1. Audit file terkait sebelum mengubah kode.
2. Gunakan struktur Blade dan komponen yang sudah ada.
3. Pertahankan seluruh isi materi.
4. Pertahankan identitas mint–teal OOPy.
5. Gunakan Plus Jakarta Sans.
6. Tingkatkan keterbacaan dan hierarki informasi.
7. Tambahkan interaksi hanya jika mempunyai manfaat pembelajaran.
8. Pertahankan Monaco Editor dan runtime Python.
9. Pertahankan mekanisme kuis dan penilaian.
10. Pertahankan seluruh route dan kontrak backend.
11. Jangan mengubah database.
12. Jangan melakukan refaktor besar yang tidak berhubungan dengan tugas.
13. Jangan mengganti komponen lama sebelum memeriksa apakah komponen tersebut masih digunakan.
14. Gunakan CSS dan JavaScript secara terstruktur.
15. Pastikan desain responsif.
16. Jangan menambahkan fitur yang hanya berupa dekorasi.
17. Jangan mengarang status, nilai, atau progres pengguna.
18. Jangan mengklaim pengujian berhasil jika belum benar-benar dijalankan.

**PENDEKATAN KERJA:**

- Mulai dengan audit.
- Buat rencana perubahan.
- Kerjakan secara bertahap.
- Gunakan komponen yang dapat digunakan ulang.
- Uji setiap perubahan.
- Periksa regresi.
- Laporkan seluruh file yang diubah.

**LAPORAN AKHIR WAJIB:**

1. Ringkasan perubahan.
2. Daftar file yang dimodifikasi.
3. Penjelasan peningkatan UX.
4. Fitur yang dipertahankan.
5. Hasil pengujian yang benar-benar dijalankan.
6. Screenshot sebelum dan sesudah jika tersedia.
7. Masalah yang masih ditemukan.
8. Bagian yang belum diuji.
9. Status PASS / FAIL / NOT TESTED.

Jangan melakukan commit atau push Git tanpa instruksi.

---

# 18. PENUTUP

Master UX Improvement Plan ini menjadi pedoman utama dalam menyempurnakan pengalaman belajar OOPy.

Arah pengembangan bukan membuat halaman semakin ramai atau menambahkan sebanyak mungkin fitur, tetapi menciptakan pengalaman belajar yang lebih terarah, interaktif, mudah dipahami, dan konsisten.

Prinsip utamanya:

**Pengguna tidak hanya membaca materi, tetapi memahami konsep, mencoba kode, mengenali kesalahan, memperbaiki program, dan mengevaluasi pemahamannya.**

Seluruh perubahan harus mempertahankan stabilitas sistem OOPy yang sudah berjalan.

Dokumen ini merupakan pedoman pengembangan UX, bukan izin otomatis untuk mengubah backend atau database. Setiap perubahan di luar cakupan frontend harus melalui pemeriksaan dan persetujuan terlebih dahulu.
