<?php

return [
    'description' => 'Pelajari fondasi Python melalui kasus pemantauan lahan basah, lalu pahami kapan data dan perilaku perlu dikelompokkan menggunakan pendekatan OOP.',
    'objectives' => [
        'Mengidentifikasi nilai dan tipe data int, float, str, serta bool, kemudian menggunakan variabel dan penugasan dengan benar.',
        'Menggunakan operator, input/output, percabangan, perulangan, dan list untuk menyelesaikan masalah sederhana.',
        'Membuat dan memanggil fungsi serta membedakan parameter, argumen, return, dan print().',
        'Membaca contoh program sederhana, memperkirakan output, dan memperbaiki kesalahan logika dasar.',
        'Menjelaskan perbedaan pendekatan prosedural dan OOP, termasuk pengertian class, object, attribute, dan method.',
    ],
    'sections' => [
        [
            'id' => 'apersepsi',
            'title' => 'Apersepsi — Data Pemantauan Lahan Basah',
            'nav_title' => 'Apersepsi',
            'nav_group' => 'pendahuluan',
            'paragraphs' => [
                'Bayangkan kamu membuat program sederhana untuk mencatat kondisi beberapa lokasi lahan basah. Setiap lokasi memiliki nama, tinggi air, suhu, dan pH. Program juga harus menampilkan data dan menentukan status pemantauan berdasarkan aturan latihan.',
                'Jika hanya ada satu lokasi, beberapa variabel dan fungsi mungkin sudah cukup. Namun, bagaimana jika program harus menangani puluhan lokasi, memperbarui pengukuran, dan menampilkan status setiap lokasi? Pertanyaan ini akan menjadi penghubung antara dasar Python dan Object-Oriented Programming (OOP).',
                'Pada BAB 1, kamu mempelajari unsur-unsur program Python terlebih dahulu. Setelah itu, kamu akan melihat mengapa class dan object dapat membantu mengorganisasi program yang semakin kompleks. Kamu belum harus menguasai seluruh sintaks OOP; praktik mendalamnya dimulai pada BAB 2.',
            ],
            'tip' => 'Semua nama lokasi, nilai sensor, dan batas kategori pada BAB ini merupakan contoh untuk belajar pemrograman, bukan hasil pengukuran atau standar ilmiah lingkungan.',
        ],
        [
            'id' => 'nilai-tipe-data-variabel',
            'title' => '1.1 Nilai, Tipe Data, Variabel, dan Penugasan',
            'nav_title' => '1.1 Nilai, Tipe Data & Variabel',
            'paragraphs' => [
                'Program bekerja dengan nilai. Setiap nilai di Python mempunyai tipe data yang menentukan cara nilai tersebut digunakan. Empat tipe yang sering dipakai pada tahap awal adalah int (bilangan bulat), float (bilangan desimal), str (teks), dan bool (True atau False).',
                'Variabel adalah nama yang merujuk pada suatu nilai. Operator = melakukan penugasan, misalnya tinggi_air = 135. Nama variabel sebaiknya menjelaskan isi datanya, seperti nama_lokasi atau suhu_air, agar kode mudah dipahami.',
                'Python menentukan tipe nilai secara otomatis. Kamu dapat menggunakan type() untuk memeriksa tipe sebuah nilai. Perhatikan bahwa teks "135" berbeda dengan angka 135: teks tidak dapat langsung dijumlahkan dengan bilangan tanpa konversi.',
            ],
            'tables' => [
                [
                    'caption' => 'Tipe data dasar pada Python',
                    'headers' => ['Tipe', 'Contoh penugasan', 'Keterangan'],
                    'rows' => [
                        ['int', ['code' => 'tinggi_air = 135'], 'Bilangan bulat.'],
                        ['float', ['code' => 'ph = 7.2'], 'Bilangan desimal.'],
                        ['str', ['code' => 'lokasi = "Rawa Gambut"'], 'Teks di dalam tanda petik.'],
                        ['bool', ['code' => 'sensor_aktif = True'], 'Nilai logika True atau False.'],
                    ],
                ],
            ],
            'code' => <<<'PYTHON'
lokasi = "Rawa Gambut"
tinggi_air = 135
ph = 7.2
sensor_aktif = True

print(lokasi)
print(type(tinggi_air).__name__)
print(type(ph).__name__)
print(type(sensor_aktif).__name__)

# Nilai sebuah variabel bisa diperbarui.
tinggi_air = 140
print("Tinggi terbaru:", tinggi_air)
PYTHON,
            'output' => <<<'OUTPUT'
Rawa Gambut
int
float
bool
Tinggi terbaru: 140
OUTPUT,
            'breakdown' => [
                'lokasi, tinggi_air, ph, dan sensor_aktif adalah nama variabel; nilai di sebelah kanan = diberikan kepada variabel.',
                'type(...).__name__ menampilkan nama tipe data agar hasil mudah dibaca.',
                'Penugasan tinggi_air = 140 mengganti nilai yang dirujuk oleh nama tinggi_air pada contoh ini.',
                'Teks "140" dan bilangan 140 berbeda tipe meskipun terlihat mirip saat ditampilkan.',
            ],
            'tip' => 'Penamaan variabel Python peka huruf besar-kecil: tinggi_air dan Tinggi_Air dianggap dua nama berbeda. Gunakan snake_case agar nama mudah dibaca.',
            'exploration' => [
                'Coba ubah ph menjadi 7 (tanpa desimal), jalankan kembali, lalu perhatikan hasil type().',
                'Prediksi: apa yang terjadi jika print(tinggi_air) ditulis sebelum tinggi_air = 140 dan setelah baris tersebut?',
            ],
            'practice' => [
                'Buat variabel lokasi, suhu, dan sensor_aktif dengan tipe yang sesuai, lalu tampilkan nilainya.',
                'Jelaskan mengapa "7.2" dan 7.2 mempunyai tipe data yang berbeda.',
            ],
        ],
        [
            'id' => 'operator-ekspresi',
            'title' => '1.2 Operator dan Ekspresi',
            'nav_title' => '1.2 Operator & Ekspresi',
            'paragraphs' => [
                'Operator digunakan untuk melakukan operasi terhadap nilai. Ekspresi adalah gabungan nilai, variabel, dan operator yang menghasilkan suatu nilai. Contohnya, tinggi_air + 10 menghasilkan bilangan, sedangkan tinggi_air >= 100 menghasilkan bool.',
                'Operator aritmatika dipakai untuk berhitung; operator perbandingan memeriksa hubungan antarnilai; dan operator logika menggabungkan beberapa kondisi. Ingat perbedaan penting: = melakukan penugasan, sedangkan == menguji apakah dua nilai sama.',
                'Hasil perbandingan dapat digabungkan dengan and (kedua kondisi harus benar), or (minimal satu kondisi benar), serta not (membalik nilai logika). Gunakan tanda kurung saat ekspresi mulai panjang agar maksudnya jelas.',
            ],
            'tables' => [
                [
                    'caption' => 'Kelompok operator Python',
                    'headers' => ['Kelompok', 'Operator', 'Contoh dan makna'],
                    'rows' => [
                        ['Aritmatika', ['code' => '+ - * / // % **'], 'Penjumlahan, pengurangan, perkalian, pembagian, pembagian bulat, sisa bagi, pangkat.'],
                        ['Perbandingan', ['code' => '== != > < >= <='], 'Memeriksa kesamaan, perbedaan, atau urutan nilai.'],
                        ['Logika', ['code' => 'and or not'], 'Menggabungkan atau membalik kondisi True/False.'],
                    ],
                ],
            ],
            'code' => <<<'PYTHON'
tinggi_air = 135
sensor_aktif = True

print(tinggi_air + 10)
print(tinggi_air // 10)
print(tinggi_air % 10)
print(tinggi_air == 100)
print(tinggi_air >= 100)
print(sensor_aktif and tinggi_air >= 100)
PYTHON,
            'output' => <<<'OUTPUT'
145
13
5
False
True
True
OUTPUT,
            'breakdown' => [
                '135 // 10 menghasilkan 13 karena // mengambil hasil pembagian bulat; 135 % 10 menghasilkan sisa bagi 5.',
                '135 == 100 bernilai False, sedangkan 135 >= 100 bernilai True.',
                'Ekspresi sensor_aktif and tinggi_air >= 100 bernilai True hanya jika sensor aktif dan syarat tinggi air terpenuhi.',
            ],
            'examples' => [
                [
                    'title' => 'Contoh tambahan: Memeriksa dua batas sekaligus',
                    'paragraphs' => [
                        'Python dapat membandingkan satu nilai dengan dua batas menggunakan bentuk 6.5 <= ph <= 8.5. Nilai batas ini hanya digunakan untuk latihan logika.',
                    ],
                    'code' => <<<'PYTHON'
ph = 7.2
print(6.5 <= ph <= 8.5)
print(ph < 6.5 or ph > 8.5)
PYTHON,
                    'output' => <<<'OUTPUT'
True
False
OUTPUT,
                ],
            ],
            'practice' => [
                'Prediksi output dari print(8 % 3), print(8 // 3), dan print(8 == 3), lalu periksa dengan menjalankan kodenya.',
                'Gunakan and untuk memeriksa apakah suhu bernilai 25 sampai 35 (termasuk kedua batas).',
            ],
        ],
        [
            'id' => 'input-output',
            'title' => '1.3 Input dan Output',
            'nav_title' => '1.3 Input & Output',
            'paragraphs' => [
                'Program dapat menerima masukan (input) dan menampilkan hasil (output). Fungsi print() menampilkan nilai ke layar, sedangkan input() membaca masukan pengguna dalam bentuk teks (str).',
                'Jika masukan akan dihitung sebagai angka, ubah tipenya menggunakan int() atau float(). Sebagai contoh, float(input(...)) mengubah teks masukan menjadi bilangan desimal. Masukan yang bukan angka akan menimbulkan ValueError ketika dikonversi; pemeriksaan kesalahan akan dipelajari pada tahap berikutnya.',
                'F-string mempermudah penggabungan teks dan nilai variabel: tulis huruf f sebelum tanda petik, lalu masukkan nama variabel di dalam kurung kurawal.',
            ],
            'code' => <<<'PYTHON'
nama = input("Nama lokasi: ")
tinggi = float(input("Tinggi air (cm): "))

print(f"{nama}: {tinggi} cm")
PYTHON,
            'examples' => [
                [
                    'title' => 'Alternatif praktik tanpa dialog input',
                    'paragraphs' => [
                        'Jika editor di browser belum menampilkan dialog input(), gunakan nilai variabel langsung. Logika menampilkan output tetap sama.',
                    ],
                    'code' => <<<'PYTHON'
nama = "Rawa Gambut"
tinggi = 135.0
print(f"{nama}: {tinggi:.1f} cm")
PYTHON,
                    'output' => 'Rawa Gambut: 135.0 cm',
                ],
            ],
            'tip' => 'input() selalu menghasilkan str. Pyodide mendukung sintaks Python, tetapi tersedianya dialog input interaktif bergantung pada implementasi editor OOPy.',
            'practice' => [
                'Buat program yang menyimpan nama lokasi dan suhu, lalu tampilkan kalimat menggunakan f-string.',
                'Jelaskan mengapa hasil input("Umur: ") perlu dikonversi sebelum dijumlahkan dengan angka.',
            ],
        ],
        [
            'id' => 'percabangan',
            'title' => '1.4 Percabangan',
            'paragraphs' => [
                'Percabangan membuat program memilih tindakan berdasarkan kondisi. Python menyediakan if untuk kondisi pertama, elif untuk kondisi alternatif, dan else untuk keadaan ketika seluruh kondisi sebelumnya salah.',
                'Kondisi diperiksa dari atas ke bawah. Dalam satu rangkaian if/elif/else, hanya blok dari kondisi pertama yang bernilai True yang dijalankan. Tanda titik dua (:) memulai blok, sedangkan indentasi—umumnya empat spasi—menentukan baris yang termasuk dalam blok tersebut.',
                'Dalam contoh berikut, angka 135 tidak memenuhi syarat >= 150, tetapi memenuhi syarat >= 100. Karena itu status yang dihasilkan adalah "Perlu dipantau".',
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
            'breakdown' => [
                'Jika tinggi_air bernilai 170, kondisi pertama sudah True dan program memilih "Waspada".',
                'Jika tinggi_air bernilai 120, kondisi pertama False dan kondisi kedua True sehingga hasilnya "Perlu dipantau".',
                'Jika tinggi_air bernilai 80, kedua kondisi False sehingga blok else dijalankan.',
                'Urutan kondisi penting: syarat >= 150 harus ditempatkan sebelum >= 100 agar nilai 170 tidak salah diklasifikasikan.',
            ],
            'tip' => 'Batas 100 dan 150 hanya contoh untuk melatih logika percabangan, bukan batas kondisi lingkungan yang berlaku secara umum.',
            'practice' => [
                'Ubah tinggi_air secara bergantian menjadi 80, 120, dan 170. Catat status yang muncul dan jelaskan alasannya.',
                'Apa akibatnya jika kondisi >= 100 ditulis lebih dulu daripada >= 150? Jelaskan sebelum menjalankan program.',
            ],
        ],
        [
            'id' => 'perulangan-list',
            'title' => '1.5 Perulangan dan List',
            'nav_title' => '1.5 Perulangan & List',
            'paragraphs' => [
                'List menyimpan beberapa nilai dalam satu urutan, misalnya nama-nama lokasi. List ditulis dengan tanda kurung siku, seperti ["Sungai", "Rawa", "Mangrove"]. Setiap elemen memiliki indeks yang dimulai dari 0: lokasi[0] berarti elemen pertama.',
                'Perulangan (loop) menjalankan blok kode berulang. for cocok untuk mengunjungi setiap anggota list. while cocok ketika pengulangan bergantung pada kondisi yang terus diperiksa.',
                'Pada while, kondisi harus bisa menjadi False agar perulangan berhenti. Kedua jenis loop juga akan berguna pada OOP, misalnya ketika program perlu memproses banyak object dalam sebuah list.',
            ],
            'code' => <<<'PYTHON'
lokasi = ["Sungai", "Rawa", "Mangrove"]

print("Lokasi pertama:", lokasi[0])
for nama in lokasi:
    print("Memeriksa:", nama)
PYTHON,
            'output' => <<<'OUTPUT'
Lokasi pertama: Sungai
Memeriksa: Sungai
Memeriksa: Rawa
Memeriksa: Mangrove
OUTPUT,
            'examples' => [
                [
                    'title' => 'Contoh perulangan while',
                    'paragraphs' => [
                        'Variabel hitung diawali dengan 1. Pada setiap pengulangan, nilainya bertambah satu. Saat hitung menjadi 4, kondisi hitung <= 3 bernilai False sehingga loop selesai.',
                    ],
                    'code' => <<<'PYTHON'
hitung = 1
while hitung <= 3:
    print("Pemeriksaan ke-", hitung)
    hitung += 1
PYTHON,
                    'output' => <<<'OUTPUT'
Pemeriksaan ke- 1
Pemeriksaan ke- 2
Pemeriksaan ke- 3
OUTPUT,
                ],
                [
                    'title' => 'Menambahkan elemen pada list',
                    'paragraphs' => [
                        'Metode append() dapat menambahkan satu elemen pada akhir list. Contoh ini memperlihatkan bahwa isi list dapat berubah.',
                    ],
                    'code' => <<<'PYTHON'
lokasi = ["Sungai", "Rawa"]
lokasi.append("Mangrove")
print(len(lokasi))
print(lokasi[-1])
PYTHON,
                    'output' => <<<'OUTPUT'
3
Mangrove
OUTPUT,
                ],
            ],
            'breakdown' => [
                'for nama in lokasi mengambil elemen list satu demi satu; nama berisi elemen yang sedang diperiksa.',
                'while memeriksa suatu kondisi sebelum setiap pengulangan.',
                'len(lokasi) memberikan banyaknya elemen dalam list; indeks -1 mengakses elemen terakhir.',
            ],
            'practice' => [
                'Buat list berisi tiga nama lokasi pilihanmu, lalu tampilkan seluruh elemen dengan for.',
                'Buat while yang menampilkan angka 1 sampai 5 dan berhenti dengan benar.',
                'Jelaskan perbedaan situasi yang cocok untuk for dan while.',
            ],
        ],
        [
            'id' => 'fungsi',
            'title' => '1.6 Fungsi, Parameter, Argumen, dan return',
            'nav_title' => '1.6 Fungsi & Parameter',
            'paragraphs' => [
                'Fungsi adalah blok kode bernama yang dapat dipanggil berulang kali. Fungsi membantu kita menghindari penulisan logika yang sama di banyak tempat. Dalam Python, fungsi dibuat menggunakan def, diikuti nama, tanda kurung, titik dua, dan blok kode yang menjorok ke dalam.',
                'Parameter adalah nama yang ditulis pada definisi fungsi, sedangkan argumen adalah nilai yang dikirimkan saat fungsi dipanggil. Contohnya, nilai merupakan parameter dalam def status_ph(nilai), sedangkan 7.2 adalah argumen ketika status_ph(7.2) dipanggil.',
                'Perhatikan perbedaan return dan print(). return mengirim hasil dari fungsi kepada bagian program yang memanggilnya, sedangkan print() hanya menampilkan sesuatu. Hasil return dapat disimpan, dibandingkan, atau diolah kembali.',
            ],
            'code' => <<<'PYTHON'
def status_ph(nilai):
    if 6.5 <= nilai <= 8.5:
        return "Stabil (latihan)"
    return "Perlu diperiksa"

hasil = status_ph(7.2)
print(hasil)
print(status_ph(9.0))
PYTHON,
            'output' => <<<'OUTPUT'
Stabil (latihan)
Perlu diperiksa
OUTPUT,
            'breakdown' => [
                'status_ph adalah nama fungsi dan nilai merupakan parameternya.',
                '7.2 dan 9.0 merupakan argumen pada dua pemanggilan fungsi yang berbeda.',
                'return mengakhiri fungsi dan mengembalikan nilai; print(hasil) kemudian menampilkan nilai tersebut.',
                'Penempatan if dan return memungkinkan fungsi memilih hasil berdasarkan nilai yang diterima.',
            ],
            'examples' => [
                [
                    'title' => 'Contoh fungsi dengan tiga parameter',
                    'paragraphs' => [
                        'Fungsi dapat menerima lebih dari satu argumen. Pada contoh berikut, tiga nilai dijumlahkan dan dibagi tiga untuk memperoleh rata-rata.',
                    ],
                    'code' => <<<'PYTHON'
def rata_rata(a, b, c):
    return (a + b + c) / 3

hasil = rata_rata(24, 27, 30)
print(hasil)
PYTHON,
                    'output' => '27.0',
                ],
            ],
            'tip' => 'Rentang pH dalam contoh hanya untuk mempelajari fungsi dan kondisi, bukan pedoman universal kualitas air. Istilah parameter dan argumen perlu dibedakan meskipun sering dipakai bergantian dalam percakapan sehari-hari.',
            'practice' => [
                'Buat fungsi kali_dua(angka) yang mengembalikan hasil perkalian angka dengan 2, kemudian tampilkan hasil pemanggilannya.',
                'Tulis fungsi rata_rata(a, b, c) sendiri tanpa menyalin contoh, lalu uji dengan tiga angka lain.',
                'Jelaskan apa perbedaan hasil fungsi yang memakai return dengan fungsi yang hanya memakai print().',
            ],
        ],
        [
            'id' => 'prosedural-ke-oop',
            'title' => '1.7 Dari Prosedural ke OOP',
            'nav_title' => '1.7 Prosedural → OOP',
            'paragraphs' => [
                'Sampai di sini kamu telah memakai variabel untuk menyimpan data dan fungsi untuk memprosesnya. Pendekatan ini cocok untuk program sederhana. Namun, saat jumlah lokasi bertambah, kita mungkin harus memastikan nama, tinggi air, dan fungsi status selalu digunakan bersama dengan benar.',
                'Pada pendekatan prosedural, data biasanya dikelola terpisah dari fungsi yang memprosesnya. Pada pendekatan OOP, data yang saling berkaitan (attribute/state) dan operasi terhadap data tersebut (method/behavior) dikelompokkan dalam sebuah object yang dibuat berdasarkan class.',
                'Bandingkan dua contoh berikut. Keduanya menghasilkan informasi yang sama. Contoh pertama memakai variabel dan fungsi terpisah; contoh kedua memakai satu class bernama LokasiPantau untuk menyimpan nama dan tinggi air sekaligus menyediakan perilaku untuk menentukan status.',
            ],
            'code' => <<<'PYTHON'
# Pendekatan prosedural: data dan fungsi terpisah.
nama_lokasi = "Rawa Gambut"
tinggi_air = 135

def status_air(tinggi):
    if tinggi >= 150:
        return "Waspada"
    elif tinggi >= 100:
        return "Dipantau"
    return "Normal"

print(nama_lokasi, "-", status_air(tinggi_air))
PYTHON,
            'output' => 'Rawa Gambut - Dipantau',
            'examples' => [
                [
                    'title' => 'Contoh pengenalan OOP: data dan perilaku dalam satu object',
                    'paragraphs' => [
                        'Class adalah rancangan untuk membuat object. Pada contoh ini, object lokasi menyimpan nama serta tinggi air sebagai attribute, lalu mempunyai method status_air() untuk memproses datanya.',
                        'Bagian __init__() dan self cukup diamati sebagai cara Python menyiapkan serta mengakses attribute milik object. Kamu akan mempraktikkannya secara bertahap pada BAB 2.',
                    ],
                    'code' => <<<'PYTHON'
class LokasiPantau:
    def __init__(self, nama, tinggi_air):
        self.nama = nama
        self.tinggi_air = tinggi_air

    def status_air(self):
        if self.tinggi_air >= 150:
            return "Waspada"
        elif self.tinggi_air >= 100:
            return "Dipantau"
        return "Normal"

lokasi = LokasiPantau("Rawa Gambut", 135)
print(lokasi.nama, "-", lokasi.status_air())
PYTHON,
                    'output' => 'Rawa Gambut - Dipantau',
                ],
            ],
            'tables' => [
                [
                    'caption' => 'Istilah dasar yang akan dipelajari lebih lanjut pada BAB 2',
                    'headers' => ['Istilah', 'Makna dalam contoh'],
                    'rows' => [
                        ['Class (kelas)', 'LokasiPantau: rancangan untuk membuat object lokasi.'],
                        ['Object / instance (objek)', 'lokasi: satu object yang dibuat dari class LokasiPantau.'],
                        ['Attribute (atribut)', 'nama dan tinggi_air: data yang dimiliki object.'],
                        ['Method (metode)', 'status_air(): operasi yang dapat dipanggil pada object.'],
                        ['State (keadaan)', 'Nilai attribute object pada suatu waktu, misalnya tinggi_air = 135.'],
                    ],
                ],
                [
                    'caption' => 'Perbandingan sederhana prosedural dan OOP',
                    'headers' => ['Aspek', 'Prosedural', 'OOP'],
                    'rows' => [
                        ['Organisasi', 'Data dan fungsi dikelola secara terpisah.', 'Data dan perilaku terkait dikelompokkan dalam object.'],
                        ['Pemanggilan', 'status_air(tinggi_air)', 'lokasi.status_air()'],
                        ['Kegunaan', 'Sederhana untuk tugas atau proses yang tidak rumit.', 'Membantu memodelkan entitas dan tanggung jawab terkait.'],
                    ],
                ],
            ],
            'breakdown' => [
                'Perhatikan bahwa versi prosedural membutuhkan argumen tinggi ketika memanggil status_air(tinggi_air).',
                'Pada versi OOP, object lokasi sudah menyimpan tinggi_air sehingga method dapat mengaksesnya melalui self.tinggi_air.',
                'Membuat object lain cukup menggunakan rancangan yang sama dengan nilai berbeda, misalnya LokasiPantau("Mangrove", 170).',
                'OOP tidak selalu lebih baik untuk semua program. Pendekatan prosedural tetap berguna; OOP dipilih jika pengelompokan data dan perilaku membuat desain lebih jelas dan mudah dikelola.',
            ],
            'tip' => 'Tidak perlu menghafal seluruh sintaks class, __init__, dan self pada BAB 1. Fokuslah pada alasan pengelompokan data dan perilaku; detail pembuatan class dan object dibahas pada BAB 2.',
            'instructions' => [
                'Sebelum mengerjakan Live Coding, prediksi status untuk tinggi 80, 120, dan 170.',
                'Lengkapi fungsi status_air(tinggi) dengan if, elif, else atau return yang setara.',
                'Jalankan program, kemudian gunakan Submit untuk memeriksa berbagai nilai batas.',
                'Terakhir, hubungkan fungsi yang kamu buat dengan method status_air() di contoh class: logika penentu statusnya sama, tetapi tempat menyimpan data berbeda.',
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
                    // Pertahankan ID, kontrak fungsi, dan checker agar exercise yang ada tetap cocok.
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
            'exploration' => [
                'Bandingkan cara memanggil status_air(135) pada fungsi biasa dengan lokasi.status_air() pada contoh OOP. Jelaskan perbedaannya menggunakan kalimatmu sendiri.',
                'Bayangkan ada 20 lokasi. Menurutmu, informasi apa saja yang sebaiknya dimiliki oleh masing-masing object lokasi?',
            ],
            'practice' => [
                'Buat list berisi tiga lokasi dan tampilkan namanya menggunakan for.',
                'Buat fungsi klasifikasi_suhu(suhu) yang mengembalikan tiga kategori berdasarkan aturan latihan yang kamu pilih. Jelaskan kondisi dan batasnya.',
                'Buat fungsi rata_rata(a, b, c) yang mengembalikan rata-rata tiga angka, lalu tampilkan hasilnya.',
                'Tanpa menulis class baru, jelaskan apa keuntungan menyatukan nama lokasi, tinggi air, dan penentuan status dalam sebuah object.',
            ],
        ],
    ],
    'summary' => [
        'Nilai memiliki tipe data; variabel digunakan untuk merujuk pada nilai dan = digunakan untuk penugasan.',
        'Operator menghasilkan nilai atau kondisi, sementara if/elif/else mengatur pilihan tindakan.',
        'List menyimpan beberapa elemen; for dan while menjalankan pengulangan dengan cara yang berbeda.',
        'Fungsi dibuat dengan def. Parameter ada pada definisi fungsi, argumen dikirim saat pemanggilan, return mengembalikan nilai, dan print() menampilkan output.',
        'Pendekatan prosedural mengelola data dan fungsi secara terpisah; OOP dapat menyatukan data (attribute/state) dan perilaku (method) dalam object yang dibuat dari class.',
        'OOP adalah salah satu pendekatan desain program, bukan pengganti semua fungsi atau solusi wajib untuk setiap masalah.',
    ],
    'reflection' => [
        'Apa perbedaan antara variabel, nilai, dan tipe data? Berikan satu contoh Python.',
        'Mengapa urutan if dan elif dapat memengaruhi hasil klasifikasi status air?',
        'Kapan kamu memilih for, dan kapan kamu memilih while?',
        'Apa perbedaan return dengan print(), serta parameter dengan argumen?',
        'Mengapa pengelompokan nama lokasi, tinggi air, dan penentuan status ke dalam object dapat bermanfaat ketika lokasi semakin banyak?',
    ],
    // Quiz di sini merupakan fallback materi. Untuk situs dengan DB aktif, soal yang
    // ditampilkan berasal dari tabel questions dan tidak otomatis berubah saat PHP diedit.
    'quiz' => [
        [
            'type' => 'multiple_choice',
            'question' => 'Tipe data yang tepat untuk nilai 7.2 (tanpa tanda petik) adalah ...',
            'options' => ['int', 'float', 'str', 'bool'],
            'correct' => 1,
            'explanation' => 'Nilai 7.2 adalah bilangan desimal sehingga bertipe float.',
        ],
        [
            'type' => 'multiple_choice',
            'question' => 'Manakah pernyataan yang benar tentang = dan == dalam Python?',
            'options' => ['Keduanya digunakan untuk penugasan.', '= untuk perbandingan, == untuk penugasan.', '= untuk penugasan, == untuk membandingkan kesamaan.', 'Keduanya selalu menghasilkan bool.'],
            'correct' => 2,
            'explanation' => '= menugaskan nilai, sedangkan == membandingkan apakah dua nilai sama.',
        ],
        [
            'type' => 'multiple_choice',
            'question' => 'Jika tinggi_air = 135, berapa hasil dari ekspresi tinggi_air >= 100 and tinggi_air < 150?',
            'options' => ['True', 'False', '135', 'Error'],
            'correct' => 0,
            'explanation' => '135 memenuhi kedua syarat sehingga ekspresi and bernilai True.',
        ],
        [
            'type' => 'multiple_choice',
            'question' => 'Perulangan yang paling sesuai untuk mengunjungi setiap nama dalam list lokasi adalah ...',
            'options' => ['for', 'return', 'def', 'elif'],
            'correct' => 0,
            'explanation' => 'for dapat mengambil setiap elemen list secara berurutan.',
        ],
        [
            'type' => 'multiple_choice',
            'question' => 'Dalam pemanggilan status_ph(7.2), nilai 7.2 disebut ...',
            'options' => ['parameter', 'method', 'class', 'argumen'],
            'correct' => 3,
            'explanation' => 'Parameter ditulis pada definisi fungsi; argumen adalah nilai yang diberikan saat fungsi dipanggil.',
        ],
        [
            'type' => 'multiple_choice',
            'question' => 'Mengapa OOP dapat membantu mengelola program dengan banyak lokasi pemantauan?',
            'options' => ['Karena OOP melarang semua fungsi biasa.', 'Karena OOP menyatukan data dan perilaku yang terkait dalam object.', 'Karena OOP menghilangkan kebutuhan variabel.', 'Karena satu program OOP hanya boleh memiliki satu object.'],
            'correct' => 1,
            'explanation' => 'OOP membantu mengorganisasi state dan behavior yang berkaitan di dalam object.',
        ],
        [
            'type' => 'code_fill',
            'question' => 'Lengkapi kode berikut agar fungsi mengembalikan teks "Normal":',
            'code' => <<<'PYTHON'
def status_air():
    __________ "Normal"
PYTHON,
            'answer' => 'return',
            'explanation' => 'return mengembalikan hasil kepada bagian program yang memanggil fungsi.',
        ],
        [
            'type' => 'code_fill',
            'question' => 'Lengkapi kode berikut agar kondisi kedua diperiksa jika kondisi pertama salah:',
            'code' => <<<'PYTHON'
if tinggi >= 150:
    status = "Waspada"
__________ tinggi >= 100:
    status = "Dipantau"
PYTHON,
            'answer' => 'elif',
            'explanation' => 'elif memeriksa kondisi alternatif ketika kondisi sebelumnya tidak terpenuhi.',
        ],
        [
            'type' => 'code_fill',
            'question' => 'Lengkapi kode agar semua nama dalam list lokasi ditampilkan satu per satu:',
            'code' => <<<'PYTHON'
lokasi = ["Sungai", "Rawa", "Mangrove"]
__________ nama in lokasi:
    print(nama)
PYTHON,
            'answer' => 'for',
            'explanation' => 'for digunakan untuk memproses setiap elemen dari list.',
        ],
        [
            'type' => 'code_fill',
            'question' => 'Lengkapi kode berikut agar nilai variabel tinggi dikonversi menjadi bilangan desimal:',
            'code' => <<<'PYTHON'
tinggi = __________("135.5")
PYTHON,
            'answer' => 'float',
            'explanation' => 'float() mengubah teks angka menjadi nilai bilangan desimal.',
        ],
    ],
];
