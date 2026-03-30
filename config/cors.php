<?php

/**
 * CORS Configuration
 *
 * For development, this allows all origins.
 * IMPORTANT: For production, restrict 'allowed_origins' to your frontend domain only,
 * e.g. ['https://app.yourcompany.com']
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // For production, change this to your actual frontend domain:
    // 'allowed_origins' => ['https://app.yourcompany.com'],
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Authorization'],

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    | Token-based auth (Sanctum Bearer tokens) does NOT require credentials.
    | Set to true only if using cookie-based Sanctum SPA authentication.
    */
    'supports_credentials' => false,

    'max_age' => 0,
];
