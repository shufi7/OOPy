# Redesign Dashboard Pengguna OOPy

Redesign `/dashboard` selesai pada 19 November 2026. Implementasi mengikuti
brief pengguna dan `DESIGN.md`, menggunakan antislop mode During serta panduan
UI, komentar, aksesibilitas, dan layout mobile. Arah visual: ruang belajar yang
tenang, jelas dan terstruktur, ENERGY 2 / RHYTHM 2 / MOTION 1.

Audit mencakup view dashboard dan navigasi, CSS lama, JavaScript sidebar,
controller, DashboardProgressService, CodingProgressService, pengujian PHP dan
browser, komponen ikon, layout/global CSS, dokumentasi, dan rencana UX induk.
Screenshot awal diambil dari aplikasi lokal sebelum kode tampilan diubah.

Masalah awal yang diperbaiki:

- Profil meregang setinggi tabel, avatar terlalu besar, dan nama muncul dua
  kali di dalam profil yang sama.
- Rekomendasi belajar berada di bawah profil, jauh dari ringkasan progres.
- Jumlah BAB selesai muncul di dua area dan bersaing dengan informasi nilai.
- Kolom nomor tabel mengulang identitas BAB, sementara judul dan status
  memerlukan ruang lebih banyak.
- Coding dan riwayat mengambil dua panel selebar halaman secara berurutan.
- Sidebar memakai banyak lapisan gradient, panel, border, dan shadow yang
  bersaing dengan konten.

| Keputusan desain | Alasan fungsional |
| --- | --- |
| Ringkasan progres dan rekomendasi pada panel atas | Progres dan aksi berikutnya dapat dibaca dalam satu area keputusan. |
| Rata-rata kuis dan jumlah kuis sebagai baris ringkas | Metrik tetap tersedia tanpa kartu statistik berulang. |
| Profil compact dengan avatar 52px dan satu nama | Identitas tetap jelas tanpa ruang kosong akibat stretching. |
| Grid profil/tabel menggunakan `align-items: start` | Tinggi masing-masing panel mengikuti isi. |
| Tabel empat kolom dengan identitas BAB dan materi digabung | Memberi ruang bagi judul panjang, nilai, status dan aksi. |
| Baris tabel ditata vertikal di bawah 768px | Nilai dan tombol tetap terbaca tanpa lima kolom kecil atau scroll halaman. |
| Coding dan riwayat berdampingan mulai 1200px | Mengurangi panjang halaman tanpa menyamakan tinggi kedua panel. |
| Evaluasi Akhir di disclosure informasi | Ringkasan nilai tetap tersedia sebagai informasi sekunder. |
| Teks `#243B39` dan `#40514E`, mint dan teal OOPy | Mengikuti brief sambil meningkatkan hierarki dan kontras. |
| Warna dashboard melalui alias lokal | Global design tokens dan halaman lain tetap memakai aturan sebelumnya. |
| Plus Jakarta Sans dan ikon komponen yang sudah tersedia | Mempertahankan identitas OOPy tanpa aset atau dependensi baru. |
| Permukaan putih, border tipis, radius sesuai token | Membatasi panel berdasarkan fungsi tanpa semua elemen terlihat mengambang. |
| Bayangan bawah pada menu aktif dan tombol utama | Mempertahankan kedalaman 3D halus pada pilihan aktif dan aksi utama. |
| Transisi 160ms hanya pada interaksi/disclosure | Memberi feedback singkat; reduced motion mematikan transisi. |
| Navbar bersama Beranda dan toolbar sidebar terpisah | Logo, tujuan menu dan menu akun selalu berasal dari komponen yang sama; sidebar tetap punya kontrol sendiri. |

| File | Perubahan |
| --- | --- |
| `resources/views/dashboard/index.blade.php` | Susunan ringkasan/rekomendasi, profil, tabel, Coding, riwayat, dan Evaluasi Akhir. |
| `resources/views/dashboard/partials/navigation.blade.php` | Memakai `components.navbar` bersama Beranda, Materi dan Coding; toolbar dan sidebar tetap tersedia. |
| `public/css/oopy/dashboard/dashboard.css` | Tata letak, tipografi, state, warna lokal, responsive dan reduced motion dalam namespace dashboard. |
| `public/js/oopy-dashboard.js` | Navigasi Profil/Informasi mengatur menu aktif, fokus, scroll dan disclosure, termasuk klik berulang serta Back/Forward; mekanisme sidebar dan logout dipertahankan. |
| `tests/browser/dashboard.mjs` | Delapan viewport, screenshot tablet, dan pemeriksaan profil tidak meregang. Seluruh ekspektasi perilaku lama dipertahankan. |
| `tests/browser/dashboard-coding.mjs` | Cakupan viewport diperluas menjadi delapan; seluruh pemeriksaan hasil dan privasi dipertahankan. |
| `tests/browser/dashboard-accessibility.mjs` | Kontras, tabel semantik, fokus, navigasi, disclosure, sidebar, logout cancel, text resize dan reduced motion. |
| `tests/browser/dashboard-navbar.mjs` | Kesamaan navbar empat halaman, tujuan menu, akun/logout, sinkronisasi dua menu ponsel dan offset header pada delapan viewport. |
| `docs/evidence/dashboard-redesign-2026-11-19/` | Screenshot dan log pengujian. |

Semua binding tetap memakai data backend asli. Atribut `data-dashboard`,
`data-dashboard-chapter`, `data-dashboard-coding`, aksi continue, dan anchor
`#profil`/`#informasi` dipertahankan. Nilai kosong tetap ditampilkan sebagai
tanda pisah dari formatter backend, sedangkan nilai 0 tetap 0. Tanda baca pada
judul latihan/rekomendasi tetap mengikuti data yang diminta pengguna.

Tidak ada perubahan controller, service, route, database/migration,
autentikasi/role, perhitungan progres/nilai, penyimpanan kuis, atau Coding Grader.
Tidak ada commit/push. Pengujian browser memakai SQLite disposable terpisah
dengan fixture existing; tes PHP memakai SQLite memory. Database utama tidak
diubah. Akun, email dan nilai pada screenshot berasal dari akun QA tersebut.

| Screenshot | Sebelum | Sesudah |
| --- | --- | --- |
| Desktop 1440px | [Sebelum](evidence/dashboard-redesign-2026-11-19/before-1440.png) | [Sesudah](evidence/dashboard-redesign-2026-11-19/after-1440.png) |
| Tablet 768px | [Sebelum](evidence/dashboard-redesign-2026-11-19/before-768.png) | [Sesudah](evidence/dashboard-redesign-2026-11-19/after-768.png) |
| Ponsel 390px | [Sebelum](evidence/dashboard-redesign-2026-11-19/before-390.png) | [Sesudah](evidence/dashboard-redesign-2026-11-19/after-390.png) |

Screenshot perbandingan memakai akun dan data yang sama: dua BAB selesai,
satu BAB dengan nilai 0, Coding 50%, dan riwayat terisi. Ketiga ukuran ditinjau
secara visual, termasuk setelah perbaikan terakhir pada ukuran teks/status.
Screenshot tambahan akun kosong, progres sebagian, semua BAB selesai, dan nama
panjang tersimpan lokal di `storage/framework/testing/dashboard-redesign-browser`.

| Pengujian | Hasil |
| --- | --- |
| `php artisan test --filter='DashboardTest\|CodingTest\|CodingMultifileTest\|LoginTest\|LogoutTest\|RoleAuthorizationTest'` | PASS, 84 tes / 1182 assertion. [Log](evidence/dashboard-redesign-2026-11-19/php-tests.txt). |
| `node tests/browser/dashboard.mjs` | PASS, data kuis nyata, akun kosong/sebagian/selesai/nama panjang, nilai 0 dan kosong, rekomendasi, seluruh link, logout/login, isolasi akun dan pembacaan tanpa JS. [Log](evidence/dashboard-redesign-2026-11-19/dashboard-browser.txt). |
| `node tests/browser/dashboard-coding.mjs` | PASS, Coding 0/50/100%, belum dimulai/belum lulus/selesai, nilai 0/kosong, latest five, waktu WITA, route latihan, privasi, dan grader tidak tersedia tidak memberi progres palsu. [Log](evidence/dashboard-redesign-2026-11-19/coding-browser.txt). |
| `node tests/browser/dashboard-accessibility.mjs` | PASS, sidebar, inert, Escape/backdrop, fokus, disclosure, logout cancel, pembesaran teks, navigasi nyata dan header cache. Setelah navbar disatukan, kontras minimal seluruh teks termasuk navbar adalah 4,82:1. [Log terbaru](evidence/dashboard-redesign-2026-11-19/dashboard-shared-navbar-accessibility.txt). |
| Viewport | PASS pada 320, 360, 390, 768, 1024, 1366, 1440 dan 1600px. |
| Pembesaran teks | PASS 200% pada delapan viewport, termasuk batas posisi seluruh tombol yang terlihat. |
| `npm.cmd run build` | PASS, Vite membangun 58 modul. |
| `node --check` pada JavaScript yang diubah/ditambahkan dan `git diff --check` | PASS. |

Pada pemeriksaan 200%, toggle menu awalnya keluar viewport. Perbaikan dilakukan
pada responsivitas topbar dan diuji ulang tanpa mengurangi pemeriksaan batas
kontrol. Kontras diperiksa pada teks yang terlihat, termasuk panel informasi
terbuka serta sidebar; pengujian Coding juga memeriksa ketiga statusnya.

Delivery Gate:

| Gate | Hasil dan bukti |
| --- | --- |
| R-03, responsif | PASS, delapan viewport dan text resize 200% tanpa overflow atau kontrol keluar layar. |
| R-17/R-18/R-23/R-36/R-38, konten dan aset | PASS, seluruh nilai/status dinamis, aset existing, tanpa statistik, gambar atau aktivitas buatan di produksi. |
| R-24/R-26/R-35, interaksi | PASS, logo/Beranda membuka home, Belajar membuka katalog, continue dengan Enter membuka rekomendasi, dan aksi Coding membuka kedua latihan. |
| R-25, kontras | PASS, teks seluruh dashboard termasuk navbar bersama minimal 4,82:1; status juga memiliki label teks. |
| R-27, state | PASS, empty state kuis/Coding, nilai kosong dan 0, progres sebagian/selesai, serta error grader diverifikasi. Dashboard dirender server; tidak ada fetch/loading baru. |
| R-32, keyboard | PASS, fokus terlihat, disclosure Enter/Space, Escape/sidebar, inert dan pemulihan fokus saat logout dibatalkan. |
| R-33/R-34, implementasi dan tema | PASS, sumber diedit langsung, tema terang mengikuti brief, tanpa toggle atau tema baru. |
| R-37, arahan | PASS, brief dan DESIGN.md menjadi acuan sebelum implementasi. |
| Purpose Gate | PASS, alasan warna, layout, font, spacing, ikon, panel, shadow dan motion tercatat di atas. |
| Liveliness | PASS, ENERGY 2 / RHYTHM 2 / MOTION 1; hierarki BAB, nilai kuis, rekomendasi dan latihan Python memberi identitas pendidikan OOPy. |
| Craftsmanship | PASS, review visual tiga ukuran, backend regression, browser nyata, dan header `Cache-Control: no-store, private` diverifikasi. |

Batas verifikasi: browser yang diuji adalah Edge headless dengan aset CDN asli
dari cache QA. Audit screen reader menyeluruh dan browser lain belum dijalankan.
Grading Docker nyata tidak diaktifkan dalam pekerjaan frontend ini; tes browser
memakai fixture hasil resmi dan endpoint grader yang tidak tersedia, sedangkan
tes PHP memakai respons grader terkontrol. Redesign tidak mengaktifkan service
grader lokal yang sebelumnya belum berjalan.

Untuk menjalankan ulang browser, gunakan server/database QA terisolasi.
`dashboard-coding.mjs` memerlukan `DB_CONNECTION=sqlite` dan nama database
`coding-dashboard-<32-hex>.sqlite`, diinisialisasi dengan fixture existing.
Set `OOPY_BASE_URL` ke server tersebut, `OOPY_ASSET_MANIFEST` bila memakai cache
CDN, serta opsional `OOPY_SCREENSHOT_DIR`. Tes aksesibilitas dapat memakai
`OOPY_DASHBOARD_STORAGE_STATE` untuk akun QA yang sudah memiliki progres;
tanpa nilai ini, tes mendaftarkan akun QA baru.

Perbaikan lanjutan pada menu Profil Saya dan Informasi:

- Klik menu mengaktifkan penanda lokasi di sidebar dan memindahkan fokus ke
  heading profil atau summary informasi, lalu menggulir dengan jarak aman dari
  topbar. Profil dan summary informasi memperoleh penanda batas saat dituju.
- Informasi membuka disclosure, menyelaraskan `aria-expanded` ketika panel
  ditutup manual, dan dapat dibuka lagi walau URL sudah memiliki hash yang sama.
- Pada ponsel, sidebar ditutup dan `inert` dilepas sebelum memindahkan fokus.
- Back/Forward dan reload URL `#profil`/`#informasi` mempertahankan tujuan menu.
- CSS/JS dashboard memakai versi berdasarkan waktu perubahan file agar browser
  meminta aset terbaru setelah implementasi diperbarui.

Validasi lanjutan: 33 tes PHP / 442 assertion untuk Dashboard dan Logout lulus.
Tes browser aksesibilitas memeriksa menu aktif, fokus tujuan, klik berulang,
Back/Forward, reload hash, sinkronisasi disclosure, mobile, delapan viewport
dan pembesaran teks 200%. Suite browser dashboard lama juga dijalankan ulang
dan lulus. Log tersedia pada berkas `dashboard-navigation-*.txt` di direktori
evidence, dengan screenshot `navigation-profile-1440.png` serta
`navigation-information-390.png`.

Navbar dashboard selanjutnya disatukan dengan Beranda menggunakan
`@include('components.navbar')`. Materi, Coding dan halaman publik lain sudah
memakai komponen tersebut; tidak ada salinan menu baru. Tampilan logo, warna,
ukuran, tombol akun dan tautan utama berasal dari markup/CSS bersama.
Menu aktif mengikuti route halaman. ResizeObserver menyesuaikan offset sidebar/anchor terhadap
tinggi header sebenarnya, termasuk saat navbar ponsel terbuka atau teks
diperbesar.

Validasi navbar bersama: 41 tes PHP / 518 assertion lulus untuk dashboard,
logout dan otorisasi. Browser memverifikasi kesamaan navbar Beranda, Materi,
Coding dan Dashboard; menu akun/logout, delapan viewport, fokus profil/informasi,
Back/Forward, text resize 200%, dan reduced motion. Log terbaru memakai awalan
`dashboard-shared-navbar-` di direktori evidence. Screenshot sesudah pada
390/768/1440px diperbarui dengan navbar bersama.

Atas permintaan berikutnya, tombol Menu dashboard dan seluruh baris toolbar
dihapus. Sidebar desktop selalu terlihat. Pada ponsel, Profil Saya dan
Informasi tersedia langsung di menu hamburger navbar; Materi/Belajar, Dashboard
dan Logout juga tetap dapat diakses dari navbar bersama. Tidak ada tombol
pengganti di bawah navbar atau backdrop sidebar. Anchor tetap membuka bagian
yang benar, menutup menu navbar, lalu memindahkan fokus setelah transisi selesai.
Fallback fokus logout saat berpindah dari desktop ke ponsel diarahkan ke
hamburger navbar apabila tombol sidebar sudah tidak terlihat.
