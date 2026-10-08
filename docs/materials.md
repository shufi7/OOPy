# Halaman materi OOPy

Daftar BAB tetap berada di `/materi`. BAB 1–6 tersedia melalui route `materi.show`
(`GET /materi/{slug}`): `dasar-pemrograman-oop`, `kelas-dan-objek`, `enkapsulasi`,
`pewarisan`, `polimorfisme`, dan `kelas-abstrak`. BAB 7 (`evaluasi-akhir`) tersedia
dengan layout evaluasi tersendiri. Katalog menampilkan tujuh tombol **Pelajari BAB**,
tanpa card Segera hadir.
Slug yang belum
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
- `resources/materi/pewarisan.php`: lima tujuan, delapan bagian BAB 4, termasuk
  superclass/subclass, super(), overriding, inheritance/composition, contoh
  StasiunPemantau, Live Coding dan Ayo Berlatih; lima rangkuman, tiga refleksi.
- `resources/materi/polimorfisme.php`: tujuan, enam bagian BAB 5, termasuk
  polimorfisme melalui inheritance, duck typing, pengurangan pemeriksaan tipe,
  Live Coding dan Ayo Berlatih; empat rangkuman, tiga refleksi.
- `resources/materi/kelas-abstrak.php`: lima tujuan, enam bagian BAB 6, contoh
  ABC/TypeError/method konkret/duck typing, hierarki sensor, dua tabel, Live Coding,
  tiga latihan mandiri, lima rangkuman, tiga refleksi dan kuis 3 PG + 2 isian kode.
- `resources/materi/evaluasi-akhir.php`: bank 20 soal (10 PG, 5 isian, 5 uraian),
  jawaban objektif dan pertanyaan uraian.
- `config/evaluasi.php`: durasi, ambang objektif, key dan versi storage.
- `app/Http/Controllers/EvaluasiAkhirController.php`: tiga halaman BAB 7;
  route spesifik didefinisikan sebelum route materi dinamis.
- `resources/views/evaluasi/`: layout OOPy bersama, aturan/riwayat, ujian, hasil
  dan hasil evaluasi.
- `public/js/evaluasi/`: model/penilaian, storage, ticker dan interaksi halaman.
- `public/css/oopy/evaluasi/evaluasi.css`: gaya khusus `.oopy-final-exam`.
- `app/Http/Controllers/MateriController.php`: `index()` untuk daftar dan `show()`
  untuk detail yang terdaftar, tanpa query database.
- `resources/views/materi/show.blade.php`: breadcrumb, header, konten BAB,
  kuis dan satu navigasi dinamis antar-BAB beserta tautan kembali ke daftar materi.
- `resources/views/materi/partials/quiz.blade.php`: struktur aktivitas kuis dan hasil.
- `public/js/oopy-quiz.js`: pilihan jawaban, navigasi, hasil agregat, kelulusan,
  progres browser dan Coba Lagi.
- `resources/views/materi/partials/navigation.blade.php`: daftar isi sidebar.
- `resources/views/materi/partials/section.blade.php`: paragraf, contoh kode, catatan
  serta lokasi opsional komponen Live Coding pada setiap bagian materi.
- `public/css/oopy/material/index.css`: card pada halaman daftar materi.
- `public/css/oopy/material/material.css`: struktur artikel, bagian materi dan navigasi antar-BAB.
- `public/css/oopy/material/navigation.css`: sidebar dan daftar isi BAB.
- `public/css/oopy/material/code.css`: contoh kode Python dan tema token Prism.
- `public/css/oopy/material/quiz.css`: form dan kartu hasil agregat kuis.
- `public/js/oopy-material.js`: menu mobile, penanda bagian aktif, pembukaan grup
  tujuan navigasi dan fokus anchor.

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
BAB 1–6 memiliki Refleksi sesudah Rangkuman dan sebelum Kuis BAB. Sidebar memakai
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

Navigasi otomatis mengikuti registry: BAB 1 → BAB 2 → BAB 3 → BAB 4 → BAB 5 → BAB 6 → BAB 7.
BAB 3 memiliki Previous ke BAB 2 dan Next ke BAB 4; BAB 4 memiliki Previous ke
BAB 3 dan Next ke BAB 5. Next terkunci sebelum kuis lulus (minimal 4/5 benar).
BAB 5 memiliki Previous ke BAB 4 dan Next/CTA ke BAB 6 yang terbuka setelah
kuis lulus. BAB 6 memiliki Previous ke BAB 5 dan Next ke Evaluasi Akhir setelah
minimal 4/5 benar (80%). BAB 7 memiliki Previous ke BAB 6 dan kembali ke daftar
materi, tanpa Next ke BAB lanjutan. Direct URL seluruh BAB tetap HTTP 200 tanpa
progres tersimpan; gate tersebut mengarahkan alur belajar pada browser.

## Sidebar navigasi BAB

Sidebar memakai hierarki **BAB → kelompok → submateri**, dengan label BAB kecil,
judul 18px, label kelompok 15px, dan submateri 14px. Urutannya adalah Tujuan
Pembelajaran, Pendahuluan (Apersepsi), Materi BAB, Penutup (Rangkuman/Refleksi),
lalu Kuis BAB. Semua anchor tetap tersedia tepat sekali; judul dan urutan artikel
tidak berubah. Sidebar berakhir pada tautan Kuis BAB dengan padding bawah yang
ringkas, tanpa judul atau bar progres BAB.

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
indentasi. JavaScript membuka parent grup saat klik anchor, direct link seperti
`#percabangan`/`#rangkuman`, perubahan hash, atau navigasi Back/Forward. Pengguna
tetap dapat menutup grup yang memuat link aktif; scroll dan resize memperbarui
penanda aktif tanpa mengubah pilihan collapse native `<details>`.

Desktop mempertahankan sidebar sticky pada 24px dan lebar layout existing.
Panel hanya bergulir vertikal bila melebihi viewport, dengan scrollbar tipis.
Di bawah 992px, menu luar tertutup pada awal halaman dan menampilkan **Daftar Isi
BAB**; ketika dibuka, struktur kelompoknya sama. Membuka grup aktif tidak membuka
menu mobile secara otomatis. Indentasi mobile dikurangi dan link minimal 44px
agar nyaman disentuh. Quiz, Live Coding, progres kuis dan navigasi bawah tidak berubah.

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
Section tetap mengikuti urutan data. BAB 1–6 memakai template yang sama.
Entry kelas-abstrak memuat kelas-abstrak.php; tidak ada route atau view BAB khusus.

BAB 6 menambahkan field opsional `breakdown` (list Bedah Kode), `hierarchy`
(caption, label, contract, children), `examples` (title, paragraphs, code, output),
`instructions` (langkah bernomor sebelum editor), dan `exploration` (catatan sesudah
editor). Partial section memakai gaya existing, HTML semantik dan escaping Blade.
Field yang tidak disediakan tidak dirender sehingga struktur BAB 1–5 tetap sama.

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

BAB 4 menggunakan `bab4-pewarisan-ekosistem` dengan `main.py`. Mahasiswa
melengkapi `Sungai` dan `Rawa`, mewarisi `Ekosistem`, menggunakan
`super().__init__()` untuk nama/lokasi, menyimpan `panjang_km`/`luas_ha`, dan
override `info()`. Delapan check memeriksa inheritance, inisialisasi kedua class,
pemanggilan super(), kedua implementasi info(), serta data instance terpisah.
Checker mengamati pemanggilan super yang benar-benar dijalankan pada object uji
dan nilai nama/lokasi sesudah inisialisasi superclass. Komentar, cabang mati,
atau super yang menginisialisasi nilai salah tidak cukup untuk lulus. Bentuk
super eksplisit dan penyimpanan referensi super juga diterima. `info()` diuji
dengan beberapa data; perubahan satu instance tidak boleh mengubah instance lain,
termasuk pada Rawa. Starter mendapat 25%; solusi benar mendapat 100%.

BAB 5 menggunakan `bab5-polimorfisme-sensor` dengan `main.py`. Targetnya empat
object `SensorPH`, `SensorSuhu`, `SensorTinggiAir`, dan `SensorKekeruhan` dalam
list `sensor`, dengan `status()` yang menghasilkan empat teks berbeda dan tidak
kosong. Tujuh check memeriksa empat class, perilaku status() pada object buatan
mahasiswa, isi list, dan pemrosesan semua object melalui loop. Constructor dengan
argument dan method yang diwarisi tetap diterima bila
kontrak perilaku terpenuhi. Checker membaca `main.py` dari workspace virtual
existing, memakai AST dan menjalankan ulang kode dalam namespace terpisah sambil
mengamati pemanggilan status(). Output replay tidak digandakan di terminal.
Loop mati/kosong atau yang hanya memproses satu object gagal; alias list,
enumerate, comprehension, serta pemanggilan melalui helper diterima.
Percabangan berdasarkan isinstance/type/nama class untuk memilih perilaku sensor
ditolak; pemakaian type() untuk debugging di luar percabangan tetap diperbolehkan.
Starter mendapat 29%; solusi benar mendapat 100%. Run/Submit/Reset, feedback,
persentase dan batas runtime memakai engine yang sama dengan BAB 1–3.

### BAB 6 — Kelas Abstrak (Abstract Class)

Deskripsi BAB: **Menyatakan kontrak perilaku minimum ketika desain memerlukannya.**
Dokumen OOPy_Modul_Ajar_FINAL(2).docx tidak tersedia dalam workspace; materi
mengikuti spesifikasi lengkap BAB 6 dalam permintaan implementasi.

Lima tujuan pembelajaran: membedakan class konkret dan ABC; memakai ABC dan
abstractmethod dari abc; membuat subclass konkret; menggabungkan ABC dengan
inheritance/polimorfisme; serta memahami bahwa ABC adalah pilihan desain,
bukan syarat semua polimorfisme. Enam section berurutan:

1. `apersepsi`: sensor pH, suhu dan tinggi air pada lahan basah Kalimantan Selatan,
   dikaitkan dengan inheritance BAB 4 dan polimorfisme BAB 5.
2. `membuat-abstract-base-class`: class konkret/abstrak, modul abc, decorator,
   contoh SensorLingkungan → SensorPH (output 7.1), Bedah Kode dan hierarki
   SensorPH/SensorSuhu sebagai representasi semantik Gambar 6.1. Contoh tambahan
   menangkap TypeError pada ABC/subclass yang belum lengkap dan menjelaskan
   bahwa error itu diharapkan.
3. `abstract-method-method-konkret`: sumber() diwarisi, baca() diimplementasikan
   SensorSuhu; output OOPy dan 29.5, beserta tabel perbandingan kedua jenis method.
4. `kapan-abc-digunakan`: manfaat/batasan kontrak eksplisit, tabel Class Biasa vs
   ABC, contoh duck typing tanpa ABC dengan output simulasi/pengamatan manual.
5. `ayo-coba-kelas-abstrak`: sembilan langkah, komponen Live Coding dan eksplorasi
   menghapus/pemulihan baca() setelah editor.
6. `ayo-berlatih-kelas-abstrak`: LaporanAir/LaporanHabitat, method konkret sumber(),
   dan pemilihan duck typing beserta alasan desain.

ABC memeriksa pemenuhan method abstrak, bukan otomatis membuktikan kebenaran
hasilnya. Abstract method boleh memiliki body. Mewarisi ABC tanpa abstract method
yang belum terpenuhi tidak otomatis melarang instansiasi. Angka sensor adalah
data simulasi latihan dan tidak menyatakan ambang ilmiah kondisi lingkungan.

Live Coding **Coba sendiri: Kontrak AlatPantau** memakai ID
`bab6-kelas-abstrak-alat-pantau`, satu main.py, Monaco/Pyodide dan engine existing.
Starter valid secara sintaks, bisa Run tanpa error, dan masih memiliki tiga pass.
Sembilan pemeriksaan menilai:

1. AlatPantau mewarisi ABC Python dan masih abstrak.
2. baca() benar-benar tercatat sebagai abstract method, bukan teks decorator saja.
3. Instansiasi ABC dan subclass yang belum lengkap ditolak dengan TypeError;
   exception yang diharapkan ditangani dalam checker.
4. SensorTinggiAir mewarisi AlatPantau.
5. SensorSuhu mewarisi AlatPantau.
6. Kedua subclass konkret, menyediakan baca(), dan object dapat dibuat.
7. Hasil baca() berupa angka terbatas (termasuk 0) atau teks sensor tidak kosong,
   serta berbeda. None, bool, NaN, infinity dan string kosong ditolak.
8. Satu object tiap subclass disimpan dalam satu list dengan nama variabel bebas.
9. Loop yang dijalankan benar-benar memanggil baca() pada kedua object.

Checker memakai introspeksi dan AST/replay dalam workspace virtual existing,
dengan output replay disembunyikan. Alias list, enumerate, comprehension,
helper, constructor berargument dan implementasi yang diwarisi dari subclass
konkret diterima. Tidak ada string hasil atau angka pengukuran tunggal yang
diwajibkan. Loop mati/kosong/yang hanya membaca satu object gagal. Starter mendapat
56%; solusi benar/alternatif mendapat 100%. Runtime dan jumlah worker tidak berubah.

## Kuis interaktif

BAB 1 memiliki lima soal sesuai modul: tipe float, argument, gagasan OOP,
serta melengkapi kode dengan `return` dan `elif`.
Semua BAB memiliki tepat **5 soal: 3 pilihan ganda + 2 code-fill**.
BAB 2 mempertahankan soal class/object, `__init__`, dan `self`, lalu menguji
instance attribute melalui assignment `self.nama = nama` serta pembuatan object
dengan pemanggilan class `Ekosistem`. Signature `nama, lokasi` mengikuti materi.
BAB 3 mempertahankan soal tujuan enkapsulasi, konvensi `_`, dan name mangling `__`,
lalu menguji decorator `@property` dan `@tinggi_air.setter` yang telah diajarkan.
BAB 4 menguji superclass, super(), relasi has-a, deklarasi `Ekosistem`, dan
`super().__init__(nama, lokasi)`. BAB 5 menguji polimorfisme, duck typing,
fleksibilitas pemanggil, overriding `status`, dan pemanggilan `info` melalui loop.
Seluruh BAB tersedia memakai engine kuis existing dan standar kelulusan yang sama.
BAB 6 memakai soal ABC untuk kontrak eksplisit (B), class abstrak tidak dapat
diinstansiasi secara normal (D), ABC tidak wajib untuk semua polimorfisme (B),
serta isian `@abstractmethod` dan `abc`. Explanation tetap tersedia di data PHP
dan disaring dari JSON UI sesuai engine existing. Lulus BAB 6 membuka Next ke
BAB 7; retry/refresh tetap mempertahankan hasil terbaik.
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

Engine memakai card/counter/navigasi/hasil yang sama. Pada soal code-fill,
input teks berada langsung pada posisi garis kosong di dalam potongan kode,
bukan pada field terpisah di bawahnya. Kotak memiliki label aksesibel dan
lebar menyesuaikan teks dengan batas responsif. Jawaban dinormalisasi dengan
`trim()` dan dibandingkan secara case sensitive; `Return` tidak sama dengan
`return`. Input kosong atau hanya spasi belum dihitung sebagai jawaban.
Jawaban teks tetap tersimpan saat berpindah soal dan dihapus saat Coba Lagi atau
refresh. Hasil tidak menampilkan jawaban pengguna, kunci, atau pembahasan soal.
Syntax highlighting memproses span kode sebelum dan sesudah kotak, sehingga
input beserta event penilaiannya tidak tergantikan oleh Prism. Potongan kode
pilihan ganda tetap menampilkan source lengkap tanpa kotak isian. Mekanisme ini
berlaku pada dua isian di setiap kuis BAB 1–6, termasuk decorator dan isian di
tengah ekspresi. Enter, validasi jawaban kosong, passing 80% dan progres terbaik
localStorage memakai perilaku existing.

Verifikasi isian inline BAB 1–6 pada 8 Oktober 2026: `php artisan test --compact`
lulus (33 tes, 1173 assertions), 19 tes Node lulus, serta `quiz.mjs`, `visual.mjs`
dan `material.mjs` lulus di Edge headless. Dua isian tiap BAB diuji pada
320/390/768/1024/1440px, termasuk posisi kotak, indentasi/source, keyboard/Enter,
retensi dan pengubahan jawaban, jawaban kosong/spasi, nilai 0–100, gate 80%,
retry/refresh/progres terbaik serta fallback Prism. Screenshot desktop/mobile
diperiksa. Sintaks JavaScript, Pint `--test --dirty` dan `git diff --check` lulus;
tidak ada page error JavaScript pada eksekusi browser akhir.
BAB 1–6 memakai engine campuran yang sama. Code-fill juga dapat berupa assignment
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
Template BAB berkuis tanpa `$nextChapter` tidak membuat CTA/URL kosong dan
menampilkan **Evaluasi selesai.** ketika lulus. Saat ini keenam BAB berkuis
memiliki Next; BAB 7 menggunakan evaluasi terpisah dan tidak memakai engine ini.

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

- Sidebar tidak menampilkan judul atau bar progres BAB. Progres kuis di browser
  dan progres latihan Live Coding tetap tersedia pada fitur masing-masing.
- Latihan status_air BAB 1, Spesies dan SensorAir BAB 2, serta enkapsulasi BAB 3 memakai komponen
  Monaco/Pyodide yang sama dari CDN, termasuk pewarisan BAB 4 dan polimorfisme BAB 5. Materi teks dan contoh
  `<pre><code>` tetap dapat dibaca ketika editor belum siap.
- `input()` dijelaskan dengan contoh untuk terminal lokal; editor browser belum
  mendukung input interaktif.
- Gating kuis merupakan **client-side learning flow**, bukan security/access-control.
  Pengguna masih dapat membuka URL BAB langsung atau mengubah localStorage.
  Kunci untuk perhitungan frontend tetap tersedia di JSON browser, meski tidak
  ditampilkan pada UI; explanation tetap di data PHP dan tidak dikirim.
  Tidak ada backend progres atau penyimpanan nilai. Setelah login/dashboard dan
  progres backend tersedia, gating dapat dipindahkan ke server. Status kuis tidak
  mengubah hasil Live Coding.
- BAB 1–6 memiliki detail dan BAB 7 memiliki evaluasi khusus. Belum ada BAB 8, autentikasi, database materi,
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

Tes `MateriTest` mencakup BAB 1–6, tautan valid, 404 slug tidak dikenal, satu navigasi akhir,
urutan section/sidebar, refleksi kosong/hilang/terisi, array opsional, ID unik,
keenam kuis, render Live Coding, serta regresi Beranda dan `/editor`.
Sidebar juga diperiksa untuk kelompok Materi/Penutup, state awal tertutup,
metadata `nav_title` dan fallback, judul artikel yang tetap lengkap, serta semua
anchor yang tetap unik dan berurutan. Tes browser memeriksa collapse/expand dengan
Enter/Space, focus-visible, collapse manual selama scroll/resize, `aria-current`,
pembukaan grup tujuan klik/hash/Back/Forward, direct link
`#percabangan`/`#rangkuman`, serta native navigasi tanpa JavaScript. Sidebar mobile
yang dibuka dan seluruh submenu turut diperiksa tanpa horizontal overflow pada
320/390/768/1024/1440px.
`LiveCodeTest` memeriksa starter BAB 1–6, heading kontekstual, aset sekali,
dan seluruh kontrak komponen sebelumnya.
`tests/browser/chapters.mjs` menyimpan kontrak eksplisit keenam BAB dan fixture
solusi BAB 4/5/6 yang digunakan bersama oleh test browser dan checker lokal.
`node --test tests/js/*.test.js` juga menjalankan checker Python dari file materi
terhadap starter, solusi salah dan alternatif benar. PHP dan Python perlu tersedia
di PATH (override: `OOPY_PHP`, `OOPY_PYTHON`). Harness memakai file temporer yang
dibersihkan setelah pengujian; kode Python fixture tidak dijalankan oleh aplikasi Laravel.

Untuk tes browser, gunakan Playwright dan browser Edge/Chrome yang terpasang
seperti pada [panduan tes Live Coding](live-coding.md#verifikasi):

```powershell
# Khusus server pengujian, tanpa membutuhkan MySQL atau mengubah .env:
$env:SESSION_DRIVER = 'array'
$env:CACHE_STORE = 'array'
php artisan serve --host=127.0.0.1 --port=8017 --no-reload
# Terminal terpisah:
node tests/browser/material.mjs
node tests/browser/material.mjs --sidebar-only
node tests/browser/quiz.mjs
node tests/browser/live-code.mjs
node tests/browser/visual.mjs
```

Atur `OOPY_BROWSER=chrome` jika menggunakan Chrome dan `OOPY_BASE_URL` jika alamat
server berbeda. Tes memeriksa alur Beranda → Materi → BAB 1, navigasi/fokus pada
390/768/1024/1440px, Run/Submit/Reset, sidebar tanpa bar progres BAB,
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

Tes kuis memeriksa BAB 1–6: hasil awal tersembunyi, Next terkunci, semua soal wajib
dijawab, 0–3 benar tetap terkunci, tepat 4 benar membuka Next tanpa reload, semua
benar bernilai 100, CTA memakai URL yang sama, hasil agregat tanpa review/kunci,
refresh setelah lulus, Coba Lagi serta kegagalan berikutnya tetap mempertahankan
progres, serta BAB 6 yang membuka evaluasi khusus. Browser baru, storage rusak dan kegagalan
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

Implementasi BAB 6 mempertahankan materi utama BAB 1–5, CSS, route, controller,
engine Monaco/Pyodide, aturan kuis 80%, localStorage, dan manual collapse sidebar.
Partial section hanya mendapat field opsional untuk materi BAB 6. Konfigurasi
`.env` dan database tidak diubah; tidak ada dependency baru, commit atau push.

Progres localStorage hanya berlaku pada browser dan origin yang sama, tidak
tersinkron antarperangkat atau akun. Menghapus storage menghapus kelulusan;
penyimpanan yang diblokir hanya mempertahankan kelulusan selama halaman terbuka.
Hasil latihan coding dan jawaban kuis tidak dipersistenkan.

## Riwayat verifikasi BAB 4/5 — 8 Oktober 2026 (sebelum BAB 6)

Tabel berikut mencatat eksekusi sebelum aktivasi BAB 6. Pada tahap itu BAB 5
merupakan BAB terakhir. Hasil implementasi BAB 6 dicatat sesudah riwayat ini.

| Perintah | Hasil eksekusi |
| --- | --- |
| `php artisan test` (diulang dengan `--compact` pada verifikasi akhir) | Lulus: 27 tes, 853 assertions. |
| `node --test tests/js/*.test.js` | Lulus: 8 tes (6 runtime manager, 2 suite checker dengan 29 skenario). |
| `node --check public/js/oopy-material.js` | Lulus. |
| `node --check public/js/oopy-quiz.js` | Lulus. |
| `node tests/browser/material.mjs` | Lulus: alur BAB 1 → 5, gate 4/5, Previous, BAB 5 tanpa Next, regresi latihan BAB 1–3, instruksi, ID/aset dan sidebar responsif. |
| `node tests/browser/material.mjs --sidebar-only` | Lulus: 5 BAB × 5 viewport, collapse manual, Enter/Space, scroll/resize, deep link, Back/Forward, anchor dan aria-current. |
| `node tests/browser/quiz.mjs` | Lulus: lima kuis, nilai 0/20/40/60/80/100, refresh/retry/skor lebih rendah, storage kosong/rusak/tidak tersedia pada semua BAB; BAB 5 tanpa CTA ke BAB 6. |
| `node tests/browser/live-code.mjs` | Lulus: regresi engine/demo dan BAB 4/5 melalui Monaco/Pyodide asli, starter/salah/benar/alternatif, output/feedback/progres/Reset, satu worker/loader. |
| `node tests/browser/visual.mjs` | Lulus: 8 halaman, BAB 1–5, Prism/fallback, kuis campuran, pembacaan tanpa JS dan layout 320/390/768/1024/1440px. |
| `php vendor/bin/pint --test --dirty` | Lulus setelah normalisasi line ending file polimorfisme. |
| `git diff --check` | Lulus. |

Browser menggunakan Edge headless dan server Laravel lokal `127.0.0.1:8017`.
Percobaan awal dalam sandbox gagal karena akses CDN ditolak
(`ERR_NETWORK_ACCESS_DENIED`), sehingga Bootstrap/Monaco/Pyodide tidak termuat.
Semua suite di atas kemudian dijalankan ulang dengan akses jaringan dan lulus;
kegagalan awal itu tidak disembunyikan dengan penghapusan assertion layout/runtime.
Satu assertion lama yang menganggap navigasi BAB 3 hanya mempunyai satu tombol
diubah menjadi pemeriksaan semua tombol ketika Previous dan Next sama-sama ada.
Tidak ditemukan page error JavaScript pada eksekusi browser akhir.

PHP CLI masih mengeluarkan peringatan `Module "openssl" is already loaded` dari
konfigurasi lingkungan. Peringatan tersebut tidak menggagalkan tes; konfigurasi
PHP di luar repository tidak diubah. Tidak ada tes wajib yang belum dijalankan
atau kegagalan fungsional yang masih terbuka pada pekerjaan ini.

## Riwayat verifikasi implementasi BAB 6 — 8 Oktober 2026 (sebelum BAB 7)

Tabel ini mencatat kondisi sebelum Evaluasi Akhir tersedia. BAB 6 masih menjadi
BAB terakhir pada eksekusi tersebut; hasil BAB 7 dicatat pada bagian berikutnya.

| Perintah | Hasil eksekusi |
| --- | --- |
| `php artisan test` (eksekusi memakai `--compact`) | Lulus: 29 tes, 1011 assertions. |
| `node --test tests/js/*.test.js` | Lulus: 10 tes, mencakup 55 skenario checker BAB 4/5/6 serta empat contoh Python BAB 6 dengan output yang sesuai. |
| `node tests/browser/material.mjs` | Lulus: alur BAB 1 → 6, BAB 5 → 6 terbuka setelah 4/5 benar, BAB 6 tanpa Next, Previous kembali ke BAB 1, sidebar/layout, regresi latihan BAB 1–3. |
| `node tests/browser/material.mjs --sidebar-only` | Lulus: 6 BAB × 5 viewport, collapse mouse/Enter/Space, scroll/resize, deep link, Back/Forward, anchor dan aria-current. |
| `node tests/browser/quiz.mjs` | Lulus: keenam kuis, 3 PG + 2 isian, nilai 0/20/40/60/80/100, jawaban kosong/spasi, retry, refresh, skor lebih rendah, storage kosong/rusak/tidak tersedia. |
| `node tests/browser/live-code.mjs` | Lulus: regresi engine dan BAB 4/5/6 melalui Monaco/Pyodide asli, Run/Submit/Reset, output, feedback, progres, solusi alternatif, satu worker/loader. Eksperimen TypeError BAB 6 diuji saat Run dan Submit sebelum memulihkan solusi. |
| `node tests/browser/visual.mjs` | Lulus: 9 halaman dan seluruh enam BAB; contoh Python utama/tambahan dengan Prism, fallback saat Prism diblokir, pembacaan tanpa JavaScript, kuis dan responsivitas. |
| `node --check public/js/oopy-material.js` | Lulus. |
| `node --check public/js/oopy-quiz.js` | Lulus. |
| `php vendor/bin/pint --test --dirty` | Lulus. |
| `git diff --check` | Lulus. |

Browser menggunakan Edge headless dengan akses CDN existing dan server Laravel
lokal pada 127.0.0.1:8017. Ukuran materi/kuis/sidebar/visual adalah
320, 390, 768, 1024 dan 1440px. Tidak ada page error JavaScript pada hasil akhir.
BAB 6 mempunyai 26 fixture checker: starter 56%, 17 solusi salah ditolak, serta
solusi benar dan tujuh alternatif memperoleh 100%. TypeError kontrak abstrak
ditangani aman oleh checker; TypeError eksplorasi program ditampilkan oleh
runtime existing tanpa menggagalkan suite.

Tidak ada pengujian wajib yang belum dijalankan atau kegagalan yang masih terbuka.
Peringatan konfigurasi PHP OpenSSL ganda tetap tidak menghambat pengujian.
DOCX acuan tidak tersedia, sehingga kesesuaian materi didasarkan pada spesifikasi
tertulis dalam permintaan. Kode/skor latihan belum dipersistenkan; input interaktif,
autentikasi, sinkronisasi progres server dan BAB lanjutan belum tersedia.
Tidak ada dependency baru, commit atau push pada implementasi ini.

## BAB 7 — Evaluasi Akhir

Tiga halaman menggunakan visual OOPy dan pola dua screenshot referensi:

| URL | Named route | Isi |
| --- | --- | --- |
| `/materi/evaluasi-akhir` | `evaluasi.index` | Aturan, konfirmasi Mulai, resume sesi dan riwayat. |
| `/materi/evaluasi-akhir/ujian` | `evaluasi.exam` | Timer, grid 20 nomor, satu soal, penanda tinjauan dan ringkasan pengumpulan. |
| `/materi/evaluasi-akhir/hasil` | `evaluasi.results` | Hasil objektif, uraian Belum dinilai, tinjauan jawaban, riwayat dan ulangi. Query `attempt` memilih hasil tersimpan tertentu. |

Dokumen OOPy_Modul_Ajar_FINAL(2).docx tidak tersedia dalam workspace. Bank soal,
pertanyaan uraian mengikuti teks lengkap dalam permintaan.
Screenshot dipakai sebagai inspirasi susunan aturan/riwayat serta sidebar/soal;
tidak ada logo, palet, pertanyaan atau footer StegoLearn yang disalin.

### Konfigurasi

Ubah `config/evaluasi.php` untuk aturan latihan. Durasi 40 menit dan nilai 70
merupakan konfigurasi referensi UI, bukan ketentuan asli modul. Evaluasi dapat
langsung diulang setelah selesai, termasuk setelah nilai di bawah ambang atau waktu habis.
Jumlah/komposisi soal diturunkan dari bank di `resources/materi/evaluasi-akhir.php`.

| Key | Default | Makna |
| --- | ---: | --- |
| `duration_seconds` | 2400 | Durasi setelah pengguna mengonfirmasi Mulai. |
| `pass_threshold` | 70 | Ambang nilai bagian objektif, bukan kelulusan keseluruhan. |
| `storage_key` | `oopy.finalExam.v1` | Namespace terpisah dari `oopy.quiz.progress`. |
| `schema_version` / `content_version` | 1 / 1 | Kontrak data dan versi bank soal; perubahan versi memerlukan migrasi/penanganan sesi lama. |

Sesi menyimpan snapshot durasi/ambang sehingga perubahan konfigurasi
tidak diam-diam mengubah aturan sesi yang sudah dimulai. Jika konfigurasi Laravel
di-cache, perbarui cache konfigurasi melalui prosedur deployment yang digunakan.

### Pengerjaan, timer dan navigasi

Tidak ada sesi sebelum pengguna mengonfirmasi dialog Mulai. Sesi menyimpan
startedAt/deadline dan timer selalu menghitung selisih timestamp. Refresh,
berpindah halaman dan melanjutkan pengerjaan tidak memulai ulang 40 menit.
Satu ticker per dokumen dihentikan saat pagehide dan dipulihkan saat kembali
melalui bfcache. Peringatan tampil pada lima menit dan satu menit terakhir.
Saat deadline tercapai, sesi diselesaikan sekali, termasuk ketika halaman aturan
sedang terbuka. Waktu selesai otomatis dibatasi pada deadline. Setelah selesai,
tombol Ulangi Evaluasi langsung aktif tanpa countdown jeda.

Grid lima kolom memiliki 20 tombol. Jawaban/soal aktif/penanda disimpan setiap
perubahan. Label aria-current, teks aria-label dan simbol membedakan terjawab,
belum dijawab, aktif dan ditandai, tanpa memberi indikator jawaban salah selama
sesi aktif. Desktop mulai 992px memakai sidebar 280px dan card soal. Tablet/mobile
memakai satu kolom, timer ringkas dan native details "Navigasi Soal".

Selesai Evaluasi membuka ringkasan jumlah terjawab/belum dijawab dan sisa waktu.
Pengguna bisa kembali mengerjakan atau mengumpulkan dengan konfirmasi. Pengumpulan
dengan jawaban kosong diperbolehkan setelah ringkasan; objektif kosong dihitung
tidak benar, sedangkan uraian tetap tidak diberi nilai otomatis.

### Penilaian dan hasil

Bank berisi 10 PG, 5 isian kode dan 5 uraian. Kunci PG (indeks 0-based):
2, 3, 0, 2, 0, 2, 3, 0, 1, 3. Kunci isian: self.nilai = nilai,
return self._ph, super().__init__(nama, lokasi), baca_data, @abstractmethod.
Isian dibandingkan sebagai token terbatas: case Python dan batas token dipertahankan;
spasi luar/jarak antartoken yang setara diterima. Substring, tambahan statement,
komentar atau newline di tengah jawaban tidak diterima. Tidak ada eval atau
eksekusi isian kode.

Nilai objektif = benar / 15 × 100. Status memakai nilai sebelum pembulatan;
tampilan maksimal dua angka desimal. Contoh 10/15 = 66,67 di bawah 70, sedangkan
11/15 = 73,33 memenuhi ambang. Uraian hanya dihitung keterisiannya, ditampilkan
untuk tinjauan dengan label Belum dinilai, dan tidak dinilai berdasarkan panjang
teks. Tidak ada status Lulus Evaluasi Akhir karena uraian belum dinilai.
Peninjauan jawaban benar/salah hanya tersedia setelah sesi selesai.

Riwayat berasal dari sesi nyata, terbaru di atas, dan tidak disemai dengan contoh.
ID sesi menjaga idempotensi Submit/refresh. Hasil kedaluwarsa ditandai Waktu habis
beserta status objektif tersendiri. Durasi, jumlah benar/tidak benar dan uraian
ditampilkan terpisah. Kunci di data frontend diperlukan untuk penilaian browser
dan dapat diperiksa melalui developer tools, meskipun tidak ditampilkan sebagai
feedback selama ujian berlangsung.

### Penyimpanan dan batasan

Schema v1 berisi `version`, `active`, dan `history`. Attempt menyimpan id,
contentVersion, startedAt, deadline, policy, status, current, answers dan review;
record selesai menambahkan finishedAt, timedOut dan result. Pembacaan memvalidasi
ID, timestamp/deadline, jawaban, penanda, status serta versi; hasil tersimpan
dihitung ulang dari jawaban. Record rusak diabaikan dengan pesan dan record valid
dipertahankan. Write menggabungkan riwayat menurut ID agar Submit berulang atau
record yang sudah ada tidak terduplikasi.

Storage read/write menggunakan try/catch. Jika sesi baru tidak bisa disimpan,
timer tidak dilanjutkan ke halaman ujian dan pengguna mendapat pesan. Jika write
gagal saat sesi aktif, jawaban dipertahankan dalam memori halaman dengan peringatan
agar tidak refresh/pindah. Jika pengumpulan gagal disimpan, hasil sementara tetap
terlihat dan tombol Coba Simpan Hasil Lagi dapat memulihkan hasil setelah storage
tersedia. Kuis BAB 1–6 tidak membaca/menulis namespace evaluasi.

Penyimpanan hanya berlaku pada browser/origin yang sama, tanpa akun, backend,
sinkronisasi perangkat, atau jaminan akses bersamaan lintas-tab. Menghapus storage
menghapus progres/riwayat. Timer dan kunci di sisi browser dapat dimanipulasi;
fitur ini merupakan latihan mandiri dan bukan pengamanan atau sertifikasi ujian resmi.

### Isian kode langsung di dalam potongan kode

Lima isian kode mempunyai satu textbox kosong pada posisi garis bawah di dalam
pre/code. Teks sebelum dan sesudah kotak mempertahankan indentasi serta syntax
highlighting; Prism hanya memproses dua span teks sehingga tidak menghapus input
atau event penyimpanannya. Jawaban disimpan sebagai string yang sama seperti versi
sebelumnya, dengan aturan normalisasi dan penilaian objektif tetap.

Kotak pada potongan print(item.[kotak]()) dibuat lebih ringkas daripada kotak
pernyataan lengkap. Input berlabel aksesibel, mendukung keyboard/focus ring,
mempertahankan jawaban saat pindah soal/refresh, dan diuji pada lima viewport.

Mini project evaluasi akhir dihapus: tidak ada route, halaman, data/rubrik,
editor atau tombol mini project pada katalog, aturan dan hasil. URL lama
/materi/evaluasi-akhir/mini-project menghasilkan 404. Live Coding BAB 1–6 tetap.

Riwayat dan sesi aktif v1 tetap terbaca. Field cooldownSeconds pada policy lama
diabaikan/dihapus ketika data dibaca; jawaban, deadline dan hasil tetap dipertahankan.

### Menjalankan tes Evaluasi Akhir

Gunakan server/dependensi browser yang telah dijelaskan di atas, kemudian:

```powershell
php artisan test
node --test tests/js/*.test.js
node tests/browser/evaluasi.mjs
node tests/browser/material.mjs
node tests/browser/quiz.mjs
node tests/browser/visual.mjs
Get-ChildItem public/js/evaluasi/*.js | ForEach-Object { node --check $_.FullName }
php vendor/bin/pint --test --dirty
git diff --check
```

Test baru mencakup bank soal dan route, nilai/ambang tanpa pembulatan, normalisasi
token, deadline, migrasi policy lama, schema rusak, write failure, interval tunggal/binding
browser, UI keyboard/grid/flag, refresh/resume, Submit ganda, expiry, review tanpa
HTML injection, pemulihan penyimpanan, kotak isian inline dan layout lima viewport.

### Riwayat verifikasi awal BAB 7 — 8 Oktober 2026 (sebelum penghapusan jeda/mini project)

Bagian ini mencatat implementasi awal. Jeda ulang dan mini project kemudian
dihapus sesuai permintaan; verifikasi perubahan tersebut dicatat sesudah riwayat ini.

| Perintah | Hasil yang dijalankan |
| --- | --- |
| `php artisan test` | Lulus: 33 tes, 1152 assertions, termasuk regresi BAB 1–6. |
| `node --test tests/js/*.test.js` | Lulus: 19 tes (9 logika evaluasi, 10 regresi runtime/checker/contoh materi). |
| `node tests/browser/evaluasi.mjs` | Lulus: konfirmasi/timer, 20 nomor, PG/isian/uraian, flag, refresh/resume, hasil 66,67/73,33, cooldown, expiry saat ujian/aturan, Submit ganda, review aman, storage rusak/ditolak, retry write dan mini project asli. |
| `node tests/browser/material.mjs` | Lulus: BAB 1–6 dan gate BAB 6 → BAB 7, kembali melalui Previous, sidebar dan latihan existing. |
| `node tests/browser/quiz.mjs` | Lulus: keenam kuis lima soal, passing 80%, retry/refresh/storage dan Next BAB 6 menuju evaluasi. |
| `node tests/browser/visual.mjs` | Lulus: 13 halaman (9 existing + 4 evaluasi), Prism/fallback/no-JS dan responsivitas. |
| `node --check public/js/evaluasi/*.js` (tiap file), `oopy-material.js`, `oopy-quiz.js` | Lulus. |
| `php vendor/bin/pint --test --dirty` serta pemeriksaan empat file PHP baru | Lulus. |
| `git diff --check` | Lulus. |

Browser menggunakan Edge headless dan CDN existing pada server lokal
127.0.0.1:8017. Viewport: 320, 390, 768, 1024 dan 1440px; input isian kode dan
textarea uraian turut diuji pada kelima ukuran tersebut. Screenshot aturan dan
ujian desktop/mobile diperiksa. Verifikasi tambahan mini project memastikan
Run/Reset tampil, sedangkan Submit/progres otomatis disembunyikan untuk rubrik
manual. Tidak ada page error JavaScript pada eksekusi akhir.

Percobaan awal browser menemukan kesalahan binding fungsi timer Chromium;
fungsi global kini dipanggil dengan receiver yang benar dan mempunyai test regresi.
Test navigasi kembali juga diperbarui untuk layout BAB 7 yang terpisah dari
material-navigation. Pengujian terkait diulang dan lulus, tanpa menghapus
assertion penting. Tidak ada kegagalan yang masih terbuka atau perintah wajib
yang belum dijalankan. Peringatan konfigurasi PHP OpenSSL ganda tetap tidak
menggagalkan tes; konfigurasi PHP di luar repository tidak diubah.

Semua perubahan berada pada workspace lokal. Tidak ada commit/push, dependency,
database, autentikasi, atau perubahan engine kuis/editor existing dalam pekerjaan ini.

### Verifikasi perubahan evaluasi — 8 Oktober 2026

Jeda ulang dan mini project telah dihapus. Kelima textbox isian berada di dalam
potongan kode pada posisi garis kosong, dengan lebar menyesuaikan jawaban dan
batas responsif. Syntax highlighting memproses teks di kedua sisi kotak tanpa
menghapus input. Riwayat/jawaban lama tetap tersedia; policy jeda lama tidak
mengunci tombol Ulangi Evaluasi.

| Pengujian | Hasil eksekusi |
| --- | --- |
| `php artisan test --compact` | 33 tes lulus, 1143 assertions. |
| `node --test tests/js/*.test.js` | 19 tes lulus, termasuk kompatibilitas sesi/riwayat lama tanpa jeda. |
| `node tests/browser/evaluasi.mjs` | Lulus: ulang langsung setelah gagal/expiry dan riwayat lama, kelima input inline pada lima viewport, highlighting, autosave/refresh, penilaian, storage error/recovery, serta 404 mini project. |
| `node tests/browser/visual.mjs` | Lulus: 12 halaman existing/evaluasi, Prism/fallback, keenam kuis dan lima viewport. |
| Sintaks JS evaluasi, Pint `--test --dirty`/file PHP terkait, `git diff --check` | Lulus. |

Browser menggunakan Edge headless dengan aset CDN existing. Tidak ada page error
JavaScript atau kegagalan pengujian tersisa. Tidak ada perubahan pada kuis atau
Live Coding BAB 1–6, commit, maupun push.
