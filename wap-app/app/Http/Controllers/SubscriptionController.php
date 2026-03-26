<?php

/*
|--------------------------------------------------------------------------
| SubscriptionController
|--------------------------------------------------------------------------
|
| Beheert abonnementen, abonnementtypes en tokenacties.
|
| Als je hier route-namen, formulier-velden of tokenlogica wijzigt, pas dan
| ook aan:
| - routes/web.php
| - resources/views/subscriptions/*
| - de subscriptions en subscription_types tabellen
|
*/
namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    /**
     * Toon het abonnementenoverzicht.
     *
     * Let op:
     * - Deze view gebruikt route-namen uit routes/web.php.
     * - Als je route-namen wijzigt, pas dan ook de Blade views aan.
     */
    public function index(): View
    {
        $subscriptions = DB::table('subscriptions')
            ->join('companies', 'subscriptions.company', '=', 'companies.id')
            ->join('subscription_types', 'subscriptions.type', '=', 'subscription_types.id')
            ->leftJoin('subscription_station', 'subscriptions.id', '=', 'subscription_station.subscription')
            ->select(
                'subscriptions.id',
                'subscriptions.identifier',
                'subscriptions.start_date',
                'subscriptions.end_date',
                'subscriptions.price',
                'subscriptions.notes',
                'subscriptions.token',
                'companies.name as company_name',
                'companies.city as company_city',
                'subscription_types.id as type_id',
                'subscription_types.name as type_name',
                'subscription_types.continuous',
                'subscription_types.frequency_in_hours',
                'subscription_types.frequency_in_days',
                DB::raw('COUNT(subscription_station.station) as station_count')
            )
            ->groupBy(
                'subscriptions.id', 'subscriptions.identifier', 'subscriptions.start_date',
                'subscriptions.end_date', 'subscriptions.price', 'subscriptions.notes',
                'subscriptions.token', 'companies.name', 'companies.city',
                'subscription_types.id', 'subscription_types.name', 'subscription_types.continuous',
                'subscription_types.frequency_in_hours', 'subscription_types.frequency_in_days'
            )
            ->orderBy('subscriptions.start_date', 'desc')
            ->get();

        $types = DB::table('subscription_types')
            ->select('subscription_types.*', DB::raw('(SELECT COUNT(*) FROM subscriptions WHERE subscriptions.type = subscription_types.id) as subscriber_count'))
            ->get();

        $today = now()->format('Y-m-d');

        $summary = [
            'subscription_count'  => $subscriptions->count(),
            'active_count'        => $subscriptions->filter(fn($s) => empty($s->end_date) || $s->end_date >= $today)->count(),
            'type_count'          => $types->count(),
            'total_station_links' => DB::table('subscription_station')->count(),
            'total_revenue'       => $subscriptions->sum('price'),
        ];

        return view('subscriptions.index', compact('subscriptions', 'types', 'summary'));
    }

    /**
     * Toon detailpagina van één abonnement.
     *
     * Deze pagina gebruikt hetzelfde token als de edit-pagina.
     * Nieuwe velden of extra knoppen moeten daarom zowel hier als in
     * resources/views/subscriptions/form.blade.php worden bijgehouden.
     */
    public function show(string $identifier): View
    {
        $subscription = $this->findSubscription($identifier);
        abort_if(! $subscription, 404);

        $stations = DB::table('subscription_station')
            ->join('station', 'subscription_station.station', '=', 'station.name')
            ->leftJoin('nearestlocation', 'station.name', '=', 'nearestlocation.station_name')
            ->where('subscription_station.subscription', $subscription->id)
            ->select(
                'station.name as stn',
                'nearestlocation.name as location_label',
                'station.latitude as lat',
                'station.longitude as lon'
            )
            ->get();

        $activity = DB::table('endpoint_activity')
            ->where('identifier', $identifier)
            ->orderByDesc('activity_date')
            ->orderByDesc('activity_time')
            ->limit(20)
            ->get();

        $allTypes = DB::table('subscription_types')->orderBy('name')->get();

        return view('subscriptions.detail', compact('subscription', 'stations', 'activity', 'allTypes'));
    }

    /**
     * Toon formulier voor nieuw abonnement.
     */
    public function create(): View
    {
        return view('subscriptions.subscription-form', $this->subscriptionFormData());
    }

    /**
     * Sla nieuw abonnement op in tabel subscriptions.
     *
     * Als je extra verplichte databasevelden toevoegt, pas dan ook:
     * - validateSubscription()
     * - resources/views/subscriptions/form.blade.php
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateSubscription($request, null);

        try {
            DB::table('subscriptions')->insert($data);
            Log::info('Abonnement aangemaakt', ['identifier' => $data['identifier']]);

            return redirect()->route('subscriptions.index')->with('success', 'Abonnement aangemaakt.');
        } catch (\Throwable $e) {
            Log::error('Fout bij abonnement aanmaken', $this->errorContext($e, ['payload' => $data]));

            return back()->withInput()->with('error', 'Abonnement kon niet worden aangemaakt.');
        }
    }

    /**
     * Toon formulier voor bestaand abonnement.
     *
     * De edit-view bevat ook token-acties. Die schrijven naar:
     * - subscriptions.token
     * - subscriptions.notes
     *
     * Bij wijzigingen aan deze acties ook routes/web.php en de form-view aanpassen.
     */
    public function edit(string $identifier): View
    {
        $subscription = DB::table('subscriptions')->where('identifier', $identifier)->first();
        abort_if(! $subscription, 404);

        return view('subscriptions.subscription-form', $this->subscriptionFormData($subscription));
    }

    /**
     * Werk bestaand abonnement bij.
     */
    public function update(Request $request, string $identifier): RedirectResponse
    {
        $subscription = DB::table('subscriptions')->where('identifier', $identifier)->first();
        abort_if(! $subscription, 404);

        $data = $this->validateSubscription($request, $subscription->id);

        try {
            DB::table('subscriptions')->where('id', $subscription->id)->update($data);
            Log::info('Abonnement bijgewerkt', ['identifier' => $identifier]);

            return redirect()->route('subscriptions.show', $data['identifier'])->with('success', 'Abonnement bijgewerkt.');
        } catch (\Throwable $e) {
            Log::error('Fout bij abonnement bijwerken', $this->errorContext($e, ['identifier' => $identifier, 'payload' => $data]));

            return back()->withInput()->with('error', 'Abonnement kon niet worden bijgewerkt.');
        }
    }

    /**
     * Verwijder abonnement en gekoppelde relaties.
     */
    public function destroy(string $identifier): RedirectResponse
    {
        $subscription = DB::table('subscriptions')->where('identifier', $identifier)->first();
        abort_if(! $subscription, 404);

        try {
            DB::transaction(function () use ($subscription) {
                DB::table('subscription_station')->where('subscription', $subscription->id)->delete();
                DB::table('endpoint_activity')->where('identifier', $subscription->identifier)->delete();
                DB::table('subscriptions')->where('id', $subscription->id)->delete();
            });

            Log::info('Abonnement verwijderd', ['identifier' => $identifier]);

            return redirect()->route('subscriptions.index')->with('success', 'Abonnement verwijderd.');
        } catch (\Throwable $e) {
            Log::error('Fout bij abonnement verwijderen', $this->errorContext($e, ['identifier' => $identifier]));

            return back()->with('error', 'Abonnement kon niet worden verwijderd.');
        }
    }

    /**
     * Genereer een nieuw token en sla dit direct op in subscriptions.token.
     *
     * De knop hiervoor staat op:
     * - resources/views/subscriptions/detail.blade.php
     * - resources/views/subscriptions/form.blade.php
     */
    public function regenerateToken(string $identifier): RedirectResponse
    {
        $subscription = DB::table('subscriptions')->where('identifier', $identifier)->first();
        abort_if(! $subscription, 404);

        $token = strtoupper(bin2hex(random_bytes(12)));

        try {
            DB::table('subscriptions')->where('id', $subscription->id)->update(['token' => $token]);
            Log::info('Token opnieuw gegenereerd', ['identifier' => $identifier]);

            return redirect()->route('subscriptions.edit', $identifier)->with('success', 'Nieuw token gegenereerd en opgeslagen in de database.');
        } catch (\Throwable $e) {
            Log::error('Fout bij token genereren', $this->errorContext($e, ['identifier' => $identifier]));

            return back()->with('error', 'Nieuw token kon niet worden gegenereerd.');
        }
    }

    /**
     * Markeer token als verstuurd door een tijdstempel aan notes toe te voegen.
     *
     * Er is in deze codebasis geen aparte mailqueue of verzendhistorietabel.
     * Daarom blijft deze actie klein en veilig: de database krijgt alleen een notitie-update.
     */
    public function markTokenSent(string $identifier): RedirectResponse
    {
        $subscription = DB::table('subscriptions')->where('identifier', $identifier)->first();
        abort_if(! $subscription, 404);

        $stamp = 'Token verstuurd op ' . now()->format('d-m-Y H:i');
        $notes = trim(($subscription->notes ? $subscription->notes . PHP_EOL : '') . $stamp);

        try {
            DB::table('subscriptions')->where('id', $subscription->id)->update(['notes' => $notes]);
            Log::info('Token als verstuurd gemarkeerd', ['identifier' => $identifier]);

            return redirect()->route('subscriptions.edit', $identifier)->with('success', 'Token als verstuurd gemarkeerd in de database-notities.');
        } catch (\Throwable $e) {
            Log::error('Fout bij token versturen markeren', $this->errorContext($e, ['identifier' => $identifier]));

            return back()->with('error', 'Token kon niet als verstuurd worden gemarkeerd.');
        }
    }

    /**
     * Toon overzicht van alle abonnementtypes.
     */
    public function typesIndex(): View
    {
        $types = DB::table('subscription_types')
            ->select(
                'subscription_types.*',
                DB::raw('(SELECT COUNT(*) FROM subscriptions WHERE subscriptions.type = subscription_types.id) as subscriber_count')
            )
            ->orderBy('name')
            ->get();

        return view('subscriptions.types', compact('types'));
    }

    /**
     * Toon formulier voor nieuw type.
     */
    public function typesCreate(): View
    {
        $type = null;
        return view('subscriptions.subscription-type-form', compact('type'));
    }

    /**
     * Sla nieuw abonnementtype op.
     */
    public function typesStore(Request $request): RedirectResponse
    {
        $data = $this->validateType($request);

        try {
            $data['continuous'] = $request->boolean('continuous') ? 1 : 0;
            DB::table('subscription_types')->insert($data);
            Log::info('Abonnementtype aangemaakt', ['name' => $data['name']]);

            return redirect()->route('subscription-types.index')->with('success', 'Abonnementtype aangemaakt.');
        } catch (\Throwable $e) {
            Log::error('Fout bij abonnementtype aanmaken', $this->errorContext($e, ['payload' => $data]));

            return back()->withInput()->with('error', 'Abonnementtype kon niet worden aangemaakt.');
        }
    }

    /**
     * Toon formulier voor bestaand type.
     */
    public function typesEdit(int $id): View
    {
        $type = DB::table('subscription_types')->where('id', $id)->first();
        abort_if(! $type, 404);

        return view('subscriptions.subscription-type-form', compact('type'));
    }

    /**
     * Werk abonnementtype bij.
     */
    public function typesUpdate(Request $request, int $id): RedirectResponse
    {
        $data = $this->validateType($request);

        try {
            $data['continuous'] = $request->boolean('continuous') ? 1 : 0;
            DB::table('subscription_types')->where('id', $id)->update($data);
            Log::info('Abonnementtype bijgewerkt', ['type_id' => $id]);

            return redirect()->route('subscription-types.index')->with('success', 'Abonnementtype bijgewerkt.');
        } catch (\Throwable $e) {
            Log::error('Fout bij abonnementtype bijwerken', $this->errorContext($e, ['type_id' => $id, 'payload' => $data]));

            return back()->withInput()->with('error', 'Abonnementtype kon niet worden bijgewerkt.');
        }
    }

    /**
     * Verwijder abonnementtype.
     */
    public function typesDestroy(int $id): RedirectResponse
    {
        try {
            DB::table('subscription_types')->where('id', $id)->delete();
            Log::info('Abonnementtype verwijderd', ['type_id' => $id]);

            return redirect()->route('subscription-types.index')->with('success', 'Abonnementtype verwijderd.');
        } catch (\Throwable $e) {
            Log::error('Fout bij abonnementtype verwijderen', $this->errorContext($e, ['type_id' => $id]));

            return back()->with('error', 'Abonnementtype kon niet worden verwijderd. Mogelijk is het type nog gekoppeld aan abonnementen.');
        }
    }

    /**
     * Verzamel data voor create/edit-formulier.
     */
    private function subscriptionFormData(?object $subscription = null): array
    {
        $companies = DB::table('companies')->orderBy('name')->get();
        $types = DB::table('subscription_types')->orderBy('name')->get();

        return compact('subscription', 'companies', 'types');
    }

    /**
     * Validatie voor subscriptions-tabel.
     */
    private function validateSubscription(Request $request, ?int $subscriptionId): array
    {
        return $request->validate([
            'identifier' => ['required', 'string', 'max:45', 'unique:subscriptions,identifier,' . ($subscriptionId ?? 'NULL') . ',id'],
            'company' => ['required', 'integer', 'exists:companies,id'],
            'type' => ['required', 'integer', 'exists:subscription_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'price' => ['required', 'numeric'],
            'notes' => ['nullable', 'string', 'max:255'],
            'token' => ['nullable', 'string', 'max:100'],
        ]);
    }

    /**
     * Validatie voor subscription_types-tabel.
     */
    private function validateType(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'nr_stations' => ['nullable', 'integer'],
            'frequency_in_hours' => ['nullable', 'integer'],
            'frequency_in_days' => ['nullable', 'integer'],
            'price_per_station' => ['required', 'numeric'],
            'valid_through' => ['nullable', 'date'],
        ]);
    }

    /**
     * Haal detailrecord op voor show-pagina.
     */
    private function findSubscription(string $identifier): ?object
    {
        return DB::table('subscriptions')
            ->join('companies', 'subscriptions.company', '=', 'companies.id')
            ->join('subscription_types', 'subscriptions.type', '=', 'subscription_types.id')
            ->where('subscriptions.identifier', $identifier)
            ->select(
                'subscriptions.*',
                'companies.name as company_name',
                'companies.city as company_city',
                'subscription_types.name as type_name',
                'subscription_types.description as type_description',
                'subscription_types.price_per_station'
            )
            ->first();
    }

    /**
     * Gestandaardiseerde logcontext voor debugging.
     */
    private function errorContext(\Throwable $e, array $context = []): array
    {
        return array_merge($context, [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }
}
