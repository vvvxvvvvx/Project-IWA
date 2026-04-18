<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contract_role_permissions')) {
            Schema::create('contract_role_permissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('subscription_id');
                $table->string('role_label', 50);
                $table->boolean('can_view_contract')->default(true);
                $table->boolean('can_view_queries')->default(false);
                $table->boolean('can_manage_queries')->default(false);
                $table->boolean('can_manage_authorized_users')->default(false);
                $table->timestamps();

                $table->unique(['subscription_id', 'role_label']);
                $table->foreign('subscription_id')->references('id')->on('subscriptions')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_role_permissions');
    }
};
