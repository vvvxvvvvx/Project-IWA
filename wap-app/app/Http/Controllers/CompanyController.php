<?php

/*
|--------------------------------------------------------------------------
| CompanyController
|--------------------------------------------------------------------------
|
| Beheert bedrijven en contactpersonen. Contactpersonen staan in tabel
| relations en zijn functioneel gekoppeld aan een bedrijf.
|
| Als je hier routes of view-namen wijzigt, pas dan ook aan:
| - routes/web.php
| - resources/views/companies/*
|
*/
namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CompanyController extends Controller
{
    /**
     * Overzicht van alle bedrijven.
     */
    public function index(): View
    {
        $companies = DB::table('companies')
            ->leftJoin('country', 'companies.country', '=', 'country.country_code')
            ->select('companies.*', 'country.country as country_name')
            ->orderBy('companies.name')
            ->get();

        // Let op: deze view heet bewust company-list.blade.php voor duidelijkere naamgeving.
        return view('companies.company-list', compact('companies'));
    }

    /**
     * Detailpagina van één bedrijf.
     *
     * Contactpersonen worden opgehaald uit tabel relations.
     * Als de database-structuur wijzigt, pas dan ook edit() en createContact() aan.
     */
    public function show(int $id): View
    {
        $company = DB::table('companies')
            ->leftJoin('country', 'companies.country', '=', 'country.country_code')
            ->where('companies.id', $id)
            ->select('companies.*', 'country.country as country_name')
            ->first();

        abort_if(! $company, 404);

        $contacts = DB::table('relations')
            ->where('company', $id)
            ->orderBy('name')
            ->get();

        $subscriptions = DB::table('subscriptions')
            ->join('subscription_types', 'subscriptions.type', '=', 'subscription_types.id')
            ->leftJoin('subscription_station', 'subscriptions.id', '=', 'subscription_station.subscription')
            ->where('subscriptions.company', $id)
            ->select(
                'subscriptions.id',
                'subscriptions.identifier',
                'subscriptions.price',
                'subscriptions.start_date',
                'subscriptions.end_date',
                'subscription_types.name as type_name',
                DB::raw('COUNT(subscription_station.station) as station_count')
            )
            ->groupBy(
                'subscriptions.id', 'subscriptions.identifier', 'subscriptions.price',
                'subscriptions.start_date', 'subscriptions.end_date', 'subscription_types.name'
            )
            ->get();

        return view('companies.company-details', compact('company', 'contacts', 'subscriptions'));
    }

    /**
     * Formulier voor nieuw bedrijf.
     */
    public function create(): View
    {
        $company = null;
        $countries = DB::table('country')->orderBy('country')->get();
        $contacts = collect();

        return view('companies.company-form', compact('company', 'countries', 'contacts'));
    }

    /**
     * Sla nieuw bedrijf op in companies.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCompany($request);

        try {
            DB::table('companies')->insert($data);
            Log::info('Bedrijf aangemaakt', ['name' => $data['name']]);

            return redirect()->route('companies.index')->with('success', 'Bedrijf aangemaakt.');
        } catch (\Throwable $e) {
            Log::error('Fout bij bedrijf aanmaken', $this->errorContext($e, ['payload' => $data]));

            return back()->withInput()->with('error', 'Bedrijf kon niet worden aangemaakt.');
        }
    }

    /**
     * Formulier voor bestaand bedrijf.
     *
     * Contactpersonen worden hier ook meegegeven zodat de gebruiker vanaf de edit-pagina
     * direct ziet welke contactpersonen al in relations staan.
     *
     * Bij wijzigingen in de contacts-sectie ook aanpassen:
     * - resources/views/companies/company-contact-form.blade.php
     * - createContact(), editContact(), storeContact(), updateContact()
     */
    public function edit(int $id): View
    {
        $company = DB::table('companies')->where('id', $id)->first();
        abort_if(! $company, 404);

        $countries = DB::table('country')->orderBy('country')->get();
        $contacts = DB::table('relations')->where('company', $id)->orderBy('name')->get();

        return view('companies.company-form', compact('company', 'countries', 'contacts'));
    }

    /**
     * Werk bestaand bedrijf bij.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $this->validateCompany($request);

        try {
            DB::table('companies')->where('id', $id)->update($data);
            Log::info('Bedrijf bijgewerkt', ['company_id' => $id]);

            return redirect()->route('companies.show', $id)->with('success', 'Bedrijf bijgewerkt.');
        } catch (\Throwable $e) {
            Log::error('Fout bij bedrijf bijwerken', $this->errorContext($e, ['company_id' => $id, 'payload' => $data]));

            return back()->withInput()->with('error', 'Bedrijf kon niet worden bijgewerkt.');
        }
    }

    /**
     * Verwijder bedrijf en gekoppelde contactpersonen.
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            DB::transaction(function () use ($id) {
                DB::table('relations')->where('company', $id)->delete();
                DB::table('companies')->where('id', $id)->delete();
            });

            Log::info('Bedrijf verwijderd', ['company_id' => $id]);

            return redirect()->route('companies.index')->with('success', 'Bedrijf verwijderd.');
        } catch (\Throwable $e) {
            Log::error('Fout bij bedrijf verwijderen', $this->errorContext($e, ['company_id' => $id]));

            return back()->with('error', 'Bedrijf kon niet worden verwijderd. Controleer gekoppelde gegevens.');
        }
    }

    /**
     * Toon apart formulier voor nieuwe contactpersoon.
     */
    public function createContact(int $id): View
    {
        $company = DB::table('companies')->where('id', $id)->first();
        abort_if(! $company, 404);

        $contact = null;

        return view('companies.company-contact-form', compact('company', 'contact'));
    }

    /**
     * Sla nieuwe contactpersoon op in relations.
     */
    public function storeContact(Request $request, int $id): RedirectResponse
    {
        $company = DB::table('companies')->where('id', $id)->first();
        abort_if(! $company, 404);

        $data = $this->validateContact($request);
        $data['company'] = $id;

        try {
            DB::table('relations')->insert($data);
            Log::info('Contactpersoon aangemaakt', ['company_id' => $id, 'email' => $data['email'] ?? null]);

            return redirect()->route('companies.edit', $id)->with('success', 'Contactpersoon aangemaakt.');
        } catch (\Throwable $e) {
            Log::error('Fout bij contactpersoon aanmaken', $this->errorContext($e, ['company_id' => $id, 'payload' => $data]));

            return back()->withInput()->with('error', 'Contactpersoon kon niet worden aangemaakt.');
        }
    }

    /**
     * Toon apart formulier voor wijzigen van contactpersoon.
     */
    public function editContact(int $companyId, int $contactId): View
    {
        $company = DB::table('companies')->where('id', $companyId)->first();
        $contact = DB::table('relations')->where('id', $contactId)->where('company', $companyId)->first();

        abort_if(! $company || ! $contact, 404);

        return view('companies.company-contact-form', compact('company', 'contact'));
    }

    /**
     * Werk contactpersoon bij in relations.
     */
    public function updateContact(Request $request, int $companyId, int $contactId): RedirectResponse
    {
        $data = $this->validateContact($request);

        try {
            DB::table('relations')
                ->where('id', $contactId)
                ->where('company', $companyId)
                ->update($data);

            Log::info('Contactpersoon bijgewerkt', ['company_id' => $companyId, 'contact_id' => $contactId]);

            return redirect()->route('companies.edit', $companyId)->with('success', 'Contactpersoon bijgewerkt.');
        } catch (\Throwable $e) {
            Log::error('Fout bij contactpersoon bijwerken', $this->errorContext($e, ['company_id' => $companyId, 'contact_id' => $contactId, 'payload' => $data]));

            return back()->withInput()->with('error', 'Contactpersoon kon niet worden bijgewerkt.');
        }
    }

    /**
     * Verwijder contactpersoon uit relations.
     */
    public function destroyContact(int $companyId, int $contactId): RedirectResponse
    {
        try {
            DB::table('relations')->where('id', $contactId)->where('company', $companyId)->delete();
            Log::info('Contactpersoon verwijderd', ['company_id' => $companyId, 'contact_id' => $contactId]);

            return redirect()->route('companies.edit', $companyId)->with('success', 'Contactpersoon verwijderd.');
        } catch (\Throwable $e) {
            Log::error('Fout bij contactpersoon verwijderen', $this->errorContext($e, ['company_id' => $companyId, 'contact_id' => $contactId]));

            return back()->with('error', 'Contactpersoon kon niet worden verwijderd.');
        }
    }

    /**
     * Validatie voor companies-tabel.
     */
    private function validateCompany(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'street' => ['nullable', 'string', 'max:100'],
            'number' => ['nullable', 'integer'],
            'number_additional' => ['nullable', 'string', 'max:15'],
            'zip_code' => ['nullable', 'string', 'max:15'],
            'country' => ['required', 'string', 'size:2'],
            'email' => ['nullable', 'email', 'max:100'],
        ]);
    }

    /**
     * Validatie voor relations-tabel.
     */
    private function validateContact(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'first_name' => ['nullable', 'string', 'max:45'],
            'initials' => ['nullable', 'string', 'max:12'],
            'prefix' => ['nullable', 'string', 'max:10'],
            'function' => ['nullable', 'string', 'max:45'],
            'title' => ['nullable', 'string', 'max:45'],
            'email' => ['nullable', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:25'],
        ]);
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
