# Live Coding OOPy

`/editor` adalah **Live Coding Component Demo / Development Playground**. Halaman
ini memakai komponen yang sama dengan halaman materi: Python dasar (1 file), kelas
dan objek (2 file), serta contoh pewarisan prototype sebelumnya (4 file, 11 check).
Tidak ada perubahan pada database, autentikasi, homepage, atau halaman materi.

## Arsitektur

```text
Laravel: config latihan -> <x-live-code :config="$latihan" />
Browser: LiveCode instance A/B/C -> RuntimeManager (antrean FIFO)
                                    -> satu Web Worker -> satu Pyodide
         Monaco loader sekali -> editor dan model tersendiri per komponen
```

| File | Tanggung jawab |
| --- | --- |
| `app/View/Components/LiveCode.php` | Validasi konfigurasi developer. |
| `resources/views/components/live-code.blade.php` | Explorer, tab, editor, aksi, output, feedback, progres latihan; aset dimuat sekali lewat stack Blade. |
| `resources/live-code/demos.php` | Starter code dan checker tiga demo, terpisah dari engine. |
| `public/css/oopy/live-code/live-code.css` | Gaya prototype yang dibatasi ke `.oopy-live-code`, termasuk tampilan mobile. |
| `public/js/live-code/live-code.js` | Inisialisasi komponen, pemeriksaan ID duplikat, peringatan saat meninggalkan kode yang berubah. |
| `public/js/live-code/live-code-instance.js` | Model, tab, dirty indicator, shortcut, Reset, output dan skor satu latihan. |
| `public/js/live-code/monaco-loader.js` | Promise loader bersama dan tema Monaco 0.52.2 dari jsDelivr. |
| `public/js/live-code/runtime-manager.js` | Worker tunggal, antrean, routing pesan, timeout, Stop, retry. |
| `public/js/live-code/python-worker.js` | Pyodide 0.27.7, validasi payload, batching dan pembatasan output. |
| `public/js/live-code/project-runner.py` | Runner generik di browser: filesystem, globals, import, checker dan cleanup. Bukan checker global. |

Versi Monaco/Pyodide dipertahankan dari prototype. Tidak diperlukan Vite untuk
komponen ini. Referensi API runtime:
[Pyodide 0.27.7 JavaScript API](https://pyodide.org/en/0.27.7/usage/api/js-api.html).

Setiap eksekusi menulis file ke `/workspaces/<id>/`, dengan globals baru, entry
file sebagai `__main__`, serta direktori project dan entry pada `sys.path`.
Selesai atau error, runner membersihkan file sementara, modul yang baru di-import,
cache import, serta memulihkan path, argv, streams, builtins dan environment.
Bytecode dimatikan agar edit berukuran sama tidak menggunakan cache lama.
Project lain tidak mewarisi file buatan program atau modul latihan sebelumnya.

Runtime hanya memproses satu request sekaligus. Editor lain dapat diedit atau
mengantre Run/Submit. Editor yang mengantre atau berjalan dikunci agar hasil cocok
dengan snapshot kode yang dikirim. Output dan feedback dirutekan berdasarkan ID
request. Menunggu antrean atau CDN tidak mengurangi batas eksekusi 10 detik.

Stop pada antrean hanya membatalkan request tersebut. Stop pada request aktif atau
timeout mengakhiri worker, lalu memuat satu runtime pengganti. Antrean lain tetap
dilanjutkan, model dan output editor lain tetap tersimpan. Jadi Pyodide dimuat
sekali pada penggunaan normal, dan dimuat ulang bila worker harus dihentikan.

## Membuat latihan single-file

Siapkan array di controller atau file data PHP, lalu kirim ke view:

```php
$latihan = [
    'id' => 'variabel-1',
    'title' => 'Nama Ekosistem',
    'description' => 'Isi nama dengan Rawa Bangkau.',
    'entry_file' => 'main.py',
    'files' => [
        'main.py' => "nama = 'Rawa Bangkau'\nprint(nama)\n",
    ],
    'checker' => "assert nama == 'Rawa Bangkau', 'Periksa nilai nama.'",
];

return view('materi.contoh', compact('latihan'));
```

```blade
@extends('layouts.app')
@section('content')
    <p>Penjelasan materi sebelum latihan.</p>
    <x-live-code :config="$latihan" />
    <p>Penjelasan materi setelah latihan.</p>
@endsection
```

Layout harus menampilkan `@stack('styles')` di head dan `@stack('scripts')` sebelum
akhir body; `layouts.app` sudah menyediakan keduanya. Komponen memakai font dan
Bootstrap Icons dari layout OOPy, tanpa memerlukan body class atau sidebar khusus.

## Membuat latihan multi-file dan entry berbeda

```php
$latihanObjek = [
    'id' => 'objek-1',
    'title' => 'Membuat Class Ekosistem',
    'description' => 'Buat objek dari modul ekosistem.',
    'entry_file' => 'mulai.py',
    'files' => [
        'ekosistem.py' => <<<'PYTHON'
class Ekosistem:
    def __init__(self, nama):
        self.nama = nama
PYTHON,
        'mulai.py' => <<<'PYTHON'
from ekosistem import Ekosistem
objek = Ekosistem("Rawa Bangkau")
print(objek.nama)
PYTHON,
    ],
    'checker' => 'assert isinstance(objek, Ekosistem)',
];
```

Gunakan `<x-live-code :config="$latihanObjek" />`. Menambahkan file hanya memerlukan
entri pada `files`; explorer, tabs dan model otomatis mengikuti urutan array.
Tidak ada batas empat file. Package seperti `habitat/__init__.py` dan
`habitat/rawa.py` juga didukung. Run selalu menjalankan `entry_file`, bukan tab aktif.

Aturan config:

- `id` wajib unik pada halaman, diawali huruf; selanjutnya huruf, angka, `-`, `_`.
- `files` wajib array tidak kosong, isi setiap file berupa string. Path relatif
  `.py` memakai huruf ASCII, angka, `_`, `-`, dan `/` untuk direktori. Import Python
  tetap mengikuti aturan nama modul Python. Path absolut dan `..` ditolak.
- `entry_file` default `main.py`, wajib tersedia dalam `files`.
- `title` default `Live Coding`; `description` default kosong. Judul dan tugas
  ditampilkan terpisah pada header aktivitas sebelum editor.
  `description` tetap teks yang di-escape Blade; apit nama variabel/nilai/metode
  dengan backtick agar dirender sebagai inline `<code>`. HTML tidak dirender.
- `checker` berupa source Python, default kosong. Submit dinonaktifkan jika kosong.
- Config diserialisasi sebagai JSON yang aman untuk elemen script, bukan `eval`.

Untuk banyak latihan, render komponen beberapa kali dengan ID berbeda. Developer
tidak perlu menyentuh worker, engine, loader Monaco, atau implementasi Pyodide.

## Membuat checker

Submit menjalankan entry file terlebih dahulu. Jika program gagal, traceback
ditampilkan dan checker tidak dijalankan. Setelah program berhasil, checker mendapat
salinan globals entry: variabel, objek dan class yang di-import. Checker juga boleh
melakukan import dari file lain dalam project.

Cara sederhana: tulis `assert` berdasarkan perilaku objek. Semua assert berhasil
menghasilkan satu hasil lulus; `AssertionError` menghasilkan satu hasil gagal dengan
pesan assert. Eksekusi berhenti pada assert pertama yang gagal.

```python
assert nama == "Rawa Bangkau", "Periksa nilai nama."
```

```python
from sungai import Sungai
from ekosistem import Ekosistem
assert issubclass(Sungai, Ekosistem), "Sungai harus mewarisi Ekosistem."
```

```python
import inspect
from ekosistem import Ekosistem
assert inspect.isabstract(Ekosistem), "Ekosistem harus berupa abstract class."
```

Untuk beberapa hasil terpisah, isi variabel `results` dengan list berisi 1–200 dict:

```python
results = []

def check(label, operation, hint):
    try:
        passed = bool(operation())
        results.append({"label": label, "passed": passed,
                        "feedback": "" if passed else hint})
    except Exception as error:
        results.append({"label": label, "passed": False,
                        "feedback": f"{hint} ({type(error).__name__}: {error})"})

check("Objek Ekosistem", lambda: isinstance(objek, Ekosistem), "Buat instance Ekosistem.")
check("Nama objek", lambda: objek.nama == "Rawa Bangkau", "Periksa atribut nama.")
```

`label` dan `feedback` harus string, `passed` harus boolean. `results` kosong atau
format salah menghasilkan error checker, bukan skor 100%. Error selain assert
ditampilkan sebagai `Checker Python Error` beserta traceback. Skor adalah persentase
hasil yang lulus dan akan dihapus jika kode diedit atau di-reset. Contoh Pewarisan
di `/editor` mempertahankan 11 pemeriksaan perilaku dari prototype. Latihan BAB 4
(`bab4-pewarisan-ekosistem`) menggunakan delapan pemeriksaan inheritance,
inisialisasi, super(), overriding dan data instance. Latihan BAB 5
(`bab5-polimorfisme-sensor`) menggunakan tujuh pemeriksaan class, perilaku
status(), list dan loop. Keduanya memakai `main.py`, starter belum lengkap,
feedback per pemeriksaan, Run/Submit/Reset dan engine existing yang sama.

Checker BAB 4 mengamati pemanggilan super().__init__ yang benar-benar dijalankan,
termasuk nilai yang diinisialisasi pada object; source yang hanya memuat super()
dalam komentar atau cabang mati tidak lulus. BAB 5 membaca source dari file
workspace virtual, menggunakan AST untuk memeriksa percabangan tipe dan mengamati
status() saat replay program dalam namespace terpisah. Replay tidak menambahkan
output ke terminal. Alias list, enumerate, comprehension, helper dan implementasi
status() melalui inheritance diterima jika keempat object memenuhi kontrak.
Status diperiksa pada object mahasiswa sehingga constructor dengan argument
tidak dipaksa menjadi constructor tanpa argument.
Detail dan hasil pengujian terkini ada di [dokumentasi materi](materials.md).

## Fitur dan keterbatasan

- Ctrl+Enter / Cmd+Enter menjalankan latihan yang fokus. Tab file mendukung panah,
  Home dan End; dirty indicator, posisi/cursor per tab, serta konfirmasi Reset tetap ada.
- Output stdout/stderr dibatasi 100.000 karakter dan dikirim dalam batch. Traceback
  juga dibatasi; feedback checker dibatasi panjangnya agar tidak membanjiri DOM.
- `input()` interaktif belum didukung; gunakan variabel pada starter code.
- Internet diperlukan untuk CDN. Kegagalan Python menyediakan Coba lagi; kegagalan
  Monaco meminta reload. Batas pemuatan masing-masing 90 detik.
- Tidak ada penyimpanan kode/skor, database, autentikasi, atau progres kursus.
  Reset dan meninggalkan halaman memberi peringatan bila kode berubah.
- Pemeriksaan tersedia di browser dan bukan penilaian tepercaya untuk ujian.
  Workspace cleanup mengisolasi latihan normal, bukan sandbox keamanan terhadap
  kode yang sengaja memodifikasi internal interpreter/modul bersama atau memakai
  bridge JavaScript. Worker tidak menjalankan kode pada server Laravel/PHP.
- Tidak ada pemasangan package otomatis, stdin interaktif, pengelolaan file baru
  lewat UI, atau dukungan mount/unmount komponen dinamis pada navigasi SPA.
- Banyak editor memakai model Monaco sendiri; runtime bersama menghemat pemuatan
  Python, tetapi jumlah model tetap memengaruhi penggunaan memori browser.

## Verifikasi

```powershell
php artisan serve --host=127.0.0.1 --port=8017
php artisan test
node --test tests/js/*.test.js
Get-ChildItem public/js/live-code/*.js | ForEach-Object { node --check $_.FullName }
php vendor/bin/pint --test --dirty
git diff --check
```

Tes Laravel memeriksa route, tiga komponen, ID unik, aset sekali, serialisasi JSON,
entry custom, enam file, dan validasi config. Tes Node memeriksa antrean dan routing,
pembatalan, failure/retry, serta timeout tanpa harus memuat CDN.
Test Node checker BAB 4/5 juga memerlukan PHP dan Python lokal (PATH atau
`OOPY_PHP`/`OOPY_PYTHON`); test memakai fixture tepercaya dan direktori temporer,
bukan eksekusi kode pengguna pada server aplikasi.

Tes browser memakai Monaco dan Pyodide asli. Instal Playwright sebagai alat lokal
pengujian (tidak diperlukan oleh aplikasi), kemudian jalankan server di atas:

```powershell
npm install --no-save --package-lock=false playwright
$env:OOPY_BROWSER = 'msedge' # atau chrome; browser harus terpasang
node tests/browser/live-code.mjs
```

URL default `http://127.0.0.1:8017`; ubah melalui `OOPY_BASE_URL`. Tes mencakup A Run,
B Run, A edit, C Submit, A Reset; checker gagal; syntax/import error; package dan
entry custom; cache import; output limit; Stop/timeout dan pemulihan antrean;
keyboard; lebar 390/768/940/1440; serta kegagalan CDN dan retry. CDN harus dapat diakses.
Suite yang sama juga membuka BAB 4/5, menguji starter dan solusi salah, solusi
benar/alternatif 100%, output Python, feedback/persentase, Reset serta satu worker
dan loader per halaman.

Pemeriksaan manual tambahan: baca instruksi dan feedback dengan pembaca layar,
pastikan konfirmasi Reset/navigasi muncul setelah perubahan, dan buka Beranda serta
Materi untuk memastikan layout global tetap normal.

Verifikasi integrasi 8 Oktober 2026: 27 tes Laravel (853 assertions), 8 tes Node
(6 runtime manager dan 2 suite checker, 29 skenario), serta seluruh suite browser
materi/sidebar/kuis/Live Coding/visual lulus di Edge headless dengan CDN asli.
Starter BAB 4 mendapat 25%, BAB 5 mendapat 29%; solusi benar dan alternatif
mendapat 100%. Sintaks JavaScript, `git diff --check`, serta Pint `--test --dirty`
juga lulus. Rincian perintah dan keterbatasan lingkungan dicatat dalam
[hasil verifikasi materi](materials.md).

BAB 6 memakai `bab6-kelas-abstrak-alat-pantau` dengan satu main.py dan sembilan
check: ABC asli, abstract method, penolakan instansiasi, dua relasi inheritance,
subclass konkret, hasil baca() bermakna/berbeda, list object dan loop yang dijalankan.
Nama list bebas; constructor dengan argument, helper, enumerate, comprehension
dan implementasi yang diwarisi tetap diterima jika kontrak terpenuhi. Starter
valid secara sintaks mendapat 56%; solusi benar/alternatif mendapat 100%.
Instruksi sembilan langkah tampil sebelum editor, dengan eksplorasi TypeError
dan pemulihan baca() sesudah editor. Engine/worker/editor existing dipertahankan.

Verifikasi implementasi BAB 6: 29 tes Laravel (1011 assertions), 10 tes Node
(55 skenario checker dan empat contoh materi), seluruh lima perintah browser,
sintaks JavaScript, Pint --test --dirty dan git diff --check lulus. Rincian
eksekusi serta batasan sumber DOCX tersedia di dokumentasi materi tersebut.
