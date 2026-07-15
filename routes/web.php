<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentUploadController;


// Root: arahkan ke dashboard bila sudah login, selain itu ke halaman login
Route::get('/', function () {
    return redirect()->route(Auth::check() ? 'dashboard' : 'login');
});

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::delete('/documents/bulk-destroy', [DocumentController::class, 'bulkDestroy'])
        ->name('documents.bulk-destroy');
    Route::resource('documents', DocumentController::class)
        ->only(['index','show', 'destroy']);

    Route::middleware(['auth'])->group(function () {
    Route::get('/upload-dokumen', [DocumentUploadController::class, 'create'])
        ->name('documents.upload');

    Route::post('/upload-dokumen', [DocumentUploadController::class, 'store'])
        ->name('documents.upload.store');

    Route::get('/dokumen/{document}/file', [DocumentUploadController::class, 'file'])
        ->name('documents.file');

    Route::post('/dokumen/{document}/process', [DocumentUploadController::class, 'processOne'])
        ->name('documents.process');

    Route::get('/upload/batch/{batchId}/status', [DocumentUploadController::class, 'batchStatus'])
        ->name('documents.batch.status');

    Route::get('/dokumen/{document}/review', [DocumentUploadController::class, 'review'])
        ->name('documents.review');

    Route::patch('/dokumen/{document}/review', [DocumentUploadController::class, 'updateReview'])
    ->name('documents.review.update');

    Route::post('/dokumen/{document}/confirm', [DocumentUploadController::class, 'confirm'])
    ->name('documents.confirm');

    Route::post('/dokumen/confirm-bulk', [DocumentUploadController::class, 'confirmBulk'])
        ->name('documents.confirm-bulk');

    Route::get('/upload/batch/{batchId}', [DocumentUploadController::class, 'batchResult'])
        ->name('documents.batch');

    Route::post('/upload/batch/{batchId}/confirm-all', [DocumentUploadController::class, 'confirmAll'])
        ->name('documents.batch.confirm-all');

    Route::get('/outputs/download', [DocumentUploadController::class, 'downloadIndex'])
        ->name('outputs.download');

    Route::post('/outputs/download', [DocumentUploadController::class, 'downloadZip'])
        ->name('outputs.download.zip');

});
});

Route::get('/auth/google', [App\Http\Controllers\Auth\GoogleController::class, 'redirect'])
    ->name('auth.google');
Route::get('/debug-oauth', function () {
    return [
        'app_url' => config('app.url'),
        'google_redirect' => config('services.google.redirect'),
        'google_client_id' => config('services.google.client_id'),
        'google_secret' => config('services.google.client_secret') ? 'SET' : 'NOT SET',
    ];
});

require __DIR__.'/auth.php';
