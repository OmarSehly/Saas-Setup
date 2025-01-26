<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class TenantsMigrateCommand extends Command
{
    protected $signature = 'tenants:migrate {--fresh : Drop all tables and re-run all migrations}';
    protected $description = 'Run migrations for all tenants';

    public function handle()
    {
        // Fetch all tenants from the `tenants` table
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $this->info("Running migrations for tenant: {$tenant->name} (Database: {$tenant->database})");

            // Switch to the tenant's database
            Config::set('database.connections.tenant.database', $tenant->database);
            DB::purge('tenant');

            // Determine the migration command based on the --fresh option
            $command = $this->option('fresh') ? 'migrate:fresh' : 'migrate';

            // Run migrations
            $this->call($command, [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenants',
                '--force' => true,
            ]);
        }

        $this->info('Migrations completed for all tenants.');
    }
}