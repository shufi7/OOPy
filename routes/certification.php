<?php

use App\Http\Controllers\CertificationController;
use App\Http\Middleware\CertificateNoStore;
use Illuminate\Support\Facades\Route;

Route::get('/sertifikat/verifikasi/{publicId}', [CertificationController::class, 'verify'])
    ->middleware('throttle:60,1')->name('certificates.verify');

Route::middleware(CertificateNoStore::class)->group(function () {
    Route::get('/sertifikasi', [CertificationController::class, 'index'])->name('certification.index');
    Route::middleware('auth')->group(function () {
        Route::post('/sertifikasi', [CertificationController::class, 'confirm'])
            ->middleware('throttle:10,1')->name('certification.confirm');
        Route::get('/sertifikasi/{publicId}/pdf', [CertificationController::class, 'download'])->name('certificates.download');
        Route::get('/sertifikasi/checkout', [CertificationController::class, 'checkout'])->name('certification.checkout');
        Route::post('/sertifikasi/checkout', [CertificationController::class, 'confirm'])
            ->middleware('throttle:10,1')->name('certification.confirm.legacy');
        Route::get('/sertifikat-saya', [CertificationController::class, 'certificates'])->name('certificates.index');
        Route::get('/sertifikat-saya/{publicId}/pdf', [CertificationController::class, 'download'])->name('certificates.download.legacy');
        Route::get('/sertifikat-saya/{publicId}', [CertificationController::class, 'show'])->name('certificates.show');
    });
});
