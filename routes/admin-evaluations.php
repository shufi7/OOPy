<?php

use App\Http\Controllers\Admin\FinalExamController;
use Illuminate\Support\Facades\Route;

Route::get('/evaluations/{evaluation}', [FinalExamController::class, 'show'])->name('evaluations.show');
Route::put('/evaluations/{evaluation}', [FinalExamController::class, 'update'])->name('evaluations.update');
