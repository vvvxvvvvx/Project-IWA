<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SubscriptionStationController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth.subscription'])->group(function () {
    // Haal een lijst op van alle stations die bij een abonnement horen
    Route::get('/IWA/abonnement/{identifier}/stations', [SubscriptionStationController::class, 'index']);

    // Haal details van een specifiek station op binnen een abonnement
    Route::get('/IWA/abonnement/{identifier}/station/{naam}', [SubscriptionStationController::class, 'show']);
});
