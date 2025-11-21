<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AppRequestController; // Tambahkan ini
use Illuminate\Foundation\Application;
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
    // ... (Tambahkan rute custom lain di sini jika ada)

    // Resource Route untuk AppRequest (index, create, store, show, edit, update, destroy)
    Route::resource('app-requests', AppRequestController::class);
});

require __DIR__.'/auth.php';
