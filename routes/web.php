<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AppRequestController; // Tambahkan ini
use Illuminate\Foundation\Application;
use App\Http\Controllers\DevelopmentActivityController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Rute untuk Profil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rute untuk Permohonan (AppRequest)
    Route::get('/app-requests/{appRequest}/download', [AppRequestController::class, 'download'])->name('app-requests.download'); // Perbaikan: Menambahkan nama rute
    Route::post('/app-requests/{appRequest}/verify', [AppRequestController::class, 'verify'])->name('app-requests.verify');
    Route::patch('/app-requests/{appRequest}/update-status', [AppRequestController::class, 'updateStatus'])->name('app-requests.update-status');
    // Rute untuk menambahkan dokumen dan gambar pendukung ke sebuah riwayat (history)
    Route::post('/app-requests/{appRequest}/history/{history}/add-document', [AppRequestController::class, 'storeDocSupport'])->name('app-request.history.add-document');
    Route::post('/app-requests/{appRequest}/history/{history}/add-image', [AppRequestController::class, 'storeImageSupport'])->name('app-request.history.add-image');

    // Rute untuk verifikasi dan melihat/mengunduh dokumen & gambar pendukung
    Route::get('/app-requests/doc-support/{docSupport}/download', [AppRequestController::class, 'downloadDocSupport'])->name('app-request.doc-support.download');
    Route::get('/app-requests/image-support/{imageSupport}/view', [AppRequestController::class, 'viewImageSupport'])->name('app-request.image-support.view');
    Route::post('/app-requests/doc-support/{docSupport}/verify', [AppRequestController::class, 'verifyDocSupport'])->name('app-request.doc-support.verify');
    Route::post('/app-requests/image-support/{imageSupport}/verify', [AppRequestController::class, 'verifyImageSupport'])->name('app-request.image-support.verify');

    // Rute untuk Development Activities (Hanya Admin)
    Route::middleware('role:admin')->group(function () {
        Route::post('/app-requests/{appRequest}/development-activities', [DevelopmentActivityController::class, 'store'])->name('app-request.development-activity.store');
        Route::patch('/development-activities/{developmentActivity}', [DevelopmentActivityController::class, 'update'])->name('development-activity.update');
        Route::delete('/development-activities/{developmentActivity}', [DevelopmentActivityController::class, 'destroy'])->name('development-activity.destroy');
        Route::post('/development-activities/reorder', [DevelopmentActivityController::class, 'reorder'])->name('development-activity.reorder');
        // Tambahkan rute lain yang memerlukan hak akses admin di sini
    });

    // ... (Tambahkan rute custom lain di sini jika ada)

    // Resource Route untuk AppRequest (index, create, store, show, edit, update, destroy)
    Route::resource('app-requests', AppRequestController::class);
});

require __DIR__.'/auth.php';
