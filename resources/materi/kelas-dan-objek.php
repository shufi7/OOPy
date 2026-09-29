<?php

return [
    'description' => 'Pelajari bagaimana class digunakan sebagai cetak biru untuk membuat object dalam Python. Materi menggunakan contoh ekosistem lahan basah seperti sungai, rawa, mangrove, spesies, dan sensor air.',

    'objectives' => [
        'Menjelaskan hubungan antara class dan object.',
        'Membuat constructor __init__ dan atribut instance.',
        'Membuat dan memanggil instance method.',
        'Membuat beberapa object dari satu class.',
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
            'paragraphs' => [
                'Dalam lingkungan lahan basah terdapat berbagai entitas seperti sungai, rawa, mangrove, tumbuhan, hewan, dan sensor. Setiap entitas memiliki data yang berbeda, tetapi kita dapat menemukan pola yang sama untuk memodelkannya dalam program.',
                'Sebagai contoh, sebuah ekosistem dapat memiliki nama dan lokasi. Ekosistem tersebut juga dapat memiliki perilaku tertentu, misalnya menampilkan informasi mengenai dirinya sendiri. Konsep class dan object membantu kita membuat model seperti ini dalam Python.',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 2. Class dan Object
        |--------------------------------------------------------------------------
        */

        [
            'id' => 'class-object',
            'title' => 'Class dan Object',
            'paragraphs' => [
                'Class dapat dipahami sebagai cetak biru atau rancangan yang digunakan untuk membuat object. Class menentukan data apa yang dimiliki object dan perilaku apa yang dapat dilakukan oleh object tersebut.',
                'Object merupakan hasil atau instance yang dibuat berdasarkan sebuah class. Satu class dapat digunakan untuk membuat banyak object dengan data yang berbeda.',
                'Pada contoh berikut, class Ekosistem memiliki data berupa nama dan lokasi. Object rawa dibuat berdasarkan class tersebut dengan nilai "Rawa" dan "Kalimantan Selatan".',
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
            'tip' => 'Class adalah rancangan, sedangkan object adalah instance yang dibuat berdasarkan rancangan tersebut.',
        ],

        /*
        |--------------------------------------------------------------------------
        | 3. Bedah Kode
        |--------------------------------------------------------------------------
        */

        [
            'id' => 'bedah-kode',
            'title' => 'Bedah Kode',
            'paragraphs' => [
                'class Ekosistem: digunakan untuk membuat class baru dengan nama Ekosistem. Class ini menjadi rancangan untuk object yang akan dibuat nantinya.',
                '__init__ merupakan constructor yang dipanggil ketika sebuah object dibuat. Constructor digunakan untuk memberikan nilai awal pada object.',
                'Parameter self merujuk pada object yang sedang menggunakan method. Karena itu, self digunakan untuk mengakses data yang dimiliki oleh object tersebut.',
                'self.nama dan self.lokasi merupakan atribut instance. Atribut tersebut menyimpan data yang dimiliki oleh masing-masing object.',
                'rawa = Ekosistem("Rawa", "Kalimantan Selatan") membuat sebuah object bernama rawa dari class Ekosistem.',
                'rawa.info() memanggil method info() milik object rawa. Method tersebut mengembalikan informasi mengenai nama dan lokasi ekosistem.',
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
        ],

        /*
        |--------------------------------------------------------------------------
        | 4. Membuat Banyak Object
        |--------------------------------------------------------------------------
        */

        [
            'id' => 'banyak-object',
            'title' => 'Membuat Beberapa Object dari Satu Class',
            'paragraphs' => [
                'Satu class dapat digunakan untuk membuat banyak object. Setiap object dapat memiliki nilai atribut yang berbeda meskipun dibuat dari class yang sama.',
                'Contohnya, class Ekosistem dapat digunakan untuk membuat object sungai, rawa, dan mangrove. Ketiga object tersebut memiliki atribut nama dan lokasi, tetapi masing-masing menyimpan nilai yang berbeda.',
                'Object-object tersebut juga dapat disimpan dalam sebuah list dan diproses menggunakan perulangan for.',
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

for objek in [sungai, rawa, mangrove]:
    print(objek.info())
PYTHON,
        ],

        /*
        |--------------------------------------------------------------------------
        | 5. Atribut dan Method
        |--------------------------------------------------------------------------
        */

        [
            'id' => 'atribut-method',
            'title' => 'Atribut dan Method',
            'paragraphs' => [
                'Atribut digunakan untuk menyimpan keadaan atau data yang dimiliki oleh sebuah object. Nilai atribut dapat berbeda antara satu object dengan object lainnya.',
                'Method merupakan fungsi yang berada di dalam class dan digunakan untuk menentukan perilaku object. Method dapat membaca maupun mengubah atribut yang dimiliki oleh object.',
                'Pada contoh berikut, atribut dipantau digunakan untuk menyimpan status pemantauan sebuah ekosistem. Nilai awalnya adalah False, kemudian method mulai_pantau() mengubahnya menjadi True.',
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
PYTHON,
        ],

        /*
        |--------------------------------------------------------------------------
        | 6. Live Coding Spesies
        |--------------------------------------------------------------------------
        */

        [
            'id' => 'live-coding-spesies',
            'title' => 'Live Coding: Class Spesies',
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
                    'description' => 'Lengkapi class Spesies dengan atribut nama dan habitat serta method deskripsi(). Setelah itu buat dua object dan tampilkan deskripsinya.',
                    'entry_file' => 'main.py',
                    'files' => [
                        'main.py' => <<<'PYTHON'
class Spesies:
    def __init__(self, nama, habitat):
        # TODO
        pass

    def deskripsi(self):
        # TODO
        pass


spesies1 = Spesies("Bekantan", "Hutan riparian")
spesies2 = Spesies("Ikan lokal", "Perairan rawa")

print(spesies1.deskripsi())
print(spesies2.deskripsi())
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
                    'description' => 'Buat class SensorAir, buat tiga object dengan lokasi dan tinggi air yang berbeda, lalu tampilkan seluruh object menggunakan perulangan.',
                    'entry_file' => 'main.py',
                    'files' => [
                        'main.py' => <<<'PYTHON'
class SensorAir:
    def __init__(self, lokasi, tinggi_air):
        # TODO
        pass

    def tampilkan(self):
        # TODO
        pass


sensor1 = SensorAir("Sungai Barito", 120)
sensor2 = SensorAir("Rawa Bangkau", 85)
sensor3 = SensorAir("Pesisir", 60)

sensor = [sensor1, sensor2, sensor3]

for objek in sensor:
    print(objek.tampilkan())
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
        'Class merupakan cetak biru atau rancangan yang digunakan untuk membuat object.',
        'Object merupakan instance yang dibuat berdasarkan sebuah class.',
        'Constructor __init__ digunakan untuk memberikan nilai awal pada object ketika object dibuat.',
        'self merujuk pada object yang sedang menggunakan method.',
        'Atribut digunakan untuk menyimpan data atau keadaan yang dimiliki oleh object.',
        'Method digunakan untuk menentukan perilaku yang dapat dilakukan oleh object.',
        'Satu class dapat digunakan untuk membuat banyak object dengan nilai atribut yang berbeda.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Refleksi
    |--------------------------------------------------------------------------
    */

    'reflection' => [
        'Apa perbedaan antara class dan object?',
        'Mengapa self diperlukan ketika membuat method dalam sebuah class?',
        'Atribut dan method apa saja yang dapat digunakan untuk memodelkan sebuah sungai?',
    ],

    /*
    |--------------------------------------------------------------------------
    | Kuis
    |--------------------------------------------------------------------------
    */

    'quiz' => [
        [
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
            'question' => 'Perhatikan kode berikut. Apa yang dimaksud dengan rawa?',
            'code' => <<<'PYTHON'
class Ekosistem:
    def __init__(self, nama, lokasi):
        self.nama = nama
        self.lokasi = lokasi

rawa = Ekosistem("Rawa", "Kalimantan Selatan")
PYTHON,
            'options' => [
                'Class Ekosistem',
                'Method __init__',
                'Object atau instance dari class Ekosistem',
                'Atribut lokasi',
            ],
            'correct' => 2,
            'explanation' => 'rawa adalah object atau instance yang dibuat dari class Ekosistem.',
        ],

        [
            'question' => 'Apa fungsi atribut pada sebuah object?',
            'options' => [
                'Menyimpan data atau keadaan object',
                'Membuat program berhenti',
                'Mengubah class menjadi function',
                'Menghapus seluruh object',
            ],
            'correct' => 0,
            'explanation' => 'Atribut digunakan untuk menyimpan data atau keadaan yang dimiliki oleh sebuah object.',
        ],

        [
            'question' => 'Perhatikan kode berikut. Berapa object yang dibuat dari class Ekosistem?',
            'code' => <<<'PYTHON'
sungai = Ekosistem("Sungai", "Banjarmasin")
rawa = Ekosistem("Rawa", "Hulu Sungai")
mangrove = Ekosistem("Mangrove", "Pesisir")
PYTHON,
            'options' => [
                'Satu object',
                'Dua object',
                'Tiga object',
                'Tidak ada object',
            ],
            'correct' => 2,
            'explanation' => 'Kode tersebut membuat tiga object, yaitu sungai, rawa, dan mangrove dari class Ekosistem.',
        ],

        [
            'question' => 'Apa yang dilakukan oleh method info() pada kode berikut?',
            'code' => <<<'PYTHON'
def info(self):
    return f"{self.nama} berada di {self.lokasi}"
PYTHON,
            'options' => [
                'Membuat class baru',
                'Menghapus atribut object',
                'Mengembalikan informasi nama dan lokasi object',
                'Membuat object secara otomatis',
            ],
            'correct' => 2,
            'explanation' => 'Method info() membaca atribut nama dan lokasi dari object kemudian mengembalikan informasi dalam bentuk string.',
        ],

        [
            'question' => 'Jika satu class digunakan untuk membuat beberapa object, apakah nilai atribut setiap object harus sama?',
            'options' => [
                'Ya, semua object harus memiliki nilai yang sama',
                'Tidak, setiap object dapat memiliki nilai atribut yang berbeda',
                'Ya, tetapi hanya atribut pertama',
                'Tidak, object tidak dapat memiliki atribut',
            ],
            'correct' => 1,
            'explanation' => 'Satu class dapat digunakan untuk membuat banyak object dan setiap object dapat memiliki nilai atribut yang berbeda.',
        ],
    ],
];
