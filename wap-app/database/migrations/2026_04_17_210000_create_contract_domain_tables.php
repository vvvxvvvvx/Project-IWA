<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contract_types')) {
            Schema::create('contract_types', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 50)->unique();
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        $types = [
            ['slug' => 'data_contract', 'name' => 'Datacontract', 'description' => 'Contract voor data-afname, querycriteria en meetveldselectie.'],
            ['slug' => 'service_contract', 'name' => 'Servicecontract', 'description' => 'Contract voor toegang, beheer en dienstverlening rond de levering.'],
        ];

        foreach ($types as $type) {
            DB::table('contract_types')->updateOrInsert(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        if (! Schema::hasTable('contracts')) {
            Schema::create('contracts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('contract_type_id');
                $table->string('identifier', 45)->unique();
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->decimal('price', 10, 2)->nullable();
                $table->string('status', 50)->default('Concept');
                $table->string('api_token', 100)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->foreign('contract_type_id')->references('id')->on('contract_types')->restrictOnDelete();
                $table->index(['company_id', 'contract_type_id']);
            });
        }

        if (! Schema::hasTable('contract_endpoint_activity')) {
            Schema::create('contract_endpoint_activity', function (Blueprint $table) {
                $table->id();
                $table->string('identifier', 45);
                $table->string('endpoint_used', 256);
                $table->integer('files_downloaded')->default(0);
                $table->date('activity_date');
                $table->time('activity_time');
                $table->boolean('authorized')->nullable();
                $table->integer('data_transferred')->nullable();
                $table->timestamps();

                $table->index('identifier');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_endpoint_activity');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('contract_types');
    }
};
