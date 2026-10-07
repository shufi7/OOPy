# Halaman materi OOPy

Daftar BAB tetap berada di `/materi`. Detail BAB 1–3 tersedia pada
`/materi/dasar-pemrograman-oop`, `/materi/kelas-dan-objek`, dan `/materi/enkapsulasi`
melalui route `materi.show` (`GET /materi/{slug}`). BAB 4–6 ditampilkan sebagai
**Segera hadir** (tiga card),
tanpa tautan detail. Slug yang belum
memiliki konten atau tidak dikenal menghasilkan 404; controller hanya membaca
filename yang tercantum dalam registry, bukan path dari URL pengguna.

## Struktur

- `resources/materi/chapters.php`: registry judul, poin card, dan file konten BAB.
- `resources/materi/dasar-pemrograman-oop.php`: deskripsi, tujuan, sembilan bagian
  materi, contoh kode, catatan, konfigurasi Live Coding, rangkuman dan data kuis BAB 1.
- `resources/materi/kelas-dan-objek.php`: tujuh bagian BAB 2, termasuk apersepsi,
  bedah kode, latihan Spesies/SensorAir, rangkuman, refleksi, dan delapan soal kuis.
- `resources/materi/enkapsulasi.php`: lima tujuan, sembilan bagian BAB 3,
  latihan property SensorAir, tujuh poin rangkuman, tiga refleksi, dan delapan soal.
- `app/Http/Controllers/MateriController.php`: `index()` untuk daftar dan `show()`
  untuk detail yang terdaftar, tanpa query database.
- `resources/views/materi/show.blade.php`: breadcrumb, header, konten BAB,
  kuis dan satu navigasi dinamis antar-BAB beserta tautan kembali ke daftar materi.
- `resources/views/materi/partials/quiz.blade.php`: struktur aktivitas kuis dan hasil.
- `public/js/oopy-quiz.js`: pilihan jawaban, navigasi, skor, pembahasan dan Coba Lagi.
- `resources/views/materi/partials/navigation.blade.php`: daftar isi dan progres.
- `resources/views/materi/partials/section.blade.php`: paragraf, contoh kode, catatan
  serta lokasi opsional komponen Live Coding pada setiap bagian materi.
- `public/css/oopy/material/index.css`: card pada halaman daftar materi.
- `public/css/oopy/material/material.css`: struktur artikel, bagian materi dan navigasi antar-BAB.
- `public/css/oopy/material/navigation.css`: sidebar, daftar isi dan progres BAB.
- `public/css/oopy/material/code.css`: contoh kode Python dan tema token Prism.
- `public/css/oopy/material/quiz.css`: form, hasil dan pembahasan kuis.
- `public/js/oopy-material.js`: menu mobile, penanda bagian aktif dan fokus anchor.

Layout aplikasi memuat `public/css/oopy/base.css`, `layout.css` dan `navbar.css`,
diikuti `@stack('styles')`. Beranda menambahkan `home.css` melalui `@push`.
Daftar materi hanya menambahkan `material/index.css`; detail BAB menambahkan
`material.css`, `navigation.css`, `code.css` dan `quiz.css` melalui `@push`.
Gaya artikel dan contoh kode tetap dibatasi ke `.oopy-material`, sedangkan kuis
dibatasi ke `.oopy-quiz`. Komponen Live Coding memuat
`public/css/oopy/live-code/live-code.css` melalui `@pushOnce`, termasuk ketika
beberapa latihan tampil dalam satu halaman. File CSS dipanggil langsung melalui
link Blade tanpa `@import`; nilai variabel tema tetap berada di `base.css`.

BAB 1 memiliki anchor `tujuan`, `python`, `variabel`, `tipe-data`, `input-output`,
`operator`, `percabangan`, `perulangan`, `fungsi`, `oop`, `rangkuman`, `kuis`.
BAB 2 menambahkan Refleksi sesudah Rangkuman dan sebelum Kuis BAB. Sidebar memakai
urutan data section yang sama dengan artikel; link Refleksi hanya muncul jika
`reflection` berisi pertanyaan. Tidak ada jumlah section yang diwajibkan antar-BAB.
Sidebar sticky pada lebar minimal 992px. Di bawahnya, daftar isi menggunakan
`details`/`summary` dalam alur halaman sehingga tidak menutupi materi. Tanpa
JavaScript, konten dan navigasi anchor tetap tersedia, termasuk menu native.

BAB 3 mengikuti alur Tujuan → Apersepsi → Mengenal Enkapsulasi → Public Attribute
dan Interface Object → Konvensi Non-Public dengan `_` → Name Mangling dengan `__`
→ Getter dan Setter → Property dengan `@property` → Bedah Kode SensorAir → Live
Coding / Aktivitas Enkapsulasi → Rangkuman → Refleksi → Kuis BAB 3.
Enkapsulasi mencakup data, perilaku, dan interface; `_` adalah konvensi internal,
`__` melakukan name mangling, bukan jaminan keamanan akses. Property mengontrol
pembacaan/perubahan dengan syntax atribut. Angka hanya data latihan, bukan
pengukuran lapangan. Constructor contoh lengkap memakai setter agar nilai awal
juga divalidasi.

Rujukan konsep Python: [konvensi non-public dan name mangling](https://docs.python.org/3/tutorial/classes.html#private-variables)
serta [property dan setter](https://docs.python.org/3/library/functions.html#property).

Navigasi otomatis: BAB 1 → BAB 2; BAB 2 → BAB 1 / BAB 3; BAB 3 → BAB 2.
BAB 3 tidak memiliki Next ke BAB 4. Semua BAB memiliki tautan ke daftar materi.

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
Section tetap mengikuti urutan data. Template ini menjadi acuan BAB 4–6; konten
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

BAB 3 memakai komponen yang sama dengan ID `bab3-enkapsulasi-sensor` dan judul
**Coba sendiri: Enkapsulasi Sensor Air**. Starter `main.py` memiliki tiga `pass`:
penyimpanan nilai awal, getter, dan setter. Mahasiswa melengkapi class, membaca 85,
memperbarui menjadi 90, serta menampilkan hasilnya. Nilai tidak negatif (termasuk
0) diterima, sedangkan nilai negatif ditolak dengan `ValueError` tanpa merusak data.

Checker menggunakan format `results` yang sudah didukung. Sembilan label memeriksa
class, pembuatan object, lokasi, getter property, nilai awal, setter nilai valid,
penolakan nilai negatif, data setelah penolakan, dan independensi data antarobject.
Checker membuat object sendiri dan menggunakan beberapa nilai (termasuk 0 dan
desimal), tanpa memeriksa string source atau mewajibkan nama variabel mahasiswa.
Pemeriksaan property dilakukan pada object Python, bukan pencarian syntax.
Setiap kegagalan memberikan petunjuk. Skor latihan kembali 0% saat kode diedit
atau di-reset dan tidak disimpan. Tidak ada konfigurasi atau engine baru.

## Kuis interaktif

BAB 1 memiliki lima soal: variabel, tipe data, percabangan, perulangan dan fungsi.
BAB 2 dan BAB 3 masing-masing memiliki delapan soal. Kuis BAB 3 mencakup
enkapsulasi, public attribute, konvensi `_`, name mangling, getter, validasi setter,
property, serta analisis kode. Empat soal BAB 3 menggunakan potongan kode Python.
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
- Latihan Variabel BAB 1, Spesies dan SensorAir BAB 2, serta enkapsulasi BAB 3 memakai komponen
  Monaco/Pyodide yang sama dari CDN. Materi teks dan contoh
  `<pre><code>` tetap dapat dibaca ketika editor belum siap.
- `input()` dijelaskan dengan contoh untuk terminal lokal; editor browser belum
  mendukung input interaktif.
- Kuis adalah prototype frontend, tanpa backend kuis, storage browser, atau
  penyimpanan nilai. Kunci soal tersedia di browser, bukan penilaian ujian tepercaya.
  Skor kuis tidak mengubah progres BAB maupun hasil Live Coding.
- Hanya BAB 1–3 yang memiliki detail. Tidak ada autentikasi, database materi,
  dashboard, atau perubahan pada engine Live Coding, navbar, footer, dan Beranda.

## Pengujian

```powershell
php artisan test
node --test tests/js/*.test.js
node --check public/js/oopy-material.js
node --check public/js/oopy-quiz.js
php vendor/bin/pint --test --dirty
php vendor/bin/pint --test resources/materi/enkapsulasi.php
git diff --check
```

Tes `MateriTest` mencakup BAB 1–3, tautan valid, 404 BAB 4–6, satu navigasi akhir,
urutan section/sidebar, refleksi kosong/hilang/terisi, array opsional, ID unik,
ketiga kuis, render Live Coding, serta regresi Beranda dan `/editor`.
`LiveCodeTest` memeriksa starter BAB 2/3, heading kontekstual, aset sekali,
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
node tests/browser/live-code.mjs
```

Atur `OOPY_BROWSER=chrome` jika menggunakan Chrome dan `OOPY_BASE_URL` jika alamat
server berbeda. Tes memeriksa alur Beranda → Materi → BAB 1, navigasi/fokus pada
390/768/1024/1440px, Run/Submit/Reset, progres BAB terpisah dari skor latihan,
deep link mobile, dan navigasi tanpa JavaScript. Untuk BAB 2, tes juga memeriksa
320px, Refleksi/fokus, Prism, navigasi dua arah, reduced motion, satu worker/loader,
dan Submit starter/salah/benar serta Reset pada kedua latihan.
BAB 3 turut diuji pada 320/390/768/1024/1440px: kesesuaian sidebar/section,
instruksi, editor dalam viewport, navigasi, ID unik, heading, focus/reduced motion,
pembacaan tanpa JavaScript, satu worker/loader, dan tanpa error JavaScript.
Submit menguji starter yang belum lengkap, setter yang menerima negatif, setter
yang melempar error setelah merusak data, solusi benar 100%, serta Reset ke starter
dan 0%.
`OOPY_SCREENSHOT_DIR` opsional
menyimpan screenshot desktop dan mobile ke direktori yang sudah ada.

Tes kuis memeriksa instruksi, navigasi maju/mundur, radio keyboard, jawaban tersimpan,
pengubahan jawaban, penolakan penyelesaian dengan jawaban kosong, skor 0/80/100%,
pembahasan, Coba Lagi, refresh, sidebar aktif dan layout responsif. Tidak ada request
penyimpanan nilai ke server selama interaksi kuis. BAB 2 turut diperiksa untuk
delapan soal, skor 100%, pembahasan dengan Prism, Coba Lagi, dan layout hasil.
BAB 3 juga memeriksa jawaban kosong, skor 0/88/100%, pembahasan, Coba Lagi,
refresh, dan layout form/hasil pada kelima lebar tersebut.

Jalankan perintah di atas pada environment yang menyediakan dependensi tes;
integrasi browser memerlukan akses ke CDN Monaco dan Pyodide.

Verifikasi BAB 1–3: `php artisan test` lulus (23 tes, 370 assertions),
6 tes Node runtime manager lulus, dan ketiga skrip browser `material.mjs`,
`quiz.mjs`, serta `live-code.mjs` lulus di Edge headless dengan CDN asli.
Pemeriksaan sintaks JavaScript materi/kuis dan skrip browser, lint PHP BAB 3,
Pint `--test --dirty` serta tes Pint eksplisit pada file baru, dan
`git diff --check` juga lulus. Server browser memakai
session/cache `array` karena MySQL lokal tidak aktif; konfigurasi `.env` tetap.
