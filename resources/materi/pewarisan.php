
<?php

return [
    'description' => 'Bangun keluarga class Ekosistem untuk Sungai, Rawa, dan Mangrove. Pelajari kapan memakai pewarisan, bagaimana super() menghindari pengulangan inisialisasi, dan kapan hubungan has-a lebih sesuai.',

    'objectives' => [
        'Menjelaskan hubungan superclass dan subclass menggunakan Ekosistem, Sungai, Rawa, dan Mangrove.',
        'Menggunakan method yang diwarisi dan menambahkan atribut khusus subclass.',
        'Menggunakan super().__init__() untuk memakai inisialisasi superclass tanpa mengulanginya.',
        'Melakukan method overriding untuk memberi perilaku yang lebih khusus.',
        'Memilih inheritance untuk hubungan is-a dan has-a untuk hubungan kepemilikan atau penggunaan object.',
        'Membedakan hubungan has-a sederhana dengan komposisi ketat dalam pemodelan UML.',
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
                'Pada BAB 2, class Ekosistem digunakan untuk membuat object dengan nama dan lokasi. Kini kita dapat memakai inheritance ketika beberapa jenis ekosistem memiliki data dan perilaku umum yang sama.',
                'Sungai, Rawa, dan Mangrove adalah jenis Ekosistem (is-a), sehingga dapat menjadi subclass. StasiunPemantau memiliki atau menggunakan Sensor (has-a); stasiun bukan jenis sensor.',
                'Kita akan melihat cara mewarisi method, menambahkan data khusus, dan memilih hubungan antarkelas berdasarkan maknanya, bukan sekadar untuk mempersingkat kode.',
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

                'Dalam contoh pemantauan lahan basah, Ekosistem menjadi superclass yang menyimpan data umum nama dan lokasi serta menyediakan method info(). Sungai, Rawa, dan Mangrove menjadi subclass karena masing-masing merupakan jenis ekosistem (is-a).',

                'Penulisan class Sungai(Ekosistem) berarti Sungai mewarisi atribut dan method Ekosistem. Jika subclass belum mendefinisikan ulang info(), object-nya tetap dapat memakai method induk tersebut.',
                'Contoh membuat satu object dari setiap subclass. Semuanya memakai info() yang diwarisi, tetapi masing-masing menyimpan data sendiri.',
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

class Rawa(Ekosistem):
    pass

class Mangrove(Ekosistem):
    pass

ekosistem = [
    Sungai("Sungai Barito", "Banjarmasin"),
    Rawa("Rawa Bangkau", "Hulu Sungai Selatan"),
    Mangrove("Pesisir", "Tanah Laut"),
]

for lokasi in ekosistem:
    print(lokasi.info())
PYTHON,

            'output' => <<<'OUTPUT'
Sungai Barito - Banjarmasin
Rawa Bangkau - Hulu Sungai Selatan
Pesisir - Tanah Laut
OUTPUT,

            'tip' => 'Kesalahan umum: menganggap subclass harus menyalin semua method induk. Jika method yang diwarisi sudah sesuai, subclass dapat langsung menggunakannya.',
        ],

        // ==================================================
        // 4.2 SUPER() DAN DUPLIKASI KODE
        // ==================================================
        [
            'id' => 'super-duplikasi',
            'title' => '4.2 super() dan Duplikasi Kode',

            'paragraphs' => [
                'Subclass dapat menambahkan atribut khusus. Pada contoh ini, Sungai memiliki panjang_km, sementara atribut nama dan lokasi sudah menjadi tanggung jawab Ekosistem.',

                'Saat subclass mendefinisikan __init__() sendiri, inisialisasi induk tidak berjalan otomatis. Panggil super().__init__(nama, lokasi) agar aturan penyimpanan nama dan lokasi digunakan kembali.',

                'Tanpa super(), kita harus mengulang self.nama = nama dan self.lokasi = lokasi di setiap subclass. Pengulangan membuat perubahan pada inisialisasi umum harus diperbaiki di banyak tempat dan dapat tidak konsisten.',

                'Setelah inisialisasi induk selesai, subclass menambahkan atributnya sendiri, yaitu self.panjang_km.',
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

            'tip' => 'super() tidak membuat object superclass baru. Pemanggilannya menjalankan implementasi berikutnya dalam hierarki untuk object Sungai yang sama. Pastikan argumen pada super().__init__() sesuai dengan parameter constructor induk.',
        ],

        // ==================================================
        // 4.3 METHOD OVERRIDING
        // ==================================================
        [
            'id' => 'method-overriding',
            'title' => '4.3 Method Overriding',

            'paragraphs' => [
                'Method overriding terjadi ketika subclass mendefinisikan method dengan nama yang sama untuk mengganti atau mengkhususkan perilaku yang diwarisi.',

                'Ekosistem menyediakan info() umum. Rawa dan Mangrove meng-override info() agar hasilnya lebih sesuai dengan jenis ekosistem tersebut; object Sungai yang tidak meng-override tetap memakai method umum.',

                'Saat kita memanggil objek.info(), Python menjalankan implementasi yang sesuai dengan class object itu. Cara pemanggilan tetap sama, sementara perilakunya dapat berbeda; ide ini akan dipakai kembali pada BAB 5 tentang polimorfisme.',

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
                'Inheritance digunakan untuk memodelkan hubungan is-a: suatu class adalah jenis dari class lain. Contohnya, Sungai adalah jenis Ekosistem.',

                'Hubungan has-a menunjukkan bahwa sebuah object memiliki atau menggunakan object lain. Contohnya, StasiunPemantau menyimpan beberapa object sensor.',

                'Dalam kode Python, menyimpan object sensor di dalam list stasiun menunjukkan has-a sederhana. Fakta bahwa satu object menyimpan referensi ke object lain belum dengan sendirinya menentukan kepemilikan kuat atau masa hidup object tersebut.',

                'Dalam UML, komposisi ketat ditandai wajik terisi di sisi whole (pemilik). Model itu menyatakan bagian dimiliki secara kuat oleh whole dan masa hidup bagian terkait dengan whole. Relasi has-a sederhana tidak otomatis berarti komposisi ketat; Python juga tidak menegakkan aturan UML tersebut secara otomatis.',

                'Pilih hubungan berdasarkan makna dan kebutuhan kepemilikan object, bukan sekadar agar kode lebih pendek.',
            ],

            'tables' => [
                [
                    'caption' => 'Perbandingan inheritance dan composition',

                    'headers' => [
                        'Relasi',
                        'Pertanyaan',
                        'Contoh OOPy',
                        'Makna hubungan',
                    ],

                    'rows' => [
                        [
                            'Inheritance (is-a)',
                            'Apakah A merupakan jenis dari B?',
                            'Sungai adalah jenis Ekosistem.',
                            'Subclass mewarisi bagian umum superclass dan dapat menambahkan atau mengkhususkan perilakunya.',
                        ],
                        [
                            'Has-a sederhana',
                            'Apakah A memiliki atau menggunakan B?',
                            'StasiunPemantau menyimpan beberapa SensorLingkungan.',
                            'Menunjukkan object berhubungan; belum menetapkan bahwa stasiun menguasai siklus hidup sensor.',
                        ],
                        [
                            'Komposisi ketat (UML)',
                            'Apakah A merupakan whole yang memiliki bagian secara kuat?',
                            'StasiunPemantau memiliki bagian Sensor yang masa hidupnya terkait dengan stasiun.',
                            'Wajik terisi berada di sisi whole; aturan kepemilikan kuat ini tidak muncul otomatis hanya karena Python menyimpan referensi.',
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

                'StasiunPemantau tidak mewarisi SensorLingkungan karena stasiun bukan jenis sensor. Contoh ini menunjukkan has-a sederhana: object sensor dibuat di luar stasiun, lalu referensinya disimpan. Model ini tidak menyatakan aturan komposisi ketat UML tentang kepemilikan dan masa hidup bagian.',
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

            'tables' => [
                [
                    'caption' => 'Cara mengerjakan latihan multi-file',
                    'headers' => ['Langkah', 'File', 'Tugas'],
                    'rows' => [
                        ['1', ['code' => 'ekosistem.py'], 'Pelajari class induk Ekosistem yang sudah lengkap.'],
                        ['2', ['code' => 'sungai.py'], 'Lengkapi constructor dan info() pada subclass Sungai.'],
                        ['3', ['code' => 'rawa.py'], 'Lengkapi constructor dan info() pada subclass Rawa.'],
                        ['4', ['code' => 'main.py'], 'Buat kedua object dan tampilkan informasi masing-masing.'],
                        ['5', ['code' => 'main.py'], 'Klik Run Code untuk menjalankan program, kemudian Submit untuk memeriksa.'],
                    ],
                ],
            ],

            'live_codes' => [
                [
                    'id' => 'bab4-pewarisan-ekosistem',

                    'title' => 'Coba sendiri: Sungai dan Rawa',

                    'description' => 'Gunakan empat file Python: `ekosistem.py` menyediakan class induk `Ekosistem`. Lengkapi `Sungai` di `sungai.py` dan `Rawa` di `rawa.py` dengan `super().__init__(nama, lokasi)`, atribut `panjang_km` atau `luas_ha`, dan overriding `info()`. Di `main.py`, buat kedua object dan tampilkan informasinya. Klik Run Code, lalu Submit.',

                    'entry_file' => 'main.py',

                    // KODE AWAL UNTUK PENGGUNA
                    'files' => [
                        'ekosistem.py' => <<<'PYTHON'
class Ekosistem:
    def __init__(self, nama, lokasi):
        self.nama = nama
        self.lokasi = lokasi

    def info(self):
        return f"{self.nama} - {self.lokasi}"
PYTHON,
                        'sungai.py' => <<<'PYTHON'
from ekosistem import Ekosistem


class Sungai(Ekosistem):
    # TODO: buat __init__(nama, lokasi, panjang_km)
    # Panggil super().__init__(nama, lokasi)
    # Simpan panjang_km sebagai atribut instance
    # TODO: override info() dan tampilkan panjang_km
    pass
PYTHON,
                        'rawa.py' => <<<'PYTHON'
from ekosistem import Ekosistem


class Rawa(Ekosistem):
    # TODO: buat __init__(nama, lokasi, luas_ha)
    # Panggil super().__init__(nama, lokasi)
    # Simpan luas_ha sebagai atribut instance
    # TODO: override info() dan tampilkan luas_ha
    pass
PYTHON,
                        'main.py' => <<<'PYTHON'
from sungai import Sungai
from rawa import Rawa


# TODO: Buat object Sungai dan Rawa setelah kedua subclass selesai.
# Sungai: nama="Sungai Barito", lokasi="Banjarmasin", panjang_km=25.
# Rawa: nama="Bangkau", lokasi="Hulu Sungai Selatan", luas_ha=15.
# TODO: Tampilkan informasi keduanya menggunakan method info().
PYTHON,
                    ],

                    // PEMERIKSA OTOMATIS LIVE CODING
                    'checker' => <<<'PYTHON'
import builtins
import importlib
import sys

results = []


def class_project(module_name, class_name):
    try:
        module = importlib.import_module(module_name)
        kelas = getattr(module, class_name)
        if not isinstance(kelas, type) or kelas.__module__ != module_name:
            return None
        return kelas
    except Exception:
        return None


# Main may import only the subclasses. Inspect the project modules themselves.
Ekosistem = class_project("ekosistem", "Ekosistem")
Sungai = class_project("sungai", "Sungai")
Rawa = class_project("rawa", "Rawa")

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
    kelas = globals().get(nama_class)
    init = kelas.__dict__.get("__init__")
    if init is None:
        return False

    # Observe an actual super().__init__ call, not comments or unreachable code.
    # Resolve zero-argument super in the learner's frame; explicit super works too.
    namespace = init.__globals__
    missing = object()
    previous = namespace.get("super", missing)
    calls = []

    class SuperProbe:
        def __getattribute__(self, name):
            target = object.__getattribute__(self, "target")
            method = getattr(target, name)
            if name != "__init__":
                return method

            def initialize(*args, **kwargs):
                result = method(*args, **kwargs)
                obj = target.__self__
                calls.append((obj, getattr(obj, "nama", None), getattr(obj, "lokasi", None)))
                return result
            return initialize

    def observed_super(*args):
        if not args:
            frame = sys._getframe(1)
            args = (frame.f_locals["__class__"],
                    frame.f_locals[frame.f_code.co_varnames[0]])
        proxy = object.__new__(SuperProbe)
        object.__setattr__(proxy, "target", builtins.super(*args))
        return proxy

    try:
        namespace["super"] = observed_super
        obj = kelas("Uji super", "Lokasi uji", 12.5)
        return (any(instance is obj and nama == "Uji super" and lokasi == "Lokasi uji"
                    for instance, nama, lokasi in calls)
                and obj.nama == "Uji super" and obj.lokasi == "Lokasi uji")
    finally:
        if previous is missing:
            namespace.pop("super", None)
        else:
            namespace["super"] = previous


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
    for data in ((nama, lokasi, angka), ("Uji lain", "Lokasi lain", 12.5)):
        hasil = kelas(*data).info()
        if not isinstance(hasil, str) or not all(str(value) in hasil for value in data):
            return False
    return True


def cek_data_terpisah():
    for kelas, atribut in ((Sungai, "panjang_km"), (Rawa, "luas_ha")):
        satu = kelas("Satu", "A", 10)
        dua = kelas("Dua", "B", 20)
        if (satu.nama, satu.lokasi, getattr(satu, atribut)) != ("Satu", "A", 10):
            return False
        satu.nama = "Diubah"
        satu.lokasi = "C"
        setattr(satu, atribut, 30)
        if (dua.nama, dua.lokasi, getattr(dua, atribut)) != ("Dua", "B", 20):
            return False
    return True


check(
    "Pewarisan Sungai",
    lambda: issubclass(Sungai, Ekosistem),
    "Periksa import Ekosistem dan deklarasi class Sungai(Ekosistem) di sungai.py."
)

check(
    "Pewarisan Rawa",
    lambda: issubclass(Rawa, Ekosistem),
    "Periksa import Ekosistem dan deklarasi class Rawa(Ekosistem) di rawa.py."
)

check(
    "Inisialisasi Sungai",
    cek_sungai,
    "Lengkapi __init__ di sungai.py: terima nama, lokasi, panjang_km dan simpan ketiganya."
)

check(
    "Inisialisasi Rawa",
    cek_rawa,
    "Lengkapi __init__ di rawa.py: terima nama, lokasi, luas_ha dan simpan ketiganya."
)

check(
    "super() pada kedua subclass",
    lambda: (
        menggunakan_super("Sungai")
        and menggunakan_super("Rawa")
    ),
    "Jalankan super().__init__(nama, lokasi) di constructor sungai.py dan rawa.py."
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
    "Override info() di sungai.py agar memuat nama, lokasi, dan panjang_km."
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
    "Override info() di rawa.py agar memuat nama, lokasi, dan luas_ha."
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
                'Buat class Ekosistem dengan nama dan lokasi, lalu buat subclass Sungai menggunakan super().__init__() dan tambahkan atribut panjang_km. Jelaskan bagian mana yang diwarisi dan bagian mana yang khusus.',

                'Tuliskan dua contoh relasi is-a dan dua contoh relasi has-a dalam konteks pemantauan lahan basah.',

                'Ubah satu desain yang awalnya memakai inheritance menjadi has-a sederhana, lalu jelaskan alasan perubahan tersebut. Apakah hubungan itu juga berarti komposisi ketat UML? Jelaskan kepemilikan atau masa hidup bagian yang dimaksud.',
            ],
        ],
    ],

    // ======================================================
    // RANGKUMAN
    // ======================================================
    'summary' => [
        'Inheritance menghubungkan superclass dengan subclass untuk hubungan is-a; Sungai, Rawa, dan Mangrove dapat menjadi jenis Ekosistem.',

        'Subclass mewarisi method induk, dapat menambahkan atribut sendiri, dan dapat meng-override method untuk perilaku yang lebih khusus.',

        'super().__init__() menjalankan inisialisasi induk pada object subclass dan mencegah pengulangan penyimpanan atribut umum.',

        'Has-a sederhana menunjukkan bahwa object memiliki atau menggunakan object lain. Komposisi ketat UML menambahkan gagasan kepemilikan kuat dan hubungan masa hidup.',

        'Pilih inheritance atau has-a berdasarkan makna relasi dan kebutuhan kepemilikan object.',
    ],

    // ======================================================
    // REFLEKSI
    // ======================================================
    'reflection' => [
        'Mengapa Sungai, Rawa, dan Mangrove dapat menjadi subclass Ekosistem, sedangkan StasiunPemantau tidak?',

        'Bagaimana super().__init__() mengurangi duplikasi saat Sungai memiliki atribut umum dan atribut khusus?',

        'Apa perbedaan has-a sederhana dengan komposisi ketat dalam UML?',
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
