
<?php

return [

    'description' => 'Pelajari kelas abstrak (Abstract Class) untuk menyatakan kontrak perilaku minimum dalam Python. Gunakan ABC dan abstractmethod pada contoh sensor pemantauan lahan basah, serta pahami kapan abstract base class diperlukan.',

    // ==============================================
    // TUJUAN PEMBELAJARAN
    // ==============================================

    'objectives' => [
        'Menjelaskan perbedaan class konkret dan abstract base class.',
        'Menggunakan ABC dan abstractmethod dari modul abc.',
        'Membuat subclass konkret yang memenuhi abstract method.',
        'Menggabungkan ABC dengan inheritance dan polimorfisme.',
        'Menjelaskan bahwa ABC bersifat pilihan desain dalam Python, bukan syarat untuk semua polimorfisme.',
    ],

    'sections' => [

        // ==========================================
        // APERSEPSI
        // ==========================================
        [
            'id' => 'apersepsi',
            'title' => 'Apersepsi',
            'nav_group' => 'pendahuluan',

            'paragraphs' => [
                'Semua jenis sensor pada sistem seharusnya dapat membaca data. Kita ingin mendokumentasikan dan memastikan aturan bahwa setiap subclass sensor wajib menyediakan method baca_data(). Bagaimana membuat aturan itu secara eksplisit?',

                'Pada Bab 4 kita mempelajari inheritance dan pada Bab 5 kita mempelajari polimorfisme. Pada Bab 6, kita akan memakai abstract base class (ABC) ketika sebuah keluarga class membutuhkan kontrak perilaku yang jelas.',
            ],

            'tip' => 'Bayangkan SensorLingkungan sebagai dasar bagi SensorPH dan SensorSuhu. Setiap subclass konkret harus menyediakan cara membaca data jika baca_data() ditetapkan sebagai abstract method.',
        ],

        // ==========================================
        // 6.1 MEMBUAT ABSTRACT BASE CLASS
        // ==========================================
        [
            'id' => 'membuat-abstract-base-class',
            'title' => '6.1 Membuat Abstract Base Class',

            'paragraphs' => [
                'Modul abc pada Python menyediakan ABC dan abstractmethod. Class yang mewarisi ABC dapat menandai method tertentu sebagai abstrak menggunakan decorator @abstractmethod.',

                'Subclass konkret harus menyediakan implementasi seluruh abstract method yang masih berlaku sebelum object dapat dibuat secara normal. Hal ini membantu menetapkan kontrak perilaku minimum untuk keluarga class.',

                'Pada contoh berikut, SensorLingkungan merupakan abstract base class. SensorPH mengimplementasikan baca_data(), sehingga object SensorPH dapat dibuat dan method baca_data() menghasilkan nilai 7.1.',
            ],

            'code' => <<<'PYTHON'
from abc import ABC, abstractmethod

class SensorLingkungan(ABC):
    @abstractmethod
    def baca_data(self):
        pass

class SensorPH(SensorLingkungan):
    def baca_data(self):
        return 7.1

s = SensorPH()
print(s.baca_data())
PYTHON,

            'output' => '7.1',

            'tip' => 'SensorLingkungan() tidak bisa dibuat sebagai object selama baca_data() masih abstrak. SensorPH() bisa dibuat karena subclass tersebut sudah mengimplementasikan baca_data().',
        ],

        // ==========================================
        // 6.2 ABSTRACT METHOD DAN METHOD KONKRET
        // ==========================================
        [
            'id' => 'abstract-method-method-konkret',
            'title' => '6.2 Abstract Method dan Method Konkret',

            'paragraphs' => [
                'Abstract class dapat memiliki abstract method dan method konkret sekaligus. Abstract method menetapkan bagian yang wajib diimplementasikan oleh subclass konkret.',

                'Method konkret sudah memiliki implementasi dan dapat langsung diwarisi serta digunakan oleh subclass. Dalam contoh berikut, sumber() adalah method konkret yang mengembalikan teks "OOPy", sedangkan baca() adalah abstract method yang harus diimplementasikan subclass konkret.',
            ],

            'code' => <<<'PYTHON'
from abc import ABC, abstractmethod

class AlatPantau(ABC):
    def sumber(self):
        return "OOPy"

    @abstractmethod
    def baca(self):
        pass
PYTHON,

            'tip' => 'Perhatikan perbedaannya: sumber() sudah memiliki implementasi, sementara baca() belum. Contoh ini baru mendefinisikan abstract class, sehingga belum menghasilkan output.',
        ],

        // ==========================================
        // 6.3 KAPAN ABC DIGUNAKAN?
        // ==========================================
        [
            'id' => 'kapan-abc-digunakan',
            'title' => '6.3 Kapan ABC Digunakan?',

            'paragraphs' => [
                'Python tetap dapat menjalankan polimorfisme melalui duck typing tanpa abstract base class. Karena itu, ABC tidak wajib digunakan pada semua desain class.',

                'ABC berguna ketika kontrak eksplisit membuat desain lebih jelas. Misalnya, ada banyak subclass sensor yang semuanya harus menjamin ketersediaan method baca(). Jika implementasi abstract method belum dilengkapi, Python dapat menolak pembuatan object subclass tersebut.',

                'Gunakan ABC saat kontrak perilaku benar-benar dibutuhkan. Jangan menambah abstraksi hanya supaya hierarki class terlihat lebih formal.',
            ],

            'tables' => [
                [
                    'caption' => 'Perbedaan Class Biasa dan Abstract Base Class',

                    'headers' => [
                        'Class Biasa',
                        'Abstract Base Class',
                    ],

                    'rows' => [
                        [
                            'Dapat diinstansiasi jika inisialisasinya valid.',
                            'Dapat mencegah instansiasi selama abstract method belum dipenuhi.',
                        ],
                        [
                            'Tidak harus menetapkan kontrak abstract method.',
                            'Dapat menetapkan perilaku minimum melalui @abstractmethod.',
                        ],
                        [
                            'Cocok untuk object konkret yang lengkap.',
                            'Cocok sebagai fondasi keluarga class ketika kontrak eksplisit membantu desain.',
                        ],
                    ],
                ],
            ],

            'tip' => 'Jika hanya membutuhkan object berbeda yang sama-sama memiliki method tertentu, duck typing dari Bab 5 mungkin sudah cukup. Gunakan ABC ketika ingin menyatakan dan menegakkan kewajiban method pada subclass.',
        ],

        // ==========================================
        // AYO COBA - LIVE CODING
        // ==========================================
        [
            'id' => 'ayo-coba-kelas-abstrak',
            'title' => 'Ayo Coba – Live Coding',
            'nav_title' => 'Ayo Coba – Live Coding',

            'paragraphs' => [
                'Buat abstract class AlatPantau dengan abstract method baca(). Starter code sudah menyediakan kerangka AlatPantau, SensorTinggiAir, dan SensorSuhu.',

                'Lengkapi method baca() pada SensorTinggiAir dan SensorSuhu dengan keluaran yang berbeda. Setelah itu, buat object dari kedua subclass, simpan ke list, dan panggil baca() melalui satu loop untuk menerapkan polimorfisme.',

                'Klik Run Code untuk menjalankan program dan Submit untuk memeriksa hasil. Sebagai percobaan tambahan sesuai modul, hapus sementara implementasi baca() pada salah satu subclass lalu coba buat object-nya. Amati TypeError yang muncul, kemudian kembalikan implementasinya sebelum melakukan Submit.',
            ],

            'live_codes' => [
                [
                    'id' => 'bab6-abstract-class-alat-pantau',

                    'title' => 'Coba sendiri: Abstract Class AlatPantau',

                    'description' => 'Lengkapi baca() pada SensorTinggiAir dan SensorSuhu dengan nilai keluaran yang berbeda. Buat object kedua subclass, simpan pada list sensor, lalu panggil item.baca() menggunakan satu loop. Uji juga akibat menghilangkan implementasi baca() pada salah satu subclass, lalu pulihkan kembali.',

                    'entry_file' => 'main.py',

                    // ==================================
                    // STARTER CODE
                    // ==================================
                    'files' => [
                        'main.py' => <<<'PYTHON'
from abc import ABC, abstractmethod

class AlatPantau(ABC):
    @abstractmethod
    def baca(self):
        pass

class SensorTinggiAir(AlatPantau):
    # TODO: implementasikan method baca()
    pass

class SensorSuhu(AlatPantau):
    # TODO: implementasikan method baca()
    pass

# TODO: buat object dari kedua subclass.
# TODO: simpan kedua object ke list bernama sensor.
# TODO: panggil baca() melalui for item in sensor.
PYTHON,
                    ],

                    // ==================================
                    // CHECKER OTOMATIS LIVE CODING
                    // ==================================
                    'checker' => <<<'PYTHON'
import ast
import inspect

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


# ==================================
# MEMERIKSA KEBERADAAN CLASS
# ==================================

def class_ada(nama):
    return isinstance(
        globals().get(nama),
        type
    )


# ==================================
# MEMERIKSA ABSTRACT BASE CLASS
# ==================================

def cek_abstract_class():
    return (
        class_ada("AlatPantau")
        and issubclass(AlatPantau, ABC)
        and inspect.isabstract(AlatPantau)
    )


# ==================================
# MEMERIKSA ABSTRACT METHOD
# ==================================

def cek_abstract_method():
    return (
        cek_abstract_class()
        and "baca" in AlatPantau.__abstractmethods__
    )


# ==================================
# MEMERIKSA SUBCLASS
# ==================================

def cek_subclass(nama):
    return (
        class_ada(nama)
        and cek_abstract_class()
        and issubclass(
            globals()[nama],
            AlatPantau
        )
    )


# ==================================
# MEMERIKSA IMPLEMENTASI BACA()
# ==================================

def cek_implementasi():

    if not (
        cek_subclass("SensorTinggiAir")
        and cek_subclass("SensorSuhu")
    ):
        return False

    for kelas in (
        SensorTinggiAir,
        SensorSuhu
    ):

        if inspect.isabstract(kelas):
            return False

        if "baca" not in kelas.__dict__:
            return False

    tinggi = SensorTinggiAir().baca()
    suhu = SensorSuhu().baca()

    return (
        tinggi is not None
        and suhu is not None
        and tinggi != suhu
    )


# ==================================
# MEMERIKSA LIST SENSOR
# ==================================

def cek_list_sensor():

    return (
        cek_implementasi()
        and isinstance(
            globals().get("sensor"),
            list
        )
        and len(sensor) == 2

        and sum(
            type(item) is SensorTinggiAir
            for item in sensor
        ) == 1

        and sum(
            type(item) is SensorSuhu
            for item in sensor
        ) == 1
    )


# ==================================
# MEMERIKSA LOOP POLIMORFISME
# ==================================

def cek_loop_baca():

    with open(
        "main.py",
        encoding="utf-8"
    ) as berkas:
        pohon = ast.parse(berkas.read())

    for node in ast.walk(pohon):

        if not isinstance(node, ast.For):
            continue

        if not isinstance(node.iter, ast.Name):
            continue

        if node.iter.id != "sensor":
            continue

        if not isinstance(node.target, ast.Name):
            continue

        nama_item = node.target.id

        for statement in node.body:

            for call in ast.walk(statement):

                if (
                    isinstance(call, ast.Call)
                    and isinstance(
                        call.func,
                        ast.Attribute
                    )
                    and call.func.attr == "baca"
                    and isinstance(
                        call.func.value,
                        ast.Name
                    )
                    and call.func.value.id == nama_item
                ):
                    return True

    return False


# ==================================
# MEMERIKSA KONTRAK ABC
# ==================================

def cek_kontrak_abc():

    if not cek_abstract_method():
        return False

    try:
        class SensorBelumLengkap(AlatPantau):
            pass

        SensorBelumLengkap()

    except TypeError:
        return True

    return False


# ==================================
# HASIL PEMERIKSAAN
# ==================================

check(
    "AlatPantau adalah abstract base class",
    cek_abstract_class,
    "Gunakan class AlatPantau(ABC)."
)

check(
    "Method baca() bersifat abstrak",
    cek_abstract_method,
    "Tambahkan @abstractmethod tepat sebelum def baca(self)."
)

check(
    "SensorTinggiAir mewarisi AlatPantau",
    lambda: cek_subclass("SensorTinggiAir"),
    "Gunakan class SensorTinggiAir(AlatPantau)."
)

check(
    "SensorSuhu mewarisi AlatPantau",
    lambda: cek_subclass("SensorSuhu"),
    "Gunakan class SensorSuhu(AlatPantau)."
)

check(
    "Kedua subclass mengimplementasikan baca()",
    cek_implementasi,
    "Buat def baca(self) pada setiap subclass dan kembalikan dua hasil yang berbeda."
)

check(
    "Kedua object disimpan dalam list",
    cek_list_sensor,
    "Buat list sensor berisi satu SensorTinggiAir() dan satu SensorSuhu()."
)

check(
    "Method baca() dipanggil melalui loop",
    cek_loop_baca,
    "Gunakan for item in sensor: lalu panggil item.baca()."
)

check(
    "Kontrak ABC menolak subclass yang belum lengkap",
    cek_kontrak_abc,
    "Pastikan abstract method baca() benar-benar wajib diimplementasikan."
)
PYTHON,
                ],
            ],
        ],

        // ==========================================
        // AYO BERLATIH
        // ==========================================
        [
            'id' => 'ayo-berlatih-kelas-abstrak',
            'title' => 'Ayo Berlatih',

            'paragraphs' => [
                'Kerjakan latihan berikut di Editor OOPy untuk memperkuat pemahaman tentang abstract base class.',
            ],

            'practice' => [
                'Buat abstract class Laporan dengan abstract method buat_ringkasan(). Turunkan LaporanAir dan LaporanHabitat.',

                'Tambahkan method konkret sumber() pada abstract class dan buktikan subclass mewarisinya.',

                'Buat contoh kasus di mana duck typing sudah cukup sehingga ABC tidak perlu digunakan.',
            ],
        ],
    ],

    // ==============================================
    // RANGKUMAN
    // ==============================================

    'summary' => [
        'ABC menyatakan kontrak perilaku minimum secara eksplisit.',

        '@abstractmethod menandai method yang wajib dilengkapi subclass konkret.',

        'Abstract class dapat memiliki method konkret.',

        'Python tetap mendukung duck typing; ABC adalah alat desain yang digunakan ketika memberi kejelasan atau kontrak.',

        'ABC dapat bekerja bersama inheritance, overriding, enkapsulasi, dan polimorfisme.',
    ],

    // ==============================================
    // REFLEKSI
    // ==============================================

    'reflection' => [
        'Masalah desain apa yang dapat dicegah atau ditemukan lebih awal dengan ABC?',

        'Apa perbedaan abstract method dan method konkret?',

        'Kapan ABC justru menambah kerumitan yang tidak perlu?',
    ],

    // ==============================================
    // KUIS BAB 6
    // 3 PILIHAN GANDA + 2 ISIAN KODE
    // ==============================================

    'quiz' => [

        // ======================================
        // SOAL 1 - PILIHAN GANDA
        // ======================================
        [
            'type' => 'multiple_choice',

            'question' => 'ABC digunakan untuk ...',

            'options' => [
                'Mengganti semua tipe data',
                'Menetapkan kontrak umum yang eksplisit bagi subclass',
                'Membuat database',
                'Menghapus inheritance',
            ],

            'correct' => 1,

            'explanation' => 'ABC berguna untuk menetapkan kontrak perilaku eksplisit pada subclass.',
        ],

        // ======================================
        // SOAL 2 - PILIHAN GANDA
        // ======================================
        [
            'type' => 'multiple_choice',

            'question' => 'Class yang masih memiliki abstract method yang belum dipenuhi ...',

            'options' => [
                'Otomatis menjadi list',
                'Selalu bernilai None',
                'Otomatis berubah menjadi string',
                'Tidak dapat diinstansiasi secara normal',
            ],

            'correct' => 3,

            'explanation' => 'Python menolak instansiasi class dengan abstract method yang belum diimplementasikan.',
        ],

        // ======================================
        // SOAL 3 - PILIHAN GANDA
        // ======================================
        [
            'type' => 'multiple_choice',

            'question' => 'Pernyataan paling tepat tentang ABC di Python adalah ...',

            'options' => [
                'Wajib untuk semua class',
                'Berguna untuk kontrak eksplisit tetapi tidak wajib untuk semua polimorfisme',
                'Menggantikan duck typing sepenuhnya',
                'Hanya boleh digunakan untuk angka',
            ],

            'correct' => 1,

            'explanation' => 'Duck typing tetap dapat digunakan tanpa ABC; ABC dipilih bila kontrak eksplisit diperlukan.',
        ],

        // ======================================
        // SOAL 4 - ISIAN KODE
        // ======================================
        [
            'type' => 'code_fill',

            'question' => 'Lengkapi kode berikut agar baca_data() menjadi abstract method.',

            'code' => <<<'PYTHON'
class SensorLingkungan(ABC):
    ____________________
    def baca_data(self):
        pass
PYTHON,

            'answer' => '@abstractmethod',

            'explanation' => 'Decorator @abstractmethod menandai method yang wajib diimplementasikan subclass konkret.',
        ],

        // ======================================
        // SOAL 5 - ISIAN KODE
        // ======================================
        [
            'type' => 'code_fill',

            'question' => 'Lengkapi import berikut agar ABC dan abstractmethod dapat digunakan.',

            'code' => <<<'PYTHON'
from __________ import ABC, abstractmethod
PYTHON,

            'answer' => 'abc',

            'explanation' => 'ABC dan abstractmethod disediakan oleh modul abc pada Python.',
        ],
    ],
];
