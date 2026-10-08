
<?php

return [
    'description' => 'Pelajari polimorfisme dalam Python: berbagai object dapat diproses melalui pemanggilan method yang sama, baik melalui inheritance maupun duck typing. Contohnya menggunakan sensor pemantauan lahan basah.',

    'objectives' => [
        'Menjelaskan makna polimorfisme dalam OOP Python.',
        'Menggunakan method overriding untuk perilaku berbeda pada hierarki inheritance.',
        'Menerapkan duck typing pada object yang tidak harus memiliki superclass yang sama.',
        'Memproses berbagai object melalui satu loop atau fungsi pemanggil.',
    ],

    'sections' => [

        // ==================================================
        // APERSEPSI
        // ==================================================
        [
            'id' => 'apersepsi',
            'title' => 'Apersepsi',
            'nav_group' => 'pendahuluan',

            'paragraphs' => [
                'Pada Bab 4, kita mempelajari bahwa subclass dapat mewarisi serta mendefinisikan ulang method milik superclass. Sekarang, bagaimana jika banyak object berbeda perlu diproses dengan perintah yang sama?',

                'Sensor pH, sensor suhu, dan sensor tinggi air sama-sama dapat membaca data, tetapi hasil pembacaannya berbeda. Apakah kita harus membuat if untuk setiap jenis sensor? Dengan polimorfisme, kode utama dapat memanggil baca_data() pada setiap object.',
            ],

            'tip' => 'Bayangkan satu perintah: item.baca_data(). Sensor yang berbeda dapat memberikan hasil yang berbeda tanpa mengubah cara pemanggilannya.',
        ],

        // ==================================================
        // 5.1 POLIMORFISME MELALUI INHERITANCE
        // ==================================================
        [
            'id' => 'polimorfisme-inheritance',
            'title' => '5.1 Polimorfisme melalui Inheritance',

            'paragraphs' => [
                'Polimorfisme berarti satu cara pemanggilan method dapat menghasilkan perilaku berbeda sesuai object yang menerima pemanggilan tersebut. Pada hierarki inheritance, subclass dapat menyediakan implementasi khusus melalui method overriding.',

                'SensorLingkungan menjadi superclass dengan method baca_data(). SensorPH dan SensorSuhu mewarisinya, lalu mendefinisikan ulang baca_data() sesuai kebutuhan masing-masing.',

                'Kedua object disimpan dalam list sensor. Dalam loop, baris item.baca_data() tetap sama. Python menjalankan method milik object yang sedang diproses.',
            ],

            'code' => <<<'PYTHON'
class SensorLingkungan:
    def baca_data(self):
        return "Belum ada implementasi"

class SensorPH(SensorLingkungan):
    def baca_data(self):
        return 7.1

class SensorSuhu(SensorLingkungan):
    def baca_data(self):
        return 29.5

sensor = [SensorPH(), SensorSuhu()]

for item in sensor:
    print(item.baca_data())
PYTHON,

            'output' => "7.1\n29.5",

            'tip' => 'Method overriding telah dikenalkan di Bab 4. Pada Bab 5, fokusnya adalah bagaimana berbagai object diproses dengan antarmuka pemanggilan yang sama.',
        ],

        // ==================================================
        // 5.2 DUCK TYPING
        // ==================================================
        [
            'id' => 'duck-typing',
            'title' => '5.2 Duck Typing',

            'paragraphs' => [
                'Polimorfisme di Python tidak harus menggunakan inheritance. Duck typing menekankan apakah sebuah object menyediakan method atau atribut yang dibutuhkan, bukan apakah object itu mewarisi class tertentu.',

                'Dalam contoh berikut, SensorVirtual dan LaporanManual tidak memiliki superclass khusus yang sama. Namun, keduanya menyediakan method baca_data(). Karena itu, fungsi tampilkan() dapat menerima kedua object tersebut.',

                'Fungsi tampilkan() tidak perlu memeriksa nama class object terlebih dahulu. Fungsi cukup memanggil obj.baca_data().',
            ],

            'code' => <<<'PYTHON'
class SensorVirtual:
    def baca_data(self):
        return "simulasi"

class LaporanManual:
    def baca_data(self):
        return "manual"

def tampilkan(obj):
    print(obj.baca_data())

tampilkan(SensorVirtual())
tampilkan(LaporanManual())
PYTHON,

            'output' => "simulasi\nmanual",

            'tip' => 'Duck typing tidak berarti semua object otomatis bisa dipakai. Jika object tidak menyediakan method yang diminta, pemanggilannya dapat menghasilkan AttributeError. Nama method dan cara penggunaannya tetap harus konsisten.',
        ],

        // ==================================================
        // 5.3 MENGURANGI PEMERIKSAAN TIPE
        // ==================================================
        [
            'id' => 'mengurangi-pemeriksaan-tipe',
            'title' => '5.3 Mengurangi Pemeriksaan Tipe',

            'paragraphs' => [
                'Ketika jumlah jenis sensor bertambah, rangkaian if isinstance(obj, SensorPH) dan elif isinstance(obj, SensorSuhu) dapat menjadi panjang. Kode pemanggil juga harus sering diubah saat class baru ditambahkan.',

                'Jika masing-masing sensor mempunyai method baca_data(), kode utama dapat memproses seluruh sensor melalui satu loop. Implementasi baca_data() berada pada masing-masing class, bukan dipilih melalui percabangan berdasarkan tipe object.',

                'Pendekatan ini membantu membuat program lebih mudah dikembangkan. Pemeriksaan tipe masih mungkin digunakan ketika benar-benar diperlukan, tetapi tidak harus dipakai hanya untuk memilih method yang sebenarnya sudah dimiliki tiap object.',
            ],

            'code' => <<<'PYTHON'
class SensorPH:
    def baca_data(self):
        return "pH: 7.1"

class SensorSuhu:
    def baca_data(self):
        return "Suhu: 29.5"

class SensorTinggiAir:
    def baca_data(self):
        return "Tinggi air: 128"

sensor = [
    SensorPH(),
    SensorSuhu(),
    SensorTinggiAir()
]

for item in sensor:
    print(item.baca_data())
PYTHON,

            'output' => "pH: 7.1\nSuhu: 29.5\nTinggi air: 128",

            'tip' => 'Nilai pH, suhu, dan tinggi air pada contoh merupakan data latihan Python, bukan data pengukuran atau batas ilmiah kondisi lingkungan.',
        ],

        // ==================================================
        // AYO COBA - LIVE CODING
        // ==================================================
        [
            'id' => 'ayo-coba-polimorfisme',
            'title' => 'Ayo Coba – Live Coding Polimorfisme',
            'nav_title' => 'Ayo Coba – Live Coding',

            'paragraphs' => [
                'Lengkapi method status() pada SensorPH dan SensorSuhu agar masing-masing mengembalikan teks yang berbeda. Setelah itu, buat class ketiga SensorTinggiAir yang juga memiliki method status().',

                'Simpan ketiga object ke dalam list sensor dan gunakan loop for untuk memanggil item.status() tanpa memeriksa tipe object. Selanjutnya tambahkan class keempat, SensorKekeruhan, dengan method status() serta masukkan object-nya ke list yang sama.',

                'Nama SensorTinggiAir dan SensorKekeruhan dipilih sebagai contoh pengembangan latihan. Klik Run Code untuk mengamati output, kemudian Submit untuk memeriksa penerapan polimorfisme.',
            ],

            'live_codes' => [
                [
                    'id' => 'bab5-polimorfisme-sensor',

                    'title' => 'Coba sendiri: Satu method untuk empat sensor',

                    'description' => 'Lengkapi status() pada SensorPH dan SensorSuhu, tambahkan SensorTinggiAir serta SensorKekeruhan dengan status() yang berbeda, masukkan semuanya ke list sensor, lalu panggil item.status() melalui satu loop tanpa isinstance().',

                    'entry_file' => 'main.py',

                    // ======================================
                    // STARTER CODE LIVE CODING
                    // ======================================
                    'files' => [
                        'main.py' => <<<'PYTHON'
class SensorPH:
    def status(self):
        # TODO: return teks status sensor pH
        pass


class SensorSuhu:
    def status(self):
        # TODO: return teks status sensor suhu
        pass


# TODO: Buat class SensorTinggiAir
# dengan method status().

# TODO: Buat class SensorKekeruhan
# dengan method status().

# Pastikan keempat method mengembalikan
# teks yang berbeda.


sensor = [SensorPH(), SensorSuhu()]

# TODO: Tambahkan object SensorTinggiAir
# dan SensorKekeruhan ke dalam list sensor.


for item in sensor:
    print(item.status())
PYTHON,
                    ],

                    // ======================================
                    // CHECKER OTOMATIS LIVE CODING
                    // ======================================
                    'checker' => <<<'PYTHON'
import ast

results = []


def check(label, operation, hint):
    try:
        passed = bool(operation())

        results.append({
            "label": label,
            "passed": passed,
            "feedback": "" if passed else hint,
        })

    except Exception as error:
        results.append({
            "label": label,
            "passed": False,
            "feedback": f"{hint} ({type(error).__name__}: {error})",
        })


# ======================================
# MEMERIKSA KEBERADAAN CLASS
# ======================================

def class_ada(nama):
    return isinstance(globals().get(nama), type)


def semua_class_ada():
    return all(
        class_ada(nama)
        for nama in (
            "SensorPH",
            "SensorSuhu",
            "SensorTinggiAir",
            "SensorKekeruhan"
        )
    )


# ======================================
# MEMERIKSA METHOD STATUS()
# ======================================

def cek_status():
    if not semua_class_ada():
        return False

    nama_class = (
        SensorPH,
        SensorSuhu,
        SensorTinggiAir,
        SensorKekeruhan
    )

    hasil = []

    for kelas in nama_class:

        # Method harus dibuat pada class
        # yang bersangkutan.
        if "status" not in kelas.__dict__:
            return False

        teks = kelas().status()

        # Method harus menghasilkan teks.
        if not isinstance(teks, str):
            return False

        if not teks.strip():
            return False

        hasil.append(teks.strip())

    # Hasil keempat class harus berbeda.
    return len(set(hasil)) == 4


# ======================================
# MEMERIKSA LIST SENSOR
# ======================================

def cek_list_sensor():
    if not semua_class_ada():
        return False

    if "sensor" not in globals():
        return False

    if not isinstance(sensor, list):
        return False

    if len(sensor) != 4:
        return False

    return all(
        sum(
            type(item) is kelas
            for item in sensor
        ) == 1
        for kelas in (
            SensorPH,
            SensorSuhu,
            SensorTinggiAir,
            SensorKekeruhan
        )
    )


# ======================================
# MEMERIKSA LOOP POLIMORFISME
# ======================================

def cek_loop_umum():

    with open(
        "main.py",
        encoding="utf-8"
    ) as berkas:
        pohon = ast.parse(berkas.read())

    # Tidak menggunakan pemeriksaan tipe
    # untuk memilih jenis sensor.
    for node in ast.walk(pohon):

        if isinstance(node, ast.Call):
            if isinstance(node.func, ast.Name):

                if node.func.id in (
                    "isinstance",
                    "type"
                ):
                    return False

    # Cari loop yang membaca list sensor.
    for node in ast.walk(pohon):

        if not isinstance(node, ast.For):
            continue

        if not isinstance(node.target, ast.Name):
            continue

        if not isinstance(node.iter, ast.Name):
            continue

        if node.iter.id != "sensor":
            continue

        nama_item = node.target.id

        # Cari pemanggilan item.status()
        # pada body loop.
        for bagian in node.body:

            for panggilan in ast.walk(bagian):

                if not isinstance(
                    panggilan,
                    ast.Call
                ):
                    continue

                if not isinstance(
                    panggilan.func,
                    ast.Attribute
                ):
                    continue

                if panggilan.func.attr != "status":
                    continue

                if not isinstance(
                    panggilan.func.value,
                    ast.Name
                ):
                    continue

                if (
                    panggilan.func.value.id
                    == nama_item
                ):
                    return True

    return False


# ======================================
# HASIL PEMERIKSAAN
# ======================================

check(
    "Class SensorPH",
    lambda: class_ada("SensorPH"),
    "Pastikan class SensorPH tersedia."
)

check(
    "Class SensorSuhu",
    lambda: class_ada("SensorSuhu"),
    "Pastikan class SensorSuhu tersedia."
)

check(
    "Class SensorTinggiAir",
    lambda: class_ada("SensorTinggiAir"),
    "Tambahkan class SensorTinggiAir dengan method status()."
)

check(
    "Class sensor keempat",
    lambda: class_ada("SensorKekeruhan"),
    "Tambahkan class SensorKekeruhan dengan method status()."
)

check(
    "Empat implementasi status() berbeda",
    cek_status,
    "Setiap class harus punya status() sendiri yang return teks berbeda dan tidak kosong."
)

check(
    "Empat object dalam list sensor",
    cek_list_sensor,
    "Isi list sensor dengan tepat satu object dari masing-masing empat class."
)

check(
    "Pemanggilan status() melalui satu loop",
    cek_loop_umum,
    "Gunakan for item in sensor: print(item.status()) tanpa isinstance() atau type()."
)
PYTHON,
                ],
            ],
        ],

        // ==================================================
        // AYO BERLATIH
        // ==================================================
        [
            'id' => 'ayo-berlatih-polimorfisme',
            'title' => 'Ayo Berlatih',

            'paragraphs' => [
                'Kerjakan latihan berikut melalui Editor OOPy untuk memperkuat pemahaman polimorfisme.',
            ],

            'practice' => [
                'Buat fungsi cetak_laporan(objek) yang memanggil objek.laporan(). Uji menggunakan dua class berbeda.',

                'Buat tiga class alat pantau yang semuanya memiliki method baca(). Simpan object ke list dan panggil baca() melalui satu loop.',

                'Jelaskan perbedaan polimorfisme berbasis inheritance dan duck typing menggunakan contoh buatanmu sendiri.',
            ],
        ],
    ],

    // ======================================================
    // RANGKUMAN
    // ======================================================
    'summary' => [
        'Polimorfisme memungkinkan pemanggilan method yang sama memberikan perilaku sesuai object yang digunakan.',

        'Method overriding dapat membentuk polimorfisme pada hierarki inheritance.',

        'Duck typing menekankan method atau atribut yang disediakan object, tidak hanya tipe eksplisitnya.',

        'Polimorfisme mengurangi ketergantungan kode pemanggil terhadap class konkret dan pemeriksaan tipe yang tidak diperlukan.',
    ],

    // ======================================================
    // REFLEKSI
    // ======================================================
    'reflection' => [
        'Apa hubungan inheritance, method overriding, dan polimorfisme?',

        'Mengapa duck typing sesuai dengan sifat dinamis Python?',

        'Dalam keadaan apa pemeriksaan tipe eksplisit masih mungkin diperlukan?',
    ],

    // ======================================================
    // KUIS BAB 5
    // 3 PILIHAN GANDA + 2 ISIAN KODE
    // ======================================================
    'quiz' => [

        // SOAL 1 - PILIHAN GANDA
        [
            'type' => 'multiple_choice',

            'question' => 'Polimorfisme memungkinkan ...',

            'options' => [
                'Semua object harus identik',
                'Semua atribut private',
                'Pemanggilan yang sama menghasilkan perilaku sesuai object',
                'Program hanya punya satu class',
            ],

            'correct' => 2,

            'explanation' => 'Polimorfisme memungkinkan object berbeda merespons pemanggilan method yang sama sesuai implementasinya.',
        ],

        // SOAL 2 - PILIHAN GANDA
        [
            'type' => 'multiple_choice',

            'question' => 'Duck typing menekankan ...',

            'options' => [
                'Warna editor',
                'Nama file',
                'Jumlah atribut',
                'Kemampuan atau perilaku object',
            ],

            'correct' => 3,

            'explanation' => 'Duck typing berfokus pada ketersediaan perilaku yang diperlukan tanpa mewajibkan superclass yang sama.',
        ],

        // SOAL 3 - PILIHAN GANDA
        [
            'type' => 'multiple_choice',

            'question' => 'Keuntungan polimorfisme adalah ...',

            'options' => [
                'Menghilangkan validasi',
                'Mencegah inheritance',
                'Kode pemanggil lebih fleksibel terhadap implementasi',
                'Membuat semua method static',
            ],

            'correct' => 2,

            'explanation' => 'Kode pemanggil dapat menggunakan method umum tanpa bergantung pada implementasi setiap class.',
        ],

        // SOAL 4 - ISIAN KODE
        [
            'type' => 'code_fill',

            'question' => 'Lengkapi method berikut agar Rawa melakukan overriding terhadap status() milik superclass.',

            'code' => <<<'PYTHON'
class Rawa(Ekosistem):
    def __________(self):
        return "Genangan rawa dipantau"
PYTHON,

            'answer' => 'status',

            'explanation' => 'Overriding menggunakan nama method yang sama seperti method superclass, yaitu status.',
        ],

        // SOAL 5 - ISIAN KODE
        [
            'type' => 'code_fill',

            'question' => 'Semua object pada daftar memiliki method info(). Lengkapi loop agar method tersebut dipanggil secara polimorfis.',

            'code' => <<<'PYTHON'
for obj in daftar:
    print(obj.__________())
PYTHON,

            'answer' => 'info',

            'explanation' => 'Memanggil obj.info() menggunakan perilaku yang disediakan object masing-masing.',
        ],
    ],
];
