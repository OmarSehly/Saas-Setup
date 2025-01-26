<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function show($tenantSlug)
    {
        // Ensure you're getting a single tenant
        $tenant = Tenant::where('slug', $tenantSlug)->first();

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        // Access the tenant's properties
        return response()->json([
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            // Other properties
        ]);
    }
}
