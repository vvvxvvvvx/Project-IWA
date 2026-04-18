<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class VerifyContractToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $identifier = $request->route('identifier');
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['error' => 'Geen contracttoken opgegeven.'], 401);
        }

        $contract = DB::table('contracts')->where('identifier', $identifier)->first();
        if (! $contract) {
            return response()->json(['error' => 'Contract niet gevonden.'], 404);
        }

        if (($contract->api_token ?? null) !== $token) {
            return response()->json(['error' => 'Ongeldig API token voor dit contract.'], 403);
        }

        return $next($request);
    }
}
