<?php

use App\Http\Controllers\EditorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MateriController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/materi', [MateriController::class, 'index'])->name('materi.index');
Route::get('/editor', [EditorController::class, 'index'])->name('editor.index');
