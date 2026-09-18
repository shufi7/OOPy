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
                'nomor' => 1,
                'judul' => 'Dasar Pemrograman Python dan OOP',
                'deskripsi' => 'Mengenal dasar Python dan konsep awal Object-Oriented Programming.',
                'icon' => 'bi-code-slash',
                'badge' => 'Dasar',
            ],
            [
                'nomor' => 2,
                'judul' => 'Kelas dan Objek',
                'deskripsi' => 'Memahami cara membuat class, object, atribut, dan method dalam Python.',
                'icon' => 'bi-box-seam',
                'badge' => 'Konsep Inti',
            ],
            [
                'nomor' => 3,
                'judul' => 'Enkapsulasi',
                'deskripsi' => 'Memahami cara melindungi dan mengatur akses data di dalam sebuah class.',
                'icon' => 'bi-shield-lock',
                'badge' => 'Pilar OOP',
            ],
            [
                'nomor' => 4,
                'judul' => 'Pewarisan (Inheritance)',
                'deskripsi' => 'Memahami bagaimana sebuah class dapat mewarisi atribut dan method dari class lain.',
                'icon' => 'bi-diagram-3',
                'badge' => 'Pilar OOP',
            ],
            [
                'nomor' => 5,
                'judul' => 'Polimorfisme',
                'deskripsi' => 'Memahami penggunaan method yang sama dengan perilaku berbeda pada beberapa object.',
                'icon' => 'bi-intersect',
                'badge' => 'Pilar OOP',
            ],
            [
                'nomor' => 6,
                'judul' => 'Kelas Abstrak (Abstract Class)',
                'deskripsi' => 'Memahami abstract class sebagai dasar untuk membentuk struktur class yang lebih terorganisir.',
                'icon' => 'bi-bounding-box-circles',
                'badge' => 'Tingkat Lanjut',
            ],
        ];

        return view('materi.index', compact('materiList'));
    }
}
