<?php

$checks = file_get_contents(__DIR__.'/../../services/coding-grader/checks.py');
$checker = fn (string $key): string => "_oopy_namespace = dict(globals())\n".$checks
    ."\nresults = run_checks(_oopy_namespace, __oopy_output__, '$key')\n";

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
        'key' => 'coding-latihan-2',
        'material_slug' => 'pewarisan',
        'title' => 'Latihan 2 — Pewarisan dan Method Overriding',
        'problem_title' => 'Membuat Class Ekosistem dan Subclass Rawa Menggunakan Python',
        'description' => 'Terapkan konsep inheritance dan method overriding untuk menyelesaikan permasalahan pemrograman.',
        'case' => 'Sistem pemantauan lingkungan Kalimantan Selatan membutuhkan class dasar untuk berbagai jenis ekosistem lahan basah.',
        'objective' => 'Menggunakan inheritance untuk berbagi atribut dan overriding untuk memberi perilaku khusus pada rawa.',
        'steps' => [
            'Tulis program Python dari awal pada main.py. Buat class induk bernama Ekosistem.',
            'Buat constructor __init__(self, nama, lokasi), lalu simpan kedua parameter sebagai atribut objek menggunakan self.',
            'Buat method status(self) pada Ekosistem yang mengembalikan status dasar sesuai ketentuan program.',
            'Buat class Rawa yang mewarisi Ekosistem. Warisi constructor induk atau panggil constructor induk menggunakan super() jika membuat constructor sendiri.',
            'Definisikan kembali method status(self) pada Rawa agar mengembalikan status khusus rawa.',
            'Buat objek Rawa dengan nama "Rawa Bangkau" dan lokasi "Kalimantan Selatan", lalu gunakan print() untuk menampilkan hasil status().',
        ],
        'requirements' => [
            'Nama class induk: Ekosistem. Constructor menerima nama dan lokasi; atribut publik objek bernama nama dan lokasi.',
            'Method Ekosistem.status() harus mengembalikan string "Status belum ditentukan".',
            'Rawa harus merupakan subclass Ekosistem dan memperoleh atribut nama serta lokasi dari parameter constructor.',
            'Rawa mendefinisikan method status() sendiri (overriding) yang mengembalikan string "Genangan rawa dipantau".',
            'Kedua method status() tidak menerima parameter tambahan. Kembalikan teks dengan return, kemudian cetak hasil pemanggilannya pada program utama.',
        ],
        'criteria' => [
            'Program berjalan tanpa error. Pembuatan contoh objek Rawa dan penyajian output dinilai sebagai tambahan.',
            'Constructor Ekosistem dan Rawa menyimpan nama dan lokasi yang berbeda sesuai data masing-masing objek.',
            'Pewarisan Rawa dari Ekosistem terbukti, dan pemanggilan status() pada induk serta subclass memberikan hasil yang berbeda sesuai ketentuan.',
            'Semua pemeriksaan konsep inti wajib berhasil (90 poin). Contoh object dan output merupakan tambahan (10 poin) yang tidak menentukan kelulusan. Variasi kapitalisasi dan spasi diterima; cara menulis solusi boleh berbeda.',
        ],
        'hint' => 'Tuliskan nama class induk dalam tanda kurung pada deklarasi Rawa. Gunakan nama method yang sama untuk overriding.',
        'example' => 'Genangan rawa dipantau',
        'files' => ['main.py' => ''],
        'core_checks' => ['constructor', 'attributes', 'parent_status', 'inheritance', 'child_attributes', 'overriding'],
        'checker' => $checker('coding-latihan-2'),
    ],
];
