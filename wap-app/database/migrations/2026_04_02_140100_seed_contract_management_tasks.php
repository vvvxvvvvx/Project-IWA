<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $functioneelBeheerderId = DB::table('userroles')->where('role', 'Functioneel beheerder')->value('id');
        if (!$functioneelBeheerderId) {
            $functioneelBeheerderId = DB::table('userroles')->insertGetId([
                'role' => 'Functioneel beheerder',
                'description' => 'Beheert contracten, gebruikers, queries en toegang binnen de webapplicatie.',
            ]);
        }

        $taskDescriptions = [
            'manage_contracts' => 'Contracten volledig beheren',
            'manage_contract_users' => 'Geautoriseerde contractgebruikers beheren',
            'manage_contract_queries' => 'Contractqueries beheren',
        ];

        $roles = DB::table('userroles')->pluck('id', 'role');
        $map = [
            'Commercieel medewerker' => array_keys($taskDescriptions),
            'Functioneel beheerder' => array_keys($taskDescriptions),
            'Administrator' => array_keys($taskDescriptions),
        ];

        foreach ($map as $roleName => $tasks) {
            $roleId = $roles[$roleName] ?? null;
            if (!$roleId) continue;
            foreach ($tasks as $task) {
                DB::table('role_tasks')->insertOrIgnore([
                    'role_id' => $roleId,
                    'name' => $task,
                    'description' => $taskDescriptions[$task],
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('role_tasks')->whereIn('name', [
            'manage_contracts',
            'manage_contract_users',
            'manage_contract_queries',
        ])->delete();
    }
};
