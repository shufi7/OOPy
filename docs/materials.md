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
- `resources/materi/dasar-pemrograman-oop.php`: modul BAB 1 terbaru, lima tujuan,
  apersepsi dan bagian 1.1–1.7, tabel konsep, contoh/output, latihan status air,
  Ayo Berlatih, empat rangkuman, tiga refleksi dan kuis 3 PG + 2 code-fill.
- `resources/materi/kelas-dan-objek.php`: tujuh bagian BAB 2, termasuk apersepsi,
  bedah kode, latihan Spesies/SensorAir, rangkuman, refleksi, dan kuis 3 PG + 2 code-fill.
- `resources/materi/enkapsulasi.php`: lima tujuan, sembilan bagian BAB 3,
  latihan property SensorAir, tujuh poin rangkuman, tiga refleksi, dan kuis 3 PG + 2 code-fill.
- `app/Http/Controllers/MateriController.php`: `index()` untuk daftar dan `show()`
  untuk detail yang terdaftar, tanpa query database.
- `resources/views/materi/show.blade.php`: breadcrumb, header, konten BAB,
  kuis dan satu navigasi dinamis antar-BAB beserta tautan kembali ke daftar materi.
- `resources/views/materi/partials/quiz.blade.php`: struktur aktivitas kuis dan hasil.
- `public/js/oopy-quiz.js`: pilihan jawaban, navigasi, hasil agregat, kelulusan,
  progres browser dan Coba Lagi.
- `resources/views/materi/partials/navigation.blade.php`: daftar isi dan progres.
- `resources/views/materi/partials/section.blade.php`: paragraf, contoh kode, catatan
  serta lokasi opsional komponen Live Coding pada setiap bagian materi.
- `public/css/oopy/material/index.css`: card pada halaman daftar materi.
- `public/css/oopy/material/material.css`: struktur artikel, bagian materi dan navigasi antar-BAB.
- `public/css/oopy/material/navigation.css`: sidebar, daftar isi dan progres BAB.
- `public/css/oopy/material/code.css`: contoh kode Python dan tema token Prism.
- `public/css/oopy/material/quiz.css`: form dan kartu hasil agregat kuis.
- `public/js/oopy-material.js`: menu mobile, penanda bagian aktif, auto-open grup
  aktif dan fokus anchor.

Layout aplikasi memuat `public/css/oopy/base.css`, `layout.css` dan `navbar.css`,
diikuti `@stack('styles')`. Beranda menambahkan `home.css` melalui `@push`.
Daftar materi hanya menambahkan `material/index.css`; detail BAB menambahkan
`material.css`, `navigation.css`, `code.css` dan `quiz.css` melalui `@push`.
Gaya artikel dan contoh kode tetap dibatasi ke `.oopy-material`, sedangkan kuis
dibatasi ke `.oopy-quiz`. Komponen Live Coding memuat
`public/css/oopy/live-code/live-code.css` melalui `@pushOnce`, termasuk ketika
beberapa latihan tampil dalam satu halaman. File CSS dipanggil langsung melalui
link Blade tanpa `@import`; nilai variabel tema tetap berada di `base.css`.

BAB 1 berjudul **Dasar Pemrograman Python dan OOP**, dengan deskripsi
“Fondasi singkat yang dibutuhkan sebelum memasuki pemodelan object.” Slug tetap
`dasar-pemrograman-oop`. Anchor-nya: `tujuan`, `apersepsi`,
`nilai-tipe-data-variabel`, `operator-ekspresi`, `input-output`, `percabangan`,
`perulangan-list`, `fungsi`, `prosedural-ke-oop`, `rangkuman`, `refleksi`, `kuis`.
Live Coding berada di akhir 1.7, lalu tiga aktivitas Ayo Berlatih berupa latihan
mandiri (list/for, klasifikasi suhu, rata-rata), tanpa editor tambahan.
Ilustrasi transisi prosedural ke OOP dijelaskan melalui teks karena asset modul
belum tersedia. Batas tinggi air/pH adalah angka latihan, bukan standar ilmiah.
BAB 1–3 memiliki Refleksi sesudah Rangkuman dan sebelum Kuis BAB. Sidebar memakai
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

## Sidebar navigasi BAB

Sidebar memakai hierarki **BAB → kelompok → submateri**, dengan label BAB kecil,
judul 18px, label kelompok 15px, dan submateri 14px. Urutannya adalah Tujuan
Pembelajaran, Pendahuluan (Apersepsi), Materi BAB, Penutup (Rangkuman/Refleksi),
lalu Kuis BAB. Semua anchor tetap tersedia tepat sekali; judul dan urutan artikel
tidak berubah. Progres tetap memakai bar existing dengan jarak yang lebih compact.

Materi dan Penutup memakai native `<details>/<summary>` dan tertutup pada awal
halaman agar sidebar ringkas. Kelompok kosong tidak ditampilkan. Summary mendukung
Enter/Space, chevron berotasi, dan focus-visible tersedia untuk summary/link.
Animasi chevron dinonaktifkan saat `prefers-reduced-motion: reduce`.
Semua grup tetap dapat dibuka dan seluruh link dipakai tanpa JavaScript.

Metadata section bersifat opsional:

```php
'nav_title' => '1.6 Fungsi & Parameter', // Sidebar saja; title artikel tetap lengkap.
'nav_group' => 'materi', // Default; pilihan lainnya: pendahuluan atau penutup.
```

BAB 1 memakai label singkat Apersepsi, 1.1 Nilai/Tipe Data/Variabel, 1.2 Operator,
1.3 Input/Output, 1.5 Perulangan/List, 1.6 Fungsi/Parameter, dan 1.7 Prosedural → OOP.
BAB 2/3 juga memendekkan beberapa label panjang. Tanpa `nav_title`, sidebar
menggunakan `title`. Metadata intro menempatkan Apersepsi di Pendahuluan; sections
lainnya masuk Materi secara default. Rangkuman/Refleksi ditambahkan ke Penutup
jika tersedia. Tidak ada mapping slug BAB dalam view; gunakan kelompok mengikuti
alur Pendahuluan → Materi → Penutup saat menambah BAB.

Link aktif tetap memakai `aria-current="location"`, latar `--oopy-selected`, dan
garis kiri primary. Bullet pada semua item telah dihapus; garis submenu menunjukkan
indentasi. JavaScript membuka parent grup ketika section aktif berubah lewat
scroll, klik anchor, atau direct link seperti `#percabangan`/`#rangkuman`. Grup
yang memuat link aktif tetap terbuka agar posisi baca tidak tersembunyi.

Desktop mempertahankan sidebar sticky pada 24px dan lebar layout existing.
Panel hanya bergulir vertikal bila melebihi viewport, dengan scrollbar tipis.
Di bawah 992px, menu luar tertutup pada awal halaman dan menampilkan **Daftar Isi
BAB**; ketika dibuka, struktur kelompoknya sama. Membuka grup aktif tidak membuka
menu mobile secara otomatis. Indentasi mobile dikurangi dan link minimal 44px
agar nyaman disentuh. Quiz, Live Coding, progres dan navigasi bawah tidak berubah.

## Menggunakan template untuk BAB berikutnya

1. Siapkan file data BAB mengikuti struktur BAB 1/BAB 2: `description`,
   `objectives`, `sections`, `summary`, `reflection`, dan `quiz`. Deskripsi dan
   array opsional dapat dihilangkan; rangkuman/refleksi/kuis kosong tidak dirender
   dan tidak mendapatkan tautan sidebar.
   Jika memiliki kuis, siapkan tepat 5 soal; engine tidak memulai kuis dengan
   jumlah soal yang berbeda. Ikuti pola 3 pilihan ganda dan 2 code-fill.
2. Pada entri BAB di `chapters.php`, tambahkan `content` berisi nama file data.
   Card otomatis menampilkan tautan detail; navigasi sebelumnya/berikutnya
   memakai registry melalui `$previousChapter` dan `$nextChapter`.
3. Isi setiap section dengan `id` unik dan `title`. `paragraphs`, `code`, `tip`,
   `live_codes`, `tables`, `output`, dan `practice` bersifat opsional.
   Tabel memiliki `caption`, `headers`, dan `rows`; cell berupa string atau
   `['code' => '...']`. Tabel dapat digulir secara horizontal dengan keyboard.
   `output` menggunakan blok kode yang sama tanpa highlighting Python;
   `practice` adalah daftar latihan mandiri yang tampil setelah Live Coding.
   Gunakan `nav_title` untuk label sidebar singkat dan `nav_group` untuk kelompok
   pendahuluan/materi/penutup; keduanya tidak mengubah konten atau anchor artikel.
   ID harus valid untuk anchor dan tidak sama
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

BAB 1 memakai satu komponen **Coba sendiri: Status Air** dengan ID
`bab1-status-air`. Starter menyediakan `def status_air(tinggi)`, komentar TODO,
`pass`, dan `print(status_air(120))`. Area Tugasmu meminta mahasiswa mencoba
80/120/170 serta mengamati dampak urutan if/elif.
Checker `results` memberikan delapan feedback terpisah: fungsi tersedia, lalu
hasil untuk 80/120/170/99/100/149/150. Starter belum lulus: hanya ketersediaan
fungsi yang benar (13% setelah Submit). Solusi yang benar mendapat 100%; urutan
kondisi salah atau batas eksklusif tidak lulus. Implementasi berbeda dengan
behavior sama tetap diterima. Reset memulihkan starter dan progres latihan 0%.
Latihan variabel/nama ekosistem lama telah diganti tanpa perubahan engine.

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

BAB 1 memiliki lima soal sesuai modul: tipe float, argument, gagasan OOP,
serta melengkapi kode dengan `return` dan `elif`.
Semua BAB memiliki tepat **5 soal: 3 pilihan ganda + 2 code-fill**.
BAB 2 mempertahankan soal class/object, `__init__`, dan `self`, lalu menguji
instance attribute melalui assignment `self.nama = nama` serta pembuatan object
dengan pemanggilan class `Ekosistem`. Signature `nama, lokasi` mengikuti materi.
BAB 3 mempertahankan soal tujuan enkapsulasi, konvensi `_`, dan name mangling `__`,
lalu menguji decorator `@property` dan `@tinggi_air.setter` yang telah diajarkan.
Pemilihan ini mewakili lima konsep utama tiap BAB, bukan mengambil lima soal
pertama. Hanya bagian `quiz` BAB 2/3 yang berubah; materi utama dan Live Coding tetap.
Data berasal dari array `quiz` di file materi masing-masing BAB dan
diserialisasi sebagai JSON oleh Blade. Schema data tetap mempertahankan `correct`,
`answer`, dan `explanation`; `explanation` disaring sebelum dikirim ke halaman.
Untuk mengganti soal, pertahankan total lima elemen. Contoh pilihan ganda:

```php
[
    'type' => 'multiple_choice', // Engine tetap menerima data lama tanpa type.
    'question' => 'Apa tipe data nilai ini?',
    'code' => 'jumlah_habitat = 2', // Opsional jika soal tidak memerlukan kode.
    'options' => ['str', 'int', 'float', 'bool'],
    'correct' => 1, // Indeks dimulai dari 0, sehingga 1 adalah pilihan B.
    'explanation' => '2 adalah bilangan bulat, sehingga bertipe int.',
],
```

Gunakan empat pilihan dan satu indeks jawaban benar yang valid. Counter dan total
skor mengikuti lima soal. JavaScript membuat pilihan radio dari data, menyimpan
jawaban selama halaman terbuka, serta mengembalikan pilihan saat berpindah soal.

Untuk soal melengkapi kode, gunakan data berikut tanpa `options`/`correct`:

```php
[
    'type' => 'code_fill',
    'question' => 'Lengkapi kode agar fungsi mengembalikan teks Normal.',
    'code' => "def status_air():\n    __________ \"Normal\"",
    'answer' => 'return',
    'explanation' => 'return mengembalikan hasil kepada pemanggil.',
],
```

Engine memakai card/counter/navigasi/hasil yang sama, dengan satu input teks
berlabel menggantikan radio pada soal code-fill. Jawaban dinormalisasi dengan
`trim()` dan dibandingkan secara case sensitive; `Return` tidak sama dengan
`return`. Input kosong atau hanya spasi belum dihitung sebagai jawaban.
Jawaban teks tetap tersimpan saat berpindah soal dan dihapus saat Coba Lagi atau
refresh. Hasil tidak menampilkan jawaban pengguna, kunci, atau pembahasan soal.
BAB 2/3 memakai engine campuran yang sama. Code-fill juga dapat berupa assignment
atau decorator, bukan hanya satu kata. Isi setelah trim harus sama dengan `answer`;
soal assignment BAB 2 meminta satu spasi di kedua sisi tanda `=` agar format jelas.

Pengguna boleh melewati soal dan mengubah jawaban sebelum menyelesaikan kuis.
Jika ada jawaban kosong saat Selesai Kuis ditekan, pengguna diarahkan ke soal kosong
pertama dengan pesan **Masih ada soal yang belum dijawab.** Hasil belum dihitung,
ditampilkan, atau disimpan sebagai kelulusan.

Nilai integer dihitung dengan `Math.round(jawabanBenar / jumlahSoal * 100)`.
Kartu **Kuis Selesai** hanya menampilkan **Nilai**, **Benar**, **Salah**, dan
**Status** beserta pesan dan tombol latihan/navigasi. Tidak ada verdict per soal,
warna berdasarkan benar/salah, jawaban pengguna, kunci, atau pembahasan, baik saat
memilih jawaban maupun setelah submit. DOM `.oopy-quiz-review` dan seluruh renderer
review lama telah dihapus. Hasil memakai `role="status"`, `aria-live="polite"`,
dan heading yang menerima fokus sesudah submit.

Aturan kelulusan diatur melalui `TOTAL_QUESTIONS = 5` dan
`MIN_CORRECT_TO_PASS = 4` pada `public/js/oopy-quiz.js`.
**0–3 benar = Belum Lulus; 4–5 benar = Lulus**, berdasarkan jumlah benar,
tanpa passing grade persentase. Nilai berturut-turut adalah 0, 20, 40, 60, 80, 100.
Sebelum lulus, BAB berkuis yang memiliki
`$nextChapter` menampilkan teks terkunci beserta alasan; link Next disembunyikan
sejak HTML awal. Sesudah lulus, link Next di navigasi bawah langsung terbuka tanpa
reload dan CTA dengan URL yang sama muncul di kartu hasil. Previous dan kembali
ke daftar materi tetap tersedia. BAB tanpa kuis tetap memiliki link Next biasa.
BAB terakhir tidak membuat CTA/URL kosong dan menampilkan **Evaluasi selesai.**
ketika lulus, tanpa menyebut BAB berikutnya.

Controller mengirim `$chapter['slug']`; Blade meneruskannya melalui
`data-chapter-slug`, sehingga engine tidak menebak URL atau hardcode identitas BAB.
Progres menggunakan satu key localStorage **`oopy.quiz.progress`** dengan object
yang memetakan slug BAB ke hasil terbaik:

```json
{
  "dasar-pemrograman-oop": {"passed": true, "bestCorrect": 4, "bestScore": 80},
  "kelas-dan-objek": {"passed": false, "bestCorrect": 3, "bestScore": 60}
}
```

Hanya `passed`, `bestCorrect`, dan `bestScore` disimpan; tidak ada response,
kunci, atau explanation. Setiap percobaan lengkap, termasuk yang belum lulus,
memperbarui hasil terbaik menggunakan maksimum jumlah benar. Contoh 3 → 4 → 2
menyimpan hasil terbaik 4/80 dan `passed: true`; nilai terbaik tidak turun.
`passed` hanya menjadi true ketika hasil terbaik minimal 4 dari 5.
Saat refresh, hasil terbaik dibaca untuk mempertahankan Next yang sudah
terbuka, sementara jawaban dan hasil percobaan kembali kosong. **Coba Lagi**
menghapus semua jawaban, validasi dan angka/status hasil serta kembali ke soal
pertama, tetapi tidak menghapus kelulusan sebelumnya. Percobaan berikutnya yang
gagal juga tidak mencabut progres. Browser baru/storage kosong kembali terkunci.
Data storage yang rusak diabaikan; jika storage tidak tersedia, kelulusan tetap
membuka Next pada halaman saat ini, tetapi tidak bertahan setelah refresh.
Entry lama `oopy.quiz.passed.<slug>` dari aturan minimal 1 benar tidak dibaca
atau dihapus. Entry tersebut tidak membuktikan kelulusan aturan baru 4/5.
Record baru dengan `passed: true` tetapi `bestCorrect` di bawah 4 juga tidak
membuka Next. Jumlah benar dari storage harus integer dalam rentang 0–5.

## Batasan tahap ini

- Progres BAB ditampilkan sebagai judul dan bar tanpa kartu atau teks keterangan.
  Bar tetap kosong. Tidak
  mengikuti skor Submit, tidak disimpan ke browser maupun server.
- Latihan status_air BAB 1, Spesies dan SensorAir BAB 2, serta enkapsulasi BAB 3 memakai komponen
  Monaco/Pyodide yang sama dari CDN. Materi teks dan contoh
  `<pre><code>` tetap dapat dibaca ketika editor belum siap.
- `input()` dijelaskan dengan contoh untuk terminal lokal; editor browser belum
  mendukung input interaktif.
- Gating kuis merupakan **client-side learning flow**, bukan security/access-control.
  Pengguna masih dapat membuka URL BAB langsung atau mengubah localStorage.
  Kunci untuk perhitungan frontend tetap tersedia di JSON browser, meski tidak
  ditampilkan pada UI; explanation tetap di data PHP dan tidak dikirim.
  Tidak ada backend progres atau penyimpanan nilai. Setelah login/dashboard dan
  progres backend tersedia, gating dapat dipindahkan ke server. Status kuis tidak
  mengubah bar progres materi maupun hasil Live Coding.
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
Sidebar juga diperiksa untuk kelompok Materi/Penutup, state awal tertutup,
metadata `nav_title` dan fallback, judul artikel yang tetap lengkap, serta semua
anchor yang tetap unik dan berurutan. Tes browser memeriksa collapse/expand dengan
Enter/Space, focus-visible, auto-open parent aktif, `aria-current`, direct link
`#percabangan`/`#rangkuman`, serta native navigasi tanpa JavaScript. Sidebar mobile
yang dibuka dan seluruh submenu turut diperiksa tanpa horizontal overflow pada
320/390/768/1024/1440px.
`LiveCodeTest` memeriksa starter BAB 1/2/3, heading kontekstual, aset sekali,
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
node tests/browser/visual.mjs
```

Atur `OOPY_BROWSER=chrome` jika menggunakan Chrome dan `OOPY_BASE_URL` jika alamat
server berbeda. Tes memeriksa alur Beranda → Materi → BAB 1, navigasi/fokus pada
390/768/1024/1440px, Run/Submit/Reset, progres BAB terpisah dari skor latihan,
deep link mobile, dan navigasi tanpa JavaScript. Untuk BAB 2, tes juga memeriksa
320px, Refleksi/fokus, Prism, navigasi dua arah, reduced motion, satu worker/loader,
dan Submit starter/salah/benar serta Reset pada kedua latihan.
BAB 1 turut memeriksa tabel/output/latihan/refleksi, anchor yang sesuai dengan
sidebar pada 320/390/768/1024/1440px, starter 13%, urutan/batas salah 75%,
solusi biasa dan implementasi alternatif 100%, serta Reset ke starter dan 0%.
BAB 3 turut diuji pada 320/390/768/1024/1440px: kesesuaian sidebar/section,
instruksi, editor dalam viewport, navigasi, ID unik, heading, focus/reduced motion,
pembacaan tanpa JavaScript, satu worker/loader, dan tanpa error JavaScript.
Submit menguji starter yang belum lengkap, setter yang menerima negatif, setter
yang melempar error setelah merusak data, solusi benar 100%, serta Reset ke starter
dan 0%.
`OOPY_SCREENSHOT_DIR` opsional
menyimpan screenshot desktop dan mobile ke direktori yang sudah ada.

Tes kuis memeriksa BAB 1–3: hasil awal tersembunyi, Next terkunci, semua soal wajib
dijawab, 0–3 benar tetap terkunci, tepat 4 benar membuka Next tanpa reload, semua
benar bernilai 100, CTA memakai URL yang sama, hasil agregat tanpa review/kunci,
refresh setelah lulus, Coba Lagi serta kegagalan berikutnya tetap mempertahankan
progres, dan BAB terakhir tanpa Next. Browser baru, storage rusak dan kegagalan
penyimpanan turut diperiksa. Layout form/hasil diuji pada 320/390/768/1024/1440px.
Semua BAB memeriksa 3 PG + 2 code-fill, input kosong/spasi, trim, case sensitivity,
radio keyboard, retensi/pengubahan jawaban, Enter, nilai 0/20/40/60/80/100, dan sidebar
aktif. Tidak ada request penyimpanan ke server atau perubahan hasil Live Coding.
Hasil terbaik dan progres tiap BAB tetap tersedia setelah gagal, Coba Lagi,
refresh, atau berpindah BAB; storage hanya menyimpan tiga field yang diperlukan.
Progres lama dari aturan minimal 1 benar tidak membuka Next.
Tes materi menyelesaikan kuis dengan tepat 4 benar sebelum berpindah melalui Next,
serta memeriksa navigasi yang tetap terkunci tanpa JavaScript. Tes visual memeriksa
Prism pada soal dan hasil agregat yang sama ketika Prism tidak tersedia.

Jalankan perintah di atas pada environment yang menyediakan dependensi tes;
integrasi browser memerlukan akses ke CDN Monaco dan Pyodide.

Materi utama BAB 1–3, CSS global, route dan engine Live Coding tetap; controller hanya
menambahkan slug BAB untuk identitas progres. Konfigurasi `.env` tidak diubah.

Verifikasi alur kuis dan sidebar baru: `php artisan test` lulus (26 tes, 542 assertions),
6 tes Node runtime manager lulus, pemeriksaan sintaks JavaScript dan
`git diff --check` lulus, serta Pint `--test --dirty` lulus. Keempat skrip browser
`quiz.mjs`, `material.mjs`, `live-code.mjs`, dan `visual.mjs` lulus di Edge headless
dengan akses CDN Bootstrap, font, Monaco dan Pyodide. Akses CDN diperlukan untuk
memverifikasi gaya lengkap dan menjalankan tes integrasi editor.
