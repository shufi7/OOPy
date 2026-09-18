<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MateriController extends Controller
{
    //
    public function index()
    {
        $materiList = [
            [
                'bab' => 'BAB 1',
                'judul' => 'Dasar Pemrograman & OOP',
                'poin' => [
                    'Pengenalan Python',
                    'Variabel',
                    'Tipe data',
                    'Struktur kontrol',
                    'Konsep dasar paradigma OOP.',
                ],
            ],
            [
                'bab' => 'BAB 2',
                'judul' => 'Kelas dan Objek',
                'poin' => [
                    'Definisi Kelas',
                    'Instansiasi objek',
                    'Atribut',
                    'Metode',
                    'Konstruktor dalam python',
                ],
            ],
            [
                'bab' => 'BAB 3',
                'judul' => 'Enkapsulasi',
                'poin' => [
                    'Konsep pembungkusan data',
                    'Akses modifier (public, protected private)',
                    'Getter/Setter',
                ],
            ],
            [
                'bab' => 'BAB 4',
                'judul' => 'Pewarisan',
                'poin' => [
                    'Hierarki Kelas',
                    'Override Metode',
                ],
            ],
            [
                'bab' => 'BAB 5',
                'judul' => 'Polimorfisme',
                'poin' => [
                    'penggunaan polimorfisme untuk fleksibilitas kode',
                ],
            ],
            [
                'bab' => 'BAB 6',
                'judul' => 'Kelas Abstrak',
                'poin' => [
                    'Implementasi abstract base class (ABC)',
                    'metode abstrak',
                    'antarmuka dalam OOP Python',
                ],
            ],
        ];

        return view('materi.index', compact('materiList'));
    }
}
