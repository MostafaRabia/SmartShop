<?php

declare(strict_types=1);

return [
    'api_key' => env('GEMINI_API_KEY', ''),
    'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
    'endpoint' => env('GEMINI_API_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'),
    'timeout' => env('GEMINI_TIMEOUT', 8),
    // Cache the projected product catalog to reduce prompt size churn and DB calls
    'catalog_cache_seconds' => env('GEMINI_CATALOG_CACHE_SECONDS', 120),
];
