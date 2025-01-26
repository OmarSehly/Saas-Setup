<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\Tenant;

class CreateTenantCommand extends Command
{
    protected $signature = 'tenant:create {name} {slug} {database}';
    protected $description = 'Create a new tenant';

    public function handle()
    {
        $name = $this->argument('name');
        $slug = $this->argument('slug');
        $database = $this->argument('database');

        try {
            // Step 1: Create tenant database
            $this->createTenantDatabase($database);
            $this->info("Database '{$database}' created successfully.");

            // Step 2: Add tenant to the master database
            $tenant = Tenant::create([
                'name' => $name,
                'slug' => $slug,
                'database' => $database,
            ]);
            $this->info("Tenant '{$name}' added to the master database.");

            // Step 3: Copy table structures from the main database to the tenant database
            $this->copyTableStructures($database);

            // Step 4: Run tenant-specific migrations
            $tenant->makeCurrent();
            $this->info("Running migrations for tenant '{$name}'...");
            Artisan::call('tenants:migrate');
            $tenant->forgetCurrent();

            $this->info("Tenant '{$name}' created successfully.");
        } catch (\Exception $e) {
            $this->error("An error occurred: " . $e->getMessage());
        }
    }

    /**
     * Create a new tenant database.
     *
     * @param string $database
     * @return void
     */
    private function createTenantDatabase(string $database): void
    {
        try {
            DB::statement("CREATE DATABASE `{$database}`");
        } catch (\Exception $e) {
            throw new \Exception("Failed to create database '{$database}': " . $e->getMessage());
        }
    }

    /**
     * Copy table structures from the main database to the tenant database.
     *
     * @param string $database
     * @return void
     */
    private function copyTableStructures(string $database): void
    {
        // Tables to exclude from the tenant database
        $excludedTables = ['failed_jobs', 'password_reset_tokens'];

        // Get all tables from the main database
        $tables = DB::connection('mysql')->select('SHOW TABLES');

        // Switch to the tenant database
        Config::set('database.connections.tenant.database', $database);
        DB::purge('tenant');

        foreach ($tables as $table) {
            $tableName = $table->{'Tables_in_' . DB::connection('mysql')->getDatabaseName()};

            // Skip excluded tables
            if (in_array($tableName, $excludedTables)) {
                $this->info("Skipping table '{$tableName}' for tenant database '{$database}'.");
                continue;
            }

            // Copy table structure
            $this->info("Copying table '{$tableName}' to tenant database '{$database}'...");
            DB::statement("CREATE TABLE `{$database}`.`{$tableName}` LIKE `{$tableName}`");
        }

        $this->info("Table structures copied to tenant database '{$database}'.");
    }
}