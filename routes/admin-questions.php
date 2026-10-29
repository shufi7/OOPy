<?php

use App\Http\Controllers\Admin\QuestionController;
use Illuminate\Support\Facades\Route;

Route::get('questions', [QuestionController::class, 'index'])->name('questions.index');
Route::get('questions/create', [QuestionController::class, 'create'])->name('questions.create');
Route::post('questions', [QuestionController::class, 'store'])->name('questions.store');
Route::post('questions/quizzes/{quiz}/publish', [QuestionController::class, 'publish'])->name('questions.publish');
Route::get('questions/{question}', [QuestionController::class, 'show'])->name('questions.show');
Route::get('questions/{question}/edit', [QuestionController::class, 'edit'])->name('questions.edit');
Route::put('questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
Route::patch('questions/{question}/archive', [QuestionController::class, 'archive'])->name('questions.archive');
