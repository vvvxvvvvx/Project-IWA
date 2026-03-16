<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Subscription;

class VerifySubscriptionToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Haal het identifier argument uit de URL, bv: /api/IWA/abonnement/{identifier}/stations
        $identifier = $request->route('identifier');
        
        // Haal de bearer token uit de Authorization header
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Geen API token opgegeven.'], 401);
        }

        // Zoek het abonnement
        $subscription = Subscription::where('identifier', $identifier)->first();

        // Controleer of de identifier bestaat
        if (!$subscription) {
            return response()->json(['error' => 'Abonnement niet gevonden.'], 404);
        }

        // Controleer of de token overeenkomt
        if ($subscription->token !== $token) {
            return response()->json(['error' => 'Ongeldig API token voor dit abonnement.'], 403);
        }

        return $next($request);
    }
}
