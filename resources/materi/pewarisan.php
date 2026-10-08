
<?php

return [
    'description' => 'Pelajari pewarisan (inheritance) untuk menggunakan kembali atribut dan method dari superclass, memahami super() dan method overriding, serta membedakan hubungan is-a dan has-a dalam ekosistem lahan basah.',

    'objectives' => [
        'Menjelaskan hubungan superclass dan subclass.',
        'Membuat subclass dari class yang sudah ada.',
        'Menggunakan super() untuk memanfaatkan inisialisasi superclass.',
        'Melakukan method overriding pada subclass.',
        'Membedakan inheritance (is-a) dengan composition (has-a).',
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
                'Sungai, rawa, dan mangrove dapat dipandang sebagai jenis Ekosistem. Ketiganya memiliki nama dan lokasi, tetapi masing-masing juga mempunyai informasi khusus.',
                'Sebaliknya, StasiunPemantau memiliki Sensor. Stasiun bukan jenis sensor. Dari dua contoh ini, kita akan belajar memilih hubungan antarkelas yang tepat.',
            ],
            'tip' => 'Bayangkan kalimat “Sungai adalah jenis Ekosistem” dan “StasiunPemantau memiliki Sensor”. Kalimat pertama menunjukkan is-a; kalimat kedua menunjukkan has-a.',
        ],

        // ==================================================
        // 4.1 SUPERCLASS DAN SUBCLASS
        // ==================================================
        [
            'id' => 'superclass-subclass',
            'title' => '4.1 Superclass dan Subclass',

            'paragraphs' => [
                'Inheritance adalah mekanisme pewarisan yang memungkinkan suatu class menggunakan atribut dan method dari class lain. Class yang diwarisi disebut superclass (class induk), sedangkan class yang mewarisi disebut subclass (class turunan).',

                'Dalam contoh OOPy, Ekosistem menjadi superclass. Sungai, Rawa, dan Mangrove dapat menjadi subclass karena masing-masing merupakan jenis ekosistem (is-a).',

                'Penulisan class Sungai(Ekosistem) berarti Sungai mewarisi Ekosistem. Jika subclass tidak mendefinisikan ulang method info(), method tersebut dapat digunakan dari superclass.',
            ],

            'code' => <<<'PYTHON'
class Ekosistem:
    def __init__(self, nama, lokasi):
        self.nama = nama
        self.lokasi = lokasi

    def info(self):
        return f"{self.nama} - {self.lokasi}"

class Sungai(Ekosistem):
    pass

sungai = Sungai("Sungai Barito", "Banjarmasin")
print(sungai.info())
PYTHON,

            'output' => 'Sungai Barito - Banjarmasin',

            'tip' => 'Walaupun Sungai belum mempunyai method info() sendiri, object sungai tetap dapat memanggil info() yang diwarisi dari Ekosistem.',
        ],

        // ==================================================
        // 4.2 SUPER() DAN DUPLIKASI KODE
        // ==================================================
        [
            'id' => 'super-duplikasi',
            'title' => '4.2 super() dan Duplikasi Kode',

            'paragraphs' => [
                'Subclass bisa menambahkan atribut khusus. Sebagai contoh, Sungai memiliki panjang_km, sedangkan nama dan lokasi sudah dimiliki oleh Ekosistem.',

                'Ketika subclass membuat __init__() sendiri, gunakan super().__init__(nama, lokasi) untuk menjalankan inisialisasi pada superclass. Dengan demikian, kita tidak perlu menulis ulang self.nama dan self.lokasi.',

                'Setelah super().__init__() dipanggil, barulah atribut khusus subclass ditambahkan menggunakan self.panjang_km.',
            ],

            'code' => <<<'PYTHON'
class Ekosistem:
    def __init__(self, nama, lokasi):
        self.nama = nama
        self.lokasi = lokasi

    def info(self):
        return f"{self.nama} - {self.lokasi}"

class Sungai(Ekosistem):
    def __init__(self, nama, lokasi, panjang_km):
        super().__init__(nama, lokasi)
        self.panjang_km = panjang_km

sungai = Sungai("Sungai Barito", "Banjarmasin", 25)

print(sungai.info())
print(f"Panjang: {sungai.panjang_km} km")
PYTHON,

            'output' => "Sungai Barito - Banjarmasin\nPanjang: 25 km",

            'tip' => 'super() tidak membuat object superclass baru. Pada contoh ini, pemanggilannya menginisialisasi bagian Ekosistem dari object Sungai yang sama.',
        ],

        // ==================================================
        // 4.3 METHOD OVERRIDING
        // ==================================================
        [
            'id' => 'method-overriding',
            'title' => '4.3 Method Overriding',

            'paragraphs' => [
                'Method overriding terjadi saat subclass mendefinisikan method dengan nama yang sama seperti method milik superclass untuk memberikan implementasi yang lebih spesifik.',

                'Pada contoh berikut, Rawa dan Mangrove mempunyai method info() masing-masing. Pemanggilan info() pada object akan memakai implementasi sesuai class object tersebut.',

                'Python tidak memerlukan keyword khusus untuk overriding. Kita cukup mendefinisikan ulang method dengan nama yang sama pada subclass.',
            ],

            'code' => <<<'PYTHON'
class Ekosistem:
    def __init__(self, nama, lokasi):
        self.nama = nama
        self.lokasi = lokasi

    def info(self):
        return f"{self.nama} - {self.lokasi}"

class Rawa(Ekosistem):
    def info(self):
        return f"Rawa {self.nama} berada di {self.lokasi}"

class Mangrove(Ekosistem):
    def info(self):
        return f"Kawasan mangrove {self.nama} - {self.lokasi}"

rawa = Rawa("Bangkau", "Hulu Sungai Selatan")
mangrove = Mangrove("Pesisir", "Tanah Laut")

print(rawa.info())
print(mangrove.info())
PYTHON,

            'output' => "Rawa Bangkau berada di Hulu Sungai Selatan\nKawasan mangrove Pesisir - Tanah Laut",

            'tip' => 'Overriding diperkenalkan dalam Bab 4. Cara memproses berbagai object dengan satu pemanggilan yang seragam akan dipelajari lebih lanjut pada Bab 5 Polimorfisme.',
        ],

        // ==================================================
        // 4.4 INHERITANCE VS COMPOSITION
        // ==================================================
        [
            'id' => 'inheritance-composition',
            'title' => '4.4 Inheritance vs Composition',

            'paragraphs' => [
                'Inheritance digunakan untuk memodelkan hubungan is-a: suatu class adalah jenis dari class lain. Contohnya, SensorPH adalah jenis SensorLingkungan.',

                'Composition digunakan untuk memodelkan hubungan has-a: sebuah object memiliki atau menggunakan object lain. Contohnya, StasiunPemantau memiliki kumpulan sensor.',

                'Jangan memakai inheritance hanya supaya kode tampak lebih pendek. Pilih relasi berdasarkan makna objek yang sedang dimodelkan.',
            ],

            'tables' => [
                [
                    'caption' => 'Perbandingan inheritance dan composition',

                    'headers' => [
                        'Relasi',
                        'Pertanyaan',
                        'Contoh OOPy',
                    ],

                    'rows' => [
                        [
                            'Inheritance (is-a)',
                            'Apakah A merupakan jenis dari B?',
                            'SensorPH adalah jenis SensorLingkungan.',
                        ],
                        [
                            'Composition (has-a)',
                            'Apakah A memiliki B?',
                            'StasiunPemantau memiliki beberapa SensorLingkungan.',
                        ],
                    ],
                ],
            ],
        ],

        // ==================================================
        // CONTOH COMPOSITION
        // ==================================================
        [
            'id' => 'contoh-composition',
            'title' => 'Contoh Composition: StasiunPemantau',

            'paragraphs' => [
                'StasiunPemantau menyimpan referensi ke sejumlah object sensor dalam sebuah list. Method tambah_sensor() memasukkan object sensor ke list tersebut.',

                'StasiunPemantau tidak mewarisi SensorLingkungan karena stasiun bukan jenis sensor. Hubungan yang tepat adalah memiliki sensor.',
            ],

            'code' => <<<'PYTHON'
class SensorLingkungan:
    def __init__(self, nama):
        self.nama = nama

class StasiunPemantau:
    def __init__(self, nama):
        self.nama = nama
        self.sensor = []

    def tambah_sensor(self, sensor):
        self.sensor.append(sensor)

stasiun = StasiunPemantau("Stasiun Rawa")

stasiun.tambah_sensor(SensorLingkungan("Sensor pH"))
stasiun.tambah_sensor(SensorLingkungan("Sensor Suhu"))

print(stasiun.nama)
print(len(stasiun.sensor))
PYTHON,

            'output' => "Stasiun Rawa\n2",
        ],

        // ==================================================
        // AYO COBA - LIVE CODING
        // ==================================================
        [
            'id' => 'ayo-coba-pewarisan',
            'title' => 'Ayo Coba – Live Coding Pewarisan',
            'nav_title' => 'Ayo Coba – Live Coding',

            'paragraphs' => [
                'Gunakan Ekosistem sebagai superclass untuk membuat dua subclass: Sungai dan Rawa. Sungai mempunyai atribut khusus panjang_km, sedangkan Rawa mempunyai atribut khusus luas_ha.',

                'Lengkapi constructor kedua subclass dengan super().__init__() untuk menyimpan nama dan lokasi. Kemudian override info() pada masing-masing subclass agar menampilkan nama, lokasi, dan atribut khusus.',

                'Klik Run Code untuk menjalankan kode. Setelah selesai, klik Submit untuk memeriksa pewarisan, atribut, pemanggilan super(), dan overriding. Kamu dapat mencoba membuat dua object dan menampilkan hasilnya pada akhir program.',
            ],

            'live_codes' => [
                [
                    'id' => 'bab4-pewarisan-ekosistem',

                    'title' => 'Coba sendiri: Sungai dan Rawa',

                    'description' => 'Lengkapi `Sungai` dan `Rawa` sebagai turunan `Ekosistem`. Buat `__init__()` menggunakan `super().__init__(nama, lokasi)`, simpan `panjang_km` atau `luas_ha`, dan override `info()` agar memuat nama, lokasi, serta atribut khusus. Buat object, lalu coba tampilkan hasil `info()`.',

                    'entry_file' => 'main.py',

                    // KODE AWAL UNTUK PENGGUNA
                    'files' => [
                        'main.py' => <<<'PYTHON'
class Ekosistem:
    def __init__(self, nama, lokasi):
        self.nama = nama
        self.lokasi = lokasi

    def info(self):
        return f"{self.nama} - {self.lokasi}"


class Sungai(Ekosistem):
    # TODO: buat __init__(nama, lokasi, panjang_km)
    # Panggil super().__init__(nama, lokasi)
    # Simpan panjang_km sebagai atribut instance
    # TODO: override info() dan tampilkan panjang_km
    pass


class Rawa(Ekosistem):
    # TODO: buat __init__(nama, lokasi, luas_ha)
    # Panggil super().__init__(nama, lokasi)
    # Simpan luas_ha sebagai atribut instance
    # TODO: override info() dan tampilkan luas_ha
    pass


# Setelah kedua subclass selesai, coba:
# sungai = Sungai("Sungai Barito", "Banjarmasin", 25)
# rawa = Rawa("Bangkau", "Hulu Sungai Selatan", 15)
# print(sungai.info())
# print(rawa.info())
PYTHON,
                    ],

                    // PEMERIKSA OTOMATIS LIVE CODING
                    'checker' => <<<'PYTHON'
import ast

results = []

def check(label, operation, hint):
    try:
        passed = bool(operation())

        results.append({
            "label": label,
            "passed": passed,
            "feedback": "" if passed else hint
        })

    except Exception as error:
        results.append({
            "label": label,
            "passed": False,
            "feedback": f"{hint} ({type(error).__name__}: {error})"
        })


def menggunakan_super(nama_class):
    with open("main.py", encoding="utf-8") as file:
        pohon = ast.parse(file.read())

    kelas = next(
        (
            n for n in pohon.body
            if isinstance(n, ast.ClassDef)
            and n.name == nama_class
        ),
        None
    )

    if kelas is None:
        return False

    init = next(
        (
            n for n in kelas.body
            if isinstance(n, (ast.FunctionDef, ast.AsyncFunctionDef))
            and n.name == "__init__"
        ),
        None
    )

    if init is None:
        return False

    return any(
        isinstance(n, ast.Call)
        and isinstance(n.func, ast.Attribute)
        and n.func.attr == "__init__"
        and isinstance(n.func.value, ast.Call)
        and isinstance(n.func.value.func, ast.Name)
        and n.func.value.func.id == "super"
        for n in ast.walk(init)
    )


def cek_sungai():
    obj = Sungai("Sungai Barito", "Banjarmasin", 25)

    return (
        obj.nama == "Sungai Barito"
        and obj.lokasi == "Banjarmasin"
        and obj.panjang_km == 25
    )


def cek_rawa():
    obj = Rawa("Bangkau", "Hulu Sungai Selatan", 15)

    return (
        obj.nama == "Bangkau"
        and obj.lokasi == "Hulu Sungai Selatan"
        and obj.luas_ha == 15
    )


def cek_info(kelas, nama, lokasi, angka):
    obj = kelas(nama, lokasi, angka)
    hasil = obj.info()

    return (
        isinstance(hasil, str)
        and nama in hasil
        and lokasi in hasil
        and str(angka) in hasil
        and " - " != hasil
    )


def cek_data_terpisah():
    satu = Sungai("Satu", "A", 10)
    dua = Sungai("Dua", "B", 20)

    return (
        satu.nama != dua.nama
        and satu.panjang_km != dua.panjang_km
    )


check(
    "Pewarisan Sungai",
    lambda: issubclass(Sungai, Ekosistem),
    "Deklarasikan class Sungai(Ekosistem)."
)

check(
    "Pewarisan Rawa",
    lambda: issubclass(Rawa, Ekosistem),
    "Deklarasikan class Rawa(Ekosistem)."
)

check(
    "Inisialisasi Sungai",
    cek_sungai,
    "Sungai harus menerima nama, lokasi, panjang_km dan menyimpan ketiganya."
)

check(
    "Inisialisasi Rawa",
    cek_rawa,
    "Rawa harus menerima nama, lokasi, luas_ha dan menyimpan ketiganya."
)

check(
    "super() pada kedua subclass",
    lambda: (
        menggunakan_super("Sungai")
        and menggunakan_super("Rawa")
    ),
    "Panggil super().__init__(nama, lokasi) di kedua constructor."
)

check(
    "Overriding info() Sungai",
    lambda: (
        Sungai.info is not Ekosistem.info
        and cek_info(
            Sungai,
            "Sungai Barito",
            "Banjarmasin",
            25
        )
    ),
    "Override info() Sungai agar memuat nama, lokasi, dan panjang_km."
)

check(
    "Overriding info() Rawa",
    lambda: (
        Rawa.info is not Ekosistem.info
        and cek_info(
            Rawa,
            "Bangkau",
            "Hulu Sungai Selatan",
            15
        )
    ),
    "Override info() Rawa agar memuat nama, lokasi, dan luas_ha."
)

check(
    "Data instance terpisah",
    cek_data_terpisah,
    "Setiap object harus menyimpan data instance yang berbeda."
)
PYTHON,
                ],
            ],
        ],

        // ==================================================
        // AYO BERLATIH
        // ==================================================
        [
            'id' => 'ayo-berlatih-pewarisan',
            'title' => 'Ayo Berlatih',

            'paragraphs' => [
                'Setelah menyelesaikan Live Coding, kerjakan latihan berikut menggunakan Editor OOPy. Jelaskan alasan ketika memilih inheritance atau composition.',
            ],

            'practice' => [
                'Buat superclass Perangkat dengan atribut nama dan status_aktif, kemudian turunkan SensorPH dan SensorSuhu.',

                'Tuliskan dua contoh relasi is-a dan dua contoh relasi has-a dalam konteks pemantauan lahan basah.',

                'Ubah satu desain yang awalnya memakai inheritance menjadi composition, lalu jelaskan alasan perubahan tersebut.',
            ],
        ],
    ],

    // ======================================================
    // RANGKUMAN
    // ======================================================
    'summary' => [
        'Inheritance memungkinkan subclass menggunakan kembali atribut dan method superclass untuk hubungan is-a.',

        'super() membantu subclass memanfaatkan inisialisasi atau implementasi superclass tanpa menduplikasi kode.',

        'Subclass dapat menambahkan atribut atau method baru dan melakukan method overriding.',

        'Composition digunakan untuk hubungan has-a, ketika suatu object memiliki object lain.',

        'Pilih inheritance atau composition berdasarkan makna hubungan antarkelas, bukan hanya untuk memperpendek kode.',
    ],

    // ======================================================
    // REFLEKSI
    // ======================================================
    'reflection' => [
        'Apa manfaat utama inheritance saat membuat beberapa class yang memiliki atribut sama?',

        'Mengapa tidak semua hubungan antarkelas cocok dimodelkan menggunakan inheritance?',

        'Jelaskan kegunaan super() melalui contoh class Ekosistem dan Sungai.',
    ],

    // ======================================================
    // KUIS BAB 4
    // 3 PILIHAN GANDA + 2 ISIAN KODE
    // ======================================================
    'quiz' => [

        // SOAL 1
        [
            'type' => 'multiple_choice',

            'question' => 'Superclass adalah ...',

            'options' => [
                'Object yang selalu private',
                'Class yang diwarisi oleh subclass',
                'Fungsi bawaan',
                'Variabel global',
            ],

            'correct' => 1,

            'explanation' => 'Superclass adalah class induk yang menyediakan atribut dan method untuk diwarisi subclass.',
        ],

        // SOAL 2
        [
            'type' => 'multiple_choice',

            'question' => 'Overriding terjadi ketika ...',

            'options' => [
                'Subclass mendefinisikan ulang method bernama sama',
                'Class tidak memiliki method',
                'Fungsi tidak memiliki parameter',
                'Object berubah menjadi string',
            ],

            'correct' => 0,

            'explanation' => 'Subclass menyediakan implementasi sendiri untuk method yang namanya sama dengan method superclass.',
        ],

        // SOAL 3
        [
            'type' => 'multiple_choice',

            'question' => '“Stasiun memiliki Sensor” paling tepat dimodelkan dengan ...',

            'options' => [
                'Inheritance wajib',
                'Rekursi',
                'Operator aritmatika',
                'Composition',
            ],

            'correct' => 3,

            'explanation' => 'StasiunPemantau memiliki Sensor; hubungan has-a tersebut lebih tepat dimodelkan dengan composition.',
        ],

        // SOAL 4 - ISIAN KODE
        [
            'type' => 'code_fill',

            'question' => 'Lengkapi definisi subclass berikut agar Sungai mewarisi Ekosistem.',

            'code' => <<<'PYTHON'
class Sungai(__________):
    pass
PYTHON,

            'answer' => 'Ekosistem',

            'explanation' => 'Superclass ditulis di dalam tanda kurung pada deklarasi subclass.',
        ],

        // SOAL 5 - ISIAN KODE
        [
            'type' => 'code_fill',

            'question' => 'Lengkapi constructor berikut agar subclass menggunakan inisialisasi superclass.',

            'code' => <<<'PYTHON'
class Sungai(Ekosistem):
    def __init__(self, nama, lokasi):
        ________________________________
PYTHON,

            'answer' => 'super().__init__(nama, lokasi)',

            'explanation' => 'Pemanggilan super().__init__(nama, lokasi) menjalankan initializer milik superclass.',
        ],
    ],
];
