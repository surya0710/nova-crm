<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Product Branding
    |--------------------------------------------------------------------------
    |
    | Centralized display names for the product. Prefer these values (or
    | config('app.name'), which shares APP_NAME) over hardcoded product names
    | in views, mail, and exports. Do not use this for namespaces, package
    | names, database identifiers, or public API header names.
    |
    */

    'product_name' => env('BRAND_PRODUCT_NAME', env('APP_NAME', 'Konnect Nex')),

    'product_short_name' => env('BRAND_SHORT_NAME', 'Konnect'),

    'company_name' => env('BRAND_COMPANY_NAME', 'Konnect Nex'),

    'copyright' => env('BRAND_COPYRIGHT', '© Konnect Nex'),

    'support_email' => env('BRAND_SUPPORT_EMAIL', 'support@example.com'),

    /*
    |--------------------------------------------------------------------------
    | Product logo
    |--------------------------------------------------------------------------
    |
    | Public path (relative to the public directory) used for the product mark
    | on login, marketing, platform chrome, and as the default org logo.
    |
    */

    'logo' => env('BRAND_LOGO', 'konnect-logo.png'),

    'logo_dark' => env('BRAND_LOGO_DARK', 'konnect-dark-logo.jpg'),

    /*
    |--------------------------------------------------------------------------
    | Filename prefix
    |--------------------------------------------------------------------------
    |
    | Safe ASCII slug used when generating download filenames (PDFs, exports).
    | Spaces are omitted so filenames remain portable.
    |
    */

    'filename_prefix' => env('BRAND_FILENAME_PREFIX', 'KonnectNex'),

];
