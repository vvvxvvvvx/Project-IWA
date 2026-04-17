<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManageStationController extends Controller
{
    public function index(Request $request)
    {
        $query  = trim((string) $request->input('q', ''));
        $country = trim((string) $request->input('country', ''));

        $countries = DB::table('country')->orderBy('country')->get();

        $stations = DB::table('station')
            ->leftJoin('nearestlocation as nl', 'station.name', '=', 'nl.station_name')
            ->leftJoin('country as c', 'c.country_code', '=', 'nl.country_code')
            ->select(
                'station.name as stn',
                'station.latitude',
                'station.longitude',
                'station.elevation',
                'nl.name as location_label',
                'nl.country_code',
                'c.country as country_name'
            )
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($q2) use ($query) {
                    $q2->where('station.name', 'like', "%{$query}%")
                       ->orWhere('nl.name', 'like', "%{$query}%");
                });
            })
            ->when($country !== '', fn ($q) => $q->where('nl.country_code', $country))
            ->orderBy('nl.name')
            ->get();

        return view('stations.manage.index', compact('stations', 'countries', 'query', 'country'));
    }

    public function create()
    {
        $countries = DB::table('country')->orderBy('country')->get();
        return view('stations.manage.create', compact('countries'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:10|unique:station,name',
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'elevation' => 'required|numeric',
            'location'  => 'nullable|string|max:100',
            'country_code' => 'required|string|size:2|exists:country,country_code',
            'region1'   => 'nullable|string|max:100',
            'region2'   => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($request) {
            DB::table('station')->insert([
                'name'      => strtoupper(trim($request->input('name'))),
                'latitude'  => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'elevation' => $request->input('elevation'),
            ]);

            DB::table('nearestlocation')->insert([
                'station_name'          => strtoupper(trim($request->input('name'))),
                'name'                  => $request->input('location') ?: null,
                'administrative_region1'=> $request->input('region1') ?: null,
                'administrative_region2'=> $request->input('region2') ?: null,
                'country_code'          => $request->input('country_code'),
                'longitude'             => $request->input('longitude'),
                'latitude'              => $request->input('latitude'),
            ]);
        });

        return redirect()
            ->route('stations.manage.show', strtoupper(trim($request->input('name'))))
            ->with('success', 'Station succesvol aangemaakt.');
    }

    public function show(string $stn)
    {
        $station = DB::table('station')
            ->leftJoin('nearestlocation as nl', 'station.name', '=', 'nl.station_name')
            ->leftJoin('country as c', 'c.country_code', '=', 'nl.country_code')
            ->where('station.name', $stn)
            ->select(
                'station.name as stn',
                'station.latitude',
                'station.longitude',
                'station.elevation',
                'nl.name as location_label',
                'nl.administrative_region1 as region1',
                'nl.administrative_region2 as region2',
                'nl.country_code',
                'c.country as country_name'
            )
            ->first();

        abort_if(! $station, 404);

        $measurementCount = DB::table('measurement')->where('station', $stn)->count();
        $latestMeasurement = DB::table('measurement')
            ->where('station', $stn)
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->select(DB::raw("CONCAT(date, ' ', time) as measured_at"), 'temperature', 'wind_speed', 'wind_direction')
            ->first();

        return view('stations.manage.show', compact('station', 'measurementCount', 'latestMeasurement'));
    }

    public function edit(string $stn)
    {
        $station = DB::table('station')
            ->leftJoin('nearestlocation as nl', 'station.name', '=', 'nl.station_name')
            ->where('station.name', $stn)
            ->select(
                'station.name as stn',
                'station.latitude',
                'station.longitude',
                'station.elevation',
                'nl.name as location_label',
                'nl.administrative_region1 as region1',
                'nl.administrative_region2 as region2',
                'nl.country_code'
            )
            ->first();

        abort_if(! $station, 404);

        $countries = DB::table('country')->orderBy('country')->get();

        return view('stations.manage.edit', compact('station', 'countries'));
    }

    public function update(Request $request, string $stn)
    {
        $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'elevation' => 'required|numeric',
            'location'  => 'nullable|string|max:100',
            'country_code' => 'required|string|size:2|exists:country,country_code',
            'region1'   => 'nullable|string|max:100',
            'region2'   => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($request, $stn) {
            DB::table('station')->where('name', $stn)->update([
                'latitude'  => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'elevation' => $request->input('elevation'),
            ]);

            $nlExists = DB::table('nearestlocation')->where('station_name', $stn)->exists();

            $nlData = [
                'name'                   => $request->input('location') ?: null,
                'administrative_region1' => $request->input('region1') ?: null,
                'administrative_region2' => $request->input('region2') ?: null,
                'country_code'           => $request->input('country_code'),
                'longitude'              => $request->input('longitude'),
                'latitude'               => $request->input('latitude'),
            ];

            if ($nlExists) {
                DB::table('nearestlocation')->where('station_name', $stn)->update($nlData);
            } else {
                DB::table('nearestlocation')->insert(array_merge($nlData, ['station_name' => $stn]));
            }
        });

        return redirect()
            ->route('stations.manage.show', $stn)
            ->with('success', 'Station succesvol bijgewerkt.');
    }
}
