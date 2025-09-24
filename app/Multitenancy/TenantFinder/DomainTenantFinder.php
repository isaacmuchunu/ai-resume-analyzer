<?php

namespace App\Multitenancy\TenantFinder;

use App\Models\Tenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class DomainTenantFinder extends TenantFinder
{
    public function findForRequest($request): ?Tenant
    {
        $host = $request->getHost();

        // Check for subdomain
        if (str_contains($host, '.')) {
            $subdomain = explode('.', $host)[0];

            if ($subdomain !== 'www') {
                $tenant = Tenant::where('subdomain', $subdomain)->first();
                if ($tenant) {
                    return $tenant;
                }
            }
        }

        // Check for custom domain
        $tenant = Tenant::where('domain', $host)->first();
        if ($tenant) {
            return $tenant;
        }

        // Fallback for development - check for tenant parameter
        if ($request->has('tenant')) {
            return Tenant::find($request->get('tenant'));
        }

        return null;
    }
}