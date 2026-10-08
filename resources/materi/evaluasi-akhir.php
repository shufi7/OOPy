<?php

return [
    'description' => 'Tinjau pemahaman Python OOP setelah BAB 1–6 melalui soal konsep, isian kode, dan uraian dalam konteks lahan basah.',
    'questions' => [
        ['id' => 'q1', 'type' => 'multiple_choice', 'question' => 'Atribut object terutama digunakan untuk menyimpan ...', 'options' => ['Nama file', 'CSS', 'State/data object', 'Module import'], 'correct' => 2],
        ['id' => 'q2', 'type' => 'multiple_choice', 'question' => 'self pada instance method merujuk pada ...', 'options' => ['Semua class Python', 'Module abc', 'Nama file', 'Instance yang sedang menggunakan method'], 'correct' => 3],
        ['id' => 'q3', 'type' => 'multiple_choice', 'question' => 'Awalan satu underscore pada atribut Python biasanya menandakan ...', 'options' => ['Penggunaan internal menurut konvensi', 'Private absolut', 'Variabel global', 'Abstract method'], 'correct' => 0],
        ['id' => 'q4', 'type' => 'multiple_choice', 'question' => 'class Sungai(Ekosistem): menunjukkan ...', 'options' => ['Recursion', 'Casting', 'Inheritance', 'Serialization'], 'correct' => 2],
        ['id' => 'q5', 'type' => 'multiple_choice', 'question' => 'Overriding terjadi ketika ...', 'options' => ['Subclass mendefinisikan method bernama sama dengan perilaku baru', 'Class tidak memiliki method', 'Variabel bertipe float', 'Fungsi memakai return'], 'correct' => 0],
        ['id' => 'q6', 'type' => 'multiple_choice', 'question' => 'Relasi "Stasiun memiliki Sensor" paling dekat dengan ...', 'options' => ['Inheritance wajib', 'Duck typing', 'Composition', 'Looping'], 'correct' => 2],
        ['id' => 'q7', 'type' => 'multiple_choice', 'question' => 'Duck typing menekankan ...', 'options' => ['Nama file', 'Warna editor', 'Jumlah komentar', 'Perilaku yang disediakan object'], 'correct' => 3],
        ['id' => 'q8', 'type' => 'multiple_choice', 'question' => 'ABC berasal dari modul ...', 'options' => ['abc', 'os', 'math', 'sys'], 'correct' => 0],
        ['id' => 'q9', 'type' => 'multiple_choice', 'question' => 'Relasi "SensorPH adalah jenis SensorLingkungan" cocok dimodelkan dengan ...', 'options' => ['String', 'Inheritance', 'Dictionary saja', 'Operator aritmatika'], 'correct' => 1],
        ['id' => 'q10', 'type' => 'multiple_choice', 'question' => 'Desain OOP yang baik seharusnya ...', 'options' => ['Menggunakan inheritance untuk semua relasi', 'Menaruh semua kode dalam satu method', 'Menghindari composition', 'Membagi tanggung jawab class secara masuk akal'], 'correct' => 3],
        [
            'id' => 'q11', 'type' => 'code_fill',
            'question' => 'Lengkapi initializer agar nilai disimpan sebagai atribut instance.',
            'code' => "class Sensor:\n    def __init__(self, nilai):\n        ________________________________",
            'answer' => 'self.nilai = nilai',
        ],
        [
            'id' => 'q12', 'type' => 'code_fill',
            'question' => 'Lengkapi property agar nilai internal pH dapat dibaca.',
            'code' => "@property\ndef ph(self):\n    ________________________________",
            'answer' => 'return self._ph',
        ],
        [
            'id' => 'q13', 'type' => 'code_fill',
            'question' => 'Lengkapi initializer subclass agar inisialisasi superclass dipanggil.',
            'code' => "class Sungai(Ekosistem):\n    def __init__(self, nama, lokasi):\n        ________________________________",
            'answer' => 'super().__init__(nama, lokasi)',
        ],
        [
            'id' => 'q14', 'type' => 'code_fill',
            'question' => 'SensorPH dan SensorSuhu sama-sama memiliki baca_data(). Lengkapi loop berikut.',
            'code' => "sensor = [SensorPH(), SensorSuhu()]\nfor item in sensor:\n    print(item.__________())",
            'answer' => 'baca_data',
        ],
        [
            'id' => 'q15', 'type' => 'code_fill',
            'question' => 'Lengkapi kode agar baca_data() menjadi abstract method.',
            'code' => "class SensorLingkungan(ABC):\n    ____________________\n    def baca_data(self):\n        pass",
            'answer' => '@abstractmethod',
        ],
        ['id' => 'q16', 'type' => 'essay', 'question' => 'Jelaskan perbedaan class dan object menggunakan satu contoh dari konteks lahan basah.'],
        ['id' => 'q17', 'type' => 'essay', 'question' => 'Mengapa enkapsulasi bukan sekadar menyembunyikan atribut?'],
        ['id' => 'q18', 'type' => 'essay', 'question' => 'Jelaskan perbedaan inheritance dan composition.'],
        ['id' => 'q19', 'type' => 'essay', 'question' => 'Bagaimana polimorfisme mengurangi percabangan if berdasarkan tipe object?'],
        ['id' => 'q20', 'type' => 'essay', 'question' => 'Kapan abstract base class lebih berguna daripada hanya mengandalkan duck typing?'],
    ],
];
