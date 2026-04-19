<?php

use App\Http\Controllers\Api\ContractAuthController;
use App\Http\Controllers\Api\ContractDataController;
use App\Http\Controllers\Api\ContractUserController;
use App\Http\Controllers\Api\SubscriptionStationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StationController;
use App\Http\Controllers\MeasurementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
    Route::get('/IWA/abonnement/{identifier}/stations', [SubscriptionStationController::class, 'index']);
    Route::get('/IWA/abonnement/{identifier}/station/{naam}', [SubscriptionStationController::class, 'show']);
    Route::get('/IWA/abonnement/{identifier}/measurements', [SubscriptionStationController::class, 'measurements']);
});

Route::post('/IWA/contracten/login', [ContractAuthController::class, 'login']);

Route::middleware(['auth.contract'])->group(function () {
    Route::post('/IWA/contract/logout', [ContractAuthController::class, 'logout']);
    Route::get('/IWA/contracten/{identifier}/stations', [ContractDataController::class, 'defaultStations']);
    Route::get('/IWA/contracten/{identifier}/{queryID}', [ContractDataController::class, 'queryData'])->whereNumber('queryID');
    Route::get('/IWA/contracten/{identifier}/{queryID}/stations', [ContractDataController::class, 'stations'])->whereNumber('queryID');
    Route::get('/IWA/contracten/{identifier}/station/{name}', [ContractDataController::class, 'station']);
    Route::get('/IWA/contracten/{identifier}/users', [ContractUserController::class, 'index']);
    Route::get('/IWA/contracten/{identifier}/user/{user_identifier}', [ContractUserController::class, 'show']);
});

Route::middleware(['auth.contract:admin'])->group(function () {
    Route::post('/IWA/contracten/{identifier}/user', [ContractUserController::class, 'store']);
    Route::put('/IWA/contracten/{identifier}/user/{user_identifier}', [ContractUserController::class, 'update']);
    Route::delete('/IWA/contracten/{identifier}/user/{user_identifier}', [ContractUserController::class, 'destroy']);
});
