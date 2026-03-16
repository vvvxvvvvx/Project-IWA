<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\measurementController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\WeatherDataController;

Route::view('/', 'pages::auth.login')->name('home');

// Generator data ingestion (no auth required)
Route::post('/postWeatherData', [WeatherDataController::class, 'store']);

// Authenticatie
Route::get('/login',  [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
Route::post('/logout',[AuthenticatedSessionController::class, 'destroy'])->name('logout');

// Beveiligde routes
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/dashboard-overview', [DashboardController::class, 'apiOverview'])->name('api.dashboard-overview');

    // Stations
    Route::get('/stations',                      [StationController::class, 'index'])->name('stations.index');
    Route::get('/stations/{stn}',                [StationController::class, 'show'])->name('stations.show');
    Route::get('/stations/{stn}/download',       [StationController::class, 'download'])->name('stations.download');

    // Abonnementen
    Route::get('/subscriptions',             [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('/subscriptions/{identifier}',[SubscriptionController::class, 'show'])->name('subscriptions.show');

    // Abonnementaanbod (types)
    Route::get('/subscription-types', [SubscriptionController::class, 'typesIndex'])->name('subscription-types.index');

    // Contracten
    Route::get('/contracts',              [ContractController::class, 'index'])->name('contracts.index');
    Route::get('/contracts/{identifier}', [ContractController::class, 'show'])->name('contracts.show');

    // Bedrijven
    Route::get('/companies',      [CompanyController::class, 'index'])->name('companies.index');
    Route::get('/companies/{id}', [CompanyController::class, 'show'])->name('companies.show')->whereNumber('id');

});

Route::post('/measurements', [measurementController::class, 'store']);

require __DIR__.'/settings.php';
