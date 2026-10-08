
<?php

return [

    'description' => 'Menyatakan kontrak perilaku minimum ketika desain memerlukannya.',

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
                'Dalam pemantauan lahan basah Kalimantan Selatan, sensor pH membaca informasi pH, sensor suhu membaca suhu, dan sensor tinggi air membaca ketinggian air. Ketiganya diharapkan mampu membaca data.',
                'BAB 4 memperkenalkan inheritance dan overriding; BAB 5 menggunakan method yang sama pada object berbeda melalui polimorfisme. Bagaimana memastikan setiap subclass sensor benar-benar menyediakan method baca_data() sebelum object tersebut digunakan?',
            ],

            'tip' => 'Pertanyaan ini mengarah pada kontrak perilaku minimum: kemampuan apa yang harus dimiliki setiap jenis sensor?',
        ],

        // ==========================================
        // 6.1 MEMBUAT ABSTRACT BASE CLASS
        // ==========================================
        [
            'id' => 'membuat-abstract-base-class',
            'title' => '6.1 Membuat Abstract Base Class',

            'paragraphs' => [
                'Class konkret adalah class yang sudah lengkap untuk dibuat menjadi object. Contohnya, SensorPH yang mempunyai cara membaca pH. Instansiasi berarti membuat object dari class, seperti SensorPH().',
                'Abstract class digunakan sebagai dasar yang menyatakan kewajiban bagi subclass. Abstract base class (ABC) adalah class dasar yang memakai mekanisme abc Python untuk menandai dan menegakkan kewajiban tersebut.',
                'Modul abc pada Python menyediakan ABC dan abstractmethod. Class yang mewarisi ABC dapat menandai method tertentu sebagai abstrak menggunakan decorator @abstractmethod.',
                'ABC menyediakan mekanisme pemeriksaan class abstrak; @abstractmethod menandai method yang harus dipenuhi subclass konkret. Inheritance menghubungkan kontrak pada superclass dengan implementasi pada subclass.',
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

sensor = SensorPH()
print(sensor.baca_data())
PYTHON,

            'output' => '7.1',

            'hierarchy' => [
                'caption' => 'Gambar 6.1 — Kontrak dan implementasi sensor',
                'label' => 'SensorLingkungan (ABC)',
                'contract' => '@abstractmethod baca_data()',
                'children' => [
                    ['label' => 'SensorPH', 'contract' => 'Mengimplementasikan baca_data() untuk membaca pH'],
                    ['label' => 'SensorSuhu', 'contract' => 'Mengimplementasikan baca_data() untuk membaca suhu'],
                ],
            ],

            'breakdown' => [
                'Bedah kode: from abc import ABC, abstractmethod mengambil mekanisme ABC dari modul bawaan Python; tidak perlu memasang library.',
                'SensorLingkungan(ABC) menggunakan mekanisme abstract base class. @abstractmethod menandai baca_data() sebagai kewajiban subclass konkret.',
                'SensorPH(SensorLingkungan) mewarisi kontrak, lalu override baca_data() dengan return 7.1. Object SensorPH dapat dibuat karena kewajiban abstract method telah dipenuhi.',
            ],

            'examples' => [
                [
                    'title' => 'Instansiasi yang ditolak karena kontrak belum lengkap',
                    'paragraphs' => [
                        'Contoh berikut sengaja mencoba membuat object dari ABC dan subclass yang belum mengimplementasikan baca_data(). Keduanya menghasilkan TypeError; try/except membuat penolakan tersebut dapat diamati tanpa menghentikan program.',
                    ],
                    'code' => <<<'PYTHON'
from abc import ABC, abstractmethod

class SensorLingkungan(ABC):
    @abstractmethod
    def baca_data(self):
        pass

class SensorBelumLengkap(SensorLingkungan):
    pass

for kelas in [SensorLingkungan, SensorBelumLengkap]:
    try:
        kelas()
    except TypeError:
        print(f"TypeError: {kelas.__name__} belum memenuhi baca_data()")
PYTHON,
                    'output' => "TypeError: SensorLingkungan belum memenuhi baca_data()\nTypeError: SensorBelumLengkap belum memenuhi baca_data()",
                ],
            ],

            'tip' => 'TypeError pada percobaan class yang belum lengkap adalah perilaku yang diharapkan, bukan masalah editor OOPy. Mewarisi ABC saja tidak otomatis mencegah instansiasi: class harus masih mempunyai abstract method yang belum dipenuhi.',
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
                'SensorSuhu cukup menyediakan baca(). Pemanggilan sensor.sumber() memakai implementasi yang diwarisi dari AlatPantau tanpa menulis ulang method tersebut.',
            ],

            'code' => <<<'PYTHON'
from abc import ABC, abstractmethod

class AlatPantau(ABC):
    def sumber(self):
        return "OOPy"

    @abstractmethod
    def baca(self):
        pass

class SensorSuhu(AlatPantau):
    def baca(self):
        return 29.5

sensor = SensorSuhu()
print(sensor.sumber())
print(sensor.baca())
PYTHON,

            'output' => "OOPy\n29.5",
            'tables' => [
                [
                    'caption' => 'Abstract method dan method konkret',
                    'headers' => ['Aspek', 'Abstract method', 'Method konkret'],
                    'rows' => [
                        ['Tujuan', 'Menetapkan kewajiban implementasi subclass konkret', 'Menyediakan implementasi yang dapat dipakai langsung'],
                        ['Penanda', '@abstractmethod', 'Tidak ditandai sebagai abstract method'],
                        ['Contoh', 'baca() pada AlatPantau', 'sumber() pada AlatPantau'],
                        ['Pemakaian subclass', 'Harus dipenuhi agar subclass menjadi konkret', 'Dapat diwarisi tanpa overriding'],
                    ],
                ],
            ],
            'tip' => 'Abstract method boleh mempunyai isi kode, tetapi tetap menandai kewajiban implementasi pada subclass konkret. pass pada contoh ini hanya kerangka. ABC memeriksa pemenuhan method, bukan otomatis menjamin kualitas atau kebenaran hasil baca().',
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
                'ABC bermanfaat ketika beberapa subclass harus menyediakan method yang sama, kontrak perlu dinyatakan dengan jelas, implementasi yang belum lengkap perlu terdeteksi saat instansiasi, dan keluarga class membutuhkan struktur yang lebih terarah.',
                'ABC tidak diperlukan ketika duck typing sudah cukup atau class sederhana tidak membutuhkan kontrak abstrak. Menambahkan abstract class tanpa kebutuhan dapat membuat rancangan lebih rumit.',
                'Contoh berikut meneruskan duck typing dari BAB 5. Dua class biasa menyediakan baca(), sehingga satu loop dapat memproses keduanya tanpa ABC atau superclass bersama.',
            ],

            'code' => <<<'PYTHON'
class SensorVirtual:
    def baca(self):
        return "Data sensor simulasi"

class LaporanManual:
    def baca(self):
        return "Data pengamatan manual"

alat = [SensorVirtual(), LaporanManual()]
for item in alat:
    print(item.baca())
PYTHON,
            'output' => "Data sensor simulasi\nData pengamatan manual",

            'tables' => [
                [
                    'caption' => 'Class Biasa vs Abstract Base Class',

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
            'title' => 'Ayo Coba — Live Coding Kelas Abstrak',
            'nav_title' => 'Ayo Coba — Live Coding',

            'paragraphs' => [
                'Buat abstract class AlatPantau dengan abstract method baca(). Starter code sudah menyediakan kerangka AlatPantau, SensorTinggiAir, dan SensorSuhu.',

                'Lengkapi method baca() pada SensorTinggiAir dan SensorSuhu dengan keluaran yang berbeda. Setelah itu, buat object dari kedua subclass, simpan ke list, dan panggil baca() melalui satu loop untuk menerapkan polimorfisme.',

                'Ikuti langkah di bawah, lalu klik Run Code untuk menjalankan program dan Submit untuk memeriksa kontrak serta perilaku kedua sensor. Angka pada latihan adalah data simulasi, bukan hasil pengukuran lapangan.',
            ],

            'instructions' => [
                'Gunakan ABC dan abstractmethod dari modul abc.',
                'Buat abstract class AlatPantau yang mempunyai abstract method baca().',
                'Buat subclass SensorTinggiAir dan SensorSuhu yang mewarisi AlatPantau.',
                'Implementasikan baca() pada masing-masing subclass.',
                'Kembalikan hasil berbeda sesuai konteks sensor: angka pengukuran yang valid atau teks informasi sensor yang tidak kosong.',
                'Buat satu object dari setiap subclass.',
                'Simpan kedua object ke dalam satu list; nama variabel list bebas.',
                'Gunakan satu loop untuk memanggil baca() pada kedua object.',
                'Jalankan program melalui Run Code, kemudian periksa jawaban melalui Submit.',
            ],
            'exploration' => [
                'Setelah latihan berhasil, hapus sementara implementasi baca() dari salah satu subclass lalu coba buat object-nya. TypeError yang muncul adalah penolakan kontrak yang diharapkan, bukan masalah editor.',
                'Kembalikan implementasi baca() sebelum Submit akhir agar kedua subclass kembali konkret.',
            ],

            'live_codes' => [
                [
                    'id' => 'bab6-kelas-abstrak-alat-pantau',

                    'title' => 'Coba sendiri: Kontrak AlatPantau',

                    'description' => 'Lengkapi `AlatPantau` menggunakan `ABC` dan `@abstractmethod` pada `baca()`. Implementasikan baca() pada `SensorTinggiAir` dan `SensorSuhu` dengan angka pengukuran atau teks sensor tidak kosong dan berbeda. Buat satu object setiap subclass dalam satu list, lalu panggil baca() pada keduanya melalui loop. Jalankan Run Code, kemudian Submit.',

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
# TODO: simpan kedua object ke dalam satu list.
# TODO: gunakan loop untuk memanggil baca() pada kedua object.
PYTHON,
                    ],

                    // ==================================
                    // CHECKER OTOMATIS LIVE CODING
                    // ==================================
                    'checker' => <<<'PYTHON'
import ast
import contextlib
import inspect
import io
import math
import sys
from abc import ABC as PythonABC

results = []


def check(label, operation, hint):
    try:
        passed = bool(operation())
        results.append({"label": label, "passed": passed,
                        "feedback": "" if passed else hint})
    except Exception as error:
        results.append({"label": label, "passed": False,
                        "feedback": f"{hint} ({type(error).__name__}: {error})"})


def cek_abc():
    kelas = globals().get("AlatPantau")
    return (isinstance(kelas, type) and issubclass(kelas, PythonABC)
            and inspect.isabstract(kelas))


def cek_abstract_method():
    return (cek_abc() and "baca" in AlatPantau.__abstractmethods__
            and callable(getattr(AlatPantau, "baca", None))
            and getattr(AlatPantau.baca, "__isabstractmethod__", False))


def cek_penolakan():
    if not cek_abstract_method():
        return False
    # Expected TypeError is feedback about the contract, not a checker failure.
    try:
        AlatPantau()
    except TypeError:
        pass
    else:
        return False

    class BelumLengkap(AlatPantau):
        def __init__(self):
            pass

    if not inspect.isabstract(BelumLengkap):
        return False
    try:
        BelumLengkap()
    except TypeError:
        return True
    return False


def cek_subclass(nama):
    kelas = globals().get(nama)
    return (cek_abc() and isinstance(kelas, type)
            and kelas is not AlatPantau and issubclass(kelas, AlatPantau))


def pasangan_dalam_list(namespace):
    tinggi = namespace.get("SensorTinggiAir")
    suhu = namespace.get("SensorSuhu")
    if not isinstance(tinggi, type) or not isinstance(suhu, type):
        return None
    # The exercise requires a list, without prescribing its variable name.
    for value in namespace.values():
        if (isinstance(value, list) and len(value) == 2
                and sum(type(item) is tinggi for item in value) == 1
                and sum(type(item) is suhu for item in value) == 1):
            return value
    return None


def object_uji(kelas):
    items = pasangan_dalam_list(globals())
    if items is not None:
        return next(item for item in items if type(item) is kelas)
    # Also accept constructors with arguments when the learner created an object
    # separately but has not yet completed the list requirement.
    for value in globals().values():
        if type(value) is kelas:
            return value
    return kelas()


def cek_konkret():
    for nama in ("SensorTinggiAir", "SensorSuhu"):
        if not cek_subclass(nama):
            return False
        kelas = globals()[nama]
        if inspect.isabstract(kelas) or not callable(getattr(kelas, "baca", None)):
            return False
        if type(object_uji(kelas)) is not kelas:
            return False
    return True


def bermakna(value):
    # No prescribed string or measurement threshold: valid numbers (including 0)
    # and nonempty sensor information are accepted; placeholders are not readings.
    if isinstance(value, str):
        return bool(value.strip())
    return type(value) in (int, float) and math.isfinite(value)


def cek_hasil():
    if not cek_konkret():
        return False
    tinggi = object_uji(SensorTinggiAir).baca()
    suhu = object_uji(SensorSuhu).baca()
    return bermakna(tinggi) and bermakna(suhu) and tinggi != suhu


def cek_loop_baca():
    with open("main.py", encoding="utf-8") as berkas:
        pohon = ast.parse(berkas.read())
    loops = [(node.lineno, node.end_lineno) for node in ast.walk(pohon)
             if isinstance(node, (ast.For, ast.While, ast.ListComp,
                                  ast.SetComp, ast.DictComp, ast.GeneratorExp))]
    if not loops:
        return False
    observed = []

    def observe_baca(obj, *args, **kwargs):
        frame = sys._getframe(1)
        in_loop = False
        while frame is not None:
            if frame.f_code.co_filename == __file__ and any(
                start <= frame.f_lineno <= end for start, end in loops
            ):
                in_loop = True
                break
            frame = frame.f_back
        value = obj.baca(*args, **kwargs)
        if in_loop:
            observed.append((obj, value))
        return value

    class ObserveCalls(ast.NodeTransformer):
        def visit_Call(self, node):
            self.generic_visit(node)
            if isinstance(node.func, ast.Attribute) and node.func.attr == "baca":
                replacement = ast.Call(
                    func=ast.Name(id="_oopy_observe_baca", ctx=ast.Load()),
                    args=[node.func.value, *node.args], keywords=node.keywords)
                return ast.copy_location(replacement, node)
            return node

    replay = {"__name__": "__main__", "__file__": __file__,
              "_oopy_observe_baca": observe_baca}
    instrumented = ast.fix_missing_locations(ObserveCalls().visit(pohon))
    with contextlib.redirect_stdout(io.StringIO()):
        exec(compile(instrumented, __file__, "exec"), replay)
    items = pasangan_dalam_list(replay)
    if items is None:
        return False
    readings = []
    for item in items:
        values = [value for obj, value in observed if obj is item and bermakna(value)]
        if not values:
            return False
        readings.append(values[0])
    return readings[0] != readings[1]


check("AlatPantau merupakan ABC", cek_abc,
      "Warisi ABC dari modul abc dan pertahankan AlatPantau sebagai class abstrak.")
check("Method baca() benar-benar abstrak", cek_abstract_method,
      "Tandai baca() dengan @abstractmethod; kontraknya harus tercatat pada class.")
check("ABC menolak instansiasi yang belum lengkap", cek_penolakan,
      "AlatPantau dan subclass yang belum memenuhi baca() harus ditolak dengan TypeError.")
check("SensorTinggiAir mewarisi AlatPantau", lambda: cek_subclass("SensorTinggiAir"),
      "Buat SensorTinggiAir sebagai subclass AlatPantau.")
check("SensorSuhu mewarisi AlatPantau", lambda: cek_subclass("SensorSuhu"),
      "Buat SensorSuhu sebagai subclass AlatPantau.")
check("Kedua subclass konkret dan dapat dibuat", cek_konkret,
      "Lengkapi baca() pada kedua subclass agar tidak lagi abstrak dan object dapat dibuat.")
check("Hasil baca() bermakna dan berbeda", cek_hasil,
      "Kembalikan dua angka pengukuran yang valid atau teks sensor tidak kosong dan berbeda; jangan gunakan pass, None atau bool.")
check("Kedua object disimpan dalam satu list", lambda: pasangan_dalam_list(globals()) is not None,
      "Buat satu object tiap subclass dan simpan keduanya dalam satu list; nama list bebas.")
check("Kedua object diproses melalui loop baca()", cek_loop_baca,
      "Jalankan loop yang benar-benar memanggil baca() pada kedua object dalam list; loop kosong atau tidak dijalankan belum cukup.")
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
                'Latihan 1 — Abstract Class Laporan: buat Laporan dengan abstract method buat_ringkasan(). Turunkan LaporanAir dan LaporanHabitat; masing-masing harus menyediakan implementasi buat_ringkasan().',

                'Latihan 2 — Method Konkret: tambahkan sumber() pada abstract class, lalu buktikan bahwa subclass dapat memakainya tanpa menulis ulang implementasi.',

                'Latihan 3 — ABC vs Duck Typing: buat satu kasus sederhana ketika duck typing sudah cukup tanpa ABC, lalu jelaskan alasan pemilihan desain tersebut.',
            ],
        ],
    ],

    // ==============================================
    // RANGKUMAN
    // ==============================================

    'summary' => [
        'ABC menyatakan kontrak perilaku minimum secara eksplisit.',

        '@abstractmethod menandai method yang wajib dilengkapi subclass konkret.',

        'Abstract class dapat mempunyai method konkret.',

        'Python tetap mendukung duck typing; ABC digunakan ketika kontrak eksplisit membantu memperjelas desain.',

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
