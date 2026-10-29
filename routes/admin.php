<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ScoreController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\PreventAdminCaching;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin', PreventAdminCaching::class])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/scores/export', [ScoreController::class, 'export'])->name('scores.export');
    Route::get('/scores', [ScoreController::class, 'index'])->name('scores.index');

    require __DIR__.'/admin-questions.php';
    require __DIR__.'/admin-materials.php';
    require __DIR__.'/admin-evaluations.php';
});
