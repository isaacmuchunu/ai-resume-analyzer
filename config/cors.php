<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'notifications/api',
        'analytics/api',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Add your frontend domains here
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'https://yourdomain.com',
        // Allow tenant subdomains in production
    ],

    'allowed_origins_patterns' => [
        // Allow tenant subdomains dynamically
        '/^https?:\/\/([a-z0-9-]+\.)?yourdomain\.com$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-Total-Count',
        'X-Current-Page',
        'X-Per-Page',
    ],

    'max_age' => 86400,

    'supports_credentials' => true,

];