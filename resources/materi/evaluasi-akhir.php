<?php

return [
    'description' => 'Tinjau pemahaman Python OOP setelah BAB 1–6 melalui 10 pilihan ganda dan 10 isian kode Python dalam konteks lahan basah.',
    'questions' => [
        [
            'id' => 'q1', 'type' => 'multiple_choice',
            'question' => 'Dua objek dibuat dari kelas yang sama. Setelah atribut objek pertama diubah, apakah keluaran program berikut?',
            'code' => <<<'PYTHON'
class Sensor:
    def __init__(self, nilai):
        self.nilai = nilai

a = Sensor(70)
b = Sensor(90)
a.nilai = 80
print(a.nilai, b.nilai)
PYTHON,
            'options' => ['80 80', '70 90', '80 90', '90 80'], 'correct' => 2,
            'explanation' => 'Atribut nilai disimpan pada masing-masing instance; perubahan a.nilai tidak mengubah b.nilai.',
        ],
        [
            'id' => 'q2', 'type' => 'multiple_choice',
            'question' => 'Objek sensor dibuat dengan Sensor(70). Pemanggilan manakah yang mengembalikan nilai 75 sekaligus menyimpan perubahan pada objek tersebut?',
            'code' => <<<'PYTHON'
class Sensor:
    def __init__(self, nilai):
        self.nilai = nilai

    def tambah(self, kenaikan):
        self.nilai = self.nilai + kenaikan
        return self.nilai

sensor = Sensor(70)
PYTHON,
            'options' => ['Sensor.tambah(5)', 'sensor.tambah(sensor, 5)', 'sensor.tambah()', 'sensor.tambah(5)'], 'correct' => 3,
            'explanation' => 'Pemanggilan melalui instance mengikat self secara otomatis; hanya kenaikan yang diberikan sebagai argumen.',
        ],
        [
            'id' => 'q3', 'type' => 'multiple_choice',
            'question' => 'Kelas Sensor menyimpan data pada self._nilai dan menyediakan property nilai. Pernyataan manakah yang tepat mengenai akses dari luar kelas?',
            'options' => [
                'Akses _nilai tetap dimungkinkan, tetapi pengguna sebaiknya memakai property nilai.',
                'Akses _nilai selalu ditolak Python karena diawali satu underscore.',
                'Akses _nilai otomatis dialihkan Python ke setter property nilai.',
                'Akses _nilai hanya dimungkinkan jika Sensor mewarisi ABC.',
            ], 'correct' => 0,
            'explanation' => 'Satu underscore menandai penggunaan internal menurut konvensi, bukan pembatas akses yang ditegakkan Python.',
        ],
        [
            'id' => 'q4', 'type' => 'multiple_choice',
            'question' => 'Sungai menambahkan atribut panjang dan tetap memerlukan inisialisasi nama dari Ekosistem. Pernyataan manakah yang tepat untuk menggantikan bagian kosong menggunakan super()?',
            'code' => <<<'PYTHON'
class Ekosistem:
    def __init__(self, nama):
        self.nama = nama

class Sungai(Ekosistem):
    def __init__(self, nama, panjang):
        ____________________
        self.panjang = panjang
PYTHON,
            'options' => ['super().__init__()', 'super().__init__(self, nama)', 'super().__init__(nama)', 'super().nama(nama)'], 'correct' => 2,
            'explanation' => 'super().__init__(nama) menjalankan initializer induk pada instance yang sama tanpa mengirim self kembali.',
        ],
        [
            'id' => 'q5', 'type' => 'multiple_choice',
            'question' => 'Analisis pemilihan method pada program berikut. Pasangan keluaran dan penjelasan manakah yang tepat?',
            'code' => <<<'PYTHON'
class Sensor:
    def baca(self):
        return 0

class SensorPH(Sensor):
    def baca(self):
        return 7.2

alat = SensorPH()
print(alat.baca())
PYTHON,
            'options' => [
                '7.2, karena method baca pada subclass menggantikan perilaku method induk.',
                '0, karena method pada superclass selalu dipilih lebih dahulu.',
                '7.2, karena method induk dan subclass dijalankan lalu hasilnya dijumlahkan.',
                'TypeError, karena subclass tidak boleh memakai nama method yang sama.',
            ], 'correct' => 0,
            'explanation' => 'Pemanggilan pada instance SensorPH memakai implementasi baca yang di-override oleh SensorPH.',
        ],
        [
            'id' => 'q6', 'type' => 'multiple_choice',
            'question' => 'StasiunPemantau mengelola beberapa objek sensor yang dapat ditambahkan ke daftar sensor. Model hubungan manakah yang sesuai dengan kebutuhan tersebut?',
            'options' => [
                'StasiunPemantau mewarisi Sensor karena keduanya menyimpan data.',
                'StasiunPemantau menyimpan daftar objek Sensor sebagai atribut instance.',
                'Sensor mewarisi StasiunPemantau agar setiap sensor memiliki stasiun.',
                'StasiunPemantau menyalin seluruh method Sensor ke kelasnya sendiri.',
            ], 'correct' => 1,
            'explanation' => 'Relasi memiliki sensor dimodelkan dengan composition, seperti contoh StasiunPemantau pada BAB 4.',
        ],
        [
            'id' => 'q7', 'type' => 'multiple_choice',
            'question' => 'Fungsi tampilkan(obj) memanggil obj.baca_data() tanpa argumen. SensorVirtual dan LaporanManual tidak saling mewarisi. Syarat manakah yang memungkinkan kedua objek diproses oleh fungsi itu?',
            'options' => [
                'Kedua kelas harus memiliki nama atribut instance yang seluruhnya sama.',
                'Kedua kelas harus mewarisi ABC dan memakai decorator abstractmethod.',
                'Kedua kelas harus mengembalikan nilai dengan tipe data yang sama.',
                'Kedua objek menyediakan method baca_data yang dapat dipanggil tanpa argumen tambahan.',
            ], 'correct' => 3,
            'explanation' => 'Duck typing bergantung pada perilaku yang dibutuhkan pemanggil, bukan kesamaan hierarki atau tipe hasil.',
        ],
        [
            'id' => 'q8', 'type' => 'multiple_choice',
            'question' => 'Kelas SensorPH belum mengimplementasikan baca(). Apa yang terjadi pada baris terakhir program berikut?',
            'code' => <<<'PYTHON'
from abc import ABC, abstractmethod

class Sensor(ABC):
    @abstractmethod
    def baca(self):
        pass

class SensorPH(Sensor):
    pass

sensor = SensorPH()
PYTHON,
            'options' => [
                'TypeError terjadi karena SensorPH masih memiliki abstract method yang belum diimplementasikan.',
                'Objek berhasil dibuat dan baca selalu mengembalikan None.',
                'Objek berhasil dibuat karena pewarisan otomatis menghapus penanda abstractmethod.',
                'SyntaxError terjadi karena abstract method tidak boleh berisi pass.',
            ], 'correct' => 0,
            'explanation' => 'Subclass tetap abstrak sampai mengimplementasikan seluruh abstract method yang diwarisi.',
        ],
        [
            'id' => 'q9', 'type' => 'multiple_choice',
            'question' => 'Nilai awal sensor adalah 85. Setelah penulisan nilai negatif ditangani, mengapa keluaran program tetap 85?',
            'code' => <<<'PYTHON'
class Sensor:
    def __init__(self, nilai):
        self.nilai = nilai

    @property
    def nilai(self):
        return self._nilai

    @nilai.setter
    def nilai(self, baru):
        if baru < 0:
            raise ValueError("Nilai tidak boleh negatif")
        self._nilai = baru

sensor = Sensor(85)
try:
    sensor.nilai = -10
except ValueError:
    pass
print(sensor.nilai)
PYTHON,
            'options' => [
                'Property hanya dapat diubah selama __init__ dijalankan.',
                'Setter menolak nilai negatif sebelum penugasan ke atribut internal dilakukan.',
                'Blok except otomatis mengembalikan seluruh atribut ke nilai awal.',
                'Awalan underscore membuat nilai atribut tidak dapat diubah setelah dibuat.',
            ], 'correct' => 1,
            'explanation' => 'raise menghentikan setter sebelum self._nilai = baru, sehingga data valid sebelumnya tetap tersimpan.',
        ],
        [
            'id' => 'q10', 'type' => 'multiple_choice',
            'question' => 'Program memproses daftar sensor dengan memanggil item.baca_data() dalam satu loop. SensorKekeruhan akan ditambahkan. Perubahan manakah yang mempertahankan pola polimorfisme tersebut?',
            'options' => [
                'Tambahkan cabang pemeriksaan nama kelas untuk setiap jenis sensor di loop.',
                'Pindahkan seluruh implementasi baca_data dari kelas sensor ke dalam loop.',
                'Implementasikan baca_data pada SensorKekeruhan, lalu tambahkan objeknya ke daftar.',
                'Ubah seluruh sensor lama menjadi SensorKekeruhan agar jenis objek seragam.',
            ], 'correct' => 2,
            'explanation' => 'Interface pemanggilan tetap sama; perilaku khusus berada pada implementasi masing-masing kelas.',
        ],
        [
            'id' => 'q11', 'type' => 'code_fill',
            'question' => 'Lengkapi satu penugasan di initializer agar setiap objek Sensor menyimpan parameter nilai pada atribut instance nilai. Jangan menggunakan nilai konstan.',
            'code' => <<<'PYTHON'
class Sensor:
    def __init__(self, nilai):
        ________________________________

a = Sensor(70)
b = Sensor(90)
print(a.nilai, b.nilai)
PYTHON,
            'answer' => 'self.nilai = nilai',
        ],
        [
            'id' => 'q12', 'type' => 'code_fill',
            'question' => 'Lengkapi satu pernyataan return agar property ph membaca atribut internal _ph milik objek yang sama tanpa memanggil property itu kembali.',
            'code' => <<<'PYTHON'
class SensorPH:
    def __init__(self, ph):
        self._ph = ph

    @property
    def ph(self):
        ________________________________

print(SensorPH(7.2).ph)
PYTHON,
            'answer' => 'return self._ph',
        ],
        [
            'id' => 'q13', 'type' => 'code_fill',
            'question' => 'Lengkapi satu pemanggilan menggunakan super() dan argumen posisi nama, lokasi agar initializer induk menyimpan kedua atribut sebelum atribut aliran ditambahkan.',
            'code' => <<<'PYTHON'
class Ekosistem:
    def __init__(self, nama, lokasi):
        self.nama = nama
        self.lokasi = lokasi

class Sungai(Ekosistem):
    def __init__(self, nama, lokasi, aliran):
        ________________________________
        self.aliran = aliran

sungai = Sungai("Barito", "Kalimantan Selatan", 5)
print(sungai.nama, sungai.lokasi, sungai.aliran)
PYTHON,
            'answer' => 'super().__init__(nama, lokasi)',
        ],
        [
            'id' => 'q14', 'type' => 'code_fill',
            'question' => 'Lengkapi ekspresi pemanggilan method melalui item agar loop menampilkan pembacaan setiap objek tanpa pemeriksaan tipe. Jangan menggunakan nilai konstan.',
            'code' => <<<'PYTHON'
class SensorPH:
    def baca_data(self):
        return 7.2

class SensorSuhu:
    def baca_data(self):
        return 29.5

for item in [SensorPH(), SensorSuhu()]:
    print(________________________________)
PYTHON,
            'answer' => 'item.baca_data()',
        ],
        [
            'id' => 'q15', 'type' => 'code_fill',
            'question' => 'Lengkapi deklarasi superclass SensorPH agar mewarisi SensorLingkungan. Implementasi baca_data yang tersedia harus memenuhi kontrak abstract method sehingga objek konkret dapat dibuat.',
            'code' => <<<'PYTHON'
from abc import ABC, abstractmethod

class SensorLingkungan(ABC):
    @abstractmethod
    def baca_data(self):
        pass

class SensorPH(________________________________):
    def baca_data(self):
        return 7.2

print(SensorPH().baca_data())
PYTHON,
            'answer' => 'SensorLingkungan',
        ],
        [
            'id' => 'q16', 'type' => 'code_fill',
            'question' => 'Lengkapi satu penugasan agar Stasiun menyimpan referensi objek yang diterima melalui parameter sensor pada atribut instance sensor. Ini merupakan relasi memiliki sensor.',
            'code' => <<<'PYTHON'
class Sensor:
    def baca(self):
        return 85

class Stasiun:
    def __init__(self, sensor):
        ________________________________

alat = Sensor()
stasiun = Stasiun(alat)
print(stasiun.sensor.baca())
PYTHON,
            'answer' => 'self.sensor = sensor',
        ],
        [
            'id' => 'q17', 'type' => 'code_fill',
            'question' => 'Lengkapi ekspresi yang memakai atribut nilai milik objek dan parameter kenaikan, dengan urutan nilai ditambah kenaikan, agar method mengembalikan hasil penambahan tanpa mengubah nilai tersimpan.',
            'code' => <<<'PYTHON'
class Sensor:
    def __init__(self, nilai):
        self.nilai = nilai

    def prediksi(self, kenaikan):
        return ________________________________

sensor = Sensor(70)
print(sensor.prediksi(5), sensor.nilai)
PYTHON,
            'answer' => 'self.nilai + kenaikan',
        ],
        [
            'id' => 'q18', 'type' => 'code_fill',
            'question' => 'Subclass memperluas pembacaan induk dengan koreksi. Lengkapi ekspresi berupa pemanggilan baca_data melalui super() ditambah atribut koreksi, dalam urutan tersebut, tanpa memanggil method subclass kembali.',
            'code' => <<<'PYTHON'
class SensorLingkungan:
    def __init__(self, ph):
        self._ph = ph

    def baca_data(self):
        return self._ph

class SensorPH(SensorLingkungan):
    def __init__(self, ph, koreksi):
        super().__init__(ph)
        self.koreksi = koreksi

    def baca_data(self):
        return ________________________________

sensor = SensorPH(7.0, 0.2)
print(sensor.baca_data())
PYTHON,
            'answer' => 'super().baca_data() + self.koreksi',
        ],
        [
            'id' => 'q19', 'type' => 'code_fill',
            'question' => 'Validasi setter sudah tersedia. Lengkapi satu penugasan yang menyimpan parameter nilai pada atribut internal _ph tanpa memanggil setter kembali.',
            'code' => <<<'PYTHON'
class SensorPH:
    def __init__(self, ph):
        self.ph = ph

    @property
    def ph(self):
        return self._ph

    @ph.setter
    def ph(self, nilai):
        if nilai < 0 or nilai > 14:
            raise ValueError("Nilai di luar rentang latihan")
        ________________________________

sensor = SensorPH(7.2)
sensor.ph = 6.8
print(sensor.ph)
PYTHON,
            'answer' => 'self._ph = nilai',
        ],
        [
            'id' => 'q20', 'type' => 'code_fill',
            'question' => 'Method sumber sudah diimplementasikan pada kelas abstrak. Lengkapi ekspresi pemanggilan melalui sensor untuk memakai method konkret yang diwarisi, tanpa mendefinisikannya ulang.',
            'code' => <<<'PYTHON'
from abc import ABC, abstractmethod

class AlatPantau(ABC):
    def sumber(self):
        return "OOPy"

    @abstractmethod
    def baca(self):
        pass

class SensorPH(AlatPantau):
    def baca(self):
        return 7.2

sensor = SensorPH()
print(________________________________)
PYTHON,
            'answer' => 'sensor.sumber()',
        ],
    ],
];
