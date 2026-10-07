<?php

return [
    'description' => 'Pelajari cara menggabungkan data dan perilaku dalam class serta menyediakan interface yang jelas. Gunakan SensorAir untuk berlatih menjaga konsistensi data pemantauan lahan basah Kalimantan Selatan.',
    'objectives' => [
        'Menjelaskan konsep enkapsulasi dalam pemrograman berorientasi objek.',
        'Membedakan public attribute, konvensi non-public menggunakan underscore, dan name mangling pada Python.',
        'Menggunakan getter dan setter untuk mengontrol pembacaan dan perubahan data object.',
        'Menggunakan @property untuk membuat interface atribut yang terkontrol.',
        'Menerapkan enkapsulasi pada class sederhana dalam konteks pemantauan lahan basah.',
    ],
    'sections' => [
        [
            'id' => 'apersepsi',
            'title' => 'Apersepsi',
            'nav_group' => 'pendahuluan',
            'paragraphs' => [
                'Pada BAB 2, kita membuat object SensorAir yang menyimpan lokasi dan tinggi air. Bayangkan beberapa bagian program memperbarui data sensor di sungai atau rawa. Tanpa pemeriksaan, nilai tinggi_air seperti -10 bisa saja tersimpan, padahal aturan latihan kita mengharuskan nilai tidak negatif.',
                'Bagaimana cara menjaga agar data object tetap digunakan melalui aturan yang sudah kita tentukan? Kita dapat menempatkan aturan pembacaan dan perubahan data di dalam class, lalu menyediakan cara berinteraksi yang jelas bagi pengguna object.',
            ],
            'tip' => 'Nilai tinggi air pada contoh digunakan untuk latihan pemrograman, bukan data hasil pengukuran lapangan. Aturan nilai tidak negatif merupakan aturan model latihan ini, bukan klaim tentang semua pengukuran tinggi air di dunia nyata.',
        ],
        [
            'id' => 'mengenal-enkapsulasi',
            'title' => 'Mengenal Enkapsulasi',
            'paragraphs' => [
                'Enkapsulasi menggabungkan data dan perilaku yang berkaitan di dalam sebuah class serta menyediakan interface yang jelas untuk berinteraksi dengan object. Jadi, enkapsulasi bukan hanya menyembunyikan data.',
                'Object memiliki data dan perilaku. Tidak semua detail internalnya perlu digunakan langsung oleh bagian program lain. Interface adalah cara yang disediakan class untuk membaca data atau meminta perubahan, misalnya method dan property.',
                'Pada atribut biasa tanpa validasi, sensor.tinggi_air = -20 dapat menyimpan nilai yang tidak sesuai aturan latihan. Dengan property yang memiliki setter, sensor.tinggi_air = 120 menjalankan pemeriksaan sebelum nilai baru disimpan; nilai negatif dapat ditolak.',
                'Pendekatan ini menjaga konsistensi data, mengurangi ketergantungan terhadap implementasi internal, dan membuat class lebih mudah dipelihara. Pengguna object cukup mengikuti interface yang disediakan.',
            ],
        ],
        [
            'id' => 'public-attribute',
            'title' => 'Public Attribute dan Interface Object',
            'nav_title' => 'Public Attribute & Interface',
            'paragraphs' => [
                'Atribut biasa seperti self.lokasi atau self.nama disebut public attribute. Pengguna object dapat membaca dan mengubahnya langsung. Python tidak menggunakan keyword public untuk mendeklarasikan atribut tersebut.',
                'Pada contoh berikut, lokasi boleh diubah langsung karena class belum menetapkan aturan khusus untuk atribut tersebut. Pilih interface sesuai kebutuhan; tidak semua atribut harus memiliki getter dan setter.',
            ],
            'code' => <<<'PYTHON'
class SensorAir:
    def __init__(self, lokasi):
        self.lokasi = lokasi

sensor = SensorAir("Sungai Barito")
print(sensor.lokasi)

sensor.lokasi = "Rawa Bangkau"
print(sensor.lokasi)
PYTHON,
            'tip' => 'print(sensor.lokasi) pertama menampilkan Sungai Barito. Setelah assignment, pembacaan berikutnya menampilkan Rawa Bangkau. Atribut public dapat menjadi bagian dari interface object.',
        ],
        [
            'id' => 'non-public',
            'title' => 'Konvensi Non-Public dengan _',
            'nav_title' => 'Konvensi Non-Public (_)',
            'paragraphs' => [
                'Satu underscore di awal nama, seperti self._lokasi, menandai non-public by convention: secara konvensi atribut dianggap untuk penggunaan internal implementasi. Istilah protected by convention juga kadang digunakan, tetapi ini bukan access modifier yang ketat seperti pada Java atau C++.',
                'Python tetap memungkinkan akses sensor._lokasi dari luar class. Awalan ini merupakan pesan bagi programmer agar menggunakan interface yang disediakan, bukan larangan akses yang ditegakkan oleh Python.',
            ],
            'code' => <<<'PYTHON'
class SensorAir:
    def __init__(self, lokasi):
        self._lokasi = lokasi

sensor = SensorAir("Sungai Barito")
print(sensor._lokasi)  # Tetap bisa diakses, tetapi dianggap internal.
PYTHON,
            'tip' => 'Kode tetap menampilkan Sungai Barito. Mengakses _lokasi secara langsung membuat pemanggil bergantung pada detail internal yang dapat berubah.',
        ],
        [
            'id' => 'name-mangling',
            'title' => 'Name Mangling dengan __',
            'nav_title' => 'Name Mangling (__)',
            'paragraphs' => [
                'Dua underscore di awal nama seperti self.__tinggi_air memicu name mangling. Di dalam class SensorAir, Python mengubah nama atribut tersebut menjadi _SensorAir__tinggi_air. Aturan ini berlaku untuk nama dengan setidaknya dua underscore di depan dan paling banyak satu underscore di belakang; __init__ bukan contoh atribut yang mengalami name mangling.',
                'Tujuan utamanya menghindari konflik nama, terutama ketika class dikembangkan melalui pewarisan, serta mengurangi penggunaan langsung secara tidak sengaja. Pewarisan akan dibahas pada BAB berikutnya.',
                'Atribut tersebut bukan private absolut dan bukan mekanisme keamanan. Nama hasil mangling masih dapat diakses dari luar, tetapi pengguna object sebaiknya berinteraksi melalui interface yang disediakan class.',
            ],
            'code' => <<<'PYTHON'
class SensorAir:
    def __init__(self, tinggi_air):
        self.__tinggi_air = tinggi_air

sensor = SensorAir(85)
# Demonstrasi name mangling, bukan pola akses yang dianjurkan:
print(sensor._SensorAir__tinggi_air)
PYTHON,
            'tip' => 'Contoh menampilkan 85 melalui nama hasil mangling. sensor.__tinggi_air tidak merujuk langsung ke atribut internal tersebut; jangan menggunakan nama hasil mangling sebagai interface sehari-hari.',
        ],
        [
            'id' => 'getter-setter',
            'title' => 'Getter dan Setter',
            'paragraphs' => [
                'Getter adalah method untuk membaca nilai, sedangkan setter adalah method untuk mengubah nilai melalui aturan tertentu. Aturan perubahan berada di class sehingga setiap pemanggil memakai pemeriksaan yang sama.',
                'Pada contoh ini, set_tinggi_air() menolak nilai negatif dengan ValueError. raise menghentikan proses perubahan dan memberi tahu pemanggil bahwa nilai tidak sesuai aturan. Nilai diperiksa sebelum disimpan agar data sebelumnya tetap utuh.',
            ],
            'code' => <<<'PYTHON'
class SensorAir:
    def __init__(self, tinggi_air):
        self.set_tinggi_air(tinggi_air)

    def get_tinggi_air(self):
        return self.__tinggi_air

    def set_tinggi_air(self, nilai):
        if nilai < 0:
            raise ValueError("Tinggi air tidak boleh negatif.")
        self.__tinggi_air = nilai

sensor = SensorAir(85)
print(sensor.get_tinggi_air())
sensor.set_tinggi_air(90)
print(sensor.get_tinggi_air())
PYTHON,
            'tip' => 'Output berurutan adalah 85 dan 90. __init__ memanggil setter agar nilai awal juga diperiksa. Python menyediakan pendekatan yang lebih natural melalui @property, dengan syntax penggunaan seperti atribut.',
        ],
        [
            'id' => 'property',
            'title' => 'Property dengan @property',
            'paragraphs' => [
                '@property memberi interface seperti atribut sambil menjalankan logika melalui method. Tanda @ di atas method menghubungkannya dengan property: @property mendefinisikan pembacaan, sedangkan @tinggi_air.setter mendefinisikan perubahan property bernama tinggi_air.',
                'Dari sisi pengguna object, sensor.tinggi_air terlihat seperti atribut biasa dan digunakan tanpa tanda kurung. Membacanya memanggil getter; assignment sensor.tinggi_air = 90 memanggil setter. Ini merupakan pendekatan Pythonic untuk atribut yang membutuhkan kontrol.',
                'Pada contoh ini, lokasi tetap public. Nilai tinggi air disimpan pada self.__tinggi_air setelah validasi. Constructor memakai self.tinggi_air = tinggi_air agar nilai awal melewati setter yang sama.',
            ],
            'code' => <<<'PYTHON'
class SensorAir:
    def __init__(self, lokasi, tinggi_air):
        self.lokasi = lokasi
        self.tinggi_air = tinggi_air

    @property
    def tinggi_air(self):
        return self.__tinggi_air

    @tinggi_air.setter
    def tinggi_air(self, nilai):
        if nilai < 0:
            raise ValueError("Tinggi air tidak boleh negatif.")
        self.__tinggi_air = nilai

sensor = SensorAir("Rawa Bangkau", 85)
print(sensor.tinggi_air)
sensor.tinggi_air = 90
print(sensor.tinggi_air)
PYTHON,
            'tip' => 'Output adalah 85 lalu 90. Simpan nilai ke self.__tinggi_air di dalam setter; menulis self.tinggi_air = nilai di dalam setter akan memanggil setter itu sendiri berulang kali.',
        ],
        [
            'id' => 'bedah-kode',
            'title' => 'Bedah Kode SensorAir',
            'paragraphs' => [
                'class SensorAir: mendefinisikan rancangan object sensor beserta data dan aturan penggunaannya.',
                '__init__(self, lokasi, tinggi_air) menginisialisasi object. self.lokasi menyimpan lokasi sebagai public attribute. self.tinggi_air = tinggi_air meneruskan nilai awal ke setter.',
                'self.__tinggi_air adalah tempat penyimpanan internal yang mengalami name mangling. Nilainya disimpan oleh setter setelah pemeriksaan berhasil.',
                '@property menghubungkan method tinggi_air dengan pembacaan property. Getter tinggi_air mengembalikan nilai internal melalui return.',
                '@tinggi_air.setter menghubungkan method dengan penulisan property. Parameter nilai menerima nilai baru ketika pengguna melakukan assignment.',
                'if nilai < 0: memeriksa aturan latihan. raise ValueError memberi pesan kesalahan sebelum assignment internal, sehingga nilai valid sebelumnya tidak berubah.',
                'SensorAir("Rawa Bangkau", 85) membuat object dengan nilai awal 85. print(sensor.tinggi_air) membaca property, sedangkan sensor.tinggi_air = 90 meminta perubahan melalui setter.',
                'Contoh berikut memakai try dan except ValueError untuk menangani penolakan. Program menampilkan pesan kesalahan, lalu tetap dapat membaca nilai sebelumnya, yaitu 90.',
            ],
            'code' => <<<'PYTHON'
# Lanjutkan dari class SensorAir pada contoh property.
sensor = SensorAir("Rawa Bangkau", 85)
sensor.tinggi_air = 90

try:
    sensor.tinggi_air = -10
except ValueError as error:
    print(error)

print(sensor.tinggi_air)  # Tetap 90.
PYTHON,
            'tip' => 'Validasi berada di setter, sedangkan pemanggil memutuskan cara menangani ValueError. Interface yang sama tetap digunakan saat penyimpanan internal class berubah.',
        ],
        [
            'id' => 'aktivitas-enkapsulasi',
            'title' => 'Live Coding / Aktivitas Enkapsulasi',
            'nav_title' => 'Latihan Enkapsulasi',
            'paragraphs' => [
                'Sekarang lengkapi bagian pass pada class SensorAir. Simpan data tinggi air pada atribut internal dan sediakan property untuk membaca serta memperbaruinya. Nilai angka yang tidak negatif, termasuk 0, harus diterima; nilai negatif harus ditolak dengan ValueError sebelum data internal diubah.',
                'Buat object dengan tinggi awal 85, tampilkan nilainya, ubah menjadi 90, lalu tampilkan kembali. Gunakan Run Code untuk melihat output dan Submit untuk memeriksa perilaku class. Checker membuat object uji sendiri sehingga nama variabel object yang kamu pilih tidak memengaruhi hasil.',
            ],
            'live_codes' => [
                [
                    'id' => 'bab3-enkapsulasi-sensor',
                    'title' => 'Coba sendiri: Enkapsulasi Sensor Air',
                    'description' => 'Lengkapi class `SensorAir`: simpan `tinggi_air` pada atribut internal, gunakan `@property` untuk membaca dan setter untuk mengubah nilainya. Terima nilai tidak negatif; tolak nilai negatif dengan `ValueError` tanpa mengubah data sebelumnya. Buat sensor di `"Rawa Bangkau"` dengan nilai `85`, tampilkan nilainya, ubah menjadi `90`, lalu tampilkan kembali.',
                    'entry_file' => 'main.py',
                    'files' => [
                        'main.py' => <<<'PYTHON'
class SensorAir:
    def __init__(self, lokasi, tinggi_air):
        self.lokasi = lokasi
        # Simpan tinggi_air pada atribut internal.
        pass

    @property
    def tinggi_air(self):
        # Kembalikan nilai tinggi air.
        pass

    @tinggi_air.setter
    def tinggi_air(self, nilai):
        # Tolak nilai negatif dengan ValueError.
        # Jika valid, simpan nilai baru.
        pass

sensor = SensorAir("Rawa Bangkau", 85)
print(sensor.tinggi_air)

# Ubah tinggi air menjadi 90.
# Tampilkan kembali nilainya.
PYTHON,
                    ],
                    'checker' => <<<'PYTHON'
results = []

def check(label, operation, hint):
    try:
        passed = bool(operation())
        results.append({"label": label, "passed": passed,
                        "feedback": "" if passed else hint})
    except Exception as error:
        results.append({"label": label, "passed": False,
                        "feedback": f"{hint} ({type(error).__name__}: {error})"})

def create_sensor():
    global sensor_uji
    sensor_uji = SensorAir("Rawa Bangkau", 85)
    return isinstance(sensor_uji, SensorAir)

def initial_values():
    sensor_lain = SensorAir("Sungai Barito", 60)
    return sensor_uji.tinggi_air == 85 and sensor_lain.tinggi_air == 60

def valid_updates():
    descriptor = getattr(SensorAir, "tinggi_air", None)
    if not isinstance(descriptor, property) or descriptor.fset is None:
        return False
    for nilai in (90, 0, 12.5, 90):
        sensor_uji.tinggi_air = nilai
        if sensor_uji.tinggi_air != nilai:
            return False
    return True

def reject_negative():
    sensor_uji.tinggi_air = 90
    for nilai in (-10, -1):
        try:
            sensor_uji.tinggi_air = nilai
        except ValueError:
            continue
        return False
    return True

def independent_objects():
    pertama = SensorAir("Rawa Bangkau", 85)
    kedua = SensorAir("Sungai Barito", 60)
    pertama.tinggi_air = 120
    return pertama.tinggi_air == 120 and kedua.tinggi_air == 60

check("Class SensorAir", lambda: isinstance(globals().get("SensorAir"), type),
      "Definisikan class SensorAir.")
check("Object SensorAir", create_sensor,
      "Pastikan SensorAir dapat dibuat dengan lokasi dan tinggi_air.")
check("Lokasi sensor", lambda: sensor_uji.lokasi == "Rawa Bangkau",
      "Simpan parameter lokasi pada self.lokasi.")
check("Getter property", lambda: isinstance(getattr(SensorAir, "tinggi_air", None), property)
      and SensorAir.tinggi_air.fget is not None and sensor_uji.tinggi_air is not None,
      "Gunakan @property dan kembalikan nilai tinggi air internal.")
check("Nilai awal tinggi air", initial_values,
      "Simpan nilai awal yang diberikan saat tiap object dibuat.")
check("Setter nilai valid", valid_updates,
      "Gunakan @tinggi_air.setter; terima 90, 0, dan bilangan desimal yang tidak negatif.")
check("Validasi nilai negatif", reject_negative,
      "Tolak nilai negatif dengan raise ValueError sebelum menyimpan nilai.")
check("Data setelah penolakan", lambda: sensor_uji.tinggi_air == 90,
      "Nilai harus tetap 90 setelah percobaan perubahan negatif.")
check("Data setiap object", independent_objects,
      "Simpan tinggi air per instance agar perubahan satu sensor tidak mengubah sensor lain.")
PYTHON,
                ],
            ],
        ],
    ],
    'summary' => [
        'Enkapsulasi menggabungkan data dan perilaku dalam class serta menyediakan interface yang jelas untuk berinteraksi dengan object.',
        'Public attribute seperti lokasi dapat dibaca dan diubah langsung; Python tidak memakai keyword public.',
        'Satu underscore menandai konvensi non-public atau penggunaan internal, bukan larangan akses dari luar.',
        'Dua underscore memicu name mangling untuk menghindari konflik nama dan penggunaan langsung secara tidak sengaja, bukan keamanan absolut.',
        'Getter membaca nilai, sedangkan setter mengubah nilai melalui aturan yang ditentukan class.',
        '@property dan @tinggi_air.setter menyediakan interface seperti atribut yang menjalankan logika melalui method.',
        'Validasi sebelum penyimpanan menjaga konsistensi data; interface yang jelas mengurangi ketergantungan pada implementasi internal dan membantu pemeliharaan class.',
    ],
    'reflection' => [
        'Mengapa mengubah atribut object secara langsung tidak selalu menjadi pilihan terbaik? Jelaskan dengan contoh SensorAir.',
        'Apa perbedaan _atribut dan __atribut pada Python, dan mengapa keduanya bukan jaminan keamanan data?',
        'Dalam situasi apa kamu akan menggunakan @property dibandingkan atribut public biasa?',
    ],
    'quiz' => [
        [
            'type' => 'multiple_choice',
            'question' => 'Beberapa bagian program perlu memperbarui tinggi air dengan aturan yang sama. Rancangan mana yang paling mencerminkan enkapsulasi?',
            'options' => [
                'Menggabungkan data dan aturan perubahan dalam class dengan interface yang jelas',
                'Menyimpan semua data di variabel global agar siapa pun dapat mengubahnya',
                'Mengganti semua nama atribut dengan underscore tanpa menyediakan perilaku',
                'Menyalin pemeriksaan yang berbeda ke setiap bagian program',
            ],
            'correct' => 0,
            'explanation' => 'Enkapsulasi menyatukan data dan perilaku dalam class serta menyediakan interface. Aturan perubahan dapat dipusatkan agar pemanggil tidak harus bergantung pada detail internal.',
        ],
        [
            'type' => 'multiple_choice',
            'question' => 'Programmer melihat self._lokasi dalam class SensorAir. Bagaimana sebaiknya atribut ini dipahami?',
            'options' => [
                'Python melarang semua akses dari luar class',
                'Nilai atribut otomatis dienkripsi',
                'Atribut ditujukan untuk penggunaan internal secara konvensi, tetapi masih dapat diakses dari luar',
                'Python otomatis mengubah namanya menjadi _SensorAir__lokasi',
            ],
            'correct' => 2,
            'explanation' => 'Satu underscore menandai konvensi non-public. Ini bukan access modifier yang membatasi akses secara ketat dan tidak memicu name mangling seperti dua underscore.',
        ],
        [
            'type' => 'multiple_choice',
            'question' => 'Apa yang terjadi pada self.__tinggi_air di dalam class SensorAir?',
            'options' => [
                'Nilainya selalu tersembunyi dan mustahil diakses',
                'Python menghapus atribut saat constructor selesai',
                'Atribut hanya dapat dibaca oleh property dan tidak oleh method',
                'Namanya mengalami name mangling menjadi _SensorAir__tinggi_air untuk membantu menghindari konflik nama',
            ],
            'correct' => 3,
            'explanation' => 'Dua underscore memicu name mangling. Nama hasil mangling tetap bisa diakses; mekanisme ini bukan keamanan absolut atau keyword private.',
        ],
        [
            'type' => 'code_fill',
            'question' => 'Lengkapi decorator agar method tinggi_air menjadi getter property yang dapat dibaca seperti atribut.',
            'code' => <<<'PYTHON'
class SensorAir:
    def __init__(self, tinggi_air):
        self.__tinggi_air = tinggi_air

    __________
    def tinggi_air(self):
        return self.__tinggi_air
PYTHON,
            'answer' => '@property',
            'explanation' => '@property menghubungkan method tinggi_air dengan pembacaan property melalui syntax atribut.',
        ],
        [
            'type' => 'code_fill',
            'question' => 'Lengkapi decorator yang menghubungkan method kedua dengan setter property tinggi_air. Setter memvalidasi nilai sebelum menyimpannya.',
            'code' => <<<'PYTHON'
class SensorAir:
    @property
    def tinggi_air(self):
        return self.__tinggi_air

    __________
    def tinggi_air(self, nilai):
        if nilai < 0:
            raise ValueError("Tinggi air tidak boleh negatif.")
        self.__tinggi_air = nilai
PYTHON,
            'answer' => '@tinggi_air.setter',
            'explanation' => '@tinggi_air.setter menghubungkan method dengan perubahan property tinggi_air sehingga assignment menjalankan validasi setter.',
        ],
    ],
];
