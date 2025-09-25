<?php

namespace App\Models;

use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];

    public function getRouteKeyName()
    {
        return 'id';
    }

    public function makeCurrent(): static
    {
        if ($this->isCurrent()) {
            return $this;
        }

        app()->forgetInstance('currentTenant');
        app()->instance('currentTenant', $this);

        return $this;
    }

    public function forget(): static
    {
        app()->forgetInstance('currentTenant');

        return $this;
    }

    public function isCurrent(): bool
    {
        if (!app()->bound('currentTenant')) {
            return false;
        }

        $currentTenant = app('currentTenant');
        return $currentTenant && $currentTenant->getKey() === $this->getKey();
    }

    public function getDatabaseName(): string
    {
        return "tenant_{$this->id}";
    }

    public function createDatabase(): void
    {
        $databaseName = $this->getDatabaseName();

        \DB::statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}`");
    }

    public function deleteDatabase(): void
    {
        $databaseName = $this->getDatabaseName();

        \DB::statement("DROP DATABASE IF EXISTS `{$databaseName}`");
    }

    public function getConnectionName(): ?string
    {
        return config('multitenancy.tenant_database_connection_name');
    }

    public function switchToDatabase(): void
    {
        $connectionName = $this->getConnectionName() ?? config('database.default');

        // For SQLite, use table prefix instead of separate databases
        if (config("database.connections.{$connectionName}.driver") === 'sqlite') {
            $prefix = "tenant_{$this->id}_";
            config([
                "database.connections.{$connectionName}.prefix" => $prefix,
            ]);
        } else {
            config([
                "database.connections.{$connectionName}.database" => $this->getDatabaseName(),
            ]);
        }

        \DB::purge($connectionName);
        \DB::reconnect($connectionName);
    }

    // Tenant specific attributes
    public function isActive(): bool
    {
        return $this->is_active ?? true;
    }

    public function getPlan(): string
    {
        return $this->plan ?? 'starter';
    }

    public function getBrandingData(): array
    {
        return $this->data['branding'] ?? [];
    }

    public function getFeatures(): array
    {
        $planFeatures = [
            'starter' => [
                'max_users' => 5,
                'max_resumes_per_month' => 100,
                'advanced_analysis' => false,
                'api_access' => false,
            ],
            'professional' => [
                'max_users' => 25,
                'max_resumes_per_month' => 500,
                'advanced_analysis' => true,
                'api_access' => true,
            ],
            'enterprise' => [
                'max_users' => -1, // unlimited
                'max_resumes_per_month' => -1, // unlimited
                'advanced_analysis' => true,
                'api_access' => true,
                'white_label' => true,
                'priority_support' => true,
            ],
        ];

        return array_merge(
            $planFeatures[$this->getPlan()] ?? $planFeatures['starter'],
            $this->data['custom_features'] ?? []
        );
    }
}
