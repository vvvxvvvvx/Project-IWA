<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ContractController extends Controller
{
    public function index(): View
    {
        $contracts = DB::table('subscriptions')
            ->join('companies', 'subscriptions.company', '=', 'companies.id')
            ->join('subscription_types', 'subscriptions.type', '=', 'subscription_types.id')
            ->leftJoin('subscription_station', 'subscriptions.id', '=', 'subscription_station.subscription')
            ->leftJoin('endpoint_activity', 'subscriptions.identifier', '=', 'endpoint_activity.identifier')
            ->select(
                'subscriptions.identifier',
                'subscriptions.start_date',
                'subscriptions.end_date',
                'subscriptions.price',
                'subscriptions.notes',
                'companies.name as company_name',
                'subscription_types.name as type_name',
                DB::raw('COUNT(DISTINCT subscription_station.station) as station_count'),
                DB::raw('SUM(CASE WHEN endpoint_activity.authorized = 1 THEN 1 ELSE 0 END) as successful_calls'),
                DB::raw('COUNT(CASE WHEN endpoint_activity.authorized = 1 THEN 1 END) as authorized_user_count'),
                DB::raw('COUNT(CASE WHEN endpoint_activity.id IS NOT NULL THEN 1 END) as query_count')
            )
            ->groupBy(
                'subscriptions.identifier', 'subscriptions.start_date', 'subscriptions.end_date',
                'subscriptions.price', 'subscriptions.notes', 'companies.name', 'subscription_types.name'
            )
            ->orderByDesc('subscriptions.start_date')
            ->get();

        // Calculate summary statistics
        $summary = [
            'contract_count' => DB::table('subscriptions')->count(),
            'active_count' => DB::table('subscriptions')
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->count(),
            'authorized_user_count' => DB::table('endpoint_activity')
                ->where('authorized', 1)
                ->distinct('identifier')
                ->count(),
            'query_count' => DB::table('endpoint_activity')->count(),
        ];

        // Contractoverzicht gebruikt een expliciete viewnaam zodat direct duidelijk is wat dit bestand toont.
        return view('contracts.contract-list', compact('contracts', 'summary'));
    }

    public function show(string $identifier): View
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        $stations = DB::table('subscription_station')
            ->join('station', 'subscription_station.station', '=', 'station.name')
            ->leftJoin('nearestlocation', 'station.name', '=', 'nearestlocation.station_name')
            ->where('subscription_station.subscription', $contract->id)
            ->select(
                'station.name as stn',
                'nearestlocation.name as location_label',
                'station.latitude as lat',
                'station.longitude as lon'
            )
            ->orderBy('station.name')
            ->get();

        $activity = DB::table('endpoint_activity')
            ->where('identifier', $identifier)
            ->orderByDesc('activity_date')
            ->orderByDesc('activity_time')
            ->limit(25)
            ->get();

        return view('contracts.contract-details', compact('contract', 'activity'));
        $authorizedUsers = DB::table('contract_authorized_users')
            ->where('subscription_id', $contract->id)
            ->orderBy('name')
            ->get();

        $queries = DB::table('contract_queries')
            ->where('subscription_id', $contract->id)
            ->orderBy('name')
            ->get();

        return view('contracts.contract-details', compact('contract', 'stations', 'activity', 'authorizedUsers', 'queries'));
    }

    public function create(): View
    {
        return view('contracts.contract-form', $this->contractFormData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateContract($request, null);

        try {
            DB::table('subscriptions')->insert($data);
            return redirect()->route('contracts.index')->with('success', 'Contract aangemaakt.');
        } catch (\Throwable $e) {
            Log::error('Fout bij contract aanmaken', ['exception' => $e->getMessage(), 'payload' => $data]);
            return back()->withInput()->with('error', 'Contract kon niet worden aangemaakt.');
        }
    }

    public function edit(string $identifier): View
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        return view('contracts.contract-form', $this->contractFormData($contract));
    }

    public function update(Request $request, string $identifier): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        $data = $this->validateContract($request, $contract->id);

        try {
            DB::table('subscriptions')->where('id', $contract->id)->update($data);
            return redirect()->route('contracts.show', $data['identifier'])->with('success', 'Contract bijgewerkt.');
        } catch (\Throwable $e) {
            Log::error('Fout bij contract bijwerken', ['exception' => $e->getMessage(), 'identifier' => $identifier, 'payload' => $data]);
            return back()->withInput()->with('error', 'Contract kon niet worden bijgewerkt.');
        }
    }

    public function destroy(string $identifier): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        try {
            DB::transaction(function () use ($contract) {
                DB::table('contract_authorized_users')->where('subscription_id', $contract->id)->delete();
                DB::table('contract_queries')->where('subscription_id', $contract->id)->delete();
                DB::table('subscription_station')->where('subscription', $contract->id)->delete();
                DB::table('endpoint_activity')->where('identifier', $contract->identifier)->delete();
                DB::table('subscriptions')->where('id', $contract->id)->delete();
            });

            return redirect()->route('contracts.index')->with('success', 'Contract verwijderd.');
        } catch (\Throwable $e) {
            Log::error('Fout bij contract verwijderen', ['exception' => $e->getMessage(), 'identifier' => $identifier]);
            return back()->with('error', 'Contract kon niet worden verwijderd.');
        }
    }

    public function regenerateToken(string $identifier): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        $token = strtoupper(bin2hex(random_bytes(12)));
        DB::table('subscriptions')->where('id', $contract->id)->update(['token' => $token]);

        return redirect()->route('contracts.edit', $identifier)->with('success', 'Nieuw contracttoken gegenereerd.');
    }

    public function markTokenSent(string $identifier): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        $stamp = 'Contracttoken verstuurd op ' . now()->format('d-m-Y H:i');
        $notes = trim(($contract->notes ? $contract->notes . PHP_EOL : '') . $stamp);
        DB::table('subscriptions')->where('id', $contract->id)->update(['notes' => $notes]);

        return redirect()->route('contracts.edit', $identifier)->with('success', 'Contracttoken als verstuurd gemarkeerd.');
    }

    public function storeAuthorizedUser(Request $request, string $identifier): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'role_label' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['subscription_id'] = $contract->id;
        $data['status'] = $data['status'] ?: 'Actief';
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::table('contract_authorized_users')->insert($data);
        return redirect()->route('contracts.show', $identifier)->with('success', 'Geautoriseerde gebruiker toegevoegd.');
    }

    public function updateAuthorizedUser(Request $request, string $identifier, int $userId): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'role_label' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['updated_at'] = now();

        DB::table('contract_authorized_users')
            ->where('subscription_id', $contract->id)
            ->where('id', $userId)
            ->update($data);

        return redirect()->route('contracts.show', $identifier)->with('success', 'Geautoriseerde gebruiker bijgewerkt.');
    }

    public function destroyAuthorizedUser(string $identifier, int $userId): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        DB::table('contract_authorized_users')
            ->where('subscription_id', $contract->id)
            ->where('id', $userId)
            ->delete();

        return redirect()->route('contracts.show', $identifier)->with('success', 'Geautoriseerde gebruiker verwijderd.');
    }

    public function storeQuery(Request $request, string $identifier): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'endpoint' => ['nullable', 'string', 'max:255'],
            'query_text' => ['nullable', 'string'],
            'format' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['subscription_id'] = $contract->id;
        $data['format'] = $data['format'] ?: 'JSON';
        $data['status'] = $data['status'] ?: 'Actief';
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::table('contract_queries')->insert($data);
        return redirect()->route('contracts.show', $identifier)->with('success', 'Contractquery toegevoegd.');
    }

    public function updateQuery(Request $request, string $identifier, int $queryId): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'endpoint' => ['nullable', 'string', 'max:255'],
            'query_text' => ['nullable', 'string'],
            'format' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['updated_at'] = now();

        DB::table('contract_queries')
            ->where('subscription_id', $contract->id)
            ->where('id', $queryId)
            ->update($data);

        return redirect()->route('contracts.show', $identifier)->with('success', 'Contractquery bijgewerkt.');
    }

    public function destroyQuery(string $identifier, int $queryId): RedirectResponse
    {
        $contract = $this->findContract($identifier);
        abort_if(! $contract, 404);

        DB::table('contract_queries')
            ->where('subscription_id', $contract->id)
            ->where('id', $queryId)
            ->delete();

        return redirect()->route('contracts.show', $identifier)->with('success', 'Contractquery verwijderd.');
    }

    private function contractFormData(?object $contract = null): array
    {
        return [
            'contract' => $contract,
            'companies' => DB::table('companies')->orderBy('name')->get(),
            'types' => DB::table('subscription_types')->orderBy('name')->get(),
        ];
    }

    private function validateContract(Request $request, ?int $ignoreId): array
    {
        return $request->validate([
            'company' => ['required', 'integer', 'exists:companies,id'],
            'type' => ['required', 'integer', 'exists:subscription_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'price' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string', 'max:256'],
            'identifier' => ['required', 'string', 'max:45', 'unique:subscriptions,identifier,' . ($ignoreId ?? 'NULL') . ',id'],
            'token' => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function findContract(string $identifier): ?object
    {
        return DB::table('subscriptions')
            ->join('companies', 'subscriptions.company', '=', 'companies.id')
            ->join('subscription_types', 'subscriptions.type', '=', 'subscription_types.id')
            ->where('subscriptions.identifier', $identifier)
            ->select(
                'subscriptions.*',
                'companies.name as company_name',
                'subscription_types.name as type_name'
            )
            ->first();
    }
}
