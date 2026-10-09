# Authentication OOPy

Authentication menggunakan guard session `web` Laravel 12 dan model `User`
existing. Tidak ada starter kit baru, tabel users kedua, migration tambahan,
atau perubahan `.env`. Cast password `hashed`, default role `user`, validasi
role, dan relasi progress/exerciseSubmissions/quizAttempts tetap dipertahankan.

## Route dan akses

| Method | URL | Nama route | Middleware | Perilaku |
| --- | --- | --- | --- | --- |
| GET | /register | register | guest | Form registrasi. |
| POST | /register | register.store | guest | Validasi, buat akun user, login otomatis. |
| GET | /login | login | guest | Form login. |
| POST | /login | login.store | guest | Validasi dan autentikasi email/password. |
| POST | /logout | logout | auth | Logout, invalidasi session, kembali ke Beranda. |

Seluruh route di atas memakai grup `web`, termasuk middleware CSRF Laravel.
Tidak ada GET logout. Guest yang mengakses endpoint dengan `auth` diarahkan
ke `/login`; pengguna authenticated yang mengakses login/register diarahkan
ke `/materi`. Redirect middleware dikonfigurasi di `bootstrap/app.php`.

Halaman `/`, `/materi`, `/materi/{slug}`, `/materi/evaluasi-akhir`,
`/materi/evaluasi-akhir/ujian`, `/materi/evaluasi-akhir/hasil`, dan `/editor`
tetap publik. Middleware role siap digunakan, tetapi tidak ada halaman admin
atau dashboard baru. Route admin pada feature test hanya hidup di proses test.

## Registrasi

`RegisterRequest` memvalidasi nama wajib/string/maksimal 255 karakter,
email wajib/valid/maksimal 255/unik, dan password wajib/string/confirmed
dengan `Password::min(8)`. Tidak ada aturan kompleksitas tambahan.
`RegisteredUserController` mengambil hanya nama, email, dan password yang
tervalidasi, menetapkan role `user` secara eksplisit, lalu menyimpan model.
Cast `hashed` Laravel melakukan hashing password.

Input `role=admin` dari body atau query string diabaikan. Role tidak termasuk
`$fillable` maupun field form. Registrasi tidak menerima email_verified_at,
remember_token, atau field akun lainnya dari request. Akun baru langsung login
dengan `Auth::guard('web')->login()`, session diregenerasi, intended URL lama
dihapus, kemudian redirect `/materi` dengan pesan berhasil.

## Login dan pembatasan percobaan

`LoginRequest` memvalidasi email/password dan memakai
`Auth::guard('web')->attempt()`. Email tidak dikenal dan password salah
menghasilkan pesan sama: **Email atau password tidak sesuai.** Email tetap
tersedia melalui `old()`, sedangkan password tidak dipertahankan dalam flash
input maupun nilai HTML.

`RateLimiter` Laravel membatasi lima kegagalan per kombinasi email/IP dalam
60 detik. Email pada key limiter disamakan huruf kecil; key di-hash. Ada pula
batas 30 kegagalan per IP dalam 60 detik untuk percobaan berganti email.
Percobaan ketika terkunci tidak menjalankan `Auth::attempt()`; termasuk jika
password sudah benar. Halaman menampilkan sisa waktu tunggu dekat email.
Request HTML kembali ke form dengan flash error; request JSON menerima 429.
Login berhasil membersihkan penghitung email/IP, tanpa menghapus penghitung
IP bersama. Semua batas menggunakan cache Laravel yang dikonfigurasi aplikasi;
gunakan penyimpanan cache bersama yang persisten pada deployment multi-instance.

Session diregenerasi setelah login berhasil. Tujuan awal dari session
`url.intended` dipakai jika berupa path internal atau URL dengan scheme, host,
dan port yang sama dengan request. URL eksternal, protocol-relative, kredensial
URL, backslash, dan karakter kontrol ditolak; fallback adalah `/materi`.
Request tidak dapat memilih tujuan melalui parameter form `redirect`.

## Logout dan session

Logout adalah form POST dengan `@csrf` dan middleware `auth`. Controller memanggil:

```php
Auth::guard('web')->logout();
$request->session()->invalidate();
$request->session()->regenerateToken();
```

Pengguna kembali ke `/`. Data session lama dibuang, ID diganti, dan token CSRF
diregenerasi. Session cookie lama tidak dapat dipakai kembali untuk login.
Cookie/session mengikuti `config/session.php` existing; tidak ada autentikasi
JavaScript atau penyimpanan password/token/session ID di localStorage.
Gunakan HTTPS dan konfigurasi secure cookie sesuai environment deployment.

## Role authorization

`EnsureUserHasRole` terdaftar sebagai alias `role` di `bootstrap/app.php`.
Gunakan middleware server pada endpoint akun berikutnya:

```php
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Daftarkan endpoint admin di tahap implementasi berikutnya.
});
```

Guest diarahkan ke login, user mendapat HTTP 403, dan admin diizinkan.
Beberapa role dapat dipakai sebagai `role:user,admin`. Pemeriksaan memakai
role pada model pengguna yang telah diautentikasi, bukan request, navbar,
JavaScript, atau localStorage. Menu navbar hanya menampilkan identitas akun;
penyembunyian menu tidak memberikan authorization.

## Promosi admin melalui terminal tepercaya

Daftarkan akun biasa dengan password pribadi terlebih dahulu. Developer atau
administrator yang mempunyai akses terminal server kemudian menjalankan:

```powershell
php artisan oopy:promote-admin <email-akun-terdaftar>
```

Command memvalidasi email, menolak akun yang tidak ditemukan, menunjukkan
identitas akun untuk diperiksa, dan meminta konfirmasi (default **tidak**).
Setelah dikonfirmasi, hanya role akun tersebut berubah menjadi admin. Password
tidak berubah dan tidak ditampilkan. Akun yang sudah admin tidak diubah ulang.
Dalam mode noninteraktif tanpa jawaban konfirmasi, promosi dibatalkan.

Command ini adalah tindakan administratif tepercaya, bukan endpoint publik.
Batasi akses shell/server pada administrator. Tidak ada akun admin default,
password tetap, seeder akun, opsi registrasi admin, atau route pengubah role.
Jangan menambahkan role ke `$fillable` atau menyalin request ke `forceFill()`.

## Tampilan dan navbar

Login/Register memakai layout utama OOPy, logo dan Plus Jakarta Sans existing,
serta token mint/aqua/teal/dark dari `base.css`. Card maksimal 460px. CSS form
berada di `public/css/oopy/auth/auth.css` dan dimuat melalui `@push('styles')`
hanya pada halaman auth. Input dan tombol menggunakan font minimal 16px,
label, autocomplete, serta error yang terhubung dengan `aria-describedby`.

Komponen `auth-field` menampilkan field bersama. Toggle password pada
`public/js/oopy-auth.js` hanya mengubah visibilitas input, dengan button,
`aria-controls`/`aria-pressed`/label dinamis. Tombol tersembunyi sampai JavaScript
aktif; form tetap dapat digunakan tanpa JavaScript. Password tidak dirender
kembali setelah validasi gagal.

Guest melihat Beranda/Materi/Masuk/Daftar; pengguna login melihat
Beranda/Materi dan dropdown nama, role, serta form Logout. Nama di-escape oleh
Blade. Placeholder Dashboard dihapus. `/editor` tetap tersedia publik.
Collapse navbar Bootstrap existing dipertahankan dan styling menu akun berada
di `navbar.css`; tidak ada perubahan pada CSS pembelajaran/sidebar/editor.

## File implementasi

```text
app/Console/Commands/PromoteUserToAdmin.php
app/Http/Controllers/Auth/
    AuthenticatedSessionController.php
    RegisteredUserController.php
app/Http/Middleware/EnsureUserHasRole.php
app/Http/Requests/Auth/
    LoginRequest.php
    RegisterRequest.php
resources/views/auth/
    login.blade.php
    register.blade.php
resources/views/components/auth-field.blade.php
public/css/oopy/auth/auth.css
public/js/oopy-auth.js
tests/Feature/Auth/
    AdminPromotionTest.php
    LoginTest.php
    LogoutTest.php
    RegistrationTest.php
    RoleAuthorizationTest.php
docs/authentication.md
```

File existing yang disesuaikan: `bootstrap/app.php`, `routes/web.php`,
`resources/views/layouts/app.blade.php`,
`resources/views/components/navbar.blade.php`, dan `public/css/oopy/navbar.css`.

## Pengujian

```powershell
php artisan test --compact
node --test tests/js/*.test.js
php artisan route:list
php artisan migrate:status
php vendor/bin/pint --test app/Http/Controllers/Auth app/Http/Requests/Auth app/Http/Middleware/EnsureUserHasRole.php app/Console/Commands/PromoteUserToAdmin.php bootstrap/app.php routes/web.php tests/Feature/Auth
git diff --check
```

Feature test memakai SQLite in-memory sesuai `phpunit.xml`. Pengujian CSRF
mengaktifkan verifier asli secara khusus karena Laravel melewati CSRF secara
default pada feature test. Pengujian logout memeriksa session lama di handler
serta replay cookie. Pengujian role memakai endpoint sementara di dalam test.
Promosi admin hanya diuji pada akun fixture di SQLite.

### Hasil verifikasi — 9 Oktober 2026

| Pemeriksaan | Hasil |
| --- | --- |
| Feature auth terpisah | 30 test lulus, 337 assertions. |
| Seluruh `php artisan test --compact` | 73 test lulus, 2062 assertions, termasuk regresi BAB 1–7 dan editor. |
| `node --test tests/js/*.test.js` | 19 test lulus, mencakup checker/runtime coding dan evaluasi existing. |
| `php artisan route:list --no-ansi` | 15 route; lima route auth dengan method yang benar, route belajar tetap publik. |
| `php artisan migrate:status --no-ansi` | Seluruh 14 migration existing berstatus Ran; tidak ada migration baru untuk auth. |
| Pint pada seluruh PHP pekerjaan | Format diperbaiki dan pemeriksaan `--test` lulus. |
| `git diff --check` | Lulus. |
| Browser Chrome melalui Playwright | Login/Register lulus pada 320, 390, 768, 1024, 1440px: tanpa overflow, font input 16px, card maksimal 460px, toggle password bekerja. |
| Alur browser mobile/desktop | Register, role user, pesan berhasil, login salah/generic error, old email, login valid, session cookie berubah, redirect guest, menu akun, POST logout, dan replay cookie lama lulus pada 390/1440px. |

Browser memakai database SQLite pengujian terpisah di folder storage yang
diabaikan Git. Migration hanya dijalankan pada database SQLite baru itu;
database OOPy lokal hanya dibaca untuk status migration. CDN diblokir oleh
sandbox browser, sehingga Bootstrap 5.3.3 dan font Plus Jakarta Sans asli
diunduh ke cache pengujian lalu disajikan lewat interception Playwright.
Referensi CDN aplikasi tidak diubah. Screenshot tersedia di
`storage/framework/testing/auth-verification/` pada workspace verifikasi ini.
Peringatan OpenSSL dimuat dua kali berasal dari konfigurasi PHP lingkungan
dan tidak menggagalkan pemeriksaan. Tidak ada commit atau push otomatis.

## Batasan dan tahap berikutnya

Tahap ini mencakup authentication session dan fondasi authorization saja.
Dashboard pembelajaran kini tersedia di GET /dashboard dengan middleware auth,
dan hanya membaca progres akun session; lihat [dashboard.md](dashboard.md).
Lupa password, verifikasi email, OAuth, dan pembatasan URL materi belum
diimplementasikan. Kuis BAB 1–6 sekarang memakai endpoint `auth`, policy
ownership, penilaian server, dan progres database; lihat
[quiz-progress.md](quiz-progress.md). Materi tetap dapat dibaca guest, sedangkan
pengumpulan kuis resmi memerlukan login. Live Coding dan BAB 7 belum diintegrasikan.
Database lokal tidak menerima akun test/admin.

Integrasi berikutnya untuk Live Coding/BAB 7 perlu endpoint dengan `auth`,
validasi ownership/policies, dan penilaian server. Jangan menerima role, nilai,
kelulusan, atau `is_correct` dari browser sebagai sumber otoritatif. Rencanakan
pemetaan progres localStorage, versioning soal, kebijakan akses materi, dan
penilaian uraian sebelum menghubungkan tabel belajar ke UI existing.

Referensi framework:
[authentication Laravel 12](https://laravel.com/docs/12.x/authentication),
[rate limiting](https://laravel.com/docs/12.x/rate-limiting), dan
[middleware aliases](https://laravel.com/docs/12.x/middleware#middleware-aliases).
