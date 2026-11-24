<?php

use App\Http\Controllers\MidtransPaymentController;
use App\Http\Middleware\CertificateNoStore;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::post('/api/payments/midtrans/notification', [MidtransPaymentController::class, 'notification'])
    ->withoutMiddleware([ValidateCsrfToken::class, StartSession::class, ShareErrorsFromSession::class])
    ->middleware([CertificateNoStore::class, 'throttle:60,1'])->name('midtrans.notification');

Route::middleware([CertificateNoStore::class, 'auth'])->group(function () {
    Route::post('/sertifikasi/payments', [MidtransPaymentController::class, 'store'])->middleware('throttle:6,1,midtrans-start')->name('midtrans.store');
    Route::post('/sertifikasi/payments/{publicId}/status', [MidtransPaymentController::class, 'status'])->middleware('throttle:12,1,midtrans-status')->name('midtrans.status');
});
