<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Path to the dump files from Downloads
        $dumpPath = 'C:\Users\Gino\Downloads\IWADBDump';

        // Define the exact order of tables to respect foreign key constraints
        $filesToRun = [
            'project_web_country.sql',
            'project_web_station.sql',
            'project_web_geolocation.sql',
            'project_web_nearestlocation.sql',
            'project_web_measurement.sql',
            'project_web_original_measurement.sql',
            'project_web_userroles.sql',
            'project_web_users.sql',
            'project_web_companies.sql',
            'project_web_relations.sql',
            'project_web_subscription_types.sql',
            'project_web_subscriptions.sql',
            'project_web_subscription_station.sql',
            'project_web_endpoint_activity.sql',
        ];

        // Disable foreign key checks while inserting to ensure raw INSERT statements don't fail cross-table
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($filesToRun as $file) {
            $filePath = $dumpPath . '/' . $file;
            if (File::exists($filePath)) {
                $this->command->info('Seeding from: ' . $file);


                // Read file contents
                $sql = File::get($filePath);


                // Since these are complete mysqldumps, we extract ONLY the insert statements
                // to prevent DROP TABLE and CREATE TABLE commands from destroying our new migrations.
                preg_match_all('/INSERT INTO.*?VALUES.*?;\r?\n/is', $sql, $matches);


                if (!empty($matches[0])) {
                    foreach ($matches[0] as $insertQuery) {
                        try {
                            DB::statement($insertQuery);
                        } catch (\Exception $e) {
                            $this->command->error("Failed to insert chunk from $file: " . $e->getMessage());
                        }
                    }
                }
            } else {
                $this->command->warn('File not found: ' . $file);
            }
        }

        // Re-hash all user passwords to Bcrypt (dump may contain non-Bcrypt hashes)
        $users = DB::table('users')->get(['id', 'password']);
        foreach ($users as $user) {
            if (!str_starts_with($user->password, '$2y$') && !str_starts_with($user->password, '$2b$')) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['password' => bcrypt('password')]);
            }
        }
        $this->command->info('Passwords re-hashed to Bcrypt.');

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->command->info('Database seeding completed successfully.');
    }
}
