<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SeedStationMeasurements extends Command
{
    protected $signature   = 'stations:seed-measurements';
    protected $description = 'Vult de database met twee weken aan meetdata (elke 30 min) voor Aalsmeer, Awans en Beek.';

    // Stationsdefinities: naam, locatie, land, coördinaten, hoogte
    private array $stations = [
        [
            'name'       => '62400',
            'location'   => 'Aalsmeer',
            'country'    => 'NL',
            'lat'        => 52.2628,
            'lon'        => 4.7614,
            'elevation'  => -2.0,
            // Klimaatprofiel (april, Nederland)
            'base_temp'  => 11.0,
            'temp_range' => 6.0,
            'base_pres'  => 1012.0,
            'base_wind'  => 4.5,
        ],
        [
            'name'       => '62700',
            'location'   => 'Bilgaard',
            'country'    => 'NL',
            'lat'        => 53.2167,
            'lon'        => 5.8333,
            'elevation'  => 1.0,
            // Klimaatprofiel (april, Friesland)
            'base_temp'  => 10.5,
            'temp_range' => 6.0,
            'base_pres'  => 1013.0,
            'base_wind'  => 5.0,
        ],
        [
            'name'       => '06380',
            'location'   => 'Beek',
            'country'    => 'NL',
            'lat'        => 50.9189,
            'lon'        => 5.7833,
            'elevation'  => 114.0,
            // Klimaatprofiel (april, Limburg)
            'base_temp'  => 12.5,
            'temp_range' => 7.5,
            'base_pres'  => 1009.5,
            'base_wind'  => 3.5,
        ],
    ];

    public function handle(): void
    {
        $this->info('Start vullen meetdata voor Aalsmeer, Awans en Beek...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($this->stations as $station) {
            $this->ensureStationExists($station);
            $inserted = $this->insertMeasurements($station);
            $this->info("✓ {$station['location']} ({$station['name']}): {$inserted} metingen ingevoegd.");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->info('Klaar!');
    }

    private function ensureStationExists(array $s): void
    {
        // Station
        DB::table('station')->updateOrInsert(
            ['name' => $s['name']],
            [
                'longitude' => $s['lon'],
                'latitude'  => $s['lat'],
                'elevation' => $s['elevation'],
            ]
        );

        // Zorg dat het land bestaat
        DB::table('country')->updateOrInsert(
            ['country_code' => $s['country']],
            ['country' => $s['country'] === 'NL' ? 'Netherlands' : 'Belgium']
        );

        // Nearestlocation
        $exists = DB::table('nearestlocation')
            ->where('station_name', $s['name'])
            ->exists();

        if (! $exists) {
            DB::table('nearestlocation')->insert([
                'station_name'          => $s['name'],
                'name'                  => $s['location'],
                'administrative_region1'=> $s['country'] === 'NL' ? 'Noord-Holland' : 'Luik',
                'administrative_region2'=> $s['country'] === 'NL' ? 'Nederland' : 'België',
                'country_code'          => $s['country'],
                'longitude'             => $s['lon'],
                'latitude'              => $s['lat'],
            ]);
        }
    }

    private function insertMeasurements(array $s): int
    {
        $start   = Carbon::now()->subWeeks(2)->startOfDay();
        $end     = Carbon::now();
        $current = $start->copy();
        $count   = 0;
        $batch   = [];

        // Verwijder bestaande data voor deze periode om duplicaten te voorkomen
        DB::table('measurement')
            ->where('station', $s['name'])
            ->where('date', '>=', $start->format('Y-m-d'))
            ->delete();

        while ($current->lte($end)) {
            $date = $current->format('Y-m-d');
            $time = $current->format('H:i:s');

            // Uurvariatie: kouder 's nachts, warmer overdag (piek om 14:00)
            $hourFraction  = $current->hour + ($current->minute / 60);
            $dailyCycle    = sin(M_PI * ($hourFraction - 6) / 12); // -1 tot +1
            $temperature   = $this->vary($s['base_temp'] + $dailyCycle * ($s['temp_range'] / 2), 0.4);

            // Dauwpunt is altijd lager dan temperatuur
            $dewpoint      = $this->vary($temperature - $this->randomBetween(2.0, 5.0), 0.3);

            // Luchtdruk varieert langzaam over de dag
            $pressureShift = sin(2 * M_PI * $hourFraction / 24) * 1.5;
            $pressureStn   = $this->vary($s['base_pres'] + $pressureShift, 0.5);
            $pressureSea   = $this->vary($pressureStn + ($s['elevation'] / 8.5), 0.5);

            // Wind
            $windSpeed     = max(0, $this->vary($s['base_wind'], 0.8));
            $windDir       = (int) (($this->randomBetween(200, 280) + sin($hourFraction) * 20) % 360);

            // Zicht (m) — verminderd bij hoge luchtvochtigheid
            $visibility    = $this->vary(15000, 3000);

            // Neerslag — meestal 0, soms een bui
            $precipitation = (mt_rand(0, 100) < 12) ? round($this->randomBetween(0.1, 3.5), 1) : 0.0;

            // Sneeuw — geen in april
            $snowDepth     = 0.0;

            // Bewolking (0-8 okta)
            $cloudCover    = round($this->randomBetween(1, 7));

            // Weercondities (SYNOP-achtig, 6 tekens)
            $conditions    = $precipitation > 0 ? 'RA    ' : ($cloudCover > 5 ? 'OVC   ' : 'FEW   ');

            $batch[] = [
                'station'               => $s['name'],
                'date'                  => $date,
                'time'                  => $time,
                'temperature'           => round($temperature, 1),
                'dewpoint_temperature'  => round($dewpoint, 1),
                'air_pressure_station'  => round($pressureStn, 1),
                'air_pressure_sea_level'=> round($pressureSea, 1),
                'visibility'            => round($visibility, 0),
                'wind_speed'            => round($windSpeed, 1),
                'percipation'           => $precipitation,
                'snow_depth'            => $snowDepth,
                'conditions'            => $conditions,
                'cloud_cover'           => $cloudCover,
                'wind_direction'        => $windDir,
            ];

            $count++;

            // Elke 200 rijen in één keer invoegen
            if (count($batch) >= 200) {
                DB::table('measurement')->insert($batch);
                $batch = [];
            }

            $current->addMinutes(30);
        }

        // Resterende rijen invoegen
        if (! empty($batch)) {
            DB::table('measurement')->insert($batch);
        }

        return $count;
    }

    private function vary(float $base, float $noise): float
    {
        return $base + ($this->randomBetween(-$noise, $noise));
    }

    private function randomBetween(float $min, float $max): float
    {
        return $min + (mt_rand() / mt_getrandmax()) * ($max - $min);
    }
}
