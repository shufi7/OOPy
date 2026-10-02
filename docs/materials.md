# Halaman materi OOPy

Daftar BAB tetap berada di `/materi`. Detail BAB 1 dan BAB 2 tersedia pada
`/materi/dasar-pemrograman-oop` dan `/materi/kelas-dan-objek` melalui route
`materi.show` (`GET /materi/{slug}`). BAB 3–6 ditampilkan sebagai **Segera hadir**,
tanpa tautan detail. Slug yang belum
memiliki konten atau tidak dikenal menghasilkan 404; controller hanya membaca
filename yang tercantum dalam registry, bukan path dari URL pengguna.

## Struktur

- `resources/materi/chapters.php`: registry judul, poin card, dan file konten BAB.
- `resources/materi/dasar-pemrograman-oop.php`: deskripsi, tujuan, sembilan bagian
  materi, contoh kode, catatan, konfigurasi Live Coding, rangkuman dan data kuis BAB 1.
- `resources/materi/kelas-dan-objek.php`: tujuh bagian BAB 2, termasuk apersepsi,
  bedah kode, latihan Spesies/SensorAir, rangkuman, refleksi, dan delapan soal kuis.
- `app/Http/Controllers/MateriController.php`: `index()` untuk daftar dan `show()`
  untuk detail yang terdaftar, tanpa query database.
- `resources/views/materi/show.blade.php`: breadcrumb, header, konten BAB,
  kuis dan satu navigasi dinamis antar-BAB beserta tautan kembali ke daftar materi.
- `resources/views/materi/partials/quiz.blade.php`: struktur aktivitas kuis dan hasil.
- `public/js/oopy-quiz.js`: pilihan jawaban, navigasi, skor, pembahasan dan Coba Lagi.
- `resources/views/materi/partials/navigation.blade.php`: daftar isi dan progres.
- `resources/views/materi/partials/section.blade.php`: paragraf, contoh kode, catatan
  serta lokasi opsional komponen Live Coding pada setiap bagian materi.
- `public/css/oopy-material.css`: gaya yang dibatasi ke `.oopy-material`.
- `public/js/oopy-material.js`: menu mobile, penanda bagian aktif dan fokus anchor.

BAB 1 memiliki anchor `tujuan`, `python`, `variabel`, `tipe-data`, `input-output`,
`operator`, `percabangan`, `perulangan`, `fungsi`, `oop`, `rangkuman`, `kuis`.
BAB 2 menambahkan Refleksi sesudah Rangkuman dan sebelum Kuis BAB. Sidebar memakai
urutan data section yang sama dengan artikel; link Refleksi hanya muncul jika
`reflection` berisi pertanyaan. Tidak ada jumlah section yang diwajibkan antar-BAB.
Sidebar sticky pada lebar minimal 992px. Di bawahnya, daftar isi menggunakan
`details`/`summary` dalam alur halaman sehingga tidak menutupi materi. Tanpa
JavaScript, konten dan navigasi anchor tetap tersedia, termasuk menu native.

## Menggunakan template untuk BAB berikutnya

1. Siapkan file data BAB mengikuti struktur BAB 1/BAB 2: `description`,
   `objectives`, `sections`, `summary`, `reflection`, dan `quiz`. Deskripsi dan
   array opsional dapat dihilangkan; rangkuman/refleksi/kuis kosong tidak dirender
   dan tidak mendapatkan tautan sidebar.
2. Pada entri BAB di `chapters.php`, tambahkan `content` berisi nama file data.
   Card otomatis menampilkan tautan detail; navigasi sebelumnya/berikutnya
   memakai registry melalui `$previousChapter` dan `$nextChapter`.
3. Isi setiap section dengan `id` unik dan `title`. `paragraphs`, `code`, `tip`,
   dan `live_codes` bersifat opsional. ID harus valid untuk anchor dan tidak sama
   dengan `tujuan`, `rangkuman`, `refleksi`, `kuis`, atau ID komponen lainnya.
4. Tambahkan tes judul, section dan URL BAB baru. Route dan template dapat dipakai
   tanpa menambahkan controller, route, atau view khusus BAB tersebut.

Urutan pembelajaran: Tujuan → Apersepsi (section opsional) → konsep/contoh/bedah
kode → aktivitas Live Coding → Rangkuman → Refleksi (opsional) → Kuis → navigasi.
Section tetap mengikuti urutan data. Template ini menjadi acuan BAB 3–6; konten
BAB tersebut belum ditambahkan.

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

Dalam section materi, komponen memakai `:heading-level="3"` agar berada di bawah
heading section `h2`. Default komponen tetap `2` untuk halaman demo editor.
CSS memilih role/class heading sehingga tampilannya sama pada kedua konteks.
Aset komponen tetap dimuat sekali oleh `@pushOnce`, termasuk saat ada dua latihan.

Starter Spesies dan SensorAir BAB 2 menyediakan signature method dan komentar
petunjuk; mahasiswa melengkapi constructor, return, pembuatan object dan output.
Contoh program lengkap di materi dan checker perilaku sebelumnya dipertahankan.
Starter belum lulus Submit; contoh solusi benar dapat lulus checker yang sama.

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

- Progres BAB ditampilkan sebagai judul dan bar tanpa kartu atau teks keterangan.
  Bar tetap kosong. Tidak
  mengikuti skor Submit, tidak disimpan ke browser maupun server.
- Latihan Variabel BAB 1 serta Spesies dan SensorAir BAB 2 memakai komponen
  Monaco/Pyodide yang sama dari CDN. Materi teks dan contoh
  `<pre><code>` tetap dapat dibaca ketika editor belum siap.
- `input()` dijelaskan dengan contoh untuk terminal lokal; editor browser belum
  mendukung input interaktif.
- Kuis adalah prototype frontend, tanpa backend kuis, storage browser, atau
  penyimpanan nilai. Kunci soal tersedia di browser, bukan penilaian ujian tepercaya.
  Skor kuis tidak mengubah progres BAB maupun hasil Live Coding.
- Hanya BAB 1 dan BAB 2 yang memiliki detail. Tidak ada autentikasi, database materi,
  dashboard, atau perubahan pada engine Live Coding, navbar, footer, dan Beranda.

## Pengujian

```powershell
php artisan test
node --check public/js/oopy-material.js
node --check public/js/oopy-quiz.js
php vendor/bin/pint --test --dirty
git diff --check
```

Tes `MateriTest` mencakup kedua BAB, tautan valid, 404 BAB 3–6, satu navigasi akhir,
urutan section/sidebar, refleksi kosong/hilang/terisi, array opsional, ID unik,
kedua kuis, render Live Coding, serta regresi Beranda dan `/editor`.
`LiveCodeTest` memeriksa kedua starter BAB 2, heading kontekstual, aset sekali,
dan seluruh kontrak komponen sebelumnya.

Untuk tes browser, gunakan Playwright dan browser Edge/Chrome yang terpasang
seperti pada [panduan tes Live Coding](live-coding.md#verifikasi):

```powershell
# Khusus server pengujian, tanpa membutuhkan MySQL atau mengubah .env:
$env:SESSION_DRIVER = 'array'
$env:CACHE_STORE = 'array'
php artisan serve --host=127.0.0.1 --port=8017 --no-reload
# Terminal terpisah:
node tests/browser/material.mjs
node tests/browser/quiz.mjs
```

Atur `OOPY_BROWSER=chrome` jika menggunakan Chrome dan `OOPY_BASE_URL` jika alamat
server berbeda. Tes memeriksa alur Beranda → Materi → BAB 1, navigasi/fokus pada
390/768/1024/1440px, Run/Submit/Reset, progres BAB terpisah dari skor latihan,
deep link mobile, dan navigasi tanpa JavaScript. Untuk BAB 2, tes juga memeriksa
320px, Refleksi/fokus, Prism, navigasi dua arah, reduced motion, satu worker/loader,
dan Submit starter/salah/benar serta Reset pada kedua latihan.
`OOPY_SCREENSHOT_DIR` opsional
menyimpan screenshot desktop dan mobile ke direktori yang sudah ada.

Tes kuis memeriksa instruksi, navigasi maju/mundur, radio keyboard, jawaban tersimpan,
pengubahan jawaban, penolakan penyelesaian dengan jawaban kosong, skor 0/80/100%,
pembahasan, Coba Lagi, refresh, sidebar aktif dan layout responsif. Tidak ada request
penyimpanan nilai ke server selama interaksi kuis. BAB 2 turut diperiksa untuk
delapan soal, skor 100%, pembahasan dengan Prism, Coba Lagi, dan layout hasil.

Jalankan perintah di atas pada environment yang menyediakan dependensi tes;
integrasi browser memerlukan akses ke CDN Monaco dan Pyodide.

Verifikasi finalisasi BAB 1/BAB 2: `php artisan test` lulus (18 tes, 207 assertions),
6 tes Node runtime manager lulus, dan ketiga skrip browser `material.mjs`,
`quiz.mjs`, serta `live-code.mjs` lulus di Edge headless dengan CDN asli.
Pint pada PHP terkait dan `git diff --check` juga lulus. Server browser memakai
session/cache `array` karena MySQL lokal tidak aktif; konfigurasi `.env` tetap.
