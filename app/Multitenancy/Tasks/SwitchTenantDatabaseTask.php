<?php

namespace App\Multitenancy\Tasks;

use App\Models\Tenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class SwitchTenantDatabaseTask implements SwitchTenantTask
{
    public function makeCurrent(Tenant $tenant): void
    {
        $tenant->switchToDatabase();
    }

    public function forgetCurrent(): void
    {
        $defaultConnectionName = config('database.default');
        $originalDatabaseName = config("database.connections.{$defaultConnectionName}.database");

        config([
            "database.connections.{$defaultConnectionName}.database" => $originalDatabaseName,
        ]);

        \DB::purge($defaultConnectionName);
        \DB::reconnect($defaultConnectionName);
    }
}