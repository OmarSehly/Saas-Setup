<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Models\Tenant;
use Exception;
class MigrationController extends Controller
{
    
    public function createTenant(Request $request)
    {
        $name = $request->input('name');
        $slug = $request->input('slug');
        $database = $request->input('database');

        try {
            // Call the Artisan command to create the tenant
            $output = Artisan::call('tenant:create', [
                'name' => $name,
                'slug' => $slug,
                'database' => $database
            ]);

            // Return the output of the command
            return response()->json([
                'message' => 'Tenant created successfully!',
                'output' => $output
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the tenant.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function runMigrations(Request $request)
    {
        // Optionally check for a specific tenant ID or name from the request
        $specificTenant = $request->input('tenant'); // Optional: For targeting a specific tenant
    
        try {
            if ($specificTenant) {
                // Run migrations for a specific tenant
                Artisan::call('tenants:migrate', [
                    '--tenant' => $specificTenant, // Only pass tenant-specific arguments
                ]);
            } else {
                // Run migrations for all tenants
                Artisan::call('tenants:migrate');
            }
    
            return response()->json([
                'message' => 'Migrations executed successfully!',
                'output' => Artisan::output(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Migration failed!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
