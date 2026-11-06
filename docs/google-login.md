# Login Google OOPy

Integrasi menggunakan Laravel Socialite, sesi OAuth `state`, dan PKCE. Kredensial hanya dibaca dari environment server. `.env` existing tidak diubah oleh implementasi ini.

## Aktivasi

1. Buka [Google Cloud Console](https://console.cloud.google.com/auth/overview), pilih atau buat project, lalu konfigurasi Google Auth Platform: Branding, Audience, dan akses data identitas dasar (`openid`, `profile`, `email`). Bila aplikasi masih dalam mode Testing, tambahkan akun penguji pada Audience.
2. Pada Clients, buat OAuth client jenis **Web application**. Daftarkan Authorized redirect URI yang persis sama dengan URL aplikasi ditambah `/auth/google/callback`, termasuk skema, host, port, dan subdirektori bila ada.
3. Isi environment server menggunakan Client ID dan Client Secret yang dibuat Google. Jangan commit nilai rahasia:

   ```dotenv
   GOOGLE_CLIENT_ID=client-id-dari-google
   GOOGLE_CLIENT_SECRET=client-secret-dari-google
   GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
   ```

   URL contoh cocok untuk `php artisan serve` pada port 8000. Sesuaikan URL bila memakai Apache/XAMPP. Gunakan HTTPS untuk deployment publik.

4. Setelah mencadangkan database sesuai prosedur deployment, jalankan migration tambahan. Perintah ini menambahkan satu kolom nullable beserta unique index; tidak mereset tabel atau akun:

   ```powershell
   php artisan migrate --path=database/migrations/2026_11_06_000001_add_google_id_to_users_table.php
   ```

   Jika deployment menggunakan cache konfigurasi, perbarui dengan `php artisan config:cache` setelah mengisi environment.

5. Buka halaman Masuk atau Daftar dan pilih **Masuk dengan Google**. Uji akun baru, masuk ulang, pembatalan izin, dan logout menggunakan akun penguji Google yang nyata.

## Perilaku akun

- Akun Google baru dibuat sebagai `user` dengan password acak. Identitas login disimpan menggunakan ID Google yang stabil, bukan email sebagai satu-satunya identitas.
- Akun pembelajar existing dengan Gmail atau Google Workspace terverifikasi dapat memakai akun yang sama; password dan progresnya tetap dipertahankan.
- Akun Admin existing tidak ditautkan otomatis berdasarkan email. Akun tersebut tetap dapat memakai login password. Akun yang sudah memiliki ID Google tetap memakai role yang tersimpan; profil Google tidak dapat menaikkan role.
- Email pihak ketiga yang sudah terdaftar atau benturan identitas diminta memakai password OOPy. Hal ini menghindari penggabungan akun hanya berdasarkan email yang tidak lagi dikelola Google.
- Email Google yang belum terverifikasi ditolak. `state` yang salah atau callback berulang juga ditolak.
- Access token dan refresh token tidak disimpan pada tabel pengguna ataupun dikirim ke halaman.
- Tanpa kredensial, tombol Google menampilkan pesan bahwa login Google belum tersedia; login password tetap berfungsi.
- Rollback migration ditolak jika masih terdapat akun dengan ID Google agar akses masuknya tidak hilang tanpa rekonsiliasi.

Pengujian otomatis memakai respons Google terisolasi, tanpa akun atau kredensial asli. Login ke Google secara langsung baru dapat diverifikasi setelah kredensial dikonfigurasi.

Referensi: [Laravel Socialite](https://laravel.com/docs/12.x/socialite), [Google OAuth untuk aplikasi server](https://developers.google.com/identity/protocols/oauth2/web-server), dan [panduan verifikasi identitas Google](https://developers.google.com/identity/sign-in/web/backend-auth).
