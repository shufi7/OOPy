<?php

$checks = file_get_contents(__DIR__.'/../../services/coding-grader/checks.py');
$checker = fn (string $key): string => "_oopy_namespace = dict(globals())\n_oopy_rubric = {}\nexec(".json_encode($checks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).", _oopy_rubric)\n"
    ."results = _oopy_rubric['run_checks'](_oopy_namespace, __oopy_output__, '$key', modules=__oopy_modules__, execution=__oopy_execution__)\n";

return [
    'latihan-1' => [
        'number' => 1,
        'key' => 'coding-latihan-1',
        'material_slug' => 'kelas-dan-objek',
        'title' => 'Latihan 1 — Membuat Class dan Object',
        'problem_title' => 'Membuat Class SensorAir Menggunakan Python',
        'description' => 'Latih pemahaman class, object, atribut, constructor, dan method pada Python.',
        'case' => 'Seorang mahasiswa membuat program pemantauan kualitas air di Rawa Bangkau, Kalimantan Selatan.',
        'objective' => 'Membuat class dan object untuk menyimpan data sensor serta menentukan status kualitas air.',
        'steps' => [
            'Tulis program Python dari awal pada file main.py. Buat class bernama SensorAir.',
            'Buat constructor __init__(self, lokasi, ph), lalu simpan lokasi dan ph sebagai atribut objek menggunakan self.',
            'Buat method status_air(self). Gunakan percabangan untuk mengembalikan status sesuai rentang pH pada ketentuan program.',
            'Buat objek SensorAir dengan lokasi "Rawa Bangkau" dan pH 7.0.',
            'Gunakan print() untuk menampilkan lokasi pada baris pertama dan teks "Status air: " diikuti hasil status_air() pada baris kedua.',
        ],
        'requirements' => [
            'Nama class: SensorAir. Constructor menerima lokasi dan ph; atribut publik objek bernama lokasi dan ph.',
            'Method status_air() tidak menerima parameter tambahan dan harus mengembalikan string, bukan hanya mencetaknya.',
            'Jika 6.5 ≤ ph ≤ 8.5, kembalikan "Normal". Nilai 6.5 dan 8.5 termasuk Normal.',
            'Jika ph < 6.5 atau ph > 8.5, kembalikan "Perlu pemeriksaan". Variasi kapitalisasi dan spasi yang tidak mengubah makna diterima.',
            'Status harus mengikuti nilai ph terbaru milik objek; jangan menetapkan hasil hanya untuk pH 7.0.',
        ],
        'criteria' => [
            'Program berjalan tanpa error. Pembuatan contoh objek SensorAir dan penyajian output dinilai sebagai tambahan.',
            'Constructor menyimpan lokasi dan ph yang diberikan pada setiap objek secara terpisah.',
            'status_air() benar untuk nilai normal, kedua batas, nilai di luar rentang, dan setelah atribut ph diubah.',
            'Semua pemeriksaan konsep inti wajib berhasil (90 poin). Contoh object dan output merupakan tambahan (10 poin) yang tidak menentukan kelulusan. Progres tersimpan setelah server mengonfirmasi kelulusan.',
        ],
        'hint' => 'Gunakan self untuk atribut, return untuk hasil method, dan perbandingan inklusif untuk kedua batas pH.',
        'example' => "Rawa Bangkau\nStatus air: Normal",
        'files' => ['main.py' => ''],
        'core_checks' => ['constructor', 'attributes', 'boundaries', 'normal', 'abnormal', 'updates'],
        'checker' => $checker('coding-latihan-1'),
    ],
    'latihan-2' => [
        'number' => 2,
        'key' => 'coding-latihan-2-multifile-v1',
        'material_slug' => 'pewarisan',
        'title' => 'Latihan 2 — Implementasi OOP dengan Multi-File Python',
        'problem_title' => 'Membangun Sistem Pemantauan Ekosistem Rawa Berbasis Multi-File',
        'description' => 'Organisasikan kelas induk, kelas turunan, dan program utama dalam tiga modul Python yang saling terhubung.',
        'case' => 'Seorang mahasiswa Pendidikan Komputer membuat simulasi pemantauan ekosistem lahan basah di Kalimantan Selatan.',
        'objective' => 'Terapkan class, object, constructor, atribut instance, inheritance, super(), overriding, serta import antarmodul. Uji perubahan perilaku objek saat atributnya diperbarui.',
        'steps' => [
            'Kerjakan ekosistem.py terlebih dahulu: definisikan superclass Ekosistem beserta constructor dan method status().',
            'Lanjutkan rawa.py: impor Ekosistem dari modul ekosistem, lalu definisikan subclass Rawa dengan constructor sendiri dan overriding status().',
            'Kerjakan main.py: impor Rawa dari modul rawa dan buat objek dengan nama "Rawa Bangkau", lokasi "Hulu Sungai Selatan", serta tinggi air 85 cm.',
            'Dari main.py, tampilkan nama dan lokasi dipisahkan " - ", lalu tampilkan "Status air: " diikuti hasil status() melalui objek.',
            'Gunakan Run Code untuk menjalankan main.py bersama kedua modul. Uji tinggi air di bawah, tepat pada, dan di atas 80 serta perubahan atribut setelah objek dibuat.',
            'Gunakan Submit untuk memeriksa seluruh proyek. Akun yang masuk memperoleh penilaian resmi server; hasil lokal tamu tidak menyimpan nilai.',
        ],
        'requirements' => [
            'ekosistem.py: Ekosistem.__init__(self, nama, lokasi) menyimpan self.nama dan self.lokasi sesuai parameter. Ekosistem.status(self) mengembalikan "Belum dipantau".',
            'rawa.py: gunakan from ekosistem import Ekosistem atau import modul yang setara. Definisikan Rawa sebagai subclass Ekosistem pada file ini.',
            'Rawa.__init__(self, nama, lokasi, tinggi_air_cm) memanggil super().__init__(nama, lokasi), lalu menyimpan self.tinggi_air_cm.',
            'Rawa.status(self) mengembalikan "Siaga" jika tinggi_air_cm >= 80 dan "Normal" jika kurang dari 80. Gunakan nilai atribut terbaru, bukan hasil yang disimpan untuk satu contoh.',
            'main.py: impor Rawa dari rawa dan jalankan contoh objek serta pemanggilan status() di file utama ini. Mendefinisikan seluruh kelas di main.py tidak memenuhi struktur modular.',
            'Ketiga file harus berisi kode. Masing-masing maksimal 30.000 karakter dan 60.000 byte UTF-8; total proyek juga maksimal 30.000 karakter dan 60.000 byte.',
            'Batas 80 cm adalah aturan simulasi latihan pemrograman, bukan standar resmi peringatan banjir.',
        ],
        'criteria' => [
            'Enam konsep inti masing-masing bernilai 15 poin: struktur modul/import; constructor dan atribut induk; status induk; inheritance; constructor subclass dengan super(); overriding dan perilaku dinamis.',
            'Semua pemeriksaan inti wajib berhasil dan skor minimal 90. Penyajian contoh objek serta output merupakan tambahan 10 poin.',
            'Status diuji pada 79.9, 80, 80.0, 85, data lain, perubahan atribut, serta dua objek independen. Solusi yang hanya benar untuk contoh 85 cm tidak lulus.',
            'Variasi gaya penulisan serta kapitalisasi dan whitespace status/output yang setara diterima. Nilai dan progres resmi hanya berasal dari server.',
        ],
        'hint' => 'Nama modul pada import tidak memakai akhiran .py. Pilih tab untuk mengedit file; main.py selalu menjadi entry point. Gunakan return pada status() dan print() di program utama.',
        'example' => "Rawa Bangkau - Hulu Sungai Selatan\nStatus air: Siaga",
        'files' => ['ekosistem.py' => '', 'rawa.py' => '', 'main.py' => ''],
        'core_checks' => ['modules', 'constructor', 'parent_status', 'inheritance', 'child_constructor', 'overriding'],
        'checker' => $checker('coding-latihan-2-multifile-v1'),
    ],
];
