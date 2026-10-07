<?php

return [
    'description' => 'Fondasi singkat yang dibutuhkan sebelum memasuki pemodelan object.',
    'objectives' => [
        'Menggunakan variabel dan tipe data dasar pada program sederhana.',
        'Menggunakan operator, input/output, percabangan, perulangan, list, dan fungsi.',
        'Membedakan parameter dan argument secara sederhana.',
        'Menjelaskan mengapa pengelompokan data dan perilaku menjadi penting saat program bertambah kompleks.',
        'Menjelaskan gagasan dasar object-oriented programming.',
    ],
    'sections' => [
        [
            'id' => 'apersepsi',
            'title' => 'Apersepsi — Data Pemantauan Lahan Basah',
            'nav_title' => 'Apersepsi',
            'nav_group' => 'pendahuluan',
            'paragraphs' => [
                'Bayangkan program yang menyimpan nama lokasi, tinggi air, suhu, dan pH. Untuk satu lokasi, beberapa variabel dan fungsi masih mudah dikelola. Apa yang terjadi jika ada puluhan lokasi dan setiap jenis sensor memiliki aturan serta perilaku yang berbeda?',
            ],
        ],
        [
            'id' => 'nilai-tipe-data-variabel',
            'title' => '1.1 Nilai, Tipe Data, Variabel, dan Penugasan',
            'nav_title' => '1.1 Nilai, Tipe Data & Variabel',
            'paragraphs' => [
                'Nilai di Python memiliki tipe. int digunakan untuk bilangan bulat, float untuk desimal, str untuk teks, dan bool untuk nilai True atau False. Tipe membantu menentukan bagaimana suatu nilai dapat digunakan dalam program.',
                'Variabel memberi nama pada nilai. Tanda = adalah operator penugasan: nilai di sisi kanan disimpan melalui nama di sisi kiri. Misalnya, tinggi_air = 135 memberi nama tinggi_air pada nilai bilangan bulat 135.',
                'Gunakan type() untuk melihat tipe nilai yang sedang disimpan. Contoh berikut menggunakan data lokasi dan pemantauan lahan basah sebagai data latihan.',
            ],
            'tables' => [
                [
                    'caption' => 'Tipe data dasar Python',
                    'headers' => ['Tipe', 'Contoh penugasan', 'Kegunaan'],
                    'rows' => [
                        ['int', ['code' => 'tinggi_air = 135'], 'Data bilangan bulat.'],
                        ['float', ['code' => 'ph = 7.2'], 'Data desimal.'],
                        ['str', ['code' => 'lokasi = "Rawa Gambut"'], 'Teks/nama.'],
                        ['bool', ['code' => 'aktif = True'], 'Status benar/salah.'],
                    ],
                ],
            ],
            'code' => <<<'PYTHON'
lokasi = "Rawa Gambut"
tinggi_air = 135
ph = 7.2
aktif = True

print(type(lokasi))
print(type(tinggi_air))
print(type(ph))
print(type(aktif))
PYTHON,
            'output' => <<<'OUTPUT'
<class 'str'>
<class 'int'>
<class 'float'>
<class 'bool'>
OUTPUT,
            'tip' => 'Nilai pada contoh adalah data latihan pemrograman, bukan data ilmiah hasil pengukuran lahan basah.',
        ],
        [
            'id' => 'operator-ekspresi',
            'title' => '1.2 Operator dan Ekspresi',
            'nav_title' => '1.2 Operator & Ekspresi',
            'paragraphs' => [
                'Operator aritmatika memproses nilai numerik. Operator perbandingan menghasilkan bool, sedangkan operator logika menggabungkan atau membalik kondisi. Ekspresi menggabungkan nilai dan operator untuk menghasilkan sebuah nilai.',
                'Konsep ini nanti digunakan untuk validasi attribute, percabangan method, dan perhitungan sederhana. Tanda = digunakan untuk penugasan, sedangkan == membandingkan apakah dua nilai sama.',
            ],
            'tables' => [
                [
                    'caption' => 'Operator dan contoh ekspresi',
                    'headers' => ['Kelompok', 'Operator', 'Contoh ekspresi'],
                    'rows' => [
                        ['Aritmatika', ['code' => '+ - * / // % **'], ['code' => 'tinggi_air + 10']],
                        ['Perbandingan', ['code' => '== != > < >= <='], ['code' => 'ph >= 6.5']],
                        ['Logika', ['code' => 'and or not'], ['code' => 'aktif and tinggi_air > 100']],
                    ],
                ],
            ],
        ],
        [
            'id' => 'input-output',
            'title' => '1.3 Input dan Output',
            'nav_title' => '1.3 Input & Output',
            'paragraphs' => [
                'print() digunakan untuk menampilkan output. input() menerima teks dari pengguna. Jika input akan digunakan sebagai angka, lakukan konversi menggunakan float() untuk desimal atau int() untuk bilangan bulat.',
                'Pada contoh ini, nama menyimpan teks lokasi dan tinggi menyimpan hasil konversi ke float. F-string menyisipkan nilai variabel ke dalam teks output.',
            ],
            'code' => <<<'PYTHON'
nama = input("Nama lokasi: ")
tinggi = float(input("Tinggi air (cm): "))

print(f"{nama}: {tinggi} cm")
PYTHON,
            'tip' => 'Untuk latihan yang memerlukan input(), Pyodide dapat menjalankan sintaks Python di browser, tetapi perilaku dialog input dapat bergantung pada implementasi editor. Bila latihan di website belum menyediakan input interaktif, gunakan nilai variabel langsung pada starter code.',
        ],
        [
            'id' => 'percabangan',
            'title' => '1.4 Percabangan',
            'paragraphs' => [
                'if, elif, dan else memilih aksi berdasarkan kondisi. Python memeriksa kondisi dari atas ke bawah dan menjalankan blok pertama yang kondisinya benar. else menangani keadaan ketika semua kondisi sebelumnya salah.',
                'Gunakan titik dua dan indentasi empat spasi untuk menandai blok. Dengan tinggi_air = 135, kondisi pertama tidak terpenuhi, tetapi kondisi kedua terpenuhi sehingga status menjadi "Perlu dipantau".',
            ],
            'code' => <<<'PYTHON'
tinggi_air = 135

if tinggi_air >= 150:
    status = "Waspada"
elif tinggi_air >= 100:
    status = "Perlu dipantau"
else:
    status = "Normal"

print(status)
PYTHON,
            'output' => 'Perlu dipantau',
            'tip' => 'Nilai batas pada contoh digunakan untuk latihan logika, bukan standar ilmiah kualitas lingkungan.',
        ],
        [
            'id' => 'perulangan-list',
            'title' => '1.5 Perulangan dan List',
            'nav_title' => '1.5 Perulangan & List',
            'paragraphs' => [
                'Loop mengeksekusi blok kode berulang. while cocok jika pengulangan bergantung pada kondisi; kondisi harus dapat berubah agar pengulangan berhenti. for nyaman untuk memproses koleksi, seperti list yang menyimpan beberapa nilai dalam satu urutan.',
                'Contoh berikut memakai satu loop untuk menampilkan setiap nama dalam list lokasi.',
                'Pada materi OOP, list sering dipakai untuk menyimpan beberapa object lalu memprosesnya dengan satu loop.',
            ],
            'code' => <<<'PYTHON'
lokasi = ["Sungai", "Rawa", "Mangrove"]

for nama in lokasi:
    print("Memeriksa:", nama)
PYTHON,
            'output' => <<<'OUTPUT'
Memeriksa: Sungai
Memeriksa: Rawa
Memeriksa: Mangrove
OUTPUT,
        ],
        [
            'id' => 'fungsi',
            'title' => '1.6 Fungsi, Parameter, Argument, dan return',
            'nav_title' => '1.6 Fungsi & Parameter',
            'paragraphs' => [
                'Fungsi adalah blok kode bernama yang dapat digunakan kembali. Definisikan dengan def, lalu panggil ketika diperlukan. return mengembalikan hasil kepada pemanggil; print() menampilkan hasil tersebut sebagai output.',
                'Parameter berada pada definisi fungsi, sedangkan argument adalah nilai nyata saat fungsi dipanggil. Pada def status_ph(nilai), nilai adalah parameter. Pada status_ph(7.2), 7.2 adalah argument.',
            ],
            'code' => <<<'PYTHON'
def status_ph(nilai):
    if 6.5 <= nilai <= 8.5:
        return "Rentang latihan: stabil"

    return "Perlu diperiksa"

print(status_ph(7.2))
PYTHON,
            'output' => 'Rentang latihan: stabil',
            'tip' => 'Rentang pH pada contoh dipakai untuk latihan sintaks dan logika, bukan acuan universal kualitas air.',
        ],
        [
            'id' => 'prosedural-ke-oop',
            'title' => '1.7 Dari Prosedural ke OOP',
            'nav_title' => '1.7 Prosedural → OOP',
            'paragraphs' => [
                'Object menggabungkan data dan perilaku yang terkait. Class mendeskripsikan kelompok object sejenis. Saat program bertambah besar, pengelompokan state dan behavior membantu membagi tanggung jawab program menjadi bagian yang lebih jelas.',
                'Dari Pendekatan Prosedural ke Object-Oriented Programming: pada pendekatan prosedural, data dan fungsi dikelola terpisah. Pendekatan ini cocok untuk program kecil, tetapi dapat sulit dirawat saat jumlah entitas dan perilaku bertambah. Ketika kompleksitas meningkat, OOP mengelompokkan data (state) dan perilaku (method) ke dalam object yang dibuat dari class.',
                'OOP bukan berarti menghapus semua fungsi. Method pada class sendiri adalah fungsi yang terkait dengan object. BAB 2 akan melanjutkan fondasi ini dengan pemodelan class dan object.',
                'Ayo Coba — Live Coding: lengkapi fungsi status_air() di bawah. Mulailah dengan kondisi untuk nilai tertinggi, lalu jalankan dengan nilai 80, 120, dan 170. Amati hasilnya, ubah urutan kondisi, dan lihat mengapa urutan if/elif penting.',
            ],
            'tables' => [
                [
                    'caption' => 'Istilah dasar OOP',
                    'headers' => ['Istilah', 'Makna'],
                    'rows' => [
                        ['Class', 'Definisi/rancangan yang mendeskripsikan attribute dan perilaku object.'],
                        ['Object / instance', 'Wujud yang dibuat berdasarkan class.'],
                        ['Attribute', 'Data yang menggambarkan state object.'],
                        ['Method', 'Perilaku/fungsi yang terkait dengan object.'],
                        ['State', 'Keadaan object yang direpresentasikan oleh nilai attribute.'],
                    ],
                ],
            ],
            'live_codes' => [
                [
                    'id' => 'bab1-status-air',
                    'title' => 'Coba sendiri: Status Air',
                    'description' => 'Lengkapi fungsi `status_air(tinggi)`: kembalikan `"Waspada"` untuk tinggi >= 150, `"Dipantau"` untuk tinggi >= 100, dan `"Normal"` untuk nilai lainnya. Jalankan dengan nilai `80`, `120`, dan `170`, lalu amati hasilnya. Ubah urutan kondisi dan lihat dampaknya untuk memahami mengapa urutan `if/elif` penting. Gunakan Submit untuk memeriksa hasil serta batas 99, 100, 149, dan 150. Angka ini hanya untuk latihan logika, bukan standar ilmiah kualitas lingkungan.',
                    'entry_file' => 'main.py',
                    'files' => [
                        'main.py' => <<<'PYTHON'
def status_air(tinggi):
    # TODO: jika tinggi >= 150, return "Waspada"
    # TODO: jika tinggi >= 100, return "Dipantau"
    # selain itu return "Normal"
    pass


print(status_air(120))
PYTHON,
                    ],
                    'checker' => <<<'PYTHON'
results = []
fungsi = globals().get("status_air")
tersedia = callable(fungsi)
results.append({
    "label": "Fungsi status_air tersedia",
    "passed": tersedia,
    "feedback": "" if tersedia else "Definisikan fungsi status_air(tinggi).",
})

for tinggi, harapan in ((80, "Normal"), (120, "Dipantau"), (170, "Waspada"),
                       (99, "Normal"), (100, "Dipantau"),
                       (149, "Dipantau"), (150, "Waspada")):
    label = f"Nilai {tinggi} menghasilkan {harapan}"
    petunjuk = f"status_air({tinggi}) harus mengembalikan {harapan!r}. Periksa batas dan urutan if/elif."
    try:
        hasil = fungsi(tinggi) if tersedia else None
        passed = tersedia and hasil == harapan
        results.append({"label": label, "passed": bool(passed),
                        "feedback": "" if passed else petunjuk})
    except Exception as error:
        results.append({"label": label, "passed": False,
                        "feedback": f"{petunjuk} ({type(error).__name__}: {error})"})
PYTHON,
                ],
            ],
            'practice' => [
                'Buat list berisi tiga nama lokasi lalu tampilkan setiap nama menggunakan for.',
                'Buat fungsi klasifikasi_suhu(suhu) yang mengembalikan tiga kategori berdasarkan batas yang kamu tentukan sendiri. Jelaskan logikanya.',
                'Buat fungsi rata_rata(a, b, c) yang mengembalikan nilai rata-rata tiga angka.',
            ],
        ],
    ],
    'summary' => [
        'Python menggunakan nilai, tipe data, variabel, operator, kontrol alur, koleksi, dan fungsi sebagai fondasi pemrograman.',
        'Parameter berada pada definisi fungsi, sedangkan argument dikirim saat fungsi dipanggil.',
        'List dan loop akan berguna untuk memproses banyak object.',
        'OOP menggabungkan state dan behavior dalam object agar tanggung jawab program lebih terorganisasi.',
    ],
    'reflection' => [
        'Bagian dasar Python mana yang masih perlu kamu latih?',
        'Kapan fungsi terpisah mulai terasa kurang nyaman untuk mengelola banyak entitas?',
        'Jelaskan OOP menggunakan analogi sederhana dengan bahasamu sendiri.',
    ],
    'quiz' => [
        [
            'type' => 'multiple_choice',
            'question' => 'Tipe data yang tepat untuk nilai 7.2 adalah ...',
            'options' => ['int', 'float', 'str', 'bool'],
            'correct' => 1,
            'explanation' => '7.2 adalah bilangan desimal sehingga pada Python bertipe float.',
        ],
        [
            'type' => 'multiple_choice',
            'question' => 'Dalam pemanggilan status_ph(7.2), nilai 7.2 disebut ...',
            'options' => ['parameter', 'method', 'class', 'argument'],
            'correct' => 3,
            'explanation' => 'Parameter ditulis pada definisi fungsi, sedangkan 7.2 merupakan nilai yang diberikan ketika fungsi dipanggil sehingga disebut argument.',
        ],
        [
            'type' => 'multiple_choice',
            'question' => 'Gagasan utama OOP adalah ...',
            'options' => ['menghilangkan semua fungsi', 'mengelompokkan data dan perilaku terkait ke dalam object', 'menghindari variabel', 'membuat semua program hanya satu class'],
            'correct' => 1,
            'explanation' => 'OOP mengorganisasi data/state dan perilaku yang berkaitan ke dalam object.',
        ],
        [
            'type' => 'code_fill',
            'question' => 'Lengkapi kode berikut agar fungsi mengembalikan teks "Normal":',
            'code' => <<<'PYTHON'
def status_air():
    __________ "Normal"
PYTHON,
            'answer' => 'return',
            'explanation' => 'return mengembalikan hasil dari fungsi kepada pemanggil. Gunakan return "Normal" pada blok fungsi; print() hanya menampilkan output.',
        ],
        [
            'type' => 'code_fill',
            'question' => 'Lengkapi kode berikut agar kondisi kedua diperiksa dengan benar:',
            'code' => <<<'PYTHON'
if tinggi >= 150:
    status = "Waspada"
__________ tinggi >= 100:
    status = "Dipantau"
PYTHON,
            'answer' => 'elif',
            'explanation' => 'elif memeriksa kondisi berikutnya hanya ketika kondisi if sebelumnya tidak terpenuhi. Ini menjaga nilai tinggi >= 150 tetap berstatus "Waspada".',
        ],
    ],
];
