<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $provided = $request->header('X-API-Key');
        $expected  = config('app.weather_api_key');

        if (! $provided || ! $expected || ! hash_equals((string) $expected, (string) $provided)) {
            return response()->json(['error' => 'Ongeldig of ontbrekend API-sleutel.'], 401);
        }

        return $next($request);
    }
}
