<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
    public function index()
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
                DB::raw('SUM(CASE WHEN endpoint_activity.authorized = 1 THEN 1 ELSE 0 END) as successful_calls')
            )
            ->groupBy(
                'subscriptions.identifier', 'subscriptions.start_date', 'subscriptions.end_date',
                'subscriptions.price', 'subscriptions.notes', 'companies.name', 'subscription_types.name'
            )
            ->orderByDesc('subscriptions.start_date')
            ->get();

        return view('contracts.index', compact('contracts'));
    }

    public function show(string $identifier)
    {
        $contract = DB::table('subscriptions')
            ->join('companies', 'subscriptions.company', '=', 'companies.id')
            ->join('subscription_types', 'subscriptions.type', '=', 'subscription_types.id')
            ->where('subscriptions.identifier', $identifier)
            ->select(
                'subscriptions.identifier',
                'subscriptions.start_date',
                'subscriptions.end_date',
                'subscriptions.price',
                'subscriptions.notes',
                'subscriptions.token',
                'companies.name as company_name',
                'subscription_types.name as type_name'
            )
            ->first();

        abort_if(! $contract, 404);

        $activity = DB::table('endpoint_activity')
            ->where('identifier', $identifier)
            ->orderByDesc('activity_date')
            ->orderByDesc('activity_time')
            ->limit(25)
            ->get();

        return view('contracts.detail', compact('contract', 'activity'));
    }
}
