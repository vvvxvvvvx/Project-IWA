<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\measurementController;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::post('/measurements', [measurementController::class, 'store']);

require __DIR__.'/settings.php';
