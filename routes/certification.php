<?php

use App\Http\Controllers\Auth\GoogleCertificatePasswordController;
use App\Http\Controllers\CertificateIdentityController;
use App\Http\Controllers\CertificationController;
use App\Http\Middleware\CertificateNoStore;
use Illuminate\Support\Facades\Route;

Route::get('/sertifikat/verifikasi/{publicId}', [CertificationController::class, 'verify'])
    ->middleware('throttle:60,1')->name('certificates.verify');

Route::middleware(CertificateNoStore::class)->group(function () {
    Route::get('/sertifikasi', [CertificationController::class, 'index'])->name('certification.index');
    Route::middleware('auth')->group(function () {
        Route::post('/sertifikasi/nama/konfirmasi', [CertificateIdentityController::class, 'confirm'])
            ->middleware('throttle:10,1,certificate-confirm')->name('certification.name.confirm');
        Route::post('/sertifikasi/nama', [CertificateIdentityController::class, 'update'])
            ->middleware('throttle:5,1,certificate-name')->name('certification.name.update');
        Route::post('/sertifikasi/{publicId}/koreksi-nama', [CertificateIdentityController::class, 'correct'])
            ->whereUuid('publicId')->middleware('throttle:5,1,certificate-name')->name('certification.name.correct');
        Route::post('/sertifikasi/{publicId}/transkrip', [CertificationController::class, 'captureTranscript'])
            ->whereUuid('publicId')->middleware('throttle:5,1,certificate-transcript')->name('certification.transcript.capture');
        Route::post('/sertifikasi/password/google', [GoogleCertificatePasswordController::class, 'start'])
            ->middleware('throttle:5,1,certificate-google')->block(30, 10)->name('certification.password.google');
        Route::post('/sertifikasi/password', [GoogleCertificatePasswordController::class, 'store'])
            ->middleware('throttle:5,1,certificate-password')->block(30, 10)->name('certification.password.store');
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
