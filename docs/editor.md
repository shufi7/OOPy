# Live Coding OOPy

Halaman `/editor` mengikuti referensi tampilan belajar: sidebar materi, tujuan
pembelajaran, file explorer, tab editor gelap, Run Code / Reset / Submit, dan output.
Pada ponsel sidebar dibuka melalui tombol menu dan tab file bisa digeser.

## Menjalankan

Dari direktori project dengan dependency Composer dan `.env` yang sudah tersedia:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Buka **http://127.0.0.1:8000/editor**, atau klik **Editor** di navbar.
Tidak perlu menjalankan Vite atau menginstal dependency frontend baru.
Koneksi internet diperlukan untuk CDN editor, Python, font, dan Bootstrap.

## File

| File | Fungsi |
| --- | --- |
| `app/Http/Controllers/EditorController.php` | Mengembalikan halaman editor. |
| `resources/views/editor/index.blade.php` | Sidebar belajar, workspace, output, catatan materi, hasil pemeriksaan. |
| `public/css/oopy-editor.css` | Tampilan khusus editor dan aturan responsif. |
| `public/js/editor/oopy-editor.js` | Satu instance Monaco, empat model, navigasi, aksi, status dan timeout. |
| `public/js/editor/starter-project.js` | Kode awal `ekosistem.py`, `sungai.py`, `rawa.py`, `main.py`. |
| `public/js/editor/python-worker.js` | Memuat Pyodide, menulis file virtual, menjalankan kode dan meneruskan output. |
| `public/js/editor/checks.py` | Sebelas pemeriksaan perilaku class/object untuk feedback latihan. |

File lama yang berubah:

- `routes/web.php`: route `GET /editor` bernama `editor.index`.
- `resources/views/components/navbar.blade.php`: tautan Editor dan active state.
- `resources/views/layouts/app.blade.php`: slot sidebar opsional dan class halaman;
  Beranda serta Materi tetap memakai navbar dan footer yang sama.
- `resources/views/materi/index.blade.php`: ID pada kartu bab untuk tujuan navigasi.

## Runtime

- [Monaco Editor](https://github.com/microsoft/monaco-editor) **0.52.2**, build AMD
  dari jsDelivr; dipin untuk integrasi tanpa bundler pada layout Blade yang ada.
- [Pyodide](https://pyodide.org/en/0.27.7/usage/webworker.html) **0.27.7**, distribusi
  `https://cdn.jsdelivr.net/pyodide/v0.27.7/full/`, dimuat di Web Worker.
- Bootstrap 5.3.3, Bootstrap Icons 1.11.3, dan Plus Jakarta Sans dari layout existing.

Run Code selalu menjalankan `main.py`, terlepas dari tab aktif. Keempat file ditulis
ke `/workspace` di filesystem virtual Pyodide; import Python bekerja secara nyata.
Cache modul milik workspace serta bytecode dibersihkan sebelum setiap Run / Submit.
Setiap operasi menggunakan globals baru. Kode Python tidak dikirim untuk dieksekusi
oleh Laravel.

Ctrl+Enter (Cmd+Enter pada macOS) menjalankan program. Submit menguji class dan
object; tidak membandingkan source code. Progres sidebar berasal dari persentase
pemeriksaan yang lulus, bukan progres seluruh kursus. Perubahan kode membatalkan
hasil pemeriksaan sebelumnya.

Reset mengembalikan semua file ke starter code dan meminta konfirmasi jika kode
berubah. Kode tersimpan selama halaman terbuka; navigasi keluar memberi peringatan
browser jika ada perubahan. Belum ada penyimpanan kode atau skor ke database.
Skor merupakan feedback latihan di browser, bukan penilaian tepercaya untuk ujian.

Eksekusi dibatasi 10 detik. Tombol Hentikan atau timeout menghentikan worker lalu
memuat ulang Python tanpa menghapus kode di editor. Output dibatasi 100.000 karakter
agar loop `print()` tidak membebani halaman. `input()` interaktif belum didukung.
Jika CDN Python gagal, gunakan Coba lagi. Jika Monaco gagal dimuat, muat ulang halaman.

## Verifikasi

```powershell
php artisan test
php vendor/bin/pint --test --dirty
node --check public/js/editor/oopy-editor.js
node --check public/js/editor/python-worker.js
git diff --check
```

Skenario browser yang perlu tetap bekerja:

1. Buka `/editor`, tunggu status Python siap dan empat tab dapat digunakan.
2. Edit satu file, pindah tab, lalu kembali: kode tetap ada.
3. Run Code menampilkan Sungai Barito / Banjarmasin dan Rawa Bangkau / Hulu Sungai Selatan.
4. Ganti status Sungai, jalankan lagi: output menggunakan perubahan terbaru.
5. Ubah import pada `rawa.py` menjadi modul yang tidak ada: traceback menyebut file.
6. Reset lalu Submit: sebelas pemeriksaan lulus, skor 100.
7. Hilangkan inheritance Sungai: pemeriksaan gagal dengan petunjuk yang spesifik.
8. Jalankan loop tanpa akhir: worker dihentikan setelah 10 detik dan bisa dipakai lagi.
9. Cek lebar 390, 768, 940, dan 1440 piksel, termasuk sidebar mobile dan navigasi keyboard.
10. Buka Beranda dan Materi: navbar, konten, dan footer tetap tampil.
