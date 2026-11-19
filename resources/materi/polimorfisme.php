
<?php

return [
    'description' => 'Gunakan satu cara pemanggilan method untuk memproses berbagai sensor. Pelajari hubungan overriding dan polimorfisme, lalu bandingkan inheritance dengan duck typing pada pemantauan lahan basah.',

    'objectives' => [
        'Menjelaskan polimorfisme sebagai pemrosesan berbagai object melalui antarmuka pemanggilan method yang seragam.',
        'Menghubungkan overriding pada subclass dengan perilaku berbeda saat method yang sama dipanggil.',
        'Menerapkan duck typing tanpa mensyaratkan superclass yang sama, selama object menyediakan method yang dibutuhkan.',
        'Membandingkan pemeriksaan tipe berulang dengan loop yang cukup memanggil method seragam.',
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
                'Pada BAB 4, subclass dapat meng-override method induk. Jika beberapa object sensor memiliki method baca_data(), program dapat memanggil method yang sama pada tiap object meskipun implementasi dan hasilnya berbeda.',

                'Kemampuan memproses object berbeda melalui antarmuka pemanggilan yang seragam disebut polimorfisme. Kita akan melihatnya melalui inheritance dan duck typing, lalu membandingkannya dengan pemeriksaan isinstance() yang berulang.',
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
                'Polimorfisme adalah kemampuan memproses object berbeda melalui antarmuka pemanggilan method yang seragam. Method yang sama dapat menghasilkan perilaku atau hasil berbeda sesuai object penerimanya.',

                'Pada inheritance, SensorLingkungan menyediakan method baca_data(), sementara SensorPH dan SensorSuhu meng-override-nya. Overriding adalah cara subclass menetapkan perilaku khusus; polimorfisme tampak ketika kode umum memanggil method itu pada object dari subclass berbeda.',

                'Kedua object disimpan dalam list sensor. Di dalam loop, pemanggil cukup menulis item.baca_data(); Python menjalankan implementasi yang sesuai dengan object saat itu.',
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
                'Polimorfisme di Python tidak harus menggunakan inheritance. Duck typing melihat apakah object menyediakan method atau atribut yang dibutuhkan, bukan apakah object mewarisi superclass tertentu.',

                'SensorVirtual dan LaporanManual pada contoh ini tidak memiliki superclass khusus yang sama, tetapi keduanya menyediakan baca_data(). Karena itu, fungsi yang memanggil obj.baca_data() dapat bekerja dengan kedua jenis object.',

                'Fungsi tampilkan() tidak perlu memeriksa nama class object terlebih dahulu. Fungsi cukup memanggil obj.baca_data(); setiap object yang diberikan tetap harus menyediakan method tersebut.',
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

            'tip' => 'Duck typing tidak mewajibkan superclass yang sama, tetapi pemanggil dan object tetap harus sepakat tentang nama serta cara penggunaan method. Tanpa baca_data(), pemanggilan dapat menghasilkan AttributeError.',
        ],

        // ==================================================
        // 5.3 MENGURANGI PEMERIKSAAN TIPE
        // ==================================================
        [
            'id' => 'mengurangi-pemeriksaan-tipe',
            'title' => '5.3 Mengurangi Pemeriksaan Tipe',

            'paragraphs' => [
                'Salah satu cara adalah memeriksa isinstance(item, SensorPH), isinstance(item, SensorSuhu), dan seterusnya pada setiap putaran. Setiap jenis baru menambah cabang yang perlu dirawat.',

                'Bandingkan dengan loop kedua pada contoh: setiap class menyediakan baca_data(), sehingga kode utama cukup memanggil item.baca_data() tanpa memilih implementasi berdasarkan tipe.',

                'Pemanggilan seragam cocok ketika semua object memang menyediakan perilaku yang sama. isinstance() tetap berguna jika tindakan yang diperlukan benar-benar berbeda berdasarkan tipe, tetapi tidak perlu dipakai hanya untuk memilih method yang sudah dimiliki object.',
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

print("Dengan pemeriksaan isinstance():")
for item in sensor:
    if isinstance(item, SensorPH):
        print(item.baca_data())
    elif isinstance(item, SensorSuhu):
        print(item.baca_data())
    elif isinstance(item, SensorTinggiAir):
        print(item.baca_data())

print("Dengan pemanggilan method seragam:")
for item in sensor:
    print(item.baca_data())
PYTHON,

            'output' => <<<'OUTPUT'
Dengan pemeriksaan isinstance():
pH: 7.1
Suhu: 29.5
Tinggi air: 128
Dengan pemanggilan method seragam:
pH: 7.1
Suhu: 29.5
Tinggi air: 128
OUTPUT,

            'tip' => 'Kesalahan umum: lupa memastikan setiap class menyediakan method dengan nama yang sama sebelum memakai loop seragam. Nilai pH, suhu, dan tinggi air pada contoh merupakan data latihan, bukan data pengukuran atau batas ilmiah lingkungan.',
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

                'Contoh teori memakai nama baca_data() untuk menekankan tindakan membaca hasil sensor. Live Coding memakai status() karena itulah antarmuka yang diperiksa checker latihan; keempat jenis sensor harus menyediakan implementasi status() yang berbeda. Nama method dapat berbeda antartugas, tetapi pola polimorfismenya sama: satu loop memanggil method seragam pada object berbeda.',

                'Nama SensorTinggiAir dan SensorKekeruhan dipilih sebagai contoh pengembangan latihan. Klik Run Code untuk mengamati output, kemudian Submit untuk memeriksa penerapan polimorfisme.',
            ],

            'live_codes' => [
                [
                    'id' => 'bab5-polimorfisme-sensor',

                    'title' => 'Coba sendiri: Satu method untuk empat sensor',

                    'description' => 'Lengkapi `status()` pada `SensorPH` dan `SensorSuhu`, tambahkan `SensorTinggiAir` serta `SensorKekeruhan` dengan status() yang berbeda, masukkan semuanya ke list `sensor`, lalu panggil status() melalui satu loop tanpa percabangan berdasarkan tipe object.',

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
import contextlib
import io
import sys

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
        # Use the learner's actual objects, including constructors with arguments.
        items = globals().get("sensor", [])
        obj = next((item for item in items if type(item) is kelas), None)
        if obj is None or not callable(getattr(obj, "status", None)):
            return False

        teks = obj.status()

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
    with open("main.py", encoding="utf-8") as berkas:
        source = berkas.read()
    pohon = ast.parse(source)
    sensor_names = {"SensorPH", "SensorSuhu", "SensorTinggiAir", "SensorKekeruhan"}

    # Reject type dispatch in branch conditions, while allowing unrelated
    # validation/debugging and ordinary value-based conditions in status().
    def type_dispatch(test):
        for node in ast.walk(test):
            if isinstance(node, ast.Name) and node.id in sensor_names:
                return True
            if isinstance(node, ast.Constant) and isinstance(node.value, str) and node.value in sensor_names:
                return True
            if isinstance(node, ast.Attribute) and node.attr == "__class__":
                return True
            if not isinstance(node, ast.Call):
                continue
            name = (node.func.id if isinstance(node.func, ast.Name)
                    else node.func.attr if isinstance(node.func, ast.Attribute) else "")
            if name == "type":
                return True
            if name == "isinstance" and len(node.args) > 1:
                if any(isinstance(part, ast.Name) and part.id in sensor_names
                       for part in ast.walk(node.args[1])):
                    return True
        return False

    for node in ast.walk(pohon):
        if isinstance(node, (ast.If, ast.IfExp)) and type_dispatch(node.test):
            return False
        if isinstance(node, ast.Match):
            if type_dispatch(node.subject) or any(
                isinstance(part, ast.MatchClass) for part in ast.walk(node)
            ):
                return False

    loops = [(node.lineno, node.end_lineno) for node in ast.walk(pohon)
             if isinstance(node, (ast.For, ast.While, ast.ListComp,
                                  ast.SetComp, ast.DictComp, ast.GeneratorExp))]
    if not loops:
        return False

    # Replay the learner's source in an isolated namespace and observe real
    # status() calls. A dead loop, empty iterable, or one sensor cannot pass.
    # This accepts aliases, enumerate, comprehensions, and helper functions.
    replay = {"__name__": "__main__", "__file__": __file__}
    observed = []

    def observe_status(obj, *args, **kwargs):
        frame = sys._getframe(1)
        in_loop = False
        while frame is not None:
            if frame.f_code.co_filename == __file__ and any(
                start <= frame.f_lineno <= end for start, end in loops
            ):
                in_loop = True
                break
            frame = frame.f_back
        value = obj.status(*args, **kwargs)
        if in_loop:
            observed.append((obj, value))
        return value

    class ObserveCalls(ast.NodeTransformer):
        def visit_Call(self, node):
            self.generic_visit(node)
            if isinstance(node.func, ast.Attribute) and node.func.attr == "status":
                replacement = ast.Call(
                    func=ast.Name(id="_oopy_observe_status", ctx=ast.Load()),
                    args=[node.func.value, *node.args], keywords=node.keywords)
                return ast.copy_location(replacement, node)
            return node

    replay["_oopy_observe_status"] = observe_status
    instrumented = ast.fix_missing_locations(ObserveCalls().visit(pohon))
    with contextlib.redirect_stdout(io.StringIO()):
        exec(compile(instrumented, __file__, "exec"), replay)
    items = replay.get("sensor", [])
    if not isinstance(items, list) or len(items) != 4:
        return False
    return all(any(obj is item and isinstance(value, str) and value.strip()
                   for obj, value in observed) for item in items)


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
    "Setiap object harus menyediakan status() yang return teks berbeda dan tidak kosong."
)

check(
    "Empat object dalam list sensor",
    cek_list_sensor,
    "Isi list sensor dengan tepat satu object dari masing-masing empat class."
)

check(
    "Pemanggilan status() melalui satu loop",
    cek_loop_umum,
    "Proses keempat object dalam list sensor melalui loop yang memanggil status(), tanpa percabangan berdasarkan tipe."
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
        'Polimorfisme memungkinkan object berbeda diproses melalui antarmuka pemanggilan method yang seragam.',

        'Pada inheritance, subclass meng-override method induk agar pemanggilan yang sama menghasilkan perilaku khusus.',

        'Duck typing tidak mensyaratkan superclass yang sama, tetapi object harus menyediakan method yang dibutuhkan.',

        'Loop dengan pemanggilan method seragam mengurangi pemeriksaan tipe yang tidak diperlukan; isinstance() tetap dapat digunakan jika logika memang bergantung pada tipe.',
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
