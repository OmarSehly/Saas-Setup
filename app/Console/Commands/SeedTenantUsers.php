<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class SeedTenantUsers extends Command
{
    protected $signature = 'tenants:seed-users';
    protected $description = 'Seed users into all tenant databases';

    public function handle()
    {
        // Fetch all tenants from the `tenants` table
        $tenants = DB::connection('mysql')->table('tenants')->get();

        foreach ($tenants as $tenant) {
            $this->info("Seeding users for tenant: {$tenant->name} (Database: {$tenant->database})");

            // Switch to tenant's database
            Config::set('database.connections.tenant.database', $tenant->database);
            DB::purge('tenant');

            try {
                // Insert user data
                DB::connection('tenant')->table('users')->insert([
                    [
                        'name' => 'Admin User for ' . $tenant->name,
                        'email' => 'admin@' . $tenant->slug . '.com',
                        'password' => bcrypt('password'),
                        'tenant_id' => $tenant->id, // Associate the tenant ID
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'name' => 'Test User for ' . $tenant->name,
                        'email' => 'test@' . $tenant->slug . '.com',
                        'password' => bcrypt('password'),
                        'tenant_id' => $tenant->id, // Associate the tenant ID
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]);

                $this->info("Users seeded for tenant: {$tenant->name}");
            } catch (\Exception $e) {
                $this->error("Failed to seed users for tenant: {$tenant->name}. Error: {$e->getMessage()}");
            }
        }

        $this->info('Seeding completed for all tenants.');
    }
}
