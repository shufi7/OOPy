<?php

return [
    'description' => 'Lanjutkan dari variabel dan fungsi prosedural di BAB 1: kelompokkan data serta perilaku yang selalu berkaitan ke dalam class dan object untuk memodelkan ekosistem lahan basah.',

    'objectives' => [
        'Menjelaskan perbedaan class, object atau instance, atribut, method, dan state.',
        'Membuat class Ekosistem dengan __init__() untuk menginisialisasi atribut instance.',
        'Menjelaskan peran self sebagai referensi ke instance yang sedang digunakan method.',
        'Membuat beberapa instance dari satu class dengan data dan state yang berbeda.',
        'Memanggil method untuk membaca dan mengubah state sebuah object.',
    ],

    'sections' => [

        /*
        |--------------------------------------------------------------------------
        | 1. Apersepsi
        |--------------------------------------------------------------------------
        */

        [
            'id' => 'apersepsi',
            'title' => 'Apersepsi',
            'nav_group' => 'pendahuluan',
            'paragraphs' => [
                'Pada BAB 1, kita menyimpan data lokasi dalam variabel dan mengolahnya dengan fungsi. Cara itu cocok untuk tugas sederhana, tetapi saat banyak lokasi dipantau, pasangan data dan fungsi untuk setiap lokasi dapat semakin sulit dikelola.',
                'Class membantu mengelompokkan pola data dan perilaku yang sama. Kita dapat merancang class Ekosistem, lalu membuat object Sungai, Rawa, atau Mangrove yang masing-masing menyimpan nama dan lokasi sendiri.',
                'Di bab ini, kita akan membedakan rancangan (class) dari object yang dibuat berdasarkan rancangan tersebut, lalu melihat bagaimana atribut dan method bekerja bersama.',
            ],
            'media' => [
                [
                    'type' => 'photo',
                    'src' => 'images/materi/photos/bab2-rawa-kalimantan-selatan.webp',
                    'alt' => 'Penggembala dan kerbau rawa berada di perairan rawa Daha Utara, Kabupaten Hulu Sungai Selatan, Kalimantan Selatan.',
                    'caption' => 'Rawa dan aktivitas masyarakat di Daha Utara memberi gambaran ekosistem nyata yang bisa dimodelkan sebagai object.',
                    'source_label' => 'Foto: Herusutimbul · Wikimedia Commons (diubah ukuran dan dikonversi ke WebP)',
                    'source_url' => 'https://commons.wikimedia.org/wiki/File:Penggembala_Kerbau_Rawa.jpg',
                    'license_label' => 'CC BY-SA 4.0',
                    'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0/',
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2. Class dan Object
        |--------------------------------------------------------------------------
        */

        [
            'id' => 'class-object',
            'title' => '2.1 Class dan Object',
            'paragraphs' => [
                'Class adalah rancangan yang mendefinisikan atribut dan method yang tersedia pada object. Object adalah satu hasil nyata yang dibuat dari class. Istilah instance merujuk pada object yang dibuat dari class tersebut; dalam percakapan OOP, object dan instance sering dipakai untuk hal yang sama.',
                'Atribut adalah data milik object, sedangkan method adalah fungsi yang didefinisikan di dalam class dan dapat dipanggil melalui object. Nilai atribut pada suatu waktu membentuk state atau keadaan object.',
                'Pada contoh ini, Ekosistem adalah class, rawa adalah object sekaligus instance, nama dan lokasi adalah atribut, dan info() adalah method.',
            ],
            'media' => [
                [
                    'type' => 'diagram',
                    'src' => 'images/materi/diagram/bab2-class-object.svg',
                    'alt' => 'Class Ekosistem dengan atribut nama dan lokasi serta method info menghasilkan object sungai, rawa, dan mangrove yang memiliki nilai berbeda.',
                    'caption' => 'Satu class menjadi rancangan bagi beberapa object dengan state yang berbeda.',
                ],
            ],
            'code' => <<<'PYTHON'
class Ekosistem:
    def __init__(self, nama, lokasi):
        self.nama = nama
        self.lokasi = lokasi

    def info(self):
        return f"{self.nama} berada di {self.lokasi}"

rawa = Ekosistem("Rawa", "Kalimantan Selatan")
print(rawa.info())
PYTHON,
            'output' => 'Rawa berada di Kalimantan Selatan',
            'tip' => 'Class adalah rancangan; object atau instance adalah hasil yang dibuat darinya. Atribut menyimpan data, method menyediakan perilaku, dan nilai atribut saat ini membentuk state object.',
        ],

        /*
        |--------------------------------------------------------------------------
        | 3. Bedah Kode
        |--------------------------------------------------------------------------
        */

        [
            'id' => 'bedah-kode',
            'title' => '2.2 Bedah Kode',
            'paragraphs' => [
                'class Ekosistem: digunakan untuk membuat class baru dengan nama Ekosistem. Class ini menjadi rancangan untuk object yang akan dibuat nantinya.',
                '__init__ adalah method inisialisasi yang dijalankan otomatis saat Ekosistem(...) membuat instance. Method ini menyiapkan nilai awal object; dalam contoh, nama dan lokasi diberikan saat object dibuat.',
                'self adalah parameter yang merujuk pada instance yang sedang ditangani method. Saat kita menulis rawa.info(), Python mengirimkan instance rawa sebagai self secara otomatis; kita tidak menulis rawa sebagai argumen pertama.',
                'self.nama dan self.lokasi merupakan atribut instance. Atribut tersebut menyimpan data yang dimiliki oleh masing-masing object.',
                'rawa = Ekosistem("Rawa", "Kalimantan Selatan") membuat sebuah object bernama rawa dari class Ekosistem.',
                'rawa.info() memanggil method info() milik instance rawa. Method membaca atribut melalui self lalu mengembalikan teks; print() menampilkan teks tersebut.',
            ],
            'code' => <<<'PYTHON'
class Ekosistem:
    def __init__(self, nama, lokasi):
        self.nama = nama
        self.lokasi = lokasi

    def info(self):
        return f"{self.nama} berada di {self.lokasi}"


rawa = Ekosistem("Rawa", "Kalimantan Selatan")

print(rawa.info())
PYTHON,
            'output' => 'Rawa berada di Kalimantan Selatan',
            'tip' => 'Kesalahan umum: menghilangkan self pada parameter method, atau mengira self harus diberikan sendiri saat menulis rawa.info(). Python mengisi referensi instance itu saat method dipanggil melalui object.',
        ],

        /*
        |--------------------------------------------------------------------------
        | 4. Membuat Banyak Object
        |--------------------------------------------------------------------------
        */

        [
            'id' => 'banyak-object',
            'title' => '2.3 Membuat Beberapa Object dari Satu Class',
            'nav_title' => '2.3 Membuat Beberapa Object',
            'paragraphs' => [
                'Satu class dapat menghasilkan banyak object dengan data awal berbeda. Masing-masing instance memiliki atribut instance sendiri, sehingga mengubah sungai.nama hanya mengubah atribut milik object sungai, bukan atribut rawa atau mangrove.',
                'Di sini, sungai, rawa, dan mangrove dibuat dari class Ekosistem yang sama. Setiap object menyimpan nama dan lokasi sendiri; kesamaan rancangan tidak membuat nilai atribut mereka menjadi satu data bersama.',
                'Object dapat ditempatkan dalam list dan diproses dengan for. Perubahan pada satu object tetap merujuk pada instance yang sama dan tidak otomatis mengubah instance lain.',
            ],
            'code' => <<<'PYTHON'
class Ekosistem:
    def __init__(self, nama, lokasi):
        self.nama = nama
        self.lokasi = lokasi

    def info(self):
        return f"{self.nama} berada di {self.lokasi}"


sungai = Ekosistem("Sungai", "Banjarmasin")
rawa = Ekosistem("Rawa", "Hulu Sungai")
mangrove = Ekosistem("Mangrove", "Pesisir")

print(sungai.info())
print(rawa.info())
print(mangrove.info())

sungai.nama = "Sungai Barito"
print("Setelah nama sungai diperbarui:")
for objek in [sungai, rawa, mangrove]:
    print(objek.info())
PYTHON,
            'output' => <<<'OUTPUT'
Sungai berada di Banjarmasin
Rawa berada di Hulu Sungai
Mangrove berada di Pesisir
Setelah nama sungai diperbarui:
Sungai Barito berada di Banjarmasin
Rawa berada di Hulu Sungai
Mangrove berada di Pesisir
OUTPUT,
            'tip' => 'Setiap pemanggilan Ekosistem(...) membuat instance baru. Atribut instance seperti self.nama tidak dipakai bersama oleh semua object; karena itu perubahan pada satu object tidak otomatis diterapkan ke object lain.',
        ],

        /*
        |--------------------------------------------------------------------------
        | 5. Atribut dan Method
        |--------------------------------------------------------------------------
        */

        [
            'id' => 'atribut-method',
            'title' => '2.4 Atribut dan Method',
            'paragraphs' => [
                'Atribut menyimpan data object. State adalah keadaan object pada suatu waktu, yaitu nilai atributnya saat itu. Dua instance dari class yang sama dapat memiliki state berbeda.',
                'Method adalah fungsi di dalam class yang bekerja pada instance melalui self. Method dapat membaca atau mengubah atribut object tersebut.',
                'Contoh ini membuat object sungai dan rawa. Keduanya bermula dengan status "Belum aktif"; pemanggilan sungai.mulai_pantau() mengubah state sungai menjadi "Aktif" sementara state rawa tetap sama.',
            ],
            'code' => <<<'PYTHON'
class Ekosistem:
    def __init__(self, nama, lokasi):
        self.nama = nama
        self.lokasi = lokasi
        self.dipantau = False

    def mulai_pantau(self):
        self.dipantau = True

    def status(self):
        return "Aktif" if self.dipantau else "Belum aktif"

sungai = Ekosistem("Sungai Barito", "Banjarmasin")
rawa = Ekosistem("Rawa Bangkau", "Hulu Sungai Selatan")

print(sungai.status())
sungai.mulai_pantau()
print(sungai.status())
print(rawa.status())
PYTHON,
            'output' => <<<'OUTPUT'
Belum aktif
Aktif
Belum aktif
OUTPUT,
            'tip' => 'State bukan class atau method: state adalah nilai atribut instance saat ini. Kesalahan umum adalah mengira method terdefinisi otomatis berjalan; method mulai_pantau() baru mengubah nilai setelah dipanggil.',
            'practice' => [
                'Buat dua object Ekosistem dengan nama dan lokasi berbeda. Ubah atribut nama pada object pertama, lalu tampilkan nama kedua object untuk membuktikan perubahan itu bersifat per-instance.',
                'Tambahkan atribut status_awal pada class Ekosistem dan method aktifkan() yang mengubah statusnya. Sebutkan class, object, atribut, method, dan state dalam programmu.',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 6. Live Coding Spesies
        |--------------------------------------------------------------------------
        */

        [
            'id' => 'live-coding-spesies',
            'title' => 'Live Coding: Class Spesies',
            'nav_title' => 'Live Coding: Spesies',
            'paragraphs' => [
                'Sekarang kita akan membuat class Spesies untuk memodelkan spesies yang hidup di lingkungan lahan basah. Class ini memiliki atribut nama dan habitat.',
                'Buat constructor __init__ untuk menyimpan kedua nilai tersebut. Kemudian buat method deskripsi() yang mengembalikan informasi mengenai spesies dan habitatnya.',
                'Setelah class selesai, buat dua object dengan data yang berbeda dan tampilkan deskripsi dari masing-masing object.',
            ],
            'code' => <<<'PYTHON'
class Spesies:
    def __init__(self, nama, habitat):
        self.nama = nama
        self.habitat = habitat

    def deskripsi(self):
        return f"{self.nama} hidup di {self.habitat}"


spesies1 = Spesies("Bekantan", "Hutan riparian")
spesies2 = Spesies("Ikan lokal", "Perairan rawa")

print(spesies1.deskripsi())
print(spesies2.deskripsi())
PYTHON,
            'live_codes' => [
                [
                    'id' => 'bab2-spesies',
                    'title' => 'Coba sendiri: Class Spesies',
                    'description' => 'Lengkapi class `Spesies` dengan atribut `nama` dan `habitat` serta method `deskripsi()`. Setelah itu buat dua object dan tampilkan deskripsinya.',
                    'entry_file' => 'main.py',
                    'files' => [
                        'main.py' => <<<'PYTHON'
class Spesies:
    def __init__(self, nama, habitat):
        # Ganti pass: simpan nama dan habitat sebagai atribut instance.
        pass

    def deskripsi(self):
        # Ganti pass: kembalikan string yang memuat nama dan habitat object.
        pass


# Buat spesies1: Bekantan dengan habitat Hutan riparian.
# Buat spesies2: Ikan lokal dengan habitat Perairan rawa.
# Tampilkan deskripsi kedua object menggunakan print().
PYTHON,
                    ],
                    'checker' => <<<'PYTHON'
assert hasattr(Spesies, "__init__"), "Class Spesies harus memiliki constructor __init__."
assert hasattr(Spesies, "deskripsi"), "Class Spesies harus memiliki method deskripsi()."

spesies1 = Spesies("Bekantan", "Hutan riparian")
spesies2 = Spesies("Ikan lokal", "Perairan rawa")

assert spesies1.nama == "Bekantan", "Atribut nama spesies1 belum benar."
assert spesies1.habitat == "Hutan riparian", "Atribut habitat spesies1 belum benar."

assert spesies2.nama == "Ikan lokal", "Atribut nama spesies2 belum benar."
assert spesies2.habitat == "Perairan rawa", "Atribut habitat spesies2 belum benar."

hasil1 = spesies1.deskripsi()
hasil2 = spesies2.deskripsi()

assert isinstance(hasil1, str), "Method deskripsi() harus mengembalikan string."
assert isinstance(hasil2, str), "Method deskripsi() harus mengembalikan string."

assert "Bekantan" in hasil1 and "Hutan riparian" in hasil1, \
    "Deskripsi spesies1 harus memuat nama dan habitat."

assert "Ikan lokal" in hasil2 and "Perairan rawa" in hasil2, \
    "Deskripsi spesies2 harus memuat nama dan habitat."
PYTHON,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 7. Latihan Sensor Air
        |--------------------------------------------------------------------------
        */

        [
            'id' => 'latihan-sensor-air',
            'title' => 'Latihan: Sensor Air',
            'paragraphs' => [
                'Setelah memahami class dan object, kita dapat menggunakannya untuk memodelkan sensor air. Buat class SensorAir dengan atribut lokasi dan tinggi_air.',
                'Tambahkan method tampilkan() yang menghasilkan teks singkat mengenai kondisi sensor. Setelah class selesai, buat tiga object SensorAir dengan lokasi dan nilai tinggi air yang berbeda.',
                'Simpan ketiga object tersebut dalam sebuah list, kemudian gunakan perulangan for untuk menampilkan informasi dari masing-masing sensor.',
            ],
            'code' => <<<'PYTHON'
class SensorAir:
    def __init__(self, lokasi, tinggi_air):
        self.lokasi = lokasi
        self.tinggi_air = tinggi_air

    def tampilkan(self):
        return f"Sensor di {self.lokasi}: tinggi air {self.tinggi_air} cm"


sensor1 = SensorAir("Sungai Barito", 120)
sensor2 = SensorAir("Rawa Bangkau", 85)
sensor3 = SensorAir("Pesisir", 60)

sensor = [sensor1, sensor2, sensor3]

for objek in sensor:
    print(objek.tampilkan())
PYTHON,
            'live_codes' => [
                [
                    'id' => 'bab2-sensor-air',
                    'title' => 'Latihan coding: Sensor Air',
                    'description' => 'Lengkapi class `SensorAir` dengan atribut `lokasi` dan `tinggi_air` serta method `tampilkan()`. Buat tiga object dengan data berbeda, lalu tampilkan semuanya menggunakan perulangan.',
                    'entry_file' => 'main.py',
                    'files' => [
                        'main.py' => <<<'PYTHON'
class SensorAir:
    def __init__(self, lokasi, tinggi_air):
        # Ganti pass: simpan lokasi dan tinggi_air sebagai atribut instance.
        pass

    def tampilkan(self):
        # Ganti pass: kembalikan string yang memuat lokasi dan tinggi air.
        pass


# Buat tiga object: Sungai Barito (120 cm), Rawa Bangkau (85 cm),
# dan Pesisir (60 cm). Simpan ketiganya dalam list bernama sensor.
# Gunakan for untuk menampilkan hasil tampilkan() dari setiap object.
PYTHON,
                    ],
                    'checker' => <<<'PYTHON'
assert hasattr(SensorAir, "__init__"), \
    "Class SensorAir harus memiliki constructor __init__."

assert hasattr(SensorAir, "tampilkan"), \
    "Class SensorAir harus memiliki method tampilkan()."

sensor1 = SensorAir("Sungai Barito", 120)
sensor2 = SensorAir("Rawa Bangkau", 85)
sensor3 = SensorAir("Pesisir", 60)

assert sensor1.lokasi == "Sungai Barito", \
    "Periksa atribut lokasi sensor1."

assert sensor1.tinggi_air == 120, \
    "Periksa atribut tinggi_air sensor1."

assert sensor2.lokasi == "Rawa Bangkau", \
    "Periksa atribut lokasi sensor2."

assert sensor2.tinggi_air == 85, \
    "Periksa atribut tinggi_air sensor2."

assert sensor3.lokasi == "Pesisir", \
    "Periksa atribut lokasi sensor3."

assert sensor3.tinggi_air == 60, \
    "Periksa atribut tinggi_air sensor3."

sensor = [sensor1, sensor2, sensor3]

assert len(sensor) == 3, \
    "List sensor harus berisi tiga object."

assert all(isinstance(objek, SensorAir) for objek in sensor), \
    "Semua isi list sensor harus merupakan object SensorAir."

for objek in sensor:
    hasil = objek.tampilkan()

    assert isinstance(hasil, str), \
        "Method tampilkan() harus mengembalikan string."

    assert objek.lokasi in hasil, \
        "Hasil tampilkan() harus memuat lokasi sensor."

    assert str(objek.tinggi_air) in hasil, \
        "Hasil tampilkan() harus memuat nilai tinggi air."
PYTHON,
                ],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Rangkuman
    |--------------------------------------------------------------------------
    */

    'summary' => [
        'Class adalah rancangan yang mendefinisikan atribut dan method; object atau instance adalah hasil yang dibuat dari class tersebut.',
        '__init__ menginisialisasi nilai awal ketika object dibuat, sedangkan self merujuk pada instance yang sedang ditangani method.',
        'Atribut menyimpan data object. State adalah nilai atribut instance pada suatu waktu; method membaca atau mengubah data melalui self.',
        'Satu class dapat menghasilkan banyak instance. Setiap instance memiliki atribut dan state sendiri, sehingga perubahan pada satu object tidak otomatis mengubah object lain.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Refleksi
    |--------------------------------------------------------------------------
    */

    'reflection' => [
        'Apa perbedaan class, object, dan instance? Berikan contoh menggunakan Ekosistem.',
        'Dalam class Ekosistem, mana yang termasuk atribut dan mana yang termasuk method? Apa yang dimaksud state object?',
        'Mengapa perubahan nama atau status pada satu object Ekosistem tidak otomatis mengubah object Ekosistem lain?',
    ],

    /*
    |--------------------------------------------------------------------------
    | Kuis
    |--------------------------------------------------------------------------
    */

    'quiz' => [
        [
            'type' => 'multiple_choice',
            'question' => 'Apa yang dimaksud dengan class dalam Python?',
            'options' => [
                'Nilai yang disimpan dalam sebuah variabel',
                'Cetak biru atau rancangan untuk membuat object',
                'Perulangan untuk menjalankan program',
                'Hasil dari pemanggilan sebuah method',
            ],
            'correct' => 1,
            'explanation' => 'Class merupakan cetak biru atau rancangan yang digunakan untuk membuat object.',
        ],
        [
            'type' => 'multiple_choice',
            'question' => 'Apa fungsi __init__ pada sebuah class?',
            'options' => [
                'Menghapus object',
                'Menampilkan semua object',
                'Menginisialisasi nilai awal object ketika dibuat',
                'Mengulang method secara otomatis',
            ],
            'correct' => 2,
            'explanation' => 'Constructor __init__ dipanggil ketika object dibuat dan digunakan untuk memberikan nilai awal pada object.',
        ],
        [
            'type' => 'multiple_choice',
            'question' => 'Apa yang dimaksud dengan self dalam method sebuah class?',
            'options' => [
                'Nama class',
                'Object yang sedang menggunakan method',
                'Nama file Python',
                'Nilai yang dikembalikan method',
            ],
            'correct' => 1,
            'explanation' => 'self merujuk pada object yang sedang menggunakan method sehingga atribut dan method milik object dapat diakses.',
        ],
        [
            'type' => 'code_fill',
            'question' => 'Lengkapi assignment untuk menyimpan parameter nama sebagai instance attribute bernama nama. Gunakan satu spasi di kedua sisi tanda =.',
            'code' => <<<'PYTHON'
class Ekosistem:
    def __init__(self, nama, lokasi):
        __________
        self.lokasi = lokasi
PYTHON,
            'answer' => 'self.nama = nama',
            'explanation' => 'self.nama = nama menyimpan nilai parameter nama pada atribut milik masing-masing object.',
        ],
        [
            'type' => 'code_fill',
            'question' => 'Lengkapi nama class yang dipanggil untuk membuat object rawa dengan nama dan lokasi sesuai materi.',
            'code' => 'rawa = __________("Rawa", "Kalimantan Selatan")',
            'answer' => 'Ekosistem',
            'explanation' => 'Memanggil Ekosistem dengan parameter nama dan lokasi membuat object atau instance dari class tersebut.',
        ],
    ],
];
