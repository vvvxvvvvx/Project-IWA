<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\measurementController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::view('/', 'pages::auth.login')->name('home');

// Login routes
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::post('/measurements', [measurementController::class, 'store']);

require __DIR__.'/settings.php';
