<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $adminRoleId = DB::table('userroles')
            ->where('role', 'Administrator')
            ->value('id');

        if ($adminRoleId) {
            DB::table('role_tasks')->insertOrIgnore([
                ['role_id' => $adminRoleId, 'name' => 'manage_users', 'description' => 'Gebruikers beheren'],
                ['role_id' => $adminRoleId, 'name' => 'manage_roles', 'description' => 'Rollen en taken beheren'],
            ]);
        }
    }

    public function down(): void
    {
        DB::table('role_tasks')->whereIn('name', ['manage_users', 'manage_roles'])->delete();
    }
};
