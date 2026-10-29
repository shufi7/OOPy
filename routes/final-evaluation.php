<?php

use App\Http\Controllers\FinalExamAttemptController;
use Illuminate\Support\Facades\Route;

Route::prefix('evaluasi/attempts')->name('evaluasi.attempts.')->group(function () {
    Route::get('/', [FinalExamAttemptController::class, 'state'])->name('state');
    Route::post('/', [FinalExamAttemptController::class, 'start'])->middleware('throttle:20,1')->block(10, 10)->name('start');
    Route::put('/{id}/answers', [FinalExamAttemptController::class, 'save'])->whereUuid('id')->middleware('throttle:120,1')->block(10, 10)->name('save');
    Route::post('/{id}/submit', [FinalExamAttemptController::class, 'submit'])->whereUuid('id')->middleware('throttle:20,1')->block(10, 10)->name('submit');
});
