# Pembayaran sertifikat dengan Midtrans Snap Sandbox

Harga backend **24999 IDR** per sertifikat Sandbox. Seluruh pembelajaran tetap gratis. Integrasi menolak mode Production dan Server Key tanpa awalan Sandbox. Endpoint API dan SDK mengarah ke host Sandbox. Lihat [upgrade konfirmasi nama dan transkrip](certification-system.md#upgrade-24-november-2026) untuk skema, kompatibilitas, dan langkah penerapan.

**Migration pembayaran sudah diterapkan pada database development lokal setelah persetujuan pengguna pada 20 November 2026.** Status migration adalah Ran, batch 7. Sebelum penerapan, target MySQL loopback, environment local, flag Production false dan [pratinjau SQL](midtrans-sandbox-migration.sql) diperiksa. Perbandingan jumlah dan SHA256 seluruh kolom lama pada 44 tabel data existing menunjukkan hasil identik sesudah migration; tabel migration/session/cache dan tabel runtime sementara dikecualikan. Tidak ada attempt pembayaran yang dibuat. Bukti digest tanpa isi data tersimpan lokal di `storage/logs/midtrans-approved-schema-audit.json`.

Koreksi setelah penerapan: pemeriksaan prefix Client Key terlalu ketat dan memblokir panel tanpa membuktikan key tidak valid. Client Key sekarang harus terisi; prefix tertentu tidak diwajibkan dan whitespace di tepi dinormalisasi pada config. Pembatasan Server Key Sandbox, flag Production false, host API/SDK Sandbox dan validasi respons tetap berlaku. Validitas serta kecocokan Client Key akhirnya ditentukan oleh SDK Midtrans Sandbox, bukan tebakan prefix. Pemeriksaan render akun lokal kini menampilkan tombol Bayar dengan QRIS. Nilai key tidak dicetak dan `.env` tidak diubah. Pengujian merchant langsung belum dilakukan.

## Arsitektur dan pemisahan sertifikat

| Lifecycle | Program key | Order | Label dokumen |
| --- | --- | --- | --- |
| Simulasi lama | `oopy-python-oop-v1` | `mode=simulation`, `provider=simulation`, `simulation_completed` | SIMULASI / DEMO |
| Midtrans Sandbox | `oopy-python-oop-midtrans-sandbox-v1` | `mode=sandbox`, `provider=midtrans`, `paid` setelah GET Status terverifikasi | SANDBOX / DEMO |
| Production | Belum tersedia | Seluruh permintaan ditolak bila flag Production aktif | Belum tersedia |

Unique constraint pengguna/program dan order/sertifikat mencegah penerbitan ganda. Tabel `certificate_payment_attempts` menyimpan referensi Midtrans, token terenkripsi, masa berlaku, merchant, transaction ID, metode, status, waktu verifikasi, dan nama yang terkunci. Referensi baru dapat dibuat ketika backend memastikan transaksi terminal atau Snap kedaluwarsa sebelum pemilihan metode. Timeout mempertahankan referensi yang sama untuk pemeriksaan. Sertifikat simulasi lama tetap tersedia melalui tautan unduh milik akun.

`CertificateEligibilityService` memeriksa enam BAB, enam kuis, kedua Coding aktif, dan Evaluasi Akhir resmi. Kelayakan dan konfirmasi nama diperiksa saat membuat referensi baru. Tanggal penyelesaian disimpan saat order dibuat. Nama yang telah dikonfirmasi tersimpan pada order dan referensi, lalu disalin ke sertifikat saat settlement terverifikasi.

Pembuatan transaksi mengunci pengguna dan order, menyimpan referensi **sebelum** API dipanggil, lalu melepas transaksi database selama HTTP berjalan. Lease 30 detik mencegah klik ganda saat token belum diterima. Timeout tidak membuat referensi baru secara otomatis. Snap yang masih pending dibuka kembali dengan token existing. GET Status 404 sebelum pemilihan metode berarti belum diproses. Token yang melewati 24 jam dan tetap tidak memiliki transaksi Core dapat diganti dengan attempt baru, tanpa menghapus attempt lama.

Semua callback Snap (`onSuccess`, `onPending`, `onError`, `onClose`) hanya meminta pemeriksaan backend. Query `transaction_status=settlement`, finish URL, HTTP 200, dan payload JavaScript tidak dapat menandai paid. Status otoritatif berasal dari GET `api.sandbox.midtrans.com/v2/{order_id}/status` memakai Basic Auth Server Key. Backend mencocokkan referensi, nominal persis, IDR, merchant, program dan environment, lalu memeriksa status/fraud. `settlement` dengan fraud tidak ditolak, atau `capture` kartu dengan fraud `accept`, dan `status_code=200` baru dapat menerbitkan sertifikat.

Update status, penandaan paid, dan penerbitan sertifikat berada dalam transaksi dengan lock pengguna/order/attempt serta unique constraint. Notifikasi ganda mengembalikan sertifikat yang sama. Pending terlambat tidak menurunkan paid. Refund/partial refund yang diverifikasi untuk attempt pembayar mencabut unduhan; sertifikat dan order tetap disimpan, dan settlement terlambat tidak mengaktifkannya lagi. Transaksi terminal yang ambigu tidak dibatalkan atau dihapus secara otomatis. Penanganan refund/reissue lanjutan memerlukan kebijakan tersendiri.

## Konfigurasi lokal

Gunakan kredensial dari **Midtrans Sandbox**, bukan Production. Pengguna mengisi sendiri `.env` lokal; implementasi tidak mengubahnya dan tidak mencetak nilainya. Placeholder tanpa nilai ada dalam `.env.example`:

```dotenv
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_SIGNATURE_SECRET_KEY=
MIDTRANS_MERCHANT_ID=
```

`MIDTRANS_MERCHANT_ID` opsional tetapi dianjurkan: mengikat respons ke merchant Sandbox tertentu sejak verifikasi pertama. Jika kosong, respons API terautentikasi harus cocok dengan merchant notifikasi dan merchant attempt yang sudah tersimpan. Hanya Client Key yang dimasukkan ke atribut SDK frontend. Server Key dan Signature Secret Key tidak dikirim ke browser. Token Snap dan redirect URL terenkripsi di database, disembunyikan dari serialisasi model, dan hanya diberikan secara eksplisit pada respons pembuatan transaksi milik pemilik akun; endpoint status tidak memuat token.

**Signature Secret Key berbeda fungsi dengan Server Key.** [Dokumentasi Access Keys terbaru](https://docs.midtrans.com/docs/access-keys) menyatakan sejak 28 Oktober 2026, webhook memakai `SHA512(order_id + status_code + gross_amount + SignatureSecretKey)`. Implementasi sudah dikoreksi agar memakai `MIDTRANS_SIGNATURE_SECRET_KEY` dengan perbandingan constant time, menolak secret kosong dan tidak mencoba fallback ke Server Key. Server Key tetap untuk Basic Auth API Snap/GET Status. Merchant lama mungkin memiliki nilai kedua secret yang sama, tetapi kode tidak mengasumsikannya. Semua webhook tetap diperiksa ulang melalui GET Status server, meskipun signature valid. Ini perubahan Hosted Snap biasa; bukan protokol BI-SNAP.

Setelah mengubah konfigurasi sendiri, jalankan `php artisan config:clear`, lalu restart server lokal. Jangan gunakan perintah yang menampilkan seluruh konfigurasi/environment. Pertahankan APP_KEY: token terenkripsi existing bergantung pada key aplikasi.

Sesudah pengguna menyetujui migration dan database tujuan ditinjau/di-backup:

```powershell
composer install
# Jalankan hanya setelah persetujuan penerapan migration:
php artisan migrate --path=database/migrations/2026_11_20_000002_add_midtrans_sandbox_payments.php
php artisan serve --host=127.0.0.1 --port=8000
```

Tidak perlu migrate:fresh, db:wipe, reseed, atau membuat nilai lulus pada database pengguna. Migration menambah lima kolom order dan satu tabel attempt beserta FK/index; baris lama mendapat default provider simulation. Down migration menolak rollback jika masih terdapat pembayaran Sandbox/attempt agar riwayat tidak hilang.

## Mencoba QRIS Sandbox

1. Di Dashboard Midtrans, pilih environment **Sandbox**. Periksa aktivasi GoPay/QRIS dan **Settings → Snap Preferences / Payment Channels**; nama submenu dapat mengikuti versi Dashboard. Aktifkan kanal yang sesuai untuk merchant Sandbox.
2. Masuk ke OOPy menggunakan akun yang memang telah memenuhi seluruh kelayakan resmi. Dashboard → **Ambil Sertifikat** → `/sertifikasi`.
3. Periksa nama sertifikat, harga Rp24.999 pada panel **Pembayaran Sandbox**, serta tab **Sertifikat** dan **Transkrip Nilai**. Centang persetujuan nama, lalu klik **Konfirmasi Nama**.
4. Klik **Bayar dengan QRIS**, baca persetujuan, lalu **Lanjut ke Sandbox**. Snap.js memakai `uiMode: 'qr'`; `enabled_payments: ['gopay']` mengikuti dukungan QRIS pada Snap. String `qris` tidak diasumsikan sebagai kanal Snap universal. Merchant tetap harus mendukung kanal tersebut.
5. Gunakan simulator pembayaran dari dokumentasi/Dashboard Sandbox. **Jangan scan dan membayar memakai aplikasi bank nyata.** OOPy tidak membuat gambar QRIS sendiri.
6. Tutup Snap dan klik **Cek Status Pembayaran**. Pending tidak menyediakan sertifikat. Settlement terverifikasi mengubah panel menjadi **Pembayaran Berhasil (Sandbox)** dan menyediakan PDF di halaman yang sama.
7. Unduh kedua halaman PDF dalam satu file. Cocokkan nama, nomor, dan keterangan demo di bagian bawah kedua halaman, lalu pindai QR verifikasi. Verifikasi publik menampilkan status sertifikat dan identitas penerbitan.

Jika SDK tidak tersedia, kode memakai redirect HTTPS hasil API yang sudah divalidasi hanya ke `app.sandbox.midtrans.com/snap/...`; setelah popup disiapkan tautan alternatif juga tersedia. Tombol **Coba Lagi** muncul pada kondisi gagal/kedaluwarsa; server menentukan reuse atau referensi baru. Tautan **Kembali ke Sertifikasi** tersedia pada halaman verifikasi; seluruh ketentuan, pembayaran dan download tetap di satu halaman utama.

## Webhook HTTPS untuk localhost

Tidak ada tunnel yang dinyalakan otomatis. Selesaikan pengujian lokal dan persetujuan migration terlebih dahulu. Jangan mengekspos port aplikasi lengkap yang menyediakan Admin, debug, file, atau endpoint internal.

Router khusus [`scripts/midtrans-webhook-router.php`](../scripts/midtrans-webhook-router.php) menerima **hanya** POST pada `/api/payments/midtrans/notification`; path lain, file statis, dan metode lain mendapat 404 sebelum Laravel dimuat. Router memaksa debug false pada environment dan config, termasuk bila config cache ada. Tidak ada halaman exception yang dikirim oleh catch router.

Jalankan manual di terminal terpisah:

```powershell
php -S 127.0.0.1:8049 scripts/midtrans-webhook-router.php
# Setelah siap, jalankan tunnel sendiri, contoh dengan ngrok yang telah dikonfigurasi:
ngrok http http://127.0.0.1:8049
```

Pada Dashboard **Sandbox → Settings → Configuration → Payment Notification URL**, isi `https://<host-tunnel>/api/payments/midtrans/notification`. Jangan mengganti URL Production. Biarkan finish URL aplikasi mengarah ke halaman sertifikasi lokal; finish redirect tidak mengonfirmasi pembayaran. Jika menggunakan fasilitas pengujian notifikasi Dashboard, pastikan request memang berasal dari transaksi Sandbox yang dibuat aplikasi, bukan contoh order yang tidak dikenal.

Verifikasi sebelum mengirim transaksi: GET `/`, GET `/admin`, GET `/.env`, GET webhook dan POST ke path selain webhook harus 404 melalui port 8049. Payload webhook invalid harus 4xx tanpa stack trace. Database tidak diteruskan melalui tunnel. Hentikan tunnel dan server port 8049 setelah selesai serta hapus/ganti Notification URL sementara. Tanpa tunnel, pemilik akun tetap dapat meminta GET Status melalui tombol cek status; webhook publik tidak akan mencapai localhost secara langsung.

## Route

| Metode / path | Nama | Proteksi dan fungsi |
| --- | --- | --- |
| POST `/sertifikasi/payments` | `midtrans.store` | Login, CSRF, throttle 6/menit, persetujuan Sandbox, kelayakan dan nama terkonfirmasi untuk referensi baru |
| POST `/sertifikasi/payments/{publicId}/status` | `midtrans.status` | Login, CSRF, kepemilikan, throttle 12/menit; GET Status backend |
| POST `/api/payments/midtrans/notification` | `midtrans.notification` | JSON <=64 KiB, signature, GET Status, throttle 60/menit; tanpa session/login/CSRF khusus endpoint ini |
| GET `/sertifikasi` | existing | Halaman tunggal informasi/pembayaran/download; public informational, data akun hanya dari session |
| POST `/sertifikasi`, POST `/sertifikasi/checkout` | existing | 410: penerbitan simulasi langsung ditutup; tidak memanggil penerbitan lama |
| GET `/sertifikasi/{publicId}/pdf`, alias lama | existing | Login, kepemilikan, sertifikat aktif dan bukti order sesuai lifecycle |
| GET `/sertifikat/verifikasi/{publicId}` | existing | Publik; watermark Sandbox/simulasi, tanpa data pembayaran privat |

Respons privat, status, pembayaran dan error memakai no-store. CSRF tetap berlaku pada seluruh endpoint pengguna, termasuk endpoint status yang mengubah snapshot hasil verifikasi.

## Error dan troubleshooting

| Gejala | Arti / tindakan |
| --- | --- |
| 401 / 419 | Login atau CSRF kedaluwarsa. Muat ulang dan masuk kembali. |
| 422 | Persetujuan/kelayakan belum terpenuhi, payload invalid atau browser mengirim harga/status/identitas terlarang. |
| 403 webhook | Signature tidak sesuai Signature Secret Key. Gunakan key khusus dari Access Keys Sandbox, bukan Server Key baru. |
| 404 webhook/status akun | Referensi tidak dikenal atau bukan milik akun. Pastikan transaksi dibuat OOPy. |
| GET Status Midtrans 404 | Belum ada charge Core sebelum metode dipilih; tidak diperlakukan sebagai pembayaran gagal. |
| 409 | Detail order/merchant/nominal/mata uang tidak cocok, creation lease masih aktif, atau sertifikat tidak aktif. Sertifikat tidak diterbitkan. |
| 429 | Terlalu banyak percobaan. Tunggu pembatasan berakhir. |
| 502 / 503 | Respons Snap tidak valid, API timeout/error, kredensial/kanal belum siap, Production ditolak, atau migration belum dipasang. Referensi attempt tetap disimpan; gunakan retry eksplisit. |
| QRIS tidak muncul | Periksa aktivasi kanal GoPay/QRIS, Snap Preferences dan dukungan merchant Sandbox. Tidak ada QR lokal palsu atau fallback ke rekening lain. |
| Refund | Unduhan dicabut, riwayat tersimpan. Tidak ada pembayaran ulang/reissue otomatis. |

Snap create tidak memakai retry buta. GET Status memakai paling banyak dua percobaan dengan timeout 5 detik/percobaan. Kegagalan koneksi menghasilkan pesan generik; respons API mentah, header Authorization dan secret tidak dicatat oleh service. Jangan menambahkan dump request/config saat troubleshooting. Tidak ada cron atau polling background: refresh status dilakukan lewat webhook atau tindakan pengguna.

## File implementasi

| File | Perubahan |
| --- | --- |
| `config/midtrans.php`, `.env.example` | Konfigurasi terpusat dan placeholder kosong |
| `app/Services/MidtransGateway.php` | API Sandbox, Basic Auth, validasi token/redirect, signature, GET Status |
| `app/Services/MidtransPaymentService.php` | Lifecycle order/attempt, lease, validasi, locking, rekonsiliasi dan status |
| `app/Http/Controllers/MidtransPaymentController.php`, `routes/payments.php`, `routes/web.php` | Endpoint khusus pembayaran dan webhook |
| `app/Models/CertificatePaymentAttempt.php`, `app/Models/CertificateOrder.php` | Penyimpanan aman, relasi dan tanggal pembayaran |
| `app/Models/Certificate.php`, `app/Services/CertificateIssuanceService.php` | Jalur issuance Sandbox terpisah dan validasi download |
| `app/Http/Controllers/CertificationController.php` | Halaman tunggal, kedua lifecycle dan penutupan POST simulasi |
| `database/migrations/2026_11_20_000002_add_midtrans_sandbox_payments.php` | Penambahan struktur pembayaran tanpa menghapus riwayat |
| `resources/views/certification/index.blade.php`, `layout.blade.php`, `partials/payment-form.blade.php` | Panel QRIS Sandbox, status, konfirmasi dan SDK |
| `resources/views/certification/partials/preview.blade.php`, `pdf.blade.php`, `verify.blade.php` | Label dokumen mengikuti lifecycle |
| `public/js/oopy-certification.js`, `public/css/oopy/certification.css` | Snap/redirect, status server, loading/error, tombol 3D dan fokus existing |
| `scripts/midtrans-webhook-router.php` | Router opsional untuk tunnel webhook saja |
| `tests/Feature/MidtransPaymentTest.php`, `tests/Feature/CertificationTest.php` | Regresi pembayaran dan kompatibilitas sertifikat lama |
| `tests/browser/midtrans.mjs`, `midtrans-sandbox.php`, `midtrans-pdf-check.py` | QA browser/PDF dan empat proses verifikasi bersamaan, database disposable |
| `tests/browser/certification.mjs` | Entry QA sertifikasi diarahkan ke alur pembayaran terbaru |
| `README.md`, `docs/README.md`, `docs/certification-system.md`, dokumen ini dan SQL preview | Panduan terbaru dan penandaan riwayat simulasi |

## Riwayat pengujian integrasi awal

Koreksi respons Snap v4: API Sandbox merchant mengembalikan HTTP 201, token valid dan URL HTTPS pada host Sandbox dengan path `/snap/v4/redirection/`. Validator lama hanya menerima `vtweb`, sehingga menolak respons yang sah. Validator sekarang mendukung path versi `vtweb` dan `redirection`, tetap membatasi origin HTTPS Sandbox dan menolak userinfo, port tambahan, query, host palsu, Production serta path traversal. **85 tes lulus, 557 assertion**; kasus baru memeriksa respons v4 tersimpan, pending direuse tanpa transaksi kedua, dan URL berbahaya ditolak. Log: `storage/logs/midtrans-v4-fix.log`.

Verifikasi merchant langsung setelah koreksi: referensi attempt akun yang sudah ada digunakan kembali sesudah GET Status memastikan belum ada charge. Token dan redirect resmi disimpan terenkripsi. Halaman redirect Sandbox dibuka dengan browser headless dan menghasilkan **HTTP 200**, konten pembayaran tampil, serta nominal Rp24.999 terlihat. Tidak ada pembayaran simulator atau uang sungguhan yang diselesaikan, dan tidak ada sertifikat diterbitkan oleh pemeriksaan ini. Token, URL bertoken dan kredensial tidak dicetak. Evidence berupa boolean aman: `storage/logs/midtrans-live-open.log`.

Koreksi Client Key dan autentikasi webhook: **83 tes lulus, 539 assertion** pada database terisolasi. Regresi memastikan Client Key tanpa prefix lama dapat membuka transaksi Sandbox, key kosong tetap ditolak, Production tetap ditolak, signature dari Signature Secret Key diterima, signature dari Server Key yang berbeda ditolak, dan secret kosong tidak memicu fallback. Pemeriksaan render read-only akun lokal menunjukkan tombol pembayaran tampil; konfigurasi Sandbox dan Signature Secret Key tersedia. Tidak ada transaksi akun yang dibuat. Log: `storage/logs/midtrans-client-fix.log`. Hasil suite lengkap dan browser berikut mencatat pengujian integrasi awal sebelum koreksi ini.

Baseline HEAD `e64c282`, working tree bersih: **255 PHP lulus, 54 gagal; 24 JavaScript lulus; Composer valid**. Baseline disimpan lokal di `storage/logs/midtrans-baseline.log` dan `.xml`. Kegagalan existing berkaitan dengan fixture/ekspektasi fitur akademik; materi atau rubrik tidak diubah untuk meluluskannya.

Gabungan tes sertifikasi dan pembayaran setelah implementasi: **81 lulus, 531 assertion** pada database testing terisolasi, terdiri dari 40 tes sertifikasi dan 41 tes pembayaran. Mencakup semua status, nominal/currency/merchant mismatch, salah signature, pembedaan Signature Secret Key, timeout/API error, token/redirect invalid, lease klik ganda, reuse pending, retry expired, duplikasi/out-of-order, refund/partial refund, spoof browser, CSRF, kepemilikan, PDF, verifikasi publik dan penghapusan akun dengan cascade attempts. Kegagalan insert sertifikat juga diuji: perubahan paid dan status attempt ikut rollback, lalu replay webhook menerbitkan tepat satu sertifikat. Transaction ID yang sudah dibayar dipin sehingga tidak dapat berubah pada verifikasi berikutnya.

Suite PHP lengkap: **296 lulus, 54 gagal, 4738 assertion**. Perbandingan himpunan kasus failure/error pada JUnit awal dan akhir identik: **0 kegagalan baru**. Kegagalan baseline tetap dilaporkan sebagai FAIL existing. JavaScript **24 lulus**, Composer valid, Pint lulus, pemeriksaan sintaks PHP/JavaScript dan `git diff --check` lulus.

QA browser: **PASS** untuk guest, belum layak, layak, pending, gagal, expire/retry, terbit dan refund. Guest/belum layak/layak/pending/terbit diperiksa pada **320, 360, 390, 768, 1024, 1440 px** tanpa overflow. Persetujuan, fokus/Enter/Escape, pembatalan dialog, alternatif redirect tervalidasi, salin tautan, disclosure panduan, unduhan HTTP dengan session, QR dan verifikasi, serta penolakan PDF akun lain diperiksa. Callback SDK tiruan mengklaim sukses ketika API masih pending: sertifikat tetap tidak terbit. **Empat proses PHP bersamaan** merekonsiliasi settlement pada SQLite WAL dan menghasilkan satu order/satu sertifikat. Tidak ada pageerror JavaScript.

Evidence browser akhir: `storage/framework/testing/midtrans-b4a6777551f6c858e7bfe532664f4a01/` berisi screenshot, hasil, PDF dan `pdf-results.json`. Parser PyMuPDF memeriksa tiga PDF, masing-masing satu halaman A4 landscape, watermark SANDBOX / DEMO, nama biasa/panjang tanpa teks terpotong, logo dan QR tertanam. OpenCV berhasil mendekode QR menjadi URL verifikasi persis. **NOT TESTED untuk penyimpanan file dari browser:** Chromium lokal membatalkan unduhan; respons PDF autentikasi HTTP berhasil dan isi PDF diperiksa terpisah. Dependensi Python QA lokal berada di `storage/framework/testing/certification-tools`; set `PYTHONPATH` ke direktori tersebut jika tidak dipasang pada interpreter QA.

Migration dan alur penerbitan juga **PASS pada MySQL lokal sementara**: database baru bernama acak dengan prefix khusus testing, seluruh migration lama, satu sertifikat simulasi existing, migration baru, attempt pending, settlement dan verifikasi duplikat. Atribut sertifikat lama tetap identik, unduhan lama tetap valid, dan satu sertifikat Sandbox dihasilkan. Database sementara kemudian dihapus; schema aplikasi tidak dimigrasikan. Snapshot memakai model fresh dari database agar perbedaan normalisasi driver tidak dianggap perubahan data. Harness satu proses mereset metadata kolom Eloquent setelah migration untuk mewakili proses web baru; restart server/worker setelah migration saat menjalankan aplikasi. Nama indeks komposit dibuat eksplisit agar tidak melewati batas 64 karakter MySQL. Harness lokal dan log: `storage/framework/testing/midtrans-mysql-check.php`, `storage/logs/midtrans-mysql.log`.

Router tunnel **PASS** pada loopback dengan SQLite terisolasi: tujuh kombinasi path/metode terlarang mendapat 404, JSON webhook tidak lengkap mendapat 422, dan input APP_DEBUG=true tidak menampilkan exception. Tidak ada tunnel publik yang dibuka. Catatan berada pada `storage/framework/testing/midtrans-b4099e6a1ce3c02bbd0969b1856e5190/webhook-router-results.txt`.

### Gate antislop untuk UI lokal

| Gate | Hasil dan bukti |
| --- | --- |
| Hard Gate | **PASS**: 30 pemeriksaan viewport tanpa overflow; persetujuan, fokus, keyboard, batal, cek status, salin, panduan dan tautan bekerja. State kosong/loading/gagal/expired/refund tersedia. Enam pasangan warna mencapai kontras 5.05–10.89:1; putih pada tombol teal 6.11:1. Data progres berasal dari service resmi; tidak ada aset/testimoni/statistik rekaan. Em dash pada banner adalah teks yang diminta pengguna. |
| Purpose-Gate | **PASS**: panel mint menonjolkan status, border teal menandai penerbitan, badge menunjukkan kelayakan nyata. Tombol 3D adalah permintaan pengguna dan memberi feedback tekan. Plus Jakarta Sans, logo, warna dan layout mengikuti DESIGN.md; tidak ada pola latar, glow, ilustrasi atau grid kartu dekoratif baru. |
| Liveliness | **PASS**: ENERGY 2 / RHYTHM 2 / MOTION 1; fokus pada harga/aksi, ritme daftar syarat → penerbitan → dokumen, whitespace membedakan fungsi, motif mint–teal dan gerak tekan terbatas. Reduced motion tetap mengikuti CSS existing. |
| Craftsmanship & Quality Locks | **PASS untuk kontrak lokal yang diuji**: alur satu halaman lengkap dari persyaratan ke pending/terbit/refund, teks tombol spesifik, hierarchy judul/harga/status terlihat pada screenshot. Browser menggunakan fake yang dinyatakan, PDF diperiksa dari HTTP; keterbatasan simpan file browser dan merchant nyata ditandai NOT TESTED, bukan diklaim berhasil. Komentar baru menjelaskan batas protokol atau perilaku browser, tanpa narasi dekoratif. |

Perintah QA:

```powershell
$env:XDEBUG_MODE='off'
php artisan test --compact --filter='CertificationTest|MidtransPaymentTest'
php artisan test --compact --log-junit=storage/logs/midtrans-final.xml
node --test tests/js/*.test.js
node tests/browser/midtrans.mjs
# Path evidence dicetak runner. QA membutuhkan Playwright dan browser msedge lokal.
python tests/browser/midtrans-pdf-check.py storage/framework/testing/midtrans-<id-run>
composer validate --no-check-publish
vendor/bin/pint --dirty --test
git diff --check
```

Runner browser membuat SQLite baru dengan nama acak di `storage/framework/testing/midtrans-*`, mengisi fixture melalui service grading resmi, dan memakai Http fake serta SDK Snap tiruan secara eksplisit. File status provider hanya dibaca dari disk oleh fixture; tidak ada endpoint pengubah pembayaran testing pada aplikasi. Fixture menolak direktori arbitrary, database existing, atau koneksi selain SQLite yang ditentukan. Tidak pernah memakai kredensial merchant pengguna.

**NOT TESTED:** pembayaran QRIS hingga selesai melalui simulator Midtrans, delivery webhook lewat tunnel publik, dan aktivasi Production. Token serta pembukaan halaman redirect merchant Sandbox sudah diverifikasi langsung pada koreksi v4 di atas; pengujian status/issuance otomatis tetap memakai fake dan tidak membuktikan pembayaran merchant selesai. Lanjutkan uji simulator sesuai langkah Sandbox di atas. Tidak ada tunnel, transaksi uang sungguhan, commit/push, atau perubahan `.env` yang dilakukan.

## Referensi resmi dan batas pengembangan berikutnya

Ditinjau 20 November 2026: [Snap integration](https://docs.midtrans.com/docs/snap-snap-integration-guide), [Snap advanced / enabled payments dan QR mode](https://docs.midtrans.com/docs/snap-advanced-feature), [HTTP notifications](https://docs.midtrans.com/docs/https-notification-webhooks), [GET Status](https://docs.midtrans.com/reference/get-transaction-status), [Basic authentication](https://docs.midtrans.com/reference/request-headers), dan [BI-SNAP signature generation](https://docs.midtrans.com/reference/signature-generation).

Production membutuhkan audit dan implementasi tersendiri: onboarding merchant/kanal aktif, autentikasi yang benar sesuai produk, program/environment dan kredensial terpisah, domain HTTPS permanen, pemantauan webhook, rekonsiliasi berkala, kebijakan refund/reissue/retensi, backup dan recovery, serta uji keamanan dan persetujuan rollout. Mengubah `MIDTRANS_IS_PRODUCTION` menjadi true pada kode ini tetap menolak transaksi dan tidak menyediakan dukungan tersebut.
