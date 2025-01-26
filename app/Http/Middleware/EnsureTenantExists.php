<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Multitenancy\Models\Tenant;

class EnsureTenantExists
{
    public function handle($request, Closure $next)
    {
        // Extract the tenant slug from the URL (path-based identification)
        $tenantSlug = $request->route('tenantSlug');

        // Find the tenant
        $tenant = Tenant::where('slug', $tenantSlug)->first();
        
        if (!$tenant) {
            return response()->json([
                'error' => 'Tenant not found.',
            ], 404);
        }

        // Set the current tenant context
        $tenant->makeCurrent();

        return $next($request);
    }
}
