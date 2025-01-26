<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\Tenant;

class TenantsMigrateCommand extends Command
{
    protected $signature = 'tenants:migrate';
    protected $description = 'Run migrations for all tenant databases by syncing with the master database schema';

    public function handle()
    {
        $this->info("Starting migration for all tenants...");

        try {
            // Fetch all tenants
            $tenants = Tenant::all();

            // Get master database name
            $masterDatabase = config('database.connections.mysql.database');

            // Excluded tables
            $excludedTables = ['failed_jobs', 'password_reset_tokens'];

            foreach ($tenants as $tenant) {
                $this->info("Migrating tenant: {$tenant->name}");

                // Switch to tenant database
                Config::set('database.connections.tenant.database', $tenant->database);
                DB::purge('tenant');
                DB::reconnect('tenant');

                // Compare schemas and sync tenant database with master database
                $this->syncTenantDatabase($masterDatabase, $tenant->database, $excludedTables);
            }

            $this->info("All tenant migrations completed successfully.");
        } catch (\Exception $e) {
            $this->error("An error occurred during tenant migrations: " . $e->getMessage());
        }
    }

    private function syncTenantDatabase(string $masterDatabase, string $tenantDatabase, array $excludedTables): void
    {
        // Fetch all tables in the master database
        $masterTables = DB::connection('mysql')->select("SHOW TABLES");
        $masterTables = array_map(function ($table) use ($masterDatabase) {
            return $table->{'Tables_in_' . $masterDatabase};
        }, $masterTables);
    
        // Fetch all tables in the tenant database
        $tenantTables = DB::connection('tenant')->select("SHOW TABLES");
        $tenantTables = array_map(function ($table) use ($tenantDatabase) {
            return $table->{'Tables_in_' . $tenantDatabase};
        }, $tenantTables);
    
        // Handle table additions and schema synchronization
        foreach ($masterTables as $table) {
            // Skip excluded tables
            if (in_array($table, $excludedTables)) {
                $this->info("Skipping table '{$table}' for tenant database '{$tenantDatabase}'.");
                continue;
            }
    
            if (!in_array($table, $tenantTables)) {
                // Table does not exist in tenant database, create it
                $this->info("Creating table '{$table}' in tenant database '{$tenantDatabase}'...");
                DB::connection('tenant')->statement("CREATE TABLE `{$tenantDatabase}`.`{$table}` LIKE `{$masterDatabase}`.`{$table}`");
            } else {
                // Table exists, check for schema differences
                $this->info("Checking schema differences for table '{$table}' in tenant database '{$tenantDatabase}'...");
                $this->syncTableSchema($masterDatabase, $tenantDatabase, $table);
            }
        }
    
        // Handle table removals
        foreach ($tenantTables as $table) {
            if (!in_array($table, $masterTables) && !in_array($table, $excludedTables)) {
                // Table exists in tenant database but not in master database, drop it
                $this->info("Dropping table '{$table}' from tenant database '{$tenantDatabase}'...");
                DB::connection('tenant')->statement("DROP TABLE `{$table}`");
            }
        }
    }
    
    private function syncTableSchema(string $masterDatabase, string $tenantDatabase, string $table): void
    {
        // Get the structure of the table from the master and tenant databases
        $masterColumns = DB::connection('mysql')->select("SHOW COLUMNS FROM `{$masterDatabase}`.`{$table}`");
        $tenantColumns = DB::connection('tenant')->select("SHOW COLUMNS FROM `{$tenantDatabase}`.`{$table}`");
    
        $masterColumnNames = array_map(fn ($col) => $col->Field, $masterColumns);
        $tenantColumnNames = array_map(fn ($col) => $col->Field, $tenantColumns);
    
        // Find columns that are in the master but not in the tenant
        $missingColumns = array_diff($masterColumnNames, $tenantColumnNames);
        foreach ($missingColumns as $column) {
            // Get column definition from the master database
            $columnDefinition = DB::connection('mysql')->selectOne("SHOW CREATE TABLE `{$masterDatabase}`.`{$table}`");
            preg_match("/`$column` .*?,/", $columnDefinition->{'Create Table'}, $matches);
    
            if (!empty($matches)) {
                $columnDefinition = trim($matches[0], ",");
                DB::connection('tenant')->statement("ALTER TABLE `{$table}` ADD {$columnDefinition}");
                $this->info("Added column '{$column}' to table '{$table}' in tenant database '{$tenantDatabase}'.");
            }
        }
    
        // Find columns that are in the tenant but not in the master
        $extraColumns = array_diff($tenantColumnNames, $masterColumnNames);
        foreach ($extraColumns as $column) {
            // Drop the extra column from the tenant database
            DB::connection('tenant')->statement("ALTER TABLE `{$table}` DROP COLUMN `{$column}`");
            $this->info("Dropped column '{$column}' from table '{$table}' in tenant database '{$tenantDatabase}'.");
        }
    }
    
}
