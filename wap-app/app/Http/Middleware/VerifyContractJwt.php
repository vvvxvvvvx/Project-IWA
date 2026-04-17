<?php

namespace App\Http\Middleware;

use App\Support\ContractJwt;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class VerifyContractJwt
{
    public function handle(Request $request, Closure $next, string $requiredRole = 'user'): Response
    {
        $token = $request->bearerToken();
        if (! $token) {
            return response()->json(['error' => 'Geen JWT token opgegeven.'], 401);
        }

        $secret = config('app.key');
        $payload = ContractJwt::decode($token, $secret);
        if (! $payload) {
            return response()->json(['error' => 'Ongeldig of verlopen JWT token.'], 401);
        }

        $session = DB::table('contract_user_sessions')
            ->where('jti', $payload['jti'] ?? '')
            ->where('is_revoked', 0)
            ->first();

        if (! $session) {
            return response()->json(['error' => 'Sessietoken is ingetrokken of onbekend.'], 401);
        }

        $user = DB::table('contract_authorized_users')
            ->where('id', $session->contract_user_id)
            ->whereNull('deleted_at')
            ->first();

        if (! $user) {
            return response()->json(['error' => 'Contractgebruiker niet gevonden.'], 401);
        }

        $identifier = $request->route('identifier');
        if ($identifier) {
            $contract = DB::table('contracts')->where('identifier', $identifier)->first();
            if (! $contract || (int) $contract->id !== (int) ($payload['contract_id'] ?? 0)) {
                return response()->json(['error' => 'Token hoort niet bij dit contract.'], 403);
            }
        }

        if ($requiredRole === 'admin' && ($payload['permission_level'] ?? 'user') !== 'admin') {
            return response()->json(['error' => 'Alleen admins mogen dit endpoint gebruiken.'], 403);
        }

        $request->attributes->set('contract_auth', [
            'jwt' => $payload,
            'user' => $user,
        ]);

        return $next($request);
    }
}
