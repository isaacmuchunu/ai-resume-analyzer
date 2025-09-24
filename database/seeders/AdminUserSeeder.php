<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create the admin user with specified credentials
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('admin123'),
                'role' => 'super_admin',
                'email_verified_at' => now(),
                'profile_data' => [
                    'bio' => 'System Administrator with full access privileges.',
                    'department' => 'IT Administration',
                    'phone' => null,
                    'avatar_url' => null,
                ],
                'preferences' => [
                    'theme' => 'light',
                    'notifications' => [
                        'email' => true,
                        'browser' => true,
                        'system_alerts' => true,
                    ],
                    'dashboard' => [
                        'default_view' => 'admin',
                        'items_per_page' => 25,
                    ],
                ],
            ]
        );

        $this->command->info('Admin user created successfully:');
        $this->command->info('Email: admin@gmail.com');
        $this->command->info('Password: admin123');
        $this->command->info('Role: super_admin');
    }
}