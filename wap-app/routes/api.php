<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SubscriptionStationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StationController;
use App\Http\Controllers\MeasurementController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Haal de stations op die horen bij het contract/bedrijf van de ingelogde gebruiker
    Route::get('/stations/my-stations', [StationController::class, 'myStations']);

    // Haal details op van een specifiek station op id of naam
    Route::get('/stations/{station}', [StationController::class, 'show']);
});

Route::post('/postWeatherData', [MeasurementController::class, 'store']);
Route::post('/weather-stations/data', [MeasurementController::class, 'store']);
Route::post('/weather-stations/measurements', [MeasurementController::class, 'store']);

Route::middleware(['auth.subscription'])->group(function () {
    // Haal een lijst op van alle stations die bij een abonnement horen
    Route::get('/IWA/abonnement/{identifier}/stations', [SubscriptionStationController::class, 'index']);

    // Haal details van een specifiek station op binnen een abonnement
    Route::get('/IWA/abonnement/{identifier}/station/{naam}', [SubscriptionStationController::class, 'show']);

    // Haal de nieuwste json data op (metingen) voor een abonnement
    Route::get('/IWA/abonnement/{identifier}/measurements', [SubscriptionStationController::class, 'measurements']);
});
