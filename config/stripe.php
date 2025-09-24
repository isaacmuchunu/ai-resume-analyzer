<?php

return [
    'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    'prices' => [
        'starter_monthly' => env('STRIPE_PRICE_STARTER_MONTHLY'),
        'professional_monthly' => env('STRIPE_PRICE_PROFESSIONAL_MONTHLY'),
        'enterprise_monthly' => env('STRIPE_PRICE_ENTERPRISE_MONTHLY'),
    ],

    'enabled' => env('ENABLE_STRIPE_PAYMENTS', false),
];