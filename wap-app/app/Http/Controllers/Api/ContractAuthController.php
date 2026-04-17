<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ContractJwt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ContractAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:45'],
            'user_identifier' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $contract = DB::table('contracts')->where('identifier', $data['identifier'])->first();
        if (! $contract) {
            return response()->json(['error' => 'Contract niet gevonden.'], 404);
        }

        $user = DB::table('contract_authorized_users')
            ->where('contract_id', $contract->id)
            ->where('user_identifier', $data['user_identifier'])
            ->whereNull('deleted_at')
            ->first();

        if (! $user || empty($user->password_hash) || ! Hash::check($data['password'], $user->password_hash)) {
            return response()->json(['error' => 'Ongeldige inloggegevens.'], 401);
        }

        if (($user->status ?? 'Actief') !== 'Actief' || ! ((int) ($user->is_active ?? 1))) {
            return response()->json(['error' => 'Deze gebruiker is niet actief.'], 403);
        }

        $jti = bin2hex(random_bytes(16));
        $token = ContractJwt::issue([
            'sub' => (string) $user->id,
            'contract_id' => (int) $contract->id,
            'identifier' => $contract->identifier,
            'user_identifier' => $user->user_identifier,
            'permission_level' => $user->permission_level ?: 'user',
            'jti' => $jti,
        ], config('app.key'), 8 * 3600);

        DB::table('contract_user_sessions')->insert([
            'contract_user_id' => $user->id,
            'contract_id' => $contract->id,
            'jti' => $jti,
            'expires_at' => now()->addHours(8),
            'is_revoked' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 28800,
            'user' => [
                'name' => $user->name,
                'user_identifier' => $user->user_identifier,
                'permission_level' => $user->permission_level,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $auth = $request->attributes->get('contract_auth');
        $jti = $auth['jwt']['jti'] ?? null;
        if ($jti) {
            DB::table('contract_user_sessions')->where('jti', $jti)->update([
                'is_revoked' => 1,
                'updated_at' => now(),
            ]);
        }

        return response()->json(['message' => 'JWT token ongeldig gemaakt.']);
    }
}
