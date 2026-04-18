<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('contract_authorized_users')) {
            Schema::create('contract_authorized_users', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('subscription_id');
                $table->string('name', 100);
                $table->string('email', 100);
                $table->string('role_label', 100)->nullable();
                $table->string('status', 50)->default('Actief');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
                $table->unique(['subscription_id', 'email'], 'contract_user_subscription_email_unique');
            });
        }

        if (!Schema::hasTable('contract_queries')) {
            Schema::create('contract_queries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('subscription_id');
                $table->string('name', 150);
                $table->string('endpoint', 255)->nullable();
                $table->text('query_text')->nullable();
                $table->string('format', 50)->default('JSON');
                $table->string('status', 50)->default('Actief');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_queries');
        Schema::dropIfExists('contract_authorized_users');
    }
};
