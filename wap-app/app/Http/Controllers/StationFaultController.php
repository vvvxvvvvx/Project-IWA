<?php

namespace App\Http\Controllers;

use App\Models\StationFault;
use App\Models\StationFaultNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StationFaultController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'station'     => 'required|string',
            'type'        => 'required|in:offline,ontbrekende_data,temperatuurcorrectie,overig',
            'description' => 'nullable|string|max:1000',
        ]);

        $fault = StationFault::create([
            'station'     => $request->station,
            'type'        => $request->type,
            'status'      => 'open',
            'description' => $request->description,
        ]);

        return redirect()->route('storingen.show', $fault->id)
            ->with('success', 'Storing aangemaakt.');
    }

    public function show(int $id)
    {
        $fault = StationFault::with('notes')->findOrFail($id);
        $stn   = $fault->station;

        $station = DB::table('station')
            ->leftJoin('nearestlocation as nl', 'station.name', '=', 'nl.station_name')
            ->leftJoin('country as c', 'c.country_code', '=', 'nl.country_code')
            ->where('station.name', $stn)
            ->select('station.name as stn', 'nl.name as location_label', 'c.country as country_name')
            ->first();

        $context = [];

        if ($fault->type === 'offline') {
            $last = DB::table('measurement')
                ->where('station', $stn)
                ->select(DB::raw("MAX(CONCAT(date, ' ', time)) as last_seen"), DB::raw('MAX(date) as last_date'))
                ->first();

            $context['last_seen']    = $last->last_seen ?? null;
            $context['days_offline'] = $last->last_date
                ? (int) abs(now()->diffInDays(\Carbon\Carbon::parse($last->last_date)))
                : null;
        }

        if ($fault->type === 'ontbrekende_data') {
            $missing = DB::table('original_measurement as om')
                ->join('measurement as m', 'm.id', '=', 'om.corrected_measurement')
                ->where('m.station', $stn)
                ->whereNotNull('om.missing_field')
                ->select('om.missing_field', DB::raw('COUNT(*) as aantal'))
                ->groupBy('om.missing_field')
                ->orderByDesc('aantal')
                ->get();

            $context['missing_fields'] = $missing;
            $context['total_missing']  = $missing->sum('aantal');
        }

        if ($fault->type === 'temperatuurcorrectie') {
            $corrections = DB::table('original_measurement as om')
                ->join('measurement as m', 'm.id', '=', 'om.corrected_measurement')
                ->where('m.station', $stn)
                ->whereNotNull('om.inavlid_temperature')
                ->select(
                    DB::raw("CONCAT(m.date, ' ', m.time) as measured_at"),
                    'om.inavlid_temperature as origineel',
                    'm.temperature as gecorrigeerd'
                )
                ->orderByDesc('m.date')
                ->orderByDesc('m.time')
                ->limit(20)
                ->get();

            $context['corrections']       = $corrections;
            $context['total_corrections'] = DB::table('original_measurement as om')
                ->join('measurement as m', 'm.id', '=', 'om.corrected_measurement')
                ->where('m.station', $stn)
                ->whereNotNull('om.inavlid_temperature')
                ->count();
        }

        return view('storingen.storing-detail', [
            'fault'   => $fault,
            'station' => $station,
            'context' => $context,
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|in:open,in_behandeling,opgelost',
        ]);

        $fault = StationFault::findOrFail($id);
        $fault->update(['status' => $request->status]);

        return redirect()->route('storingen.show', $id)
            ->with('success', 'Status bijgewerkt.');
    }

    public function storeNote(Request $request, int $id)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        StationFaultNote::create([
            'station_fault_id' => $id,
            'message'          => $request->message,
        ]);

        return redirect()->route('storingen.show', $id)
            ->with('success', 'Aantekening toegevoegd.');
    }

    public function destroy(int $id)
    {
        $fault = StationFault::findOrFail($id);
        $stn = $fault->station;
        $fault->delete();

        return redirect()->route('stations.show', $stn)
            ->with('success', 'Storing verwijderd.');
    }
}
