<?php

namespace App\TenantFinders;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;

class PathTenantFinder extends TenantFinder
{   
    use UsesMultitenancyConfig;

    // public function findForRequest(Request $request): ?Tenant
    // {
    //     // Extract the tenant slug from the first URL segment
    //     $tenantSlug = $request->segment(1);

    //     if (!$tenantSlug) {
    //         return null; // No tenant slug in the request
    //     }

    //     // Attempt to find the tenant by the slug
    //     return Tenant::where('slug', $tenantSlug)->first();
    // }
    public function findForRequest(Request $request): ?Tenant
    {
        $tenantSlug = $request->segment(1);

        return Tenant::where('slug', $tenantSlug)->first();
    }
}
