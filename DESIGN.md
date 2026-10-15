# DESIGN.md — OOPy Design System

> **Dokumen acuan desain untuk OOPy**  
> Versi: 1.0 · 16 Oktober 2026  
> Status: Arah desain proyek; implementasi dilakukan bertahap  
> Repository: https://github.com/shufi7/OOPy  
> Referensi filter kualitas: https://github.com/miqdadbadjuber/anti-slop

## 1. Tujuan dokumen

Dokumen ini adalah **sumber arahan desain (design direction)** bagi manusia dan AI coding agent yang mengembangkan antarmuka OOPy. Gunakan bersama aturan Anti-Slop, bukan menggantikannya.

OOPy adalah platform pembelajaran *Object-Oriented Programming* (OOP) menggunakan Python. Pengguna mempelajari konsep melalui materi, kuis, editor Python interaktif, latihan coding, dan pemantauan progres belajar. Sebagian konteks pembelajarannya menggunakan contoh yang berkaitan dengan lahan basah Kalimantan Selatan.

**Tujuan desain:**

1. Membantu pengguna memahami materi dan mengetahui tindakan berikutnya tanpa kebingungan.
2. Membuat aktivitas menulis, menjalankan, dan memeriksa kode menjadi pusat pengalaman belajar.
3. Membentuk identitas khas OOPy tanpa meniru dashboard SaaS, produk AI, atau template landing page.
4. Menyatukan visual beranda, materi, coding, kuis, evaluasi, autentikasi, dan dashboard.
5. Memastikan antarmuka dapat digunakan di desktop maupun perangkat seluler, termasuk melalui keyboard.
6. Mempertahankan kebenaran konten, progres, status, dan fungsi yang sudah ada.

**Prinsip pengarah:** OOPy harus terasa seperti *ruang belajar dan praktikum pemrograman yang rapi, hangat, dan dapat dipercaya*, bukan demo startup yang memamerkan dekorasi.

## 2. Design Read dan identitas visual

**Design Read:** Educational coding workspace untuk pelajar/mahasiswa yang sedang mempelajari OOP Python; bergaya modern, tenang, ramah, fungsional, dengan identitas mint–teal dan elemen praktikum yang khas OOPy.

| Liveliness dial | Nilai | Penerapan |
| --- | --- | --- |
| **ENERGY** | **2/3 — Balanced** | Berkarakter tetapi tidak ramai; judul dan aksi utama tegas. |
| **RHYTHM** | **2/3 — Varied** | Materi panjang, blok kode, petunjuk, dan rangkuman memiliki ritme berbeda sesuai fungsi. |
| **MOTION** | **1/3 — Calm** | Hover/focus/transisi seperlunya; tidak ada animasi dekoratif terus-menerus. |

### Kepribadian merek

- **Jelas:** teks tombol langsung menyatakan tindakannya: “Mulai Belajar”, “Run Code”, “Submit”.
- **Membimbing:** petunjuk menjawab *apa yang dikerjakan, bagaimana memulainya, dan bagaimana berhasil*.
- **Terstruktur:** halaman berorientasi urutan belajar, bukan galeri kartu.
- **Manusiawi:** bahasa Indonesia sederhana dan bersahabat, tanpa jargon promosi kosong.
- **Kontekstual:** logo bekantan dan contoh lahan basah Kalsel adalah identitas produk; jangan menempel ilustrasi tropis sebagai dekorasi tanpa fungsi.

### Motif visual yang boleh dipakai

Gunakan **bahasa visual praktikum**: nomor BAB dan latihan yang jelas, pemisahan antara *Instruksi → Editor → Output → Hasil*, label kode monospace, dan garis/progres yang benar-benar menyatakan tahapan. Ini lebih khas OOPy daripada pola grid, glow, atau gelembung abstrak.

Gunakan **logo OOPy yang sudah ada** (`public/images/oopy-logo.png`). Jangan mengganti, menggambar ulang, atau membuat maskot baru tanpa persetujuan pemilik proyek.

**Tes keunikan:** jika kata “OOPy” dan logo disembunyikan, halaman tetap semestinya terlihat sebagai lingkungan belajar OOP Python, bukan dashboard generik.

## 3. Palet dan design tokens

Pertahankan warna merek yang sudah digunakan. Bedakan **warna identitas** dari **warna kontrol/teks** agar kontras memenuhi kebutuhan aksesibilitas.

### 3.1 Warna utama

| Token | Hex | Peran |
| --- | --- | --- |
| `brand-teal` | `#11999E` | Warna identitas, logo/aksen visual besar, ilustrasi ringan; **bukan** default teks kecil di latar putih. |
| `brand-mint` | `#E4F9F5` | Latar sekunder dan bidang penjelas. |
| `brand-aqua` | `#30E3CA` | Aksen terbatas; gunakan teks gelap di atasnya. |
| `ink-strong` | `#243B39` | Heading dan teks yang memerlukan bobot kuat. |
| `ink-body` | `#40514E` | Teks utama. |
| `surface` | `#FFFFFF` | Permukaan konten. |
| `canvas` | `#F6FBF9` | Latar halaman netral terang. |
| `line` | `#D2EBE6` | Border pemisah ringan. |

### 3.2 Warna aksi yang lebih aman

| Token | Hex | Pasangan teks | Kontras perkiraan |
| --- | --- | --- | --- |
| `action-primary` | `#076D71` | Putih `#FFFFFF` | **6,11:1** |
| `action-primary-hover` | `#065F63` | Putih `#FFFFFF` | **7,43:1** |
| `link` | `#08777B` | Pada putih | **5,34:1** |
| `link` | `#08777B` | Pada mint `#E4F9F5` | **4,87:1** |
| `ink-body` | `#40514E` | Pada putih | **8,38:1** |

Angka di atas dihitung dari nilai hex menggunakan rumus kontras relatif WCAG dan perlu diverifikasi kembali setelah warna diterapkan pada seluruh state, transparansi, dan lapisan.

**Larangan penting:** `#FFFFFF` di atas `#11999E` menghasilkan kontras sekitar **3,46:1**; ini tidak cukup untuk teks normal. Jangan memperbaikinya dengan mengasumsikan semua teks tombol selalu besar. Gunakan `action-primary` untuk tombol berteks putih, atau pasangan warna gelap yang telah teruji.

### 3.3 Contoh tokens CSS target

Ini adalah **spesifikasi yang dituju**, bukan pernyataan bahwa seluruh kode sekarang sudah menggunakannya.

```css
:root {
  --oopy-brand: #11999e;
  --oopy-brand-mint: #e4f9f5;
  --oopy-brand-aqua: #30e3ca;

  --oopy-canvas: #f6fbf9;
  --oopy-surface: #ffffff;
  --oopy-line: #d2ebe6;
  --oopy-ink: #243b39;
  --oopy-text: #40514e;

  --oopy-action: #076d71;
  --oopy-action-hover: #065f63;
  --oopy-on-action: #ffffff;
  --oopy-link: #08777b;

  --oopy-radius-control: 8px;
  --oopy-radius-card: 14px;
  --oopy-radius-feature: 20px;

  --oopy-space-1: 4px;
  --oopy-space-2: 8px;
  --oopy-space-3: 12px;
  --oopy-space-4: 16px;
  --oopy-space-6: 24px;
  --oopy-space-8: 32px;
  --oopy-space-12: 48px;
}
```

Saat implementasi, selaraskan tokens baru dengan alias lama (`--oopy-primary`, `--oopy-blue`, variabel Bootstrap, dll.) secara bertahap. Jangan melakukan *search-and-replace* buta yang mengubah semantik warna status atau merusak halaman lain.

### 3.4 Penggunaan warna

- Dominasi kanvas netral dan permukaan putih; mint digunakan untuk membedakan area penting.
- Aksen teal ditempatkan pada aksi dan penanda relevan, bukan semua heading, ikon, border, dan badge sekaligus.
- Warna hijau untuk keberhasilan, merah untuk error, dan kuning/amber untuk peringatan hanya jika ada *state* sebenarnya; selalu sertakan teks.
- Gradient boleh digunakan hanya jika membantu hierarki secara nyata. **Jangan memakai gradient biru–ungu, neon, radial orb, atau glow** sebagai dekorasi otomatis.
- **Tema default:** terang. Editor kode boleh menggunakan permukaan gelap yang sesuai kebutuhan membaca kode. Jangan menambahkan toggle dark mode kecuali kedua tema benar-benar selesai dan teruji.

## 4. Tipografi

### Font

- **Antarmuka dan materi:** `Plus Jakarta Sans`, mengikuti dependensi yang sudah ada.
- **Kode Python, nama file, output, dan token teknis:** monospace sistem (`ui-monospace`, `Consolas`, `Menlo`, `monospace`). Font khusus tidak wajib.
- Hindari memuat banyak keluarga font baru tanpa alasan produk.

### Skala dan perilaku

| Elemen | Ukuran target | Ketentuan |
| --- | --- | --- |
| H1 beranda | `clamp(2.25rem, 4vw, 3.25rem)` | Pesan inti ringkas; satu fokus utama. |
| H1 halaman | `clamp(1.875rem, 3vw, 2.5rem)` | Nama tugas, materi, atau area pengguna. |
| H2 | `clamp(1.375rem, 2vw, 1.875rem)` | Subbagian yang mudah dipindai. |
| Isi materi | `1rem–1.125rem` | `line-height` sekitar `1.65–1.8`. |
| Label & metadata | `0.8125rem–0.9375rem` | Bukan untuk instruksi penting yang panjang. |
| Kode | `0.875rem–1rem` | Cukup lebar; scroll horizontal lokal bila perlu. |

- Jangan memaksa semua heading menjadi uppercase.
- Batasi panjang paragraf utama sekitar **65–75 karakter** per baris pada desktop.
- Gunakan bobot `400/500` untuk isi, `600/700` untuk subjudul, `700/800` untuk penanda utama.
- Gunakan struktur heading semantik; jangan memilih `h3` hanya karena ukurannya pas.
- Hindari teks yang terlalu kecil atau warna abu-abu sangat pucat demi kesan estetis.

## 5. Tata letak, grid, dan ruang

- Lebar konten umum: sekitar **1180–1280px**, tergantung tugas halaman.
- Layar coding boleh lebih lebar (sekitar **1360px**) ketika benar-benar membantu area editor.
- Desktop materi dapat memakai **daftar isi di samping + artikel utama**; artikel tetap harus nyaman dibaca.
- Jarak memakai skala konsisten (4, 8, 12, 16, 24, 32, 48px), tetapi **ritme antarseksi boleh berubah** berdasarkan bobot konten.
- Gunakan satu fokus visual yang paling penting per layar; elemen lain mendukungnya.
- Border ringan lebih diutamakan daripada bayangan besar pada setiap kartu.
- Radius dibedakan berdasarkan fungsi: kontrol 8px, kartu 14px, panel unggulan sekitar 20px. Jangan semuanya berbentuk pill.
- Jangan memaksa semua fitur menjadi tiga atau empat kartu yang identik. Konten menentukan komposisi, bukan pola template.

## 6. Komponen inti

### 6.1 Navigasi

- Pertahankan navigasi yang nyata: **Beranda, Materi, Coding**, serta **Dashboard** untuk pengguna yang masuk, dan aksi autentikasi sesuai keadaan akun.
- Aktifkan tanda halaman sekarang (`aria-current="page"` bila sesuai).
- Menu seluler harus bisa dibuka/ditutup via tombol dan keyboard, tanpa menutup konten.
- Jangan menambah item menu yang tidak mempunyai route atau fungsi.

### 6.2 Tombol

| Tipe | Kapan digunakan | Contoh |
| --- | --- | --- |
| Primary | Aksi utama dalam satu konteks | “Mulai Belajar”, “Run Code”, “Submit” |
| Secondary | Aksi alternatif tetapi tersedia | “Lihat Materi”, “Kerjakan Lagi” |
| Tertiary/link | Navigasi pendukung | “Kembali ke daftar latihan” |
| Destructive | Menghapus/me-reset hasil atau input | “Reset”, dengan konfirmasi sesuai akibatnya |

- Satu aksi utama per area keputusan; dua aksi utama hanya bila memang berbeda dan sama penting.
- Setiap tombol mempunyai state normal, hover, keyboard focus, disabled, loading bila perlu, serta hasil/feedback.
- Jangan menambahkan anak panah pada semua tombol hanya sebagai ornamen.
- Area interaktif pada layar sentuh ditargetkan setidaknya **44×44px** jika memungkinkan.

### 6.3 Card, callout, dan status

- Gunakan kartu jika isi membentuk satu unit yang jelas; jangan membungkus setiap paragraf dalam kartu.
- Callout hanya untuk **petunjuk, perhatian, contoh, atau catatan teknis** yang perlu dibedakan.
- Status “Selesai”, “Belum dikerjakan”, “Sedang memeriksa”, “Benar”, atau “Perlu diperbaiki” harus berasal dari state yang nyata.
- Status tidak boleh hanya dibedakan lewat warna atau ikon; sertakan label teks yang terbaca.
- Jangan gunakan data demo palsu di dashboard produksi.

### 6.4 Blok kode

- Gunakan monospaced font, wrapping atau scrolling yang aman, dan kontras yang terukur.
- Tampilkan contoh kode hanya ketika mendukung pembelajaran.
- Jangan membuat terminal hiasan palsu pada landing page ketika OOPy mempunyai editor sungguhan.
- Fitur salin kode harus memberi feedback yang benar jika disediakan.

## 7. Spesifikasi per halaman

### 7.1 Beranda (`/`)

**Tujuan:** pengguna memahami OOPy dan menemukan titik awal belajar.

Urutan yang disarankan:

1. Hero yang menjelaskan manfaat produk dalam satu judul spesifik.
2. Penjelasan singkat materi, praktik kode, dan kuis yang memang tersedia.
3. Aksi utama **Mulai Belajar** menuju materi; tautan **Lihat Latihan Coding** boleh menjadi aksi sekunder bila jelas.
4. Identitas logo bekantan yang sudah ada, tanpa efek visual berlapis-lapis.

**Usulan copy:**

- Judul: **Belajar OOP Python, langsung lewat praktik.**
- Deskripsi: *Pahami class, object, inheritance, dan konsep OOP lainnya melalui materi, kuis, serta latihan Python yang bisa dijalankan di browser.*
- CTA utama: **Mulai Belajar**
- CTA sekunder (opsional): **Lihat Latihan Coding**

Tidak perlu hero berisi statistik pengguna palsu, logo mitra fiktif, testimoni, badge “AI powered”, atau animasi dekoratif.

### 7.2 Daftar materi (`/materi`)

**Tujuan:** membantu pengguna memilih BAB yang tepat.

- Tampilkan urutan BAB, judul, ringkasan singkat, dan status progres yang benar-benar tersedia.
- Perbedaan status harus jelas secara teks dan visual.
- Jangan membuat semua BAB tampak “Selesai” atau menyisipkan nilai contoh.
- Hierarki nomor BAB > judul > keterangan > aksi.

### 7.3 Halaman materi (`/materi/{slug}`)

**Tujuan:** membaca teori, memahami contoh, mengerjakan aktivitas, dan melanjutkan BAB.

- Pertahankan breadcrumb dan daftar isi BAB.
- Fokus pada artikel utama; sidebar adalah navigasi, bukan pesaing utama perhatian.
- Gunakan ritme: **tujuan pembelajaran → penjelasan → contoh → aktivitas/latihan → rangkuman/refleksi → kuis** sesuai konten aktual.
- Jangan mengubah urutan atau isi kurikulum hanya demi tampilan.
- Bedakan blok teori, contoh Python, dan petunjuk melalui struktur/label, bukan tumpukan kartu seragam.
- Kuis tetap mengikuti persyaratan penyelesaian dan penguncian BAB yang diimplementasikan sistem.
- Pada seluler, daftar isi dapat dilipat dan tidak mengambil seluruh tinggi layar.

### 7.4 Daftar coding (`/coding`)

**Tujuan:** memilih latihan secara cepat.

Dua latihan yang tersedia pada kode saat dokumen ini disusun:

- **Latihan 1:** class/object dengan konteks `SensorAir`.
- **Latihan 2:** inheritance/overriding dengan konteks `Ekosistem–Rawa`.

- Tampilkan judul latihan, tujuan, konsep yang diuji, status dari data sebenarnya, dan tombol **Mulai Latihan/Kerjakan Lagi**.
- Dua latihan tidak memerlukan bento grid atau kumpulan kartu dekoratif.
- Pengguna guest dapat memahami bahwa hasil latihan tidak disimpan sebagai kelulusan akun tanpa login.

### 7.5 Detail latihan coding (`/coding/{slug}`)

**Tujuan:** menyelesaikan soal dari nol dengan Monaco Editor dan menjalankan Python.

Urutan informasi yang disarankan:

1. Breadcrumb dan **judul latihan**.
2. **Soal/tujuan** dan **instruksi pengerjaan**.
3. **Ketentuan program** beserta **contoh output** dan **kriteria berhasil**.
4. **Monaco Editor** dengan `main.py` **kosong untuk latihan mandiri**.
5. Kontrol **Run Code**, **Reset**, **Submit** dan **Hentikan** hanya jika berlaku.
6. **Output Program** dan **hasil pengecekan** dengan feedback spesifik.

Aturan interaksi wajib:

- Jangan menaruh kunci jawaban, template solusi, komentar TODO, atau kode contoh ke editor awal latihan 1/2.
- Membedakan tegas **Run Code** (menjalankan dan melihat output) dari **Submit** (memeriksa jawaban dan menyimpan hasil bila pengguna berhak).
- Loading Pyodide, editor belum siap, error sintaks, timeout, kode kosong, serta jaringan/grader bermasalah harus memiliki pesan yang jelas.
- Jangan menyatakan jawaban “benar” atau “tersimpan” sebelum mendapat hasil yang sah dari mekanisme penilaian.
- Jangan menghapus kode yang diketik pengguna saat menjalankan program.
- **Reset** harus menyatakan akibatnya secara jelas dan memakai konfirmasi jika menghilangkan pekerjaan.
- Output terminal adalah informasi penting, bukan terminal visual palsu.
- Di layar sempit, tempatkan soal di atas editor dan output di bawahnya; jangan menyusutkan editor sampai tidak dapat dipakai.
- Jika editor multi-file ditampilkan, tab dan pengelola file harus tetap dapat diakses lewat keyboard.

### 7.6 Dashboard (`/dashboard`)

**Tujuan:** memberi jawaban atas *“Saya sudah sampai mana dan harus melanjutkan apa?”*

Urutan prioritas:

1. **Progres belajar sebenarnya**, dengan label persentase dan BAB selesai.
2. **Lanjutkan belajar**, menggunakan rekomendasi yang benar.
3. **Nilai dan status kuis** yang tersimpan.
4. Profil dan aktivitas sebagai informasi sekunder.

- Selaraskan palet dashboard yang masih memiliki biru/ungu tersendiri dengan tokens utama OOPy.
- Tabel nilai harus tetap mudah diikuti pada mobile (reflow dengan label atau scroll tabel terlokalisasi).
- Jika belum ada data, tunjukkan empty state yang jujur dan aksi untuk memulai.
- Hindari grafik dekoratif yang tidak membantu pengguna membuat keputusan.

### 7.7 Login, registrasi, evaluasi akhir

- Form berlabel, dengan error dekat input dan fokus jelas.
- Evaluasi menonjolkan soal, urutan, waktu/status yang benar, dan navigasi antarsoal.
- Hindari animasi yang mengganggu membaca, mengetik, atau mengerjakan evaluasi.
- Jangan mengubah aturan autentikasi, otorisasi, penilaian, atau ketentuan ujian saat melakukan redesign visual.

## 8. Responsivitas dan aksesibilitas

Gunakan **breakpoint berdasarkan titik ketika konten mulai tidak layak**, bukan sekadar nama perangkat. Sebagai *target pengujian*, cek setidaknya lebar viewport **360, 390, 768, 1024, dan 1440px**.

| Area | Desktop | Seluler |
| --- | --- | --- |
| Navbar | Menu horizontal | Menu yang bisa dibuka dan ditutup |
| Beranda | Hero dua kolom bila cukup | Teks, CTA, lalu logo/visual dalam satu kolom |
| Materi | Daftar isi + artikel | Daftar isi dapat dilipat, artikel satu kolom |
| Daftar coding | Dua item berdampingan bila cukup | Item bertumpuk |
| Detail coding | Konten luas untuk editor | Soal → Editor → Aksi → Output/Hasil |
| Dashboard | Panel progres dan data | Susun menurut prioritas belajar |

**Kriteria wajib:**

- Tidak ada halaman yang bocor scroll horizontal; pengecualian hanya area kode/tabel yang memang mempunyai scroll lokal.
- Teks normal memenuhi kontras **≥ 4,5:1**; teks besar **≥ 3:1**; elemen UI penting juga harus dapat dibedakan.
- Tampilan tetap terbaca saat browser diperbesar hingga **200%** dan ditinjau pada reflow.
- Semua kontrol penting dapat digunakan dengan keyboard; indikator fokus terlihat jelas.
- Input, icon button, menu, dan tab mempunyai nama yang dapat dipahami screen reader.
- Tidak mengandalkan hover untuk membuka fungsi utama.
- Pesan sukses/gagal tidak hanya menggunakan warna.
- Hormati `prefers-reduced-motion: reduce`.
- Perhatikan posisi keyboard virtual di HP agar editor, tombol, dan field aktif tidak tertutup.

## 9. Motion dan feedback

- Transisi sederhana `120–220ms` untuk perubahan state yang membantu orientasi.
- Tidak ada parallax, floating blob, infinite pulse, atau scroll reveal beruntun sebagai efek default.
- Gunakan animasi hanya jika menjelaskan perubahan keadaan.
- Selama proses editor/runtime/grading, tunjukkan state sedang bekerja, dan hindari aksi ganda yang menyebabkan pengiriman tidak sengaja.
- Jangan mengubah tampilan menjadi sukses sebelum aksi benar-benar berhasil.

## 10. Bahasa antarmuka

**Tone:** bahasa Indonesia alami, lugas, membantu belajar; gunakan istilah teknis Python yang diperlukan.

| Hindari | Pilih |
| --- | --- |
| “Revolusikan perjalanan coding-mu” | “Belajar OOP Python lewat materi dan latihan” |
| “Unlock your potential now” | “Mulai Belajar” |
| “Oops! Something went wrong” tanpa detail | “Kode belum dapat dijalankan. Coba lagi.” |
| “Success!” tanpa konteks | “Jawaban berhasil diperiksa.” |
| “100% akurat” tanpa bukti | Jelaskan hasil penilaian sesuai data sebenarnya |

- Instruksi soal menjelaskan **input, proses, output, aturan, dan kondisi keberhasilan** secara terukur.
- Gunakan contoh input/output yang benar dan dapat diuji.
- Hindari emoji dekoratif, tagline berlebihan, banyak slogan, dan angka testimoni buatan.
- Jangan mengganti semua kata Indonesia dengan bahasa Inggris tanpa kebutuhan. `Run Code`, `Submit`, `class`, `object`, dan istilah pemrograman lain boleh dipertahankan jika konsisten.

## 11. Anti-Slop: filter wajib

Gunakan skill **`antislop`** sebagai inti, dibantu **`antislop-ui`**, **`antislop-copywriting`**, **`antislop-human`**, **`antislop-layoutmobile`**, dan **`antislop-code`** sesuai ruang lingkup tugas.

### Anti-pattern yang harus diperiksa

1. Gradient biru–ungu, glow, glassmorphism, dan bayangan besar yang digunakan tanpa tujuan.
2. Layout hero + kartu fitur seragam + statistik/testimoni palsu + CTA penutup hanya karena umum pada template.
3. Kartu dengan radius, ikon, dan padding identik untuk semua jenis konten.
4. Badge promosi, status dot, arrow, emoji, serta ikon dekoratif yang tidak menyampaikan informasi.
5. Dashboard penuh grafik atau nilai buatan.
6. Terminal palsu ketika produk memiliki Monaco dan runtime Python yang nyata.
7. Headline generik, hiperbola, atau klaim tanpa bukti.
8. Kontrol yang tampak aktif tetapi tidak bekerja.
9. Desain aman tetapi terlalu steril dan tidak punya identitas.
10. Mobile yang hanya merupakan desktop diperkecil.

**Jangan menerapkan “larangan estetika” secara buta.** Jika gradient, shadow, atau card tertentu mendukung hierarki dan alasan penggunaannya tertulis, elemen itu dapat dipertahankan sesuai Purpose-Gate. Peran Anti-Slop adalah **menyaring keputusan tak beralasan**, sedangkan dokumen ini memberi **arah visual positif**.

## 12. Aturan implementasi dalam repository

### Teknologi yang sudah digunakan

- **Backend dan view:** Laravel 12 dan Blade.
- **UI:** Bootstrap 5 dan CSS kustom OOPy.
- **Interaktivitas coding:** Monaco Editor, Pyodide/runtime JavaScript, mekanisme penilaian latihan yang sudah ada.
- **Identitas:** `public/images/oopy-logo.png`, font Plus Jakarta Sans, palet mint–teal.

### File yang perlu diperiksa sebelum redesain

```text
resources/views/layouts/app.blade.php
resources/views/components/navbar.blade.php
resources/views/home/index.blade.php
resources/views/materi/
resources/views/coding/
resources/views/dashboard/
resources/views/components/live-code.blade.php

public/css/oopy/base.css
public/css/oopy/navbar.css
public/css/oopy/home.css
public/css/oopy/material/
public/css/oopy/coding.css
public/css/oopy/dashboard/dashboard.css
public/css/oopy/live-code/live-code.css
```

**Ketentuan engineering:**

1. Baca komponen dan class yang sudah ada sebelum mengubah tampilannya.
2. Manfaatkan ulang Blade partial dan tokens. Buat komponen baru jika benar-benar ada pola berulang yang layak dibagikan.
3. Jangan mengganti Laravel/Blade/Bootstrap dengan framework baru hanya demi membuat desain.
4. Jangan mencampurkan refaktor besar dan redesign visual dalam satu perubahan tanpa kebutuhan.
5. Pertahankan route, autentikasi, kuis, evaluasi, progress, exercise submission, dan kontrak frontend–backend.
6. Jangan menghapus database, menjalankan `migrate:fresh`, atau mengubah data pengguna untuk menyesuaikan UI.
7. Gunakan gambar/ikon yang sudah tersedia; aset baru harus memiliki alasan dan persetujuan.
8. Buat perubahan CSS secara terstruktur, jangan menumpuk override impulsif atau membuat skrip patch CSS.
9. Jangan menulis klaim “fitur selesai” hanya dari melihat markup; lakukan pemeriksaan fungsi.
10. Perbaiki secara inkremental, sehingga setiap tahap dapat dibandingkan dan diuji.

## 13. Urutan kerja redesain

| Tahap | Fokus | Hasil yang harus tampak |
| --- | --- | --- |
| **0. Audit** | Inventaris warna, tipografi, komponen, interaksi, mobile, dan masalah kontras | Daftar temuan berbasis file serta prioritas |
| **1. Fondasi** | Tokens, button, input, fokus, heading, dan navbar | Bahasa visual konsisten lintas halaman |
| **2. Beranda** | Hero, pesan produk, CTA, logo | Nilai OOPy langsung dapat dipahami |
| **3. Dashboard** | Progres, rekomendasi, status, palet | Informasi nyata menjadi fokus |
| **4. Materi dan kuis** | Hierarki artikel, TOC, contoh kode, instruksi | Membaca dan belajar lebih mudah |
| **5. Coding** | Instruksi, editor kosong, aksi, output, feedback | Alur latihan jelas dan tidak mengubah grading |
| **6. Evaluasi dan autentikasi** | Form, validasi, navigasi, hasil | Pengalaman utuh dan konsisten |
| **7. QA** | Responsif, aksesibilitas, visual regression, tes fitur | Bukti bahwa desain dan fungsi lolos |

## 14. Definition of Done dan Delivery Gate

Perubahan UI **belum selesai** sebelum kriteria yang relevan diperiksa. Jangan melaporkan `PASS` tanpa bukti.

### Hard Gate

- [ ] Navigasi mengarah ke route/anchor yang benar.
- [ ] Semua tombol, menu, tab, dan form bekerja; tidak ada kontrol dekoratif yang berpura-pura berfungsi.
- [ ] Tidak ada angka, statistik, nilai, profil, testimonial, atau status palsu.
- [ ] Tidak ada overflow horizontal halaman pada viewport uji.
- [ ] Rasio kontras warna telah dihitung pada komponen penting.
- [ ] Loading, empty, error, disabled, dan success state tersedia sesuai konteks.
- [ ] Komponen utama dapat digunakan dengan keyboard.
- [ ] Tidak ada regresi pada alur materi, kuis, coding, submission, dan dashboard.

### Purpose-Gate dan identitas

- [ ] Setiap gradient, shadow, animasi, ikon, dan kartu penting mempunyai alasan yang dapat ditulis.
- [ ] ENERGY 2 / RHYTHM 2 / MOTION 1 tercermin pada hasil.
- [ ] Warna, tipografi, spacing, dan radius mengikuti design tokens.
- [ ] Identitas OOPy terasa jelas tanpa perlu menambah dekorasi generik.
- [ ] Komposisi mengikuti kebutuhan konten, bukan susunan template AI.

### Bukti pengujian yang diminta dari coding agent

1. Daftar file yang diubah dan alasan setiap perubahan utama.
2. Screenshot **sebelum dan sesudah** pada desktop dan mobile bila lingkungan pengujian mendukungnya.
3. Hasil tes yang benar-benar dijalankan (contohnya `php artisan test`, pengujian JS, dan browser test yang terkait).
4. Hasil pemeriksaan kontras dan keyboard.
5. Ringkasan PASS/FAIL sesuai Delivery Gate Anti-Slop.
6. Setiap keterbatasan atau pengujian yang **belum dijalankan** harus dinyatakan jujur sebagai `NOT TESTED`, bukan `PASS`.

## 15. Instruksi tetap untuk Codex

Saat diminta mengerjakan UI OOPy, gunakan arahan berikut:

> Baca `DESIGN.md` di root repository sebagai sumber arahan visual OOPy. Terapkan skill `antislop` dan skill turunannya yang relevan sebagai filter kualitas. Mulai dengan audit komponen dan style yang sudah ada; jangan langsung merombak seluruh aplikasi. Pertahankan identitas mint–teal, logo bekantan, Laravel/Blade/Bootstrap, serta semua fungsi materi, kuis, Monaco Editor, Pyodide, submission, dan dashboard. Utamakan hierarki pembelajaran, kontras WCAG AA, keyboard accessibility, layout responsif, dan konten yang benar. Jangan membuat UI SaaS/AI generik. Ubah hanya file yang perlu, jalankan pengujian yang tersedia, dan tutup pekerjaan dengan laporan Delivery Gate berbasis bukti.

---

**Catatan sumber:** Dokumen disusun berdasarkan pemeriksaan struktur dan sebagian kode OOPy pada branch `main` serta prinsip dari repository Anti-Slop. Angka ukuran, tokens baru, urutan redesign, dan contoh copy di atas merupakan **keputusan desain yang diusulkan**, bukan klaim bahwa semuanya telah diterapkan atau diuji dalam browser.
