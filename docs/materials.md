# Halaman materi OOPy

Daftar BAB tetap berada di `/materi`. Detail BAB 1 tersedia pada
`/materi/dasar-pemrograman-oop` melalui route `materi.show` (`GET /materi/{slug}`).
BAB 2–6 ditampilkan sebagai **Segera hadir**, tanpa tautan detail. Slug yang belum
memiliki konten atau tidak dikenal menghasilkan 404; controller hanya membaca
filename yang tercantum dalam registry, bukan path dari URL pengguna.

## Struktur

- `resources/materi/chapters.php`: registry judul, poin card, dan file konten BAB.
- `resources/materi/dasar-pemrograman-oop.php`: deskripsi, tujuan, sembilan bagian
  materi, contoh kode, catatan, konfigurasi Live Coding, rangkuman dan data kuis BAB 1.
- `app/Http/Controllers/MateriController.php`: `index()` untuk daftar dan `show()`
  untuk detail yang terdaftar, tanpa query database.
- `resources/views/materi/show.blade.php`: breadcrumb, header, konten BAB,
  kuis dan navigasi kembali.
- `resources/views/materi/partials/quiz.blade.php`: struktur aktivitas kuis dan hasil.
- `public/js/oopy-quiz.js`: pilihan jawaban, navigasi, skor, pembahasan dan Coba Lagi.
- `resources/views/materi/partials/navigation.blade.php`: daftar isi dan progres.
- `resources/views/materi/partials/section.blade.php`: paragraf, contoh kode, catatan
  serta lokasi opsional komponen Live Coding pada setiap bagian materi.
- `public/css/oopy-material.css`: gaya yang dibatasi ke `.oopy-material`.
- `public/js/oopy-material.js`: menu mobile, penanda bagian aktif dan fokus anchor.

BAB 1 memiliki anchor `tujuan`, `python`, `variabel`, `tipe-data`, `input-output`,
`operator`, `percabangan`, `perulangan`, `fungsi`, `oop`, `rangkuman`, `kuis`.
Section Latihan BAB telah dihapus; Rangkuman langsung diikuti Kuis BAB.
Sidebar sticky pada lebar minimal 992px. Di bawahnya, daftar isi menggunakan
`details`/`summary` dalam alur halaman sehingga tidak menutupi materi. Tanpa
JavaScript, konten dan navigasi anchor tetap tersedia, termasuk menu native.

## Menambahkan BAB 2

1. Buat `resources/materi/kelas-dan-objek.php` mengikuti struktur data BAB 1:
   `description`, `objectives`, `sections`, `summary`, dan `quiz`.
2. Pada entri `kelas-dan-objek` di `chapters.php`, tambahkan
   `'content' => 'kelas-dan-objek.php'`. Card otomatis menampilkan tautan detail.
3. Isi setiap section dengan `id` unik, `title`, dan array `paragraphs`.
   `code`, `tip`, dan `live_codes` bersifat opsional. ID harus valid untuk anchor
   dan tidak sama dengan `tujuan`, `rangkuman`, atau `kuis`.
4. Tambahkan tes judul, section dan URL BAB baru. Route dan template dapat dipakai
   tanpa menambahkan controller atau route khusus BAB 2.

Semua konten adalah data yang ditulis developer. Blade melakukan escaping pada
teks dan kode; tidak perlu memasukkan HTML ke dalam file data.

## Live Coding di materi

Tambahkan array `live_codes` pada section yang dipilih. Setiap elemennya memakai
API komponen yang sudah tersedia:

```php
'live_codes' => [
    [
        'id' => 'bab2-objek-1',
        'title' => 'Mengenal objek',
        'description' => 'Jalankan kode dan periksa nilai nama.',
        'entry_file' => 'main.py',
        'files' => ['main.py' => "nama = 'Rawa Bangkau'\nprint(nama)"],
        'checker' => "assert nama == 'Rawa Bangkau'",
    ],
],
```

Partial section merender `<x-live-code :config="$exercise" />` untuk setiap config.
Gunakan ID unik pada satu halaman. Engine `public/js/live-code/*` tidak perlu diubah.
Lihat [panduan Live Coding](live-coding.md) untuk multi-file dan checker terperinci.

## Kuis interaktif

BAB 1 memiliki lima soal: variabel, tipe data, percabangan, perulangan dan fungsi.
Data berasal dari array `quiz` di `resources/materi/dasar-pemrograman-oop.php` dan
diserialisasi sebagai JSON oleh Blade. Untuk menambah soal, tambahkan satu elemen:

```php
[
    'question' => 'Apa tipe data nilai ini?',
    'code' => 'jumlah_habitat = 2', // Opsional jika soal tidak memerlukan kode.
    'options' => ['str', 'int', 'float', 'bool'],
    'correct' => 1, // Indeks dimulai dari 0, sehingga 1 adalah pilihan B.
    'explanation' => '2 adalah bilangan bulat, sehingga bertipe int.',
],
```

Gunakan empat pilihan dan satu indeks jawaban benar yang valid. Counter dan total
skor mengikuti jumlah soal. JavaScript membuat pilihan radio dari data, menyimpan
jawaban selama halaman terbuka, serta mengembalikan pilihan saat berpindah soal.
Pengguna boleh melewati soal dan mengubah jawaban sebelum menyelesaikan kuis.
Jika ada jawaban kosong saat Selesai Kuis ditekan, pengguna diarahkan ke soal kosong
pertama; hasil belum ditampilkan.

Skor dihitung dengan `Math.round(jawabanBenar / jumlahSoal * 100)`. Hasil menampilkan
jumlah benar/salah dan pembahasan tiap soal, termasuk pilihan pengguna dan kunci.
Pembahasan tidak muncul ketika baru memilih jawaban. Coba Lagi menghapus jawaban
dan kembali ke soal pertama. Refresh juga mengulang kuis dari awal.

## Batasan tahap ini

- Progres BAB menampilkan 0% dengan keterangan pencatatan belum tersedia. Tidak
  mengikuti skor Submit, tidak disimpan ke browser maupun server.
- Satu latihan Variabel memakai Monaco/Pyodide dari CDN. Materi teks dan contoh
  `<pre><code>` tetap dapat dibaca ketika editor belum siap.
- `input()` dijelaskan dengan contoh untuk terminal lokal; editor browser belum
  mendukung input interaktif.
- Kuis adalah prototype frontend, tanpa backend kuis, storage browser, atau
  penyimpanan nilai. Kunci soal tersedia di browser, bukan penilaian ujian tepercaya.
  Skor kuis tidak mengubah progres BAB maupun hasil Live Coding.
- Hanya BAB 1 yang memiliki detail. Tidak ada autentikasi, database materi,
  dashboard, atau perubahan pada engine Live Coding, navbar, footer, dan Beranda.

## Pengujian

```powershell
php artisan test
node --check public/js/oopy-material.js
node --check public/js/oopy-quiz.js
php vendor/bin/pint --test --dirty
git diff --check
```

Tes `MateriTest` mencakup daftar BAB, tautan valid, 404 untuk BAB yang belum tersedia,
breadcrumb, seluruh section, ID unik, render Live Coding serta regresi Beranda dan
`/editor`. Tes Live Coding sebelumnya tetap dijalankan.

Untuk tes browser, gunakan Playwright dan browser Edge/Chrome yang terpasang
seperti pada [panduan tes Live Coding](live-coding.md#verifikasi):

```powershell
php artisan serve --host=127.0.0.1 --port=8017
# Terminal terpisah:
node tests/browser/material.mjs
node tests/browser/quiz.mjs
```

Atur `OOPY_BROWSER=chrome` jika menggunakan Chrome dan `OOPY_BASE_URL` jika alamat
server berbeda. Tes memeriksa alur Beranda → Materi → BAB 1, navigasi/fokus pada
390/768/1024/1440px, Run/Submit/Reset, progres BAB terpisah dari skor latihan,
deep link mobile, dan navigasi tanpa JavaScript. `OOPY_SCREENSHOT_DIR` opsional
menyimpan screenshot desktop dan mobile ke direktori yang sudah ada.

Tes kuis memeriksa instruksi, navigasi maju/mundur, radio keyboard, jawaban tersimpan,
pengubahan jawaban, penolakan penyelesaian dengan jawaban kosong, skor 0/80/100%,
pembahasan, Coba Lagi, refresh, sidebar aktif dan layout responsif. Tidak ada request
penyimpanan nilai ke server selama interaksi kuis.

Verifikasi implementasi BAB 1: 13 tes Laravel (99 assertions) lulus, termasuk seluruh
tes Live Coding yang sudah ada. Tes browser di Edge headless lulus untuk keempat
ukuran layar, integrasi Monaco/Pyodide asli, anchor, fokus, dan mode tanpa JavaScript.
Pemeriksaan sintaks JavaScript, Pint pada file terkait, dan `git diff --check` lulus.
