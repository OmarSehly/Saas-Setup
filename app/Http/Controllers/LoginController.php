<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;
use App\Models\User;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        // Get tenantSlug from the route
        $tenantSlug = $request->route('tenantSlug');

        // Fetch the tenant from the main database
        $tenant = Tenant::where('slug', $tenantSlug)->first();

        if (!$tenant) {
            return response()->json(['message' => 'Tenant not found'], 404);
        }

        // Switch to the tenant's database
        $this->switchToTenantDatabase($tenant);

        // Authenticate the user against the tenant's database
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('TenantAppToken')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user,
            ]);
        }

        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    /**
     * Switch to the tenant's database.
     *
     * @param Tenant $tenant
     */
    protected function switchToTenantDatabase(Tenant $tenant)
    {
        // Configure the tenant's database connection
        Config::set('database.connections.tenant', [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => $tenant->database, // Use the tenant's database name
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        // Set the default database connection to the tenant's database
        DB::purge('tenant');
        DB::setDefaultConnection('tenant');
    }
}