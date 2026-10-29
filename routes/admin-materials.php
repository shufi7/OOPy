<?php

use App\Http\Controllers\Admin\MaterialController;
use Illuminate\Support\Facades\Route;

Route::get('materials', [MaterialController::class, 'index'])->name('materials.index');
Route::get('materials/{material}/edit', [MaterialController::class, 'edit'])->name('materials.edit');
Route::put('materials/{material}', [MaterialController::class, 'update'])->name('materials.update');
Route::post('materials/{material}/preview', [MaterialController::class, 'preview'])->name('materials.preview');
Route::get('materials/{material}/preview', [MaterialController::class, 'showPreview'])->name('materials.preview-saved');
Route::post('materials/{material}/publish', [MaterialController::class, 'publish'])->name('materials.publish');
