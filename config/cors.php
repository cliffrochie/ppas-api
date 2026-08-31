<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS) Configuration
|--------------------------------------------------------------------------
|
| ICT blueprint (api-contract.md / security.md / devops.md): allowed origins
| MUST be restricted to the frontend domain only — never "*". The canonical
| dev origin is http://localhost:5173; production is the deployed frontend
| URL. Both are supplied via the CORS_ALLOWED_ORIGINS env var (comma
| separated), which must be documented in .env.example.
|
*/

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173')),
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
