<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Station;
use Illuminate\Http\Request;

class SubscriptionStationController extends Controller
{
    public function index($identifier)
    {
        // Zoek het specifieke abonnement op basis van de identifier en laad de stations
        $subscription = Subscription::with('stations')->where('identifier', $identifier)->first();

        if (!$subscription) {
            return response()->json(['error' => 'Abonnement niet gevonden.'], 404);
        }

        // Retourneer een lijst van alleen de stations die bij het abonnement horen
        return response()->json([
            'data' => $subscription->stations
        ]);
    }

    public function show($identifier, $name)
    {
        // Zoek het specifieke abonnement op basis van de identifier
        $subscription = Subscription::with('stations')->where('identifier', $identifier)->first();

        if (!$subscription) {
            return response()->json(['error' => 'Abonnement niet gevonden.'], 404);
        }

        // Controleer of het opgegeven station bij dit abonnement hoort
        $stationExists = $subscription->stations()->where('name', $name)->exists();

        if (!$stationExists) {
            return response()->json(['error' => 'Station behoort niet tot dit abonnement of is niet gevonden.'], 403);
        }

        // Haal het station op inclusief omliggende/dichtstbijzijnde locatie gegevens
        // We laden dit niet via subscription() omdat de relations soms specifiek geladen moeten worden.
        $stationDetails = Station::with(['geolocation', 'nearestlocation'])
                                ->where('name', $name)
                                ->first();

        return response()->json([
            'data' => $stationDetails
        ]);
    }
}
