<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contract_authorized_users') && ! Schema::hasColumn('contract_authorized_users', 'contract_id')) {
            Schema::table('contract_authorized_users', function (Blueprint $table) {
                $table->unsignedBigInteger('contract_id')->nullable()->after('subscription_id');
                $table->index('contract_id');
            });
        }

        if (Schema::hasTable('contract_queries') && ! Schema::hasColumn('contract_queries', 'contract_id')) {
            Schema::table('contract_queries', function (Blueprint $table) {
                $table->unsignedBigInteger('contract_id')->nullable()->after('subscription_id');
                $table->index('contract_id');
            });
        }

        if (Schema::hasTable('contract_role_permissions') && ! Schema::hasColumn('contract_role_permissions', 'contract_id')) {
            Schema::table('contract_role_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('contract_id')->nullable()->after('subscription_id');
                $table->index('contract_id');
            });
        }

        if (Schema::hasTable('contract_authorized_users')) {
            Schema::table('contract_authorized_users', function (Blueprint $table) {
                if (! Schema::hasColumn('contract_authorized_users', 'subscription_id')) {
                    $table->unsignedBigInteger('subscription_id')->nullable();
                }
            });
        }

        foreach (['contract_authorized_users', 'contract_queries', 'contract_role_permissions'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'subscription_id')) {
                DB::statement("ALTER TABLE {$tableName} MODIFY subscription_id BIGINT UNSIGNED NULL");
            }
        }


        if (Schema::hasTable('contract_queries')) {
            Schema::table('contract_queries', function (Blueprint $table) {
                if (! Schema::hasColumn('contract_queries', 'subscription_id')) {
                    $table->unsignedBigInteger('subscription_id')->nullable();
                }
            });
        }

        if (Schema::hasTable('contract_role_permissions')) {
            Schema::table('contract_role_permissions', function (Blueprint $table) {
                if (! Schema::hasColumn('contract_role_permissions', 'subscription_id')) {
                    $table->unsignedBigInteger('subscription_id')->nullable();
                }
            });
        }

        if (Schema::hasTable('contract_authorized_users')) {
            $indexes = collect(DB::select("SHOW INDEX FROM contract_authorized_users WHERE Key_name = 'contract_user_contract_email_unique'"));
            if ($indexes->isEmpty()) {
                Schema::table('contract_authorized_users', function (Blueprint $table) {
                    $table->unique(['contract_id', 'email'], 'contract_user_contract_email_unique');
                });
            }
        }

        if (Schema::hasTable('contract_role_permissions')) {
            $indexes = collect(DB::select("SHOW INDEX FROM contract_role_permissions WHERE Key_name = 'contract_role_permissions_contract_role_unique'"));
            if ($indexes->isEmpty()) {
                Schema::table('contract_role_permissions', function (Blueprint $table) {
                    $table->unique(['contract_id', 'role_label'], 'contract_role_permissions_contract_role_unique');
                });
            }
        }

        if (Schema::hasTable('contract_authorized_users')) {
            $foreignKeys = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contract_authorized_users' AND COLUMN_NAME = 'contract_id' AND REFERENCED_TABLE_NAME = 'contracts'"));
            if ($foreignKeys->isEmpty()) {
                Schema::table('contract_authorized_users', function (Blueprint $table) {
                    $table->foreign('contract_id')->references('id')->on('contracts')->nullOnDelete();
                });
            }
        }

        if (Schema::hasTable('contract_queries')) {
            $foreignKeys = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contract_queries' AND COLUMN_NAME = 'contract_id' AND REFERENCED_TABLE_NAME = 'contracts'"));
            if ($foreignKeys->isEmpty()) {
                Schema::table('contract_queries', function (Blueprint $table) {
                    $table->foreign('contract_id')->references('id')->on('contracts')->nullOnDelete();
                });
            }
        }

        if (Schema::hasTable('contract_role_permissions')) {
            $foreignKeys = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contract_role_permissions' AND COLUMN_NAME = 'contract_id' AND REFERENCED_TABLE_NAME = 'contracts'"));
            if ($foreignKeys->isEmpty()) {
                Schema::table('contract_role_permissions', function (Blueprint $table) {
                    $table->foreign('contract_id')->references('id')->on('contracts')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ([
            ['table' => 'contract_role_permissions', 'unique' => 'contract_role_permissions_contract_role_unique'],
            ['table' => 'contract_authorized_users', 'unique' => 'contract_user_contract_email_unique'],
        ] as $item) {
            if (Schema::hasTable($item['table'])) {
                Schema::table($item['table'], function (Blueprint $table) use ($item) {
                    try { $table->dropUnique($item['unique']); } catch (Throwable $e) {}
                });
            }
        }

        foreach (['contract_authorized_users', 'contract_queries', 'contract_role_permissions'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'contract_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    try { $table->dropForeign(['contract_id']); } catch (Throwable $e) {}
                    try { $table->dropIndex(['contract_id']); } catch (Throwable $e) {}
                    $table->dropColumn('contract_id');
                });
            }
        }
    }
};
