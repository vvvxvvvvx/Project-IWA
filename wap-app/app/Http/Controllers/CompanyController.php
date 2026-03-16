<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = DB::table('companies')
            ->leftJoin('country', 'companies.country', '=', 'country.country_code')
            ->select('companies.*', 'country.country as country_name')
            ->orderBy('companies.name')
            ->get();

        return view('companies.index', compact('companies'));
    }

    public function show(int $id)
    {
        $company = DB::table('companies')
            ->leftJoin('country', 'companies.country', '=', 'country.country_code')
            ->where('companies.id', $id)
            ->select('companies.*', 'country.country as country_name')
            ->first();

        abort_if(! $company, 404);

        $contacts = DB::table('relations')
            ->where('company', $id)
            ->get();

        $subscriptions = DB::table('subscriptions')
            ->join('subscription_types', 'subscriptions.type', '=', 'subscription_types.id')
            ->leftJoin('subscription_station', 'subscriptions.id', '=', 'subscription_station.subscription')
            ->where('subscriptions.company', $id)
            ->select(
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

        return view('companies.detail', compact('company', 'contacts', 'subscriptions'));
    }
}
