<?php

namespace App\Multitenancy\Tasks;

use App\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class PrefixCacheTask implements SwitchTenantTask
{
    public function makeCurrent(Tenant $tenant): void
    {
        $originalPrefix = config('cache.prefix');
        $tenantPrefix = $originalPrefix . '_tenant_' . $tenant->id;

        config(['cache.prefix' => $tenantPrefix]);

        app()->forgetInstance('cache');
        app()->forgetInstance('cache.store');
    }

    public function forgetCurrent(): void
    {
        $originalPrefix = config('cache.prefix');

        // Remove tenant prefix if it exists
        if (str_contains($originalPrefix, '_tenant_')) {
            $cleanPrefix = explode('_tenant_', $originalPrefix)[0];
            config(['cache.prefix' => $cleanPrefix]);
        }

        app()->forgetInstance('cache');
        app()->forgetInstance('cache.store');
    }
}