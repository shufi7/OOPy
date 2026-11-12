<?php

$questions = array_slice((require __DIR__.'/../../resources/evaluasi/baseline-2026-11-12.php')['questions'], 0, 15);

return array_merge($questions, [
    ['id' => 'q16', 'type' => 'essay', 'question' => 'Jelaskan perbedaan class dan object menggunakan satu contoh dari konteks lahan basah.'],
    ['id' => 'q17', 'type' => 'essay', 'question' => 'Mengapa enkapsulasi bukan sekadar menyembunyikan atribut?'],
    ['id' => 'q18', 'type' => 'essay', 'question' => 'Jelaskan perbedaan inheritance dan composition.'],
    ['id' => 'q19', 'type' => 'essay', 'question' => 'Bagaimana polimorfisme mengurangi percabangan if berdasarkan tipe object?'],
    ['id' => 'q20', 'type' => 'essay', 'question' => 'Kapan abstract base class lebih berguna daripada hanya mengandalkan duck typing?'],
]);
