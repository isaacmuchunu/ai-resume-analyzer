<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo tenants
        $tenants = [
            [
                'id' => 'demo-corp',
                'name' => 'Demo Corporation',
                'domain' => 'demo.resumeanalyzer.com',
                'subdomain' => 'demo',
                'plan' => 'enterprise',
                'data' => [
                    'branding' => [
                        'primary_color' => '#1f2937',
                        'secondary_color' => '#3b82f6',
                        'logo' => null,
                    ],
                    'features' => [
                        'advanced_analysis' => true,
                        'api_access' => true,
                        'white_label' => true,
                        'priority_support' => true,
                    ],
                    'limits' => [
                        'max_users' => -1, // unlimited
                        'max_resumes_per_month' => -1, // unlimited
                    ],
                ],
            ],
            [
                'id' => 'startup-inc',
                'name' => 'Startup Inc',
                'domain' => null,
                'subdomain' => 'startup',
                'plan' => 'professional',
                'data' => [
                    'branding' => [
                        'primary_color' => '#059669',
                        'secondary_color' => '#10b981',
                        'logo' => null,
                    ],
                    'features' => [
                        'advanced_analysis' => true,
                        'api_access' => true,
                        'white_label' => false,
                        'priority_support' => false,
                    ],
                    'limits' => [
                        'max_users' => 25,
                        'max_resumes_per_month' => 500,
                    ],
                ],
            ],
            [
                'id' => 'university-edu',
                'name' => 'University Career Center',
                'domain' => null,
                'subdomain' => 'university',
                'plan' => 'professional',
                'data' => [
                    'branding' => [
                        'primary_color' => '#7c3aed',
                        'secondary_color' => '#8b5cf6',
                        'logo' => null,
                    ],
                    'features' => [
                        'advanced_analysis' => true,
                        'api_access' => false,
                        'white_label' => false,
                        'priority_support' => false,
                    ],
                    'limits' => [
                        'max_users' => 100,
                        'max_resumes_per_month' => 1000,
                    ],
                ],
            ],
        ];

        foreach ($tenants as $tenantData) {
            Tenant::create($tenantData);
        }
    }
}