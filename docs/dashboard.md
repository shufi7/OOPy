# Dashboard Pengguna OOPy

Dashboard pembelajaran berada di **GET /dashboard**, route name `dashboard`,
dengan middleware `web`/`auth`. Guest diarahkan ke Login dan intended URL
tetap digunakan oleh login existing. User maupun admin melihat data akun
sendiri; tidak ada Dashboard Admin atau statistik lintas-pengguna.
Redirect default login/registrasi tetap `/materi` seperti sebelumnya.

## Integrasi yang ditemukan

Kuis BAB 1–6 sudah dinilai oleh `QuizGradingService`, menyimpan `quiz_attempts`,
lima `quiz_answers`, dan `user_progress` secara transaksional. Kelulusan
completed bersifat monotonic; retry gagal tidak mencabut kelulusan pertama.
Perbaikan timestamp MariaDB juga sudah diterapkan. Dashboard memakai hasil
tersebut, tanpa menambahkan penilaian/endpoint kuis kedua atau mempercayai
localStorage. Live Coding dan Evaluasi Akhir belum terintegrasi ke akun,
sehingga hasil keduanya tidak masuk statistik/aktivitas Dashboard.

## Sumber data dan performa

`DashboardController::index` menerima pengguna dari session request, lalu
memanggil `DashboardProgressService::forUser`. Parameter URL/body `user_id`
atau `role` tidak dipakai. Halaman bersifat read-only dan memakai
`Cache-Control: no-store, private` agar data akun tidak tersaji dari cache bersama.
Nama dan email profil akun ditampilkan dengan escaping Blade, termasuk nama
panjang atau teks HTML. Profil hanya memuat name/email/role yang tersedia pada
model User; tidak membuat NIM, kelas, foto, atau data profil dummy.

Metadata/urutan enam BAB inti memakai `resources/materi/chapters.php`; entri
yang mempunyai `quiz` pada konten existing masuk perjalanan belajar.
Tidak ada daftar judul/slug lain di view. `Chapter` dan `Material` memetakan
identitas database; material dipilih dengan slug yang sama seperti integrasi kuis.
Deskripsi berasal dari chapter DB, atau poin registry jika katalog belum di-seed.

Service memakai relasi `User.progress()` dan `User.quizAttempts()` agar scope
kepemilikan melekat pada query. Attempt di-join ke Quiz untuk membatasi
`type = chapter_quiz` dan chapter yang ada dalam registry inti. Model/relasi
existing tidak diubah. Dashboard tidak mengambil soal, kunci, jawaban per soal,
password, remember_token, atau seluruh riwayat attempt ke memori.

Pada katalog yang sudah di-seed, terdapat enam query SELECT:

1. Chapter inti.
2. Eager-load material chapter.
3. Progres material akun.
4. Aggregate MAX(score) dan COUNT(*) per chapter.
5. Chapter dengan attempt aktif akun.
6. Lima attempt selesai terbaru.

Jumlah query tidak bertambah per kartu/attempt (tidak ada N+1). Katalog kosong
tetap menampilkan enam kartu dari registry dengan empty state; tidak membuat
atau menyemai data otomatis. Tabel existing tetap harus sudah di-migrate.
Ketika tidak ada chapter DB, query eager-load material tidak perlu dijalankan.

## Definisi statistik dan status

| Informasi | Definisi |
| --- | --- |
| BAB Selesai | Jumlah material inti dengan user_progress.status completed dan completed_at terisi untuk akun session. |
| Rata-rata Nilai Kuis | Rata-rata nilai terbaik tiap BAB yang mempunyai attempt selesai valid, dibulatkan satu desimal. |
| Nilai terbaik | MAX(score) dari attempt completed untuk chapter_quiz pada BAB terkait. |
| Total Kuis Dikerjakan | COUNT attempt completed valid pada enam BAB inti, termasuk latihan ulang. |
| Progres Pembelajaran | round(BAB completed / jumlah BAB inti × 100); registry saat ini berisi enam BAB. |

Attempt valid untuk statistik/aktivitas mempunyai status completed, nilai
non-NULL dalam 0–100, waktu completion terisi dan tidak sebelum started_at.
Attempt in_progress/expired, nilai NULL dan Evaluasi Akhir tidak dihitung.
BAB tanpa nilai tidak dimasukkan sebagai nol ke rata-rata. Nilai nol dari
kuis yang benar-benar selesai tetap dihitung sebagai nilai sah. Jika tidak
ada nilai, UI menampilkan **—** / **Belum ada nilai kuis**.

Persentase 0/1/2/3/4/5/6 BAB selesai adalah 0/17/33/50/67/83/100%.
Progressbar menggunakan aria-valuenow/min/max/valuetext, tanpa animasi berulang.

Kartu **Selesai** mengikuti user_progress completed yang sah.
**Sedang Dipelajari** mengikuti progres in_progress, attempt aktif server,
atau attempt selesai yang belum menghasilkan progres completed.
Tanpa bukti tersebut, status **Belum Dimulai**. Membaca halaman materi saja
tidak mengubah status. Dashboard tidak menulis progress/attempt baru.

Aktivitas terbaru hanya lima attempt completed valid milik akun, diurutkan
completed_at lalu id secara descending. Hasil berupa nilai dan label Lulus/
Belum Lulus, memakai ambang 4/5 (80) dari config/quiz.php yang sama. Label ini
merangkum nilai tersimpan, bukan menjalankan grading ulang. Timestamp asli
ditampilkan dalam WITA (Asia/Makassar), dengan datetime ISO pada elemen time.

## Rekomendasi dan navigasi

Hero memilih BAB pertama menurut urutan registry yang belum completed.
Pengguna tanpa progres diarahkan ke BAB 1 dengan **Mulai Belajar BAB 1**.
Jika BAB pertama sedang dikerjakan atau ada BAB sebelumnya yang selesai,
tombol menjadi **Lanjutkan BAB N**. Completion yang tidak berurutan tetap
merekomendasikan BAB paling awal yang belum selesai. Setelah enam BAB selesai,
hero mengarah ke `evaluasi.index` dengan **Lanjut ke Evaluasi Akhir**.

Kartu BAB menyediakan Mulai Belajar/Lanjutkan Belajar/Pelajari Kembali sesuai
status. Semua enam BAB tetap dapat diakses. Section Evaluasi Akhir hanya
menyediakan navigasi dan deskripsi, tanpa menampilkan skor browser sebagai
hasil akun. Kebijakan URL materi publik dan navigasi bawah BAB tidak berubah.

Navbar di halaman publik tetap seperti sebelumnya dan menampilkan link Dashboard
untuk akun login. Pada Dashboard, slot app-navigation di layouts.app menampilkan
header OOPy dan sidebar khusus: Belajar, Dashboard (aria-current), Profil Saya
(anchor #profil), Informasi (anchor #informasi), dan form POST Keluar dengan CSRF.
Tidak ada menu Pengaturan yang mengarah ke route kosong.

## Tampilan dan empty state

View memakai layouts.app dengan susunan mengikuti referensi: sidebar kiri dan
header ringkas, panel progres di atas, lalu Data Profil dan Daftar Nilai
berdampingan. Enam BAB ditampilkan sebagai baris tabel dengan nilai terbaik,
status, dan aksi belajar. Ringkasan statistik ada di bawah tabel; rekomendasi
belajar ada pada bagian bawah profil. Aktivitas dan informasi Evaluasi Akhir
dikelompokkan dalam disclosure yang bisa dibuka. Data baru menampilkan 0/6, rata-rata —, total kuis 0,
progress 0%, rekomendasi BAB 1 dan penjelasan aktivitas kosong beserta CTA.
Tidak ada nama, nilai, progres atau aktivitas dummy pada halaman aplikasi.

`public/css/oopy/dashboard/dashboard.css` dimuat hanya pada Dashboard melalui
push styles. Selector tetap scoped ke Dashboard, dengan token brand base.css
dan variable warna khusus Dashboard sesuai perubahan visual yang diminta.
Tidak ada perubahan global typography/CSS materi/quiz/sidebar. Font mengikuti
Plus Jakarta Sans dan logo OOPy tetap dipertahankan. Latar utama abu-abu terang,
panel putih, CTA biru, menu aktif/kelulusan hijau, status berjalan amber,
ikon nilai ungu dan tombol Keluar merah. Tema halaman belajar tidak berubah.
Profil/daftar nilai mempunyai dua kolom pada >=1200px dan menumpuk di bawahnya.
Sidebar tetap pada desktop >=992px; pada mobile menjadi menu yang bisa dibuka
dengan tombol header. Tabel nilai berubah menjadi kartu berlabel pada <576px.
CTA/tautan aktivitas mempunyai area sentuh minimal 44px. oopy-dashboard.js hanya
mengendalikan sidebar: aria-expanded, inert saat ditutup, backdrop, Escape,
focus, dan perubahan viewport. Tidak mengubah nilai/progres atau localStorage.
Tanpa JavaScript, menu mobile tetap tampil sebagai blok navigasi yang dapat
dibaca, bukan disembunyikan. Avatar berupa inisial akun, tanpa gambar/upload baru.

## File implementasi

```text
app/Http/Controllers/DashboardController.php       (baru)
app/Services/DashboardProgressService.php          (baru)
resources/views/dashboard/index.blade.php          (baru)
resources/views/dashboard/partials/navigation.blade.php
resources/views/components/dashboard-icon.blade.php
public/css/oopy/dashboard/dashboard.css            (baru)
public/js/oopy-dashboard.js
tests/Feature/DashboardTest.php                    (baru)
tests/browser/dashboard.mjs                       (baru)
docs/dashboard.md                                 (baru)
routes/web.php                                    (route Dashboard)
resources/views/components/navbar.blade.php        (link authenticated)
resources/views/layouts/app.blade.php              (slot app-navigation)
```

Dokumentasi authentication diperbarui untuk mencatat bahwa Dashboard kini
tersedia. Tidak ada perubahan model/migration/.env, fitur grading, isi materi,
Monaco/Pyodide, checker, atau engine Evaluasi Akhir.

## Menjalankan pemeriksaan

```powershell
php artisan test --compact
node --test tests/js/*.test.js
php artisan route:list
php artisan migrate:status
php vendor/bin/pint --test app/Http/Controllers/DashboardController.php app/Services/DashboardProgressService.php routes/web.php tests/Feature/DashboardTest.php
git diff --check
node tests/browser/dashboard.mjs
```

Feature tests memakai SQLite in-memory. Browser test harus diarahkan ke server
testing yang mempunyai katalog hasil migration/seed; default URL
http://127.0.0.1:8021, dapat diganti melalui OOPY_BASE_URL. Script membuat akun
acak dan mengerjakan kuis lewat UI; jangan gunakan database produksi/akun nyata.
Pengujian workspace memakai SQLite file terpisah melalui env proses, tanpa
mengubah `.env` aplikasi. OOPY_SCREENSHOT_DIR opsional menyimpan screenshot.
OOPY_ASSET_CACHE dapat menunjuk cache Bootstrap/font asli untuk CDN terbatas.

## Batasan dan pengembangan berikutnya

### Verifikasi — 9 Oktober 2026

| Pemeriksaan | Hasil |
| --- | --- |
| php artisan test --compact | 116 test lulus, 2598 assertions; termasuk 18 kasus Dashboard. |
| node --test tests/js/*.test.js | 19 test lulus untuk runtime/checker Live Coding dan Evaluasi Akhir existing. |
| Dashboard read-only/performa | Enam SELECT pada katalog seeded; lazy-loading dicegah dalam test; tidak ada query soal/jawaban atau write. |
| Koneksi lokal MariaDB, driver mysql | Service Dashboard berhasil membaca enam kartu dengan enam SELECT; identitas/nilai akun tidak dicetak. Tidak ada data lokal ditulis. |
| Route list | GET /dashboard tersedia dengan auth; total 19 route. |
| Migration status | Seluruh 15 migration existing Ran; tidak ada migration Dashboard. |
| Pint pada PHP pekerjaan | Lulus. |
| git diff --check dan sintaks browser script | Lulus. |
| Edge headless, responsif | Empty/progress/completed/nama panjang lulus pada 320/390/768/1024/1440px; grid 1/2/3 kolom, area sentuh >=44px, tanpa overflow. Screenshot diperiksa. |
| Browser alur nyata | Register → kuis gagal → lulus BAB 1/2 → rata-rata best 90 → retry gagal tetap completed → enam BAB selesai → rekomendasi Evaluasi Akhir lulus. |
| Browser keamanan/persistence | Logout/login, akun kedua tetap kosong, nama HTML panjang di-escape, semua tautan HTTP 200, dan no-JS reading lulus. |
| Browser aktivitas | Riwayat aggregate asli, maksimal lima terbaru, waktu WITA, serta pengecualian hasil lokal berhasil diperiksa. |
| Browser console/page errors | Tidak ada error pada Dashboard. |

Browser memakai SQLite terpisah di storage/framework/testing/dashboard-verification,
bukan database akun lokal. Bootstrap/font menggunakan cache aset asli; CDN
lain dapat diakses pada proses browser pengujian yang diizinkan. Tidak ada
perubahan CDN/dependensi aplikasi. Peringatan OpenSSL dimuat dua kali berasal
dari konfigurasi PHP lingkungan dan tidak menggagalkan pengujian.

### Cakupan berikutnya

Pembaruan layout mengikuti screenshot referensi Dashboard pada 9 Oktober 2026.
Tampilan Pengaturan dalam referensi dipakai sebagai acuan visual, tanpa membuat
fitur edit profil, NIM/kelas, upload foto, atau penghapusan akun pada pekerjaan
layout ini. Seluruh 116 test PHP lulus sesudah restrukturisasi layout; 18 kasus
Dashboard diperiksa kembali setelah penyesuaian label profil (147 assertions).
Browser empty/progress/completed/nama panjang lulus pada lima viewport, termasuk
sidebar mobile, Escape, collapse desktop, no-JS reading dan logout sidebar.
Hasil grid 1/2/3 kartu pada tabel verifikasi sebelumnya adalah layout awal;
layout terbaru memakai profil/tabel dua kolom dan baris nilai responsif.
Penyempurnaan berikutnya mengikuti permintaan warna yang lebih bervariasi:
latar abu-abu, panel putih, tombol belajar biru, kelulusan/menu aktif hijau,
status berjalan amber dan Keluar merah. Progres dibuat lebih ringkas,
rekomendasi dipindahkan ke profil, statistik ke bagian bawah tabel, serta
aktivitas/informasi ke disclosure agar profil dan nilai tetap menjadi fokus.
Verifikasi terbaru: 18 test Dashboard lulus (147 assertions), browser seluruh
empat state pada 320/390/768/1024/1440px lulus tanpa overflow, dan membuka
aktivitas, sidebar, logout, isolasi akun serta no-JS reading tetap berfungsi.

Tidak ada Dashboard Admin, manipulasi role, grafik bisnis, badge dekoratif,
nilai Evaluasi Akhir lokal, atau riwayat simulasi Live Coding. Dashboard
mengasumsikan foundation tabel existing sudah dimigrasikan. Jika belum ada
progres/attempt yang tersimpan, empty state tetap valid. Kuis server sudah
terintegrasi pada workspace ini; tidak ada impor kelulusan dari localStorage.

Tahap berikutnya dapat menambahkan detail riwayat dengan pagination dan
ownership yang sama, kemudian integrasi exercise_submissions dan Evaluasi Akhir
dengan penilaian server masing-masing. Jangan menjadikan skor/localStorage
browser sebagai hasil resmi. Tidak ada commit atau push otomatis.

Referensi query/eager loading:
[relasi Eloquent Laravel 12](https://laravel.com/docs/12.x/eloquent-relationships#aggregating-related-models).
