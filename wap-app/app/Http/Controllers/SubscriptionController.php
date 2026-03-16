<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    public function index()
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

    public function show(string $identifier)
    {
        $subscription = DB::table('subscriptions')
            ->join('companies', 'subscriptions.company', '=', 'companies.id')
            ->join('subscription_types', 'subscriptions.type', '=', 'subscription_types.id')
            ->where('subscriptions.identifier', $identifier)
            ->select(
                'subscriptions.id',
                'subscriptions.identifier',
                'subscriptions.start_date',
                'subscriptions.end_date',
                'subscriptions.price',
                'subscriptions.notes',
                'subscriptions.token',
                'subscriptions.type as type_id',
                'companies.name as company_name',
                'companies.city as company_city',
                'subscription_types.name as type_name',
                'subscription_types.description as type_description',
                'subscription_types.continuous',
                'subscription_types.frequency_in_hours',
                'subscription_types.frequency_in_days'
            )
            ->first();

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

        $allTypes = DB::table('subscription_types')->get();

        return view('subscriptions.detail', compact('subscription', 'stations', 'activity', 'allTypes'));
    }

    public function typesIndex()
    {
        $types = DB::table('subscription_types')
            ->select(
                'subscription_types.*',
                DB::raw('(SELECT COUNT(*) FROM subscriptions WHERE subscriptions.type = subscription_types.id) as subscriber_count')
            )
            ->get();

        return view('subscriptions.types', compact('types'));
    }
}
