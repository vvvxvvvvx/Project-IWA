<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Station;
use App\Models\Relation;

class StationController extends Controller
{
    /**
     * Haal alle stations op die horen bij het contract van de ingelogde gebruiker.
     */
    public function myStations(Request $request)
    {
        $user = $request->user();

        // 1. Zoek de bedrijfsrelatie ("company") van deze gebruiker in de Relation tabel op basis van e-mail.
        $relation = Relation::where('email', $user->email)->first();

        if (!$relation || !$relation->company) {
            return response()->json([
                'message' => 'Geen gekoppeld contract/bedrijf gevonden voor deze gebruiker.'
            ], 403);
        }

        // 2. Haal alle stations op die gekoppeld zijn aan het contract van dit bedrijf.
        $stations = Station::with(['geolocation', 'nearestlocation'])
            ->whereHas('subscriptions', function ($query) use ($relation) {
                $query->where('company', $relation->company);
            })
            ->get();

        return response()->json([
            'data' => $stations
        ]);
    }

    /**
     * Display the specified station.
     */
    public function show($id)
    {
        // Zoek het station op basis van ID of naam. We proberen eerst op ID, als het een naam is zoeken we op naam.
        $station = Station::with(['geolocation', 'nearestlocation'])
            ->where('name', $id)
            ->orWhere('id', $id)
            ->first();

        if (!$station) {
            return response()->json([
                'message' => 'Station niet gevonden.'
            ], 404);
        }

        return response()->json([
            'data' => $station
        ]);
    }
}
