<?php

return [
    'description' => 'Mulai dari menyimpan data ekosistem, mengatur alur program, hingga mengenal cara berpikir berorientasi objek. Pelajari contoh secara berurutan, lalu coba ubah kodenya sendiri.',
    'objectives' => [
        'Menjelaskan pengertian dan kegunaan Python.',
        'Membuat variabel dengan nama yang bermakna.',
        'Menggunakan tipe data dasar Python.',
        'Menjelaskan cara menerima input dan menampilkan output.',
        'Menggunakan operator untuk mengolah dan membandingkan nilai.',
        'Membuat percabangan berdasarkan kondisi.',
        'Membuat perulangan untuk memproses beberapa data.',
        'Membuat dan memanggil fungsi sederhana.',
        'Menjelaskan konsep dasar paradigma OOP.',
    ],
    'sections' => [
        [
            'id' => 'python',
            'title' => 'Pengenalan Python',
            'paragraphs' => [
                'Python adalah bahasa pemrograman dengan sintaks yang mudah dibaca. Python digunakan dalam pendidikan, pengembangan aplikasi, analisis data, dan kecerdasan buatan. Di OOPy, kita menggunakannya untuk memodelkan informasi ekosistem lahan basah.',
                'Program tersusun dari instruksi. Contoh berikut meminta Python menampilkan sebuah pesan. Teks di antara tanda kutip merupakan nilai string, sedangkan print() menampilkannya sebagai output.',
            ],
            'code' => 'print("Mengenal ekosistem Sungai Barito")',
            'tip' => 'Contoh data di BAB ini digunakan untuk latihan pemrograman, bukan sebagai hasil pengukuran kondisi lingkungan.',
        ],
        [
            'id' => 'variabel',
            'title' => 'Variabel',
            'paragraphs' => [
                'Variabel adalah nama yang merujuk pada suatu nilai. Gunakan tanda = untuk memberikan nilai pada variabel. Nama yang jelas membantu kita memahami informasi yang disimpan tanpa membaca seluruh program.',
                'Pada contoh ini, nama_ekosistem menyimpan nama habitat, lokasi menyimpan wilayahnya, dan luas_hektar menyimpan luas area latihan. Python membedakan huruf besar dan kecil; nama_ekosistem berbeda dari Nama_Ekosistem.',
            ],
            'code' => <<<'PYTHON'
nama_ekosistem = "Rawa Bangkau"
lokasi = "Hulu Sungai Selatan"
luas_hektar = 120

print(nama_ekosistem)
print(lokasi)
print(luas_hektar)
PYTHON,
            'live_codes' => [
                [
                    'id' => 'bab1-variabel',
                    'title' => 'Coba sendiri: nama ekosistem',
                    'description' => 'Ubah nama_ekosistem menjadi "Rawa Bangkau". Klik Run Code untuk melihat output, lalu Submit untuk memeriksa nilainya.',
                    'entry_file' => 'main.py',
                    'files' => [
                        'main.py' => <<<'PYTHON'
nama_ekosistem = "Sungai Barito"
print(nama_ekosistem)
PYTHON,
                    ],
                    'checker' => 'assert nama_ekosistem == "Rawa Bangkau", "Ubah nilai nama_ekosistem menjadi Rawa Bangkau."',
                ],
            ],
        ],
        [
            'id' => 'tipe-data',
            'title' => 'Tipe Data',
            'paragraphs' => [
                'Tipe data menentukan jenis nilai dan operasi yang dapat dilakukan. str digunakan untuk teks, int untuk bilangan bulat, float untuk bilangan desimal, dan bool untuk nilai True atau False. List menyimpan beberapa nilai dalam satu urutan.',
                'Gunakan type() untuk melihat tipe suatu nilai. Angka tanpa tanda kutip berbeda dari teks yang berisi angka: 120 dapat dijumlahkan dengan bilangan, sedangkan "120" adalah string.',
            ],
            'code' => <<<'PYTHON'
nama_habitat = "Rawa Bangkau"       # str
jumlah_titik = 4                    # int
kedalaman_meter = 1.5               # float
sedang_dipantau = True              # bool
habitat = ["Sungai Barito", "Rawa Bangkau"]  # list

print(type(kedalaman_meter))
print(habitat[0])
PYTHON,
            'tip' => 'Indeks list dimulai dari 0. habitat[0] mengambil nilai pertama, yaitu Sungai Barito.',
        ],
        [
            'id' => 'input-output',
            'title' => 'Input dan Output',
            'paragraphs' => [
                'Output adalah informasi yang ditampilkan program. print() dapat menampilkan beberapa nilai sekaligus. F-string, yaitu string dengan awalan f, menyisipkan nilai variabel pada bagian yang ditulis di dalam kurung kurawal.',
                'Dalam Python yang dijalankan di komputer, input() menerima masukan pengguna sebagai string. Untuk mengolah masukan sebagai bilangan, gunakan konversi seperti int() atau float(). Contoh input berikut dapat dicoba di terminal Python lokal.',
            ],
            'code' => <<<'PYTHON'
nama_ekosistem = "Rawa Bangkau"
print(f"Ekosistem yang dipantau: {nama_ekosistem}")

# Contoh untuk terminal Python lokal:
# nama_ekosistem = input("Nama ekosistem: ")
# luas_hektar = float(input("Luas dalam hektar: "))
PYTHON,
            'tip' => 'Live Coding OOPy belum mendukung input() interaktif. Saat mencoba di halaman ini, isi nilai langsung melalui variabel.',
        ],
        [
            'id' => 'operator',
            'title' => 'Operator',
            'paragraphs' => [
                'Operator aritmetika seperti +, -, *, dan / mengolah bilangan. Operator perbandingan seperti ==, !=, >, dan <= menghasilkan nilai boolean. Gunakan and, or, dan not untuk menyusun kondisi logika.',
                'Tanda = memberikan nilai pada variabel, sedangkan == membandingkan dua nilai. Pada contoh berikut, sisa_titik menghitung pekerjaan yang tersisa dan perlu_dilanjutkan menyimpan hasil sebuah kondisi.',
            ],
            'code' => <<<'PYTHON'
total_titik = 8
titik_diperiksa = 3
sisa_titik = total_titik - titik_diperiksa
perlu_dilanjutkan = sisa_titik > 0 and titik_diperiksa < total_titik

print("Titik tersisa:", sisa_titik)
print("Lanjutkan pengamatan:", perlu_dilanjutkan)
PYTHON,
        ],
        [
            'id' => 'percabangan',
            'title' => 'Percabangan',
            'paragraphs' => [
                'Percabangan membuat program memilih instruksi berdasarkan kondisi. Gunakan if untuk kondisi pertama, elif untuk kondisi lain, dan else untuk keadaan yang belum terpenuhi. Python memeriksa kondisi dari atas ke bawah.',
                'Perhatikan titik dua dan indentasi. Baris yang menjorok ke dalam merupakan bagian dari blok kondisi tersebut. Gunakan empat spasi secara konsisten.',
            ],
            'code' => <<<'PYTHON'
kondisi_air = "keruh"

if kondisi_air == "keruh":
    print("Catat kekeruhan untuk pengamatan lanjutan.")
elif kondisi_air == "jernih":
    print("Catat hasil pengamatan air jernih.")
else:
    print("Lengkapi catatan kondisi air.")
PYTHON,
            'tip' => 'Contoh ini hanya mengelompokkan catatan pengamatan. Tampilan air saja tidak menentukan kualitas atau keamanan air.',
        ],
        [
            'id' => 'perulangan',
            'title' => 'Perulangan',
            'paragraphs' => [
                'Perulangan menjalankan instruksi yang sama untuk beberapa nilai. for cocok untuk menelusuri list atau urutan angka dari range(). Batas akhir range() tidak ikut diproses.',
                'while mengulang selama suatu kondisi bernilai True. Pastikan ada perubahan yang membuat kondisi akhirnya False agar program tidak berjalan tanpa henti.',
            ],
            'code' => <<<'PYTHON'
habitat = ["Sungai Barito", "Rawa Bangkau"]
for nama in habitat:
    print("Mengamati:", nama)

titik = 1
while titik <= 3:
    print("Titik pengamatan", titik)
    titik = titik + 1
PYTHON,
        ],
        [
            'id' => 'fungsi',
            'title' => 'Fungsi',
            'paragraphs' => [
                'Fungsi mengelompokkan instruksi yang dapat digunakan kembali. Definisikan fungsi dengan def, beri nama, lalu tulis parameter yang dibutuhkan. Fungsi baru menjalankan isinya ketika dipanggil.',
                'return mengembalikan nilai ke pemanggil. Pada contoh ini, fungsi menerima nama dan lokasi, lalu menghasilkan teks informasi habitat. print() menampilkan teks yang dikembalikan fungsi tersebut.',
            ],
            'code' => <<<'PYTHON'
def informasi_habitat(nama, lokasi):
    return f"{nama} berada di {lokasi}"

informasi = informasi_habitat("Rawa Bangkau", "Hulu Sungai Selatan")
print(informasi)
PYTHON,
        ],
        [
            'id' => 'oop',
            'title' => 'Pengantar Object-Oriented Programming',
            'paragraphs' => [
                'Object-Oriented Programming (OOP) mengorganisasi program melalui objek yang memiliki data dan perilaku. Dalam konteks lahan basah, sebuah objek ekosistem dapat menyimpan nama dan lokasi serta memiliki perilaku untuk menampilkan informasi.',
                'Class mendefinisikan struktur dan perilaku objek. Atribut menyimpan data, sedangkan metode merupakan fungsi yang terkait dengan objek. Contoh berikut memperlihatkan bentuk awal class dan objek; pembuatan konstruktor dan atribut tiap objek akan dipelajari lebih lanjut pada BAB 2.',
            ],
            'code' => <<<'PYTHON'
class Ekosistem:
    def tampilkan_info(self):
        print(f"{self.nama} berada di {self.lokasi}")

rawa = Ekosistem()
rawa.nama = "Rawa Bangkau"
rawa.lokasi = "Hulu Sungai Selatan"
rawa.tampilkan_info()
PYTHON,
            'tip' => 'Pada metode ini, self merujuk pada objek yang sedang digunakan. Pemanggilan rawa.tampilkan_info() membuat self merujuk pada rawa.',
        ],
    ],
    'summary' => [
        'Variabel memberi nama pada nilai; tipe data menentukan cara nilai tersebut dapat diolah.',
        'Input menerima data, sedangkan output menyampaikan hasil program.',
        'Operator, percabangan, dan perulangan membantu mengolah data serta mengatur alur instruksi.',
        'Fungsi membuat instruksi dapat digunakan kembali dengan parameter yang berbeda.',
        'OOP menggabungkan data dan perilaku dalam objek yang dibuat dari class.',
    ],
    'exercise' => [
        'description' => 'Buat program pencatatan sederhana untuk satu ekosistem lahan basah. Gunakan konsep yang telah dipelajari tanpa perlu membuat class terlebih dahulu.',
        'steps' => [
            'Simpan nama ekosistem, lokasi, luas area, dan kondisi air dalam variabel dengan tipe data yang sesuai.',
            'Gunakan if, elif, dan else untuk menampilkan catatan sesuai kondisi air: jernih, keruh, atau belum dicatat.',
            'Buat fungsi yang menerima data ekosistem dan mengembalikan teks informasi.',
            'Panggil fungsi tersebut, tampilkan hasilnya dengan print(), lalu coba data ekosistem lain.',
        ],
    ],
];
