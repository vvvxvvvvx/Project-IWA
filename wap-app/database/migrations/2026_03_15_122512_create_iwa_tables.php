<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('country', function (Blueprint $table) {
            $table->string('country_code', 2)->primary();
            $table->string('country', 45);
        });

        Schema::create('station', function (Blueprint $table) {
            $table->string('name', 10)->primary();
            $table->float('longitude');
            $table->float('latitude');
            $table->float('elevation');
        });

        Schema::create('geolocation', function (Blueprint $table) {
            $table->id();
            $table->string('station_name', 10);
            $table->string('country_code', 2);
            $table->string('island', 100)->nullable();
            $table->string('county', 100)->nullable();
            $table->string('place', 100)->nullable();
            $table->string('hamlet', 100)->nullable();
            $table->string('town', 100)->nullable();
            $table->string('municipality', 100)->nullable();
            $table->string('state_district', 100)->nullable();
            $table->string('administrative', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('village', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('locality', 100)->nullable();
            $table->string('postcode', 100)->nullable();
            $table->string('country', 100)->nullable();
            
            $table->foreign('station_name')->references('name')->on('station');
            $table->foreign('country_code')->references('country_code')->on('country');
        });

        Schema::create('nearestlocation', function (Blueprint $table) {
            $table->id();
            $table->string('station_name', 10);
            $table->string('name', 100)->nullable();
            $table->string('administrative_region1', 100)->nullable();
            $table->string('administrative_region2', 100)->nullable();
            $table->string('country_code', 2);
            $table->float('longitude');
            $table->float('latitude');
            
            $table->foreign('station_name')->references('name')->on('station');
            $table->foreign('country_code')->references('country_code')->on('country');
        });

        Schema::create('measurement', function (Blueprint $table) {
            $table->id();
            $table->string('station', 10);
            $table->date('date');
            $table->time('time');
            $table->float('temperature')->nullable();
            $table->float('dewpoint_temperature')->nullable();
            $table->float('air_pressure_station')->nullable();
            $table->float('air_pressure_sea_level')->nullable();
            $table->float('visibility')->nullable();
            $table->float('wind_speed')->nullable();
            $table->float('percipation')->nullable(); // Kept original spelling from SQL
            $table->float('snow_depth')->nullable();
            $table->string('conditions', 6)->nullable();
            $table->float('cloud_cover')->nullable();
            $table->integer('wind_direction')->nullable();
            
            $table->foreign('station')->references('name')->on('station');
        });

        Schema::create('original_measurement', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('corrected_measurement');
            $table->string('missing_field', 32)->nullable();
            $table->float('inavlid_temperature')->nullable(); // Kept original spelling from SQL
            
            $table->foreign('corrected_measurement')->references('id')->on('measurement');
        });

        Schema::create('userroles', function (Blueprint $table) {
            $table->id();
            $table->string('role', 45);
            $table->string('description', 256)->nullable();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('first_name', 45)->nullable();
            $table->string('initials', 12)->nullable();
            $table->string('prefix', 10)->nullable();
            $table->string('email', 100);
            $table->string('employee_code', 10)->nullable();
            $table->unsignedBigInteger('user_role');
            $table->string('password', 256);
            
            $table->foreign('user_role')->references('id')->on('userroles');
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('city', 100)->nullable();
            $table->string('street', 100)->nullable();
            $table->integer('number')->nullable();
            $table->string('number_additional', 15)->nullable();
            $table->string('zip_code', 15)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('email', 100)->nullable();
            
            $table->foreign('country')->references('country_code')->on('country');
        });

        Schema::create('relations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('first_name', 45)->nullable();
            $table->string('initials', 12)->nullable();
            $table->string('prefix', 10)->nullable();
            $table->unsignedBigInteger('company');
            $table->string('function', 45)->nullable();
            $table->string('title', 45)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('phone', 25)->nullable();
            
            $table->foreign('company')->references('id')->on('companies');
        });

        Schema::create('subscription_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 45);
            $table->string('description', 256)->nullable();
            $table->integer('nr_stations')->nullable();
            $table->integer('frequency_in_hours')->nullable();
            $table->integer('frequency_in_days')->nullable();
            $table->tinyInteger('continuous')->default(0)->nullable();
            $table->float('price_per_station')->nullable();
            $table->date('valid_through')->nullable();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company');
            $table->unsignedBigInteger('type');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->float('price')->nullable();
            $table->string('notes', 256)->nullable();
            $table->string('identifier', 45)->unique()->nullable();
            $table->string('token', 100)->nullable();
            
            $table->foreign('company')->references('id')->on('companies');
            $table->foreign('type')->references('id')->on('subscription_types');
        });

        Schema::create('subscription_station', function (Blueprint $table) {
            $table->unsignedBigInteger('subscription');
            $table->string('station', 10);
            
            $table->primary(['subscription', 'station']);
            $table->foreign('subscription')->references('id')->on('subscriptions');
            $table->foreign('station')->references('name')->on('station');
        });

        Schema::create('endpoint_activity', function (Blueprint $table) {
            $table->id();
            $table->string('identifier', 45)->nullable();
            $table->string('endpoint_used', 256)->nullable();
            $table->integer('files_downloaded')->nullable();
            $table->date('activity_date')->nullable();
            $table->time('activity_time')->nullable();
            $table->tinyInteger('authorized')->default(0)->nullable();
            $table->integer('data_transferred')->nullable();
            
            // Because identifier is UNIQUE, we can reference it.
            $table->foreign('identifier')->references('identifier')->on('subscriptions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endpoint_activity');
        Schema::dropIfExists('subscription_station');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_types');
        Schema::dropIfExists('relations');
        Schema::dropIfExists('users');
        Schema::dropIfExists('userroles');
        Schema::dropIfExists('original_measurement');
        Schema::dropIfExists('measurement');
        Schema::dropIfExists('nearestlocation');
        Schema::dropIfExists('geolocation');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('station');
        Schema::dropIfExists('country');
    }
};
