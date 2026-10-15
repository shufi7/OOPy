<?php

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
            'Jika ph < 6.5 atau ph > 8.5, kembalikan "Perlu pemeriksaan". Gunakan ejaan dan huruf besar sesuai ketentuan.',
            'Status harus mengikuti nilai ph terbaru milik objek; jangan menetapkan hasil hanya untuk pH 7.0.',
        ],
        'criteria' => [
            'Program berjalan tanpa error dan membuat objek SensorAir, dengan output seperti contoh.',
            'Constructor menyimpan lokasi dan ph yang diberikan pada setiap objek secara terpisah.',
            'status_air() benar untuk nilai normal, kedua batas, nilai di luar rentang, dan setelah atribut ph diubah.',
            'Seluruh pemeriksaan resmi server berhasil. Pemeriksaan lokal hanya memberikan feedback; progres akun tersimpan setelah server mengonfirmasi kelulusan.',
        ],
        'hint' => 'Gunakan self untuk atribut, return untuk hasil method, dan perbandingan inklusif untuk kedua batas pH.',
        'example' => "Rawa Bangkau\nStatus air: Normal",
        'files' => ['main.py' => ''],
        // Public practice checks; the official test suite lives only in services/coding-grader.
        'checker' => <<<'PY'
results = []
def check(label, callback, feedback):
    try:
        ok = bool(callback())
    except Exception:
        ok = False
    results.append({'label': label, 'passed': ok, 'feedback': feedback if not ok else ''})
check('Class dan atribut sensor', lambda: SensorAir('Rawa Bangkau', 7).lokasi == 'Rawa Bangkau' and SensorAir('Rawa Bangkau', 7).ph == 7, 'Simpan kedua parameter pada atribut self.lokasi dan self.ph.')
for ph, expected in [(6.5, 'Normal'), (7.0, 'Normal'), (8.5, 'Normal'), (6.4, 'Perlu pemeriksaan'), (8.6, 'Perlu pemeriksaan')]:
    check('Status untuk pH ' + str(ph), lambda ph=ph, expected=expected: SensorAir('Rawa Bangkau', ph).status_air() == expected, 'Periksa batas inklusif dan teks nilai kembalian status_air().')
PY,
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
            'Program berjalan tanpa error, membuat objek Rawa, dan mencetak status seperti contoh.',
            'Constructor Ekosistem dan Rawa menyimpan nama dan lokasi yang berbeda sesuai data masing-masing objek.',
            'Pewarisan Rawa dari Ekosistem terbukti, dan pemanggilan status() pada induk serta subclass memberikan hasil yang berbeda sesuai ketentuan.',
            'Seluruh pemeriksaan resmi server berhasil. Cara menulis solusi boleh berbeda selama memenuhi perilaku program yang diminta.',
        ],
        'hint' => 'Tuliskan nama class induk dalam tanda kurung pada deklarasi Rawa. Gunakan nama method yang sama untuk overriding.',
        'example' => 'Genangan rawa dipantau',
        'files' => ['main.py' => ''],
        'checker' => <<<'PY'
results = []
def check(label, callback, feedback):
    try:
        ok = bool(callback())
    except Exception:
        ok = False
    results.append({'label': label, 'passed': ok, 'feedback': feedback if not ok else ''})
check('Atribut class induk', lambda: Ekosistem('Lahan Basah', 'Kalsel').nama == 'Lahan Basah' and Ekosistem('Lahan Basah', 'Kalsel').lokasi == 'Kalsel', 'Simpan nama dan lokasi pada constructor Ekosistem.')
check('Status class induk', lambda: Ekosistem('Lahan Basah', 'Kalsel').status() == 'Status belum ditentukan', 'Periksa nilai kembalian status() pada class induk.')
check('Pewarisan Rawa', lambda: isinstance(Rawa, type) and Rawa is not Ekosistem and issubclass(Rawa, Ekosistem), 'Rawa harus menjadi subclass Ekosistem.')
check('Atribut subclass', lambda: Rawa('Rawa Bangkau', 'Kalimantan Selatan').nama == 'Rawa Bangkau' and Rawa('Rawa Bangkau', 'Kalimantan Selatan').lokasi == 'Kalimantan Selatan', 'Pastikan subclass memperoleh kedua atribut constructor induk.')
check('Method overriding', lambda: 'status' in Rawa.__dict__ and Rawa('Rawa Bangkau', 'Kalimantan Selatan').status() == 'Genangan rawa dipantau', 'Definisikan status() pada Rawa dengan nilai kembalian sesuai soal.')
PY,
    ],
];
