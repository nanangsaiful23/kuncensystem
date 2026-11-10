<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name & Store Details
    |--------------------------------------------------------------------------
    */

    'name' => env('APP_NAME', 'Laravel'),

    'offline_address' => env('APP_OFFADD', 'Getasan'),

    'phone_number' => env('APP_PHONE', '0823-2292-2654'),

    'app_color' => env('APP_COLOR', ''),

    // Kept as-is from the Laravel 6 config. Better: move it to .env (APP_SECRET).
    'secret' => env('APP_SECRET', 'fEJdsaOwdnUL2K8y94fmJ8c9jsa4stx6plDmL62!'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment / Debug / URL
    |--------------------------------------------------------------------------
    */

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | Timezone & Locale
    |--------------------------------------------------------------------------
    */

    'timezone' => 'Asia/Jakarta',

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    // Service providers are now listed in bootstrap/providers.php, and
    // package providers / facade aliases (Form, Html, QrCode, Excel) are
    // auto-discovered. Laravel's default facade aliases (DB, Validator,
    // Storage, ...) are registered automatically.

];
