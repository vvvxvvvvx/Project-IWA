<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contracts')) {
            Schema::table('contracts', function (Blueprint $table) {
                if (! Schema::hasColumn('contracts', 'description')) {
                    $table->string('description', 255)->nullable()->after('identifier');
                }
                if (! Schema::hasColumn('contracts', 'app_url')) {
                    $table->string('app_url', 255)->nullable()->after('end_date');
                }
            });
        }

        if (Schema::hasTable('contract_authorized_users')) {
            Schema::table('contract_authorized_users', function (Blueprint $table) {
                if (! Schema::hasColumn('contract_authorized_users', 'user_identifier')) {
                    $table->string('user_identifier', 100)->nullable()->after('email');
                }
                if (! Schema::hasColumn('contract_authorized_users', 'password_hash')) {
                    $table->string('password_hash', 255)->nullable()->after('user_identifier');
                }
                if (! Schema::hasColumn('contract_authorized_users', 'permission_level')) {
                    $table->string('permission_level', 20)->default('user')->after('password_hash');
                }
                if (! Schema::hasColumn('contract_authorized_users', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('status');
                }
                if (! Schema::hasColumn('contract_authorized_users', 'deleted_at')) {
                    $table->timestamp('deleted_at')->nullable()->after('updated_at');
                }
            });
        }

        if (Schema::hasTable('contract_queries')) {
            Schema::table('contract_queries', function (Blueprint $table) {
                if (! Schema::hasColumn('contract_queries', 'measurement_date_from')) {
                    $table->date('measurement_date_from')->nullable()->after('longitude_max');
                }
                if (! Schema::hasColumn('contract_queries', 'measurement_date_to')) {
                    $table->date('measurement_date_to')->nullable()->after('measurement_date_from');
                }
                if (! Schema::hasColumn('contract_queries', 'temperature_min')) {
                    $table->decimal('temperature_min', 10, 2)->nullable()->after('measurement_date_to');
                }
                if (! Schema::hasColumn('contract_queries', 'temperature_max')) {
                    $table->decimal('temperature_max', 10, 2)->nullable()->after('temperature_min');
                }
            });
        }

        if (! Schema::hasTable('contract_user_sessions')) {
            Schema::create('contract_user_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('contract_user_id');
                $table->unsignedBigInteger('contract_id');
                $table->string('jti', 64)->unique();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_revoked')->default(false);
                $table->timestamps();

                $table->foreign('contract_user_id')->references('id')->on('contract_authorized_users')->cascadeOnDelete();
                $table->foreign('contract_id')->references('id')->on('contracts')->cascadeOnDelete();
                $table->index(['contract_id', 'contract_user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_user_sessions');

        if (Schema::hasTable('contract_queries')) {
            Schema::table('contract_queries', function (Blueprint $table) {
                foreach (['measurement_date_from', 'measurement_date_to', 'temperature_min', 'temperature_max'] as $column) {
                    if (Schema::hasColumn('contract_queries', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('contract_authorized_users')) {
            Schema::table('contract_authorized_users', function (Blueprint $table) {
                foreach (['user_identifier', 'password_hash', 'permission_level', 'is_active', 'deleted_at'] as $column) {
                    if (Schema::hasColumn('contract_authorized_users', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('contracts')) {
            Schema::table('contracts', function (Blueprint $table) {
                foreach (['description', 'app_url'] as $column) {
                    if (Schema::hasColumn('contracts', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
