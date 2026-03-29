<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('name', 100);
            $table->string('description', 256)->nullable();

            $table->foreign('role_id')->references('id')->on('userroles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_tasks');
    }
};
