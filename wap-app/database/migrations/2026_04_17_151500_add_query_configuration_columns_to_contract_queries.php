<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_queries', function (Blueprint $table) {
            if (! Schema::hasColumn('contract_queries', 'measurement_fields')) {
                $table->text('measurement_fields')->nullable()->after('query_text');
            }
            if (! Schema::hasColumn('contract_queries', 'country_codes')) {
                $table->text('country_codes')->nullable()->after('measurement_fields');
            }
            if (! Schema::hasColumn('contract_queries', 'region_codes')) {
                $table->text('region_codes')->nullable()->after('country_codes');
            }
            if (! Schema::hasColumn('contract_queries', 'elevation_min')) {
                $table->decimal('elevation_min', 10, 2)->nullable()->after('region_codes');
            }
            if (! Schema::hasColumn('contract_queries', 'elevation_max')) {
                $table->decimal('elevation_max', 10, 2)->nullable()->after('elevation_min');
            }
            if (! Schema::hasColumn('contract_queries', 'latitude_min')) {
                $table->decimal('latitude_min', 10, 6)->nullable()->after('elevation_max');
            }
            if (! Schema::hasColumn('contract_queries', 'latitude_max')) {
                $table->decimal('latitude_max', 10, 6)->nullable()->after('latitude_min');
            }
            if (! Schema::hasColumn('contract_queries', 'longitude_min')) {
                $table->decimal('longitude_min', 10, 6)->nullable()->after('latitude_max');
            }
            if (! Schema::hasColumn('contract_queries', 'longitude_max')) {
                $table->decimal('longitude_max', 10, 6)->nullable()->after('longitude_min');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contract_queries', function (Blueprint $table) {
            $drop = [];
            foreach (['measurement_fields', 'country_codes', 'region_codes', 'elevation_min', 'elevation_max', 'latitude_min', 'latitude_max', 'longitude_min', 'longitude_max'] as $column) {
                if (Schema::hasColumn('contract_queries', $column)) {
                    $drop[] = $column;
                }
            }
            if ($drop) {
                $table->dropColumn($drop);
            }
        });
    }
};
