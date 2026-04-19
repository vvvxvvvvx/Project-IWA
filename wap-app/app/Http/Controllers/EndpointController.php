<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EndpointController extends Controller
{
    /**
     * Toont het overzicht van het REST-API gebruik (E-11).
     */
    public function index()
    {
        // Totale statistieken ophalen
        $totalCalls = DB::table('endpoint_activity')->count();
        $unauthorizedCalls = DB::table('endpoint_activity')->where('authorized', 0)->count();
        $totalDataTransferred = DB::table('endpoint_activity')->sum('data_transferred');
        $totalFilesDownloaded = DB::table('endpoint_activity')->sum('files_downloaded');
        
        // Groeperen per endpoint om veelgebruikte routes te zien
        $usagePerEndpoint = DB::table('endpoint_activity')
            ->select('endpoint_used', DB::raw('count(*) as total'), DB::raw('sum(authorized) as successful'), DB::raw('sum(data_transferred) as data'))
            ->groupBy('endpoint_used')
            ->orderByDesc('total')
            ->get();

        // De meest recente log regels ophalen
        $recentActivity = DB::table('endpoint_activity')
            ->orderByDesc('activity_date')
            ->orderByDesc('activity_time')
            ->limit(100)
            ->get();

        return view('endpoints.index', compact(
            'totalCalls', 'unauthorizedCalls', 'totalDataTransferred', 'totalFilesDownloaded', 'usagePerEndpoint', 'recentActivity'
        ));
    }
}
