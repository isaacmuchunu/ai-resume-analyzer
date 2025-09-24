<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class SetupTenantCommand extends Command
{
    protected $signature = 'tenant:setup {tenant_id} {--create-admin}';
    protected $description = 'Setup a tenant with database and optional admin user';

    public function handle(): void
    {
        $tenantId = $this->argument('tenant_id');
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            $this->error("Tenant '{$tenantId}' not found.");
            return;
        }

        $this->info("Setting up tenant: {$tenant->name}");

        try {
            // Make tenant current and set up context
            $tenant->makeCurrent();
            app()->instance('currentTenant', $tenant);

            // Create tenant database if using separate databases
            if (config('multitenancy.tenant_database_connection_name')) {
                $this->info('Creating tenant database...');
                $tenant->createDatabase();
            }

            // Run tenant migrations
            $this->info('Running tenant migrations...');
            Artisan::call('migrate', [
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);

            $this->info('Tenant database setup completed.');

            // Create admin user if requested
            if ($this->option('create-admin')) {
                $this->createAdminUser($tenant);
            }

            $this->info("Tenant '{$tenant->name}' setup completed successfully!");

        } catch (\Exception $e) {
            $this->error("Error setting up tenant: {$e->getMessage()}");
        } finally {
            // Forget current tenant
            $tenant->forget();
        }
    }

    private function createAdminUser(Tenant $tenant): void
    {
        $this->info('Creating admin user...');

        $email = $this->ask('Admin email address');
        $firstName = $this->ask('First name');
        $lastName = $this->ask('Last name');
        $password = $this->secret('Password');

        $user = User::create([
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'password' => Hash::make($password),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->info("Admin user created: {$user->email}");
    }
}