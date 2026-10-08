<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\EditorController;
use App\Http\Controllers\EvaluasiAkhirController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MateriController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/materi', [MateriController::class, 'index'])->name('materi.index');
Route::get('/materi/evaluasi-akhir', [EvaluasiAkhirController::class, 'index'])->name('evaluasi.index');
Route::get('/materi/evaluasi-akhir/ujian', [EvaluasiAkhirController::class, 'exam'])->name('evaluasi.exam');
Route::get('/materi/evaluasi-akhir/hasil', [EvaluasiAkhirController::class, 'results'])->name('evaluasi.results');
Route::get('/materi/{slug}', [MateriController::class, 'show'])->name('materi.show');
Route::get('/editor', [EditorController::class, 'index'])->name('editor.index');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');
