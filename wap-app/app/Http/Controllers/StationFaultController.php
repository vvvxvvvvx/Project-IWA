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

        $station = DB::table('station')
            ->leftJoin('nearestlocation as nl', 'station.name', '=', 'nl.station_name')
            ->leftJoin('country as c', 'c.country_code', '=', 'nl.country_code')
            ->where('station.name', $fault->station)
            ->select('station.name as stn', 'nl.name as location_label', 'c.country as country_name')
            ->first();

        return view('storingen.storing-detail', [
            'fault'   => $fault,
            'station' => $station,
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
