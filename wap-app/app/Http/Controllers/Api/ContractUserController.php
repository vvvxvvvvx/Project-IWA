<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ContractUserController extends Controller
{
    public function index(string $identifier): JsonResponse
    {
        $contract = $this->findContract($identifier);
        if (! $contract) {
            return response()->json(['error' => 'Contract niet gevonden.'], 404);
        }

        $users = DB::table('contract_authorized_users')
            ->where('contract_id', $contract->id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['name', 'email', 'user_identifier', 'permission_level', 'role_label', 'status', 'notes']);

        return response()->json(['data' => $users]);
    }

    public function show(string $identifier, string $user_identifier): JsonResponse
    {
        $contract = $this->findContract($identifier);
        if (! $contract) {
            return response()->json(['error' => 'Contract niet gevonden.'], 404);
        }

        $user = DB::table('contract_authorized_users')
            ->where('contract_id', $contract->id)
            ->where('user_identifier', $user_identifier)
            ->whereNull('deleted_at')
            ->first(['name', 'email', 'user_identifier', 'permission_level', 'role_label', 'status', 'notes']);

        if (! $user) {
            return response()->json(['error' => 'Gebruiker niet gevonden.'], 404);
        }

        return response()->json(['data' => $user]);
    }

    public function store(Request $request, string $identifier): JsonResponse
    {
        $contract = $this->findContract($identifier);
        if (! $contract) {
            return response()->json(['error' => 'Contract niet gevonden.'], 404);
        }

        $data = $this->validatePayload($request, true, $contract->id);
        $data['contract_id'] = $contract->id;
        $data['password_hash'] = Hash::make($data['password']);
        unset($data['password']);
        $data['is_active'] = ($data['status'] ?? 'Actief') === 'Actief' ? 1 : 0;
        $data['created_at'] = now();
        $data['is_active'] = ($data['status'] ?? 'Actief') === 'Actief' ? 1 : 0;
        $data['updated_at'] = now();

        DB::table('contract_authorized_users')->insert($data);

        return response()->json(['message' => 'Gebruiker aangemaakt.'], 201);
    }

    public function update(Request $request, string $identifier, string $user_identifier): JsonResponse
    {
        $contract = $this->findContract($identifier);
        if (! $contract) {
            return response()->json(['error' => 'Contract niet gevonden.'], 404);
        }

        $existing = DB::table('contract_authorized_users')
            ->where('contract_id', $contract->id)
            ->where('user_identifier', $user_identifier)
            ->whereNull('deleted_at')
            ->first();

        if (! $existing) {
            return response()->json(['error' => 'Gebruiker niet gevonden.'], 404);
        }

        $data = $this->validatePayload($request, false, $contract->id, $existing->id);
        if (! empty($data['password'])) {
            $data['password_hash'] = Hash::make($data['password']);
        }
        unset($data['password']);
        $data['updated_at'] = now();

        DB::table('contract_authorized_users')->where('id', $existing->id)->update($data);

        return response()->json(['message' => 'Gebruiker bijgewerkt.']);
    }

    public function destroy(string $identifier, string $user_identifier): JsonResponse
    {
        $contract = $this->findContract($identifier);
        if (! $contract) {
            return response()->json(['error' => 'Contract niet gevonden.'], 404);
        }

        DB::table('contract_authorized_users')
            ->where('contract_id', $contract->id)
            ->where('user_identifier', $user_identifier)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now(),
                'is_active' => 0,
                'status' => 'Inactief',
                'updated_at' => now(),
            ]);

        return response()->json(['message' => 'Gebruiker logisch verwijderd.']);
    }

    private function validatePayload(Request $request, bool $create, int $contractId, ?int $ignoreId = null): array
    {
        $passwordRule = $create ? ['required', 'string', 'min:8', 'max:255'] : ['nullable', 'string', 'min:8', 'max:255'];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'user_identifier' => ['required', 'string', 'max:100'],
            'permission_level' => ['required', 'in:admin,user'],
            'role_label' => ['nullable', 'in:Beheerder,Gebruiker'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'password' => $passwordRule,
        ]);

        $duplicate = DB::table('contract_authorized_users')
            ->where('contract_id', $contractId)
            ->where('user_identifier', $validated['user_identifier'])
            ->whereNull('deleted_at');

        if ($ignoreId) {
            $duplicate->where('id', '!=', $ignoreId);
        }

        if ($duplicate->exists()) {
            abort(response()->json(['error' => 'Deze user identifier bestaat al binnen dit contract.'], 422));
        }

        return $validated;
    }

    private function findContract(string $identifier): ?object
    {
        return DB::table('contracts')->where('identifier', $identifier)->first();
    }
}
