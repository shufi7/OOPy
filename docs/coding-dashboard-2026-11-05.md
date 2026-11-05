# Integrasi progres Coding ke Dashboard OOPy

Tanggal: 5 November 2026, WITA. Baseline Git: `0582dae3628a0c2572e99f2e438b622132fc4398`. Working tree bersih sebelum pengerjaan. Mode antislop: **during**, pilihan pengguna dalam sesi ini.

**A. Ringkasan implementasi**

Dashboard sekarang menampilkan **Progres Latihan Coding** dan **Riwayat Latihan Coding** setelah daftar nilai kuis. Data ditambahkan pada properti `coding` di hasil `DashboardProgressService::forUser(User $user)`; seluruh properti sebelumnya tetap tersedia.

- Hanya `coding-latihan-1` dan `coding-latihan-2` yang dihitung. Latihan live coding materi dikecualikan.
- Status awal **Belum Dimulai**; setelah submission menjadi **Belum Lulus**; minimal satu riwayat `passed` menghasilkan **Selesai**. Percobaan gagal/error berikutnya tidak membatalkan kelulusan.
- Nilai terbaik memakai maksimum skor tersimpan pada submission berstatus `passed`, `failed`, atau `error` dengan skor 0–100. Nilai `null` dan status awal `submitted` tidak menjadi nilai resmi; angka 0 tetap ditampilkan.
- Progres Coding adalah jumlah latihan selesai dibagi dua: 0%, 50%, atau 100%. Perhitungan ini terpisah dari progres enam BAB dan nilai kuis.
- Riwayat dibatasi lima hasil terbaru menurut `submitted_at DESC, id DESC`, dengan waktu Asia/Makassar, skor, dan label Lulus/Belum Lulus/Error. Tidak mengambil atau menampilkan source code/output pengguna.
- Tombol memakai route `coding.show` dan `coding.index` yang sudah ada. Dashboard kosong tetap memiliki dua latihan dan tautan untuk mulai.
- Query memakai relasi `$user->exerciseSubmissions()`, termasuk ketika Admin meminta progres mahasiswa tertentu. Parameter browser tidak memilih pemilik data.
- Dua query tambahan mengambil agregat dan riwayat terbatas. Tes dengan 12 submission mencatat delapan query baca keseluruhan, tanpa lazy loading, pembacaan kode/output, atau query per latihan.

Alur submit dan grader tetap memakai implementasi sebelumnya. Tes integrasi HTTP memastikan hasil resmi tersimpan langsung terbaca saat Dashboard dimuat lagi; respons grader tidak valid/tidak tersedia tidak menghasilkan progres palsu.

**B. File yang diubah**

| File | Alasan |
| --- | --- |
| [DashboardProgressService.php](../app/Services/DashboardProgressService.php) | Agregat nilai/status, progres dua latihan, dan riwayat privat berdasarkan parameter pengguna |
| [dashboard/index.blade.php](../resources/views/dashboard/index.blade.php) | Dua section baru, progress bar aksesibel, status, nilai, tombol, dan empty state |
| [dashboard.css](../public/css/oopy/dashboard/dashboard.css) | Baris latihan yang reflow pada tablet/ponsel dan warna status Error |
| [DashboardTest.php](../tests/Feature/DashboardTest.php) | Sembilan kasus tambahan melalui data provider/metode tes, regresi BAB/kuis, query terbatas, riwayat WITA, dan isolasi Admin |
| [CodingTest.php](../tests/Feature/CodingTest.php) | Memastikan hasil submit, retry, error, serta penolakan grader tercermin di Dashboard |
| [dashboard-coding.mjs](../tests/browser/dashboard-coding.mjs) | Verifikasi UI Coding, keyboard, navigasi, Pyodide, kegagalan verifikasi resmi, akun, kontras, dan responsivitas |
| [dashboard-coding-fixture.php](../tests/browser/dashboard-coding-fixture.php) | Fixture CLI khusus database SQLite pengujian; tidak menambahkan endpoint aplikasi |
| [dashboard.mjs](../tests/browser/dashboard.mjs) | Selector eksplisit untuk progress bar BAB dan empty state aktivitas kuis setelah ada section Coding |
| [foundation.mjs](../tests/browser/foundation.mjs) | Selector progress bar BAB agar regresi tidak ambigu dengan progress bar Coding |
| Dokumen ini dan [bukti pengujian](evidence/coding-dashboard-2026-11-05/results.json) | Laporan aktual, screenshot, pengukuran browser, dan kegagalan baseline |

Controller, model, route, katalog/rubrik Coding, Monaco, Pyodide, sandbox, dan migration tidak diubah.

**C. Perubahan database**

**Tidak ada perubahan struktur database.** Tabel, indeks, dan relasi yang ada sudah mencukupi. Tidak mengubah `.env`, menjalankan migration pada database proyek, atau menghapus data pengguna. Tes PHP memakai SQLite `:memory:`; browser memakai file SQLite baru dengan nama `coding-dashboard-<UUID>.sqlite` di direktori testing yang diabaikan Git. Akun dan submission fixture hanya dibuat di database uji tersebut.

**D. Pengujian aktual**

| Perintah/pemeriksaan | Hasil |
| --- | --- |
| `php artisan test --filter=DashboardTest` | **27 lulus**, 314 assertions, 0 gagal |
| `php artisan test --filter=CodingTest` | **16 lulus**, 254 assertions, 0 gagal pada pengujian akhir |
| `php artisan test --compact` | **171 lulus, 2 gagal**, 3570 assertions; [log](evidence/coding-dashboard-2026-11-05/php-suite.txt) |
| Dua tes gagal pada snapshot HEAD sebelum perubahan | **2 gagal yang sama**, 21 assertions; [bukti baseline](evidence/coding-dashboard-2026-11-05/baseline.txt) |
| `node --test tests/js/runtime-manager.test.js tests/js/material-checkers.test.js tests/js/final-exam.test.js tests/js/coding-checkers.test.js` | **21 lulus**, 0 gagal |
| `node tests/browser/dashboard-coding.mjs` | **18 sampel layout**, 106 pengukuran kontras, 10 catatan interaksi, **0 page errors**; [hasil](evidence/coding-dashboard-2026-11-05/results.json) |
| `node tests/browser/dashboard.mjs` | **20 sampel layout**: kosong/progres/selesai/nama panjang × lima viewport; navigasi, kuis nyata, logout/login, isolasi akun, dan tanpa JS lulus |
| `node tests/browser/foundation.mjs` | **100 sampel layout**, 40 pengukuran kontras, tujuh kelompok interaksi, **0 page errors**; [hasil](evidence/coding-dashboard-2026-11-05/foundation-results.json) |
| `vendor/bin/pint --test app/Services/DashboardProgressService.php tests/Feature/DashboardTest.php tests/Feature/CodingTest.php tests/browser/dashboard-coding-fixture.php` | PASS |
| `node --check tests/browser/dashboard-coding.mjs`, `php -l tests/browser/dashboard-coding-fixture.php`, `git diff --check` | PASS |

Kasus wajib backend tercakup: tanpa submission; hanya Latihan 1; hanya Latihan 2; keduanya; nilai tertinggi meskipun retry turun; kelulusan permanen; belum lulus; skor 0; skor kosong/belum resmi; maksimal lima riwayat dengan urutan waktu dan ID; pemisahan akun; parameter pengguna untuk Admin; regresi BAB/kuis; katalog belum di-seed; route Coding; dan query baca dengan jumlah tetap.

Kegagalan suite lengkap berada di luar integrasi ini dan direproduksi menggunakan arsip HEAD asli dengan source aplikasi/tes baseline serta database in-memory terpisah:

1. `LearningDatabaseTest.php:276`: tes rollback mengharapkan kolom `users.role` sudah hilang setelah `--step=12`, sedangkan repositori kini memiliki 18 migration. Ekspektasi rollback lama perlu ditinjau terpisah.
2. `MateriTest.php:23`: tes menghitung literal HTML `<span>Pelajari BAB` sebanyak tujuh, sedangkan markup tombol materi sekarang memakai whitespace/struktur berbeda. Halaman berhasil dirender tetapi pencocokan literal menghasilkan nol.

Peringatan PHP `Module "openssl" is already loaded` juga muncul dari konfigurasi lingkungan. Tidak diubah dalam pekerjaan ini. Percobaan browser awal menemukan pencocokan whitespace yang terlalu ketat pada tes baru; assertion diperbaiki lalu suite Coding dijalankan ulang sampai exit 0.

Browser: Microsoft Edge headless, aset CDN memakai cache lokal dari URL yang sama. Pengujian ini memverifikasi hasil render/interaksi aset tersebut, bukan ketersediaan CDN publik. CSS disajikan langsung dari `public/`; build Vite tidak diperlukan untuk perubahan ini.

**E. Verifikasi manual dan bukti browser**

1. Login ke akun mahasiswa, buka `/dashboard`: akun tanpa submission menampilkan 0 dari 2 latihan, 0%, nilai `—`, dan empty state riwayat.
2. Pilih **Mulai Latihan** pada salah satu latihan. Pastikan tujuannya `/coding/latihan-1` atau `/coding/latihan-2` dan editor tetap tersedia.
3. Tulis solusi, klik **Run Code**, lalu **Submit**. Dengan grader resmi aktif, tunggu hasil resmi yang berhasil disimpan.
4. Buka ulang atau refresh Dashboard. Satu latihan yang pernah `passed` menghasilkan 50%; kedua latihan menghasilkan 100%.
5. Submit ulang dengan jawaban gagal. Nilai terbaik dan status Selesai tetap bertahan; submission baru tampil dalam riwayat sebagai Belum Lulus/Error.
6. Periksa waktu WITA, urutan lima hasil terbaru, tombol Lanjutkan/Kerjakan Kembali, navigasi keyboard, lalu logout/login. Akun lain harus menampilkan datanya sendiri.

Langkah browser yang benar-benar dijalankan mencakup registrasi/login, Run Code menggunakan Pyodide nyata, Submit dengan grader sengaja tidak tersedia (503), kembali ke Dashboard tanpa progres palsu, pembacaan fixture DB untuk hasil lulus/gagal/error, kuis BAB nyata, logout/login, dan isolasi akun. **Hasil sukses browser berasal dari fixture database uji, bukan eksekusi Docker.** Jalur sukses controller diuji terpisah dengan `Http::fake` pada tes Laravel.

| Kondisi fixture | Ponsel 390px | Desktop 1440px |
| --- | --- | --- |
| Kosong | [Screenshot](evidence/coding-dashboard-2026-11-05/empty-390.png) | [Screenshot](evidence/coding-dashboard-2026-11-05/empty-1440.png) |
| Progres 50% | [Screenshot](evidence/coding-dashboard-2026-11-05/half-390.png) | [Screenshot](evidence/coding-dashboard-2026-11-05/half-1440.png) |
| Progres 100% dan riwayat | [Screenshot](evidence/coding-dashboard-2026-11-05/complete-390.png) | [Screenshot](evidence/coding-dashboard-2026-11-05/complete-1440.png) |

**F. Batasan dan rekomendasi**

- Docker Engine tidak tersedia pada sesi ini. Tidak mengklaim pengujian sukses grader Docker/produksi end-to-end. Setelah Engine tersedia, jalankan kembali integrasi `tests/browser/coding-grader.mjs` sesuai `docs/coding.md` menggunakan database uji terpisah.
- Pada eksperimen pembesaran **font root 200%**, area tabel nilai kuis lama melebar sampai 1499px pada viewport 1440px. Kedua section Coding baru tetap memiliki `scrollWidth === clientWidth` (1050px). Temuan lama ini didokumentasikan tanpa merombak dashboard; eksperimen font root ini bukan pengujian zoom browser penuh. Data elemen yang melebar tersedia di `results.json`.
- Tidak ditemukan horizontal overflow pada dashboard saat ukuran teks normal di viewport 320, 360, 390, 768, 1024, dan 1440px yang diuji. Belum diverifikasi pada perangkat fisik, Firefox/Safari, atau screen reader.
- Dua tes baseline dan konfigurasi OpenSSL sebaiknya diperbaiki pada pekerjaan terpisah.
- Tidak menambahkan WebSocket, polling, library frontend, atau penyimpanan nilai di localStorage. Pemuatan server biasa cukup untuk memperbarui Dashboard.

**G. Status Git**

Perubahan aplikasi terbatas pada service, Blade Dashboard, dan CSS Dashboard. Perubahan tes, fixture browser, laporan, serta bukti tercantum pada bagian B. Working tree menyimpan perubahan untuk ditinjau; **tidak ada commit atau push otomatis**. Artefak server, database uji, cache view, dan snapshot baseline berada di `storage/framework/testing/` yang diabaikan Git. Server QA sementara dihentikan setelah verifikasi.

**Alasan desain dan Delivery Gate antislop**

Design Read: dashboard ruang belajar OOP Python untuk mahasiswa, tenang dan fungsional dengan mint–teal OOPy. **ENERGY 2 / RHYTHM 2 / MOTION 1**, mengikuti `DESIGN.md`. Ringkasan pembelajaran tetap menjadi fokus awal; progres Coding hadir setelah daftar nilai agar urutan dashboard tetap konsisten.

| Keputusan | Alasan |
| --- | --- |
| Token warna dan Plus Jakarta Sans yang sudah ada | Konsisten dengan identitas dan keterbacaan dashboard |
| Dua baris latihan dengan status dan aksi | Membandingkan dua tugas yang setara tanpa membuat galeri kartu baru |
| Riwayat sebagai daftar terpisah | Memisahkan ringkasan per latihan dari kronologi percobaan |
| Grid tiga kolom, dua kolom, lalu satu kolom | Menjaga hubungan judul/nilai/aksi pada desktop dan ruang sentuh di ponsel |
| Progress bar dengan label, persentase, dan jumlah selesai | Memperjelas penyelesaian dua latihan bagi pengguna visual maupun pembaca layar |
| Status berteks dengan warna yang sudah dikenal | Kelulusan/error tidak bergantung pada warna saja |
| Tanpa ikon, efek, atau animasi baru | Data dan tindakan belajar menjadi perhatian utama |

Gate berikut **PASS untuk tambahan UI Coding dan kondisi verifikasi yang dinyatakan**, bukan sertifikasi seluruh UI lama. Temuan tabel kuis saat font root 200% tetap dicatat di bagian F.

| Hard Gate | Bukti |
| --- | --- |
| R-02 PASS | Pemisah judul latihan dan tanda nilai kosong mengikuti teks yang secara eksplisit diminta pengguna |
| R-03 PASS | 18 sampel Coding pada enam lebar tanpa overflow, tombol minimal 44px; screenshot ditinjau |
| R-17 PASS | Semua persentase/nilai akun dihitung dari DB, tanpa statistik promosi |
| R-18 PASS | Tidak menambahkan testimoni |
| R-23 PASS | Logo/navigasi lama dipertahankan; tidak membuat aset identitas baru |
| R-24 PASS | Tujuan Coding memakai route existing dan dibuka melalui browser |
| R-25 PASS | 106 sampel teks bagian baru memenuhi ambang; rasio terendah 5,10:1 |
| R-26 PASS | Link latihan, empty state, dan riwayat diuji ke halaman yang nyata |
| R-27 PASS | Empty state jelas; data dirender server; error resmi tetap ditolak dan label Error tersedia pada riwayat |
| R-28 PASS | Tidak menambahkan FAQ |
| R-32 PASS | Fokus terlihat dan Enter membuka aksi latihan; regresi menu/keyboard lulus |
| R-33 PASS | Source diedit langsung; script fixture hanya mengisi database testing |
| R-34 PASS | Memakai tema terang existing tanpa toggle tema tambahan |
| R-35 PASS | Aplikasi dijalankan pada server terpisah; klik, keyboard, Pyodide, dan screenshot diverifikasi |
| R-36 PASS | Tidak menambahkan klaim keamanan/performa/produksi tanpa bukti |
| R-37 PASS | Arah dari DESIGN dan dial 2/2/1 dinyatakan sebelum UI diubah |
| R-38 PASS | Fitur menampilkan data tersimpan; fixture hanya pada database QA dan diberi keterangan pada laporan |

| Purpose-Gate | Bukti |
| --- | --- |
| R-01 PASS | Tidak menambah gradient/glow |
| R-04 PASS | Tidak menambah ikon generik |
| R-06 PASS | Font dan skala heading dashboard dipakai untuk kesinambungan |
| R-07 PASS | Tidak menambah pola latar |
| R-08 PASS | Tidak menambah panah dekoratif pada tombol baru |
| R-09 PASS | Label status menyatakan hasil aktual, tanpa badge promosi |
| R-10 PASS | Tidak menambah glassmorphism |
| R-12 PASS | Memakai panel dashboard existing; tidak menambah elevasi bertumpuk |
| R-13 PASS | Tidak menambah glow |
| R-14 PASS | Baris setara mewakili dua latihan setara; riwayat mempunyai komposisi berbeda |
| R-19 PASS | Tidak menambah animasi; browser diuji dengan reduced motion |
| R-22 PASS | Tidak menambah ilustrasi |

| Liveliness | Bukti |
| --- | --- |
| Dial PASS | ENERGY 2 / RHYTHM 2 / MOTION 1 tertulis dan sesuai arah |
| Konsistensi PASS | Ringkasan, baris latihan, dan kronologi memiliki hierarki berbeda |
| Fokus PASS | Ringkasan BAB tetap fokus pertama; progres Coding dominan pada section sendiri |
| Whitespace PASS | Jarak/garis memisahkan ringkasan, dua latihan, dan riwayat |
| Aksen PASS | Teal dipakai pada progress bar dan aksi; status mempunyai warna semantik |
| Identitas PASS | Judul Class/Object/Inheritance dan alur latihan/Submit khas praktikum OOPy |
| Design Read PASS | Arah dinyatakan sebelum patch UI dan dicatat pada laporan |

| Craftsmanship dan Quality Locks | Bukti |
| --- | --- |
| C-1 PASS | Alasan warna, font, komposisi, grid, dan status tertulis |
| C-2 PASS | Semua aksi baru memiliki tujuan nyata dan diuji |
| C-3 PASS | Dua section memenuhi kebutuhan ringkasan dan riwayat dari brief |
| C-4 PASS | Bagian Coding lolos kosong/terisi/error, enam lebar, keyboard, dan pembesaran teks root 200% |
| C-5 PASS | Data produksi dari DB; bukti fixture dijelaskan sebagai QA |
| R-05 PASS | Memperluas komposisi dashboard existing dengan baris dan daftar, tanpa template landing page |
| R-11 PASS | Radius panel/kontrol/status existing berbeda menurut fungsinya |
| R-15 PASS | Mulai Latihan, Lanjutkan Latihan, dan Kerjakan Kembali menyatakan tindakan |
| R-16 PASS | Copy menyatakan progres, nilai, dan hasil tanpa istilah promosi |
| R-20 PASS | Isi merujuk latihan OOP Python dan status penilaian sebenarnya |
| R-21 PASS | Tema terang mengikuti DESIGN |
| R-29 PASS | Token mint–teal/netral dan warna status existing |
| R-30 PASS | Mengikuti komponen OOPy, bukan menyalin produk lain |
| R-31 PASS | Setiap keputusan visual utama mempunyai alasan pada tabel desain |
