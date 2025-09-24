<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run admin user seeder first
        $this->call(AdminUserSeeder::class);

        // Create a test user for development
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'email_verified_at' => now(),
                'profile_data' => [
                    'bio' => 'Test user for development purposes.',
                    'department' => null,
                    'phone' => null,
                    'avatar_url' => null,
                ],
                'preferences' => [
                    'theme' => 'light',
                    'notifications' => [
                        'email' => true,
                        'browser' => true,
                        'system_alerts' => false,
                    ],
                    'dashboard' => [
                        'default_view' => 'user',
                        'items_per_page' => 10,
                    ],
                ],
            ]
        );
    }
}
