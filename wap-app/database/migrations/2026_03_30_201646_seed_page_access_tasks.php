<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $tasks = [
        'view_stations'          => 'Stations pagina bekijken',
        'view_subscriptions'     => 'Abonnementen pagina bekijken',
        'view_subscription_types'=> 'Aanbod pagina bekijken',
        'view_contracts'         => 'Contracten pagina bekijken',
        'view_companies'         => 'Bedrijven pagina bekijken',
    ];

    private array $roleTaskMap = [
        'Technisch medewerker'    => ['view_stations'],
        'Technisch onderzoeker'   => ['view_stations'],
        'Technisch beheerder'     => ['view_stations'],
        'Commercieel medewerker'  => ['view_subscriptions', 'view_subscription_types', 'view_contracts', 'view_companies'],
        'Administratief medewerker'=> ['view_subscriptions', 'view_contracts', 'view_companies'],
        'Administrator'           => ['view_stations', 'view_subscriptions', 'view_subscription_types', 'view_contracts', 'view_companies'],
    ];

    public function up(): void
    {
        $roles = DB::table('userroles')->pluck('id', 'role');

        foreach ($this->roleTaskMap as $roleName => $taskNames) {
            $roleId = $roles[$roleName] ?? null;
            if (!$roleId) continue;

            foreach ($taskNames as $taskName) {
                DB::table('role_tasks')->insertOrIgnore([
                    'role_id'     => $roleId,
                    'name'        => $taskName,
                    'description' => $this->tasks[$taskName],
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('role_tasks')->whereIn('name', array_keys($this->tasks))->delete();
    }
};
