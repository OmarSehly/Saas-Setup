<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
class DashboardController extends Controller
{
    public function index($tenantSlug)
    {
        // Find the tenant by slug
        $tenant = Tenant::where('slug', $tenantSlug)->first();
        Log::info('Current Tenant:', ['tenant' => $tenant]);
    
        if (!$tenant) {
            return response()->json([
                'message' => 'Tenant not found',
            ], 404);
        }
    
        return response()->json([
            'message' => 'Welcome to the tenant dashboard',
            'tenant' => $tenant->name, // Access the name property of the single tenant
        ]);
    }
}
