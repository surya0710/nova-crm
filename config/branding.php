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
    | Filename prefix
    |--------------------------------------------------------------------------
    |
    | Safe ASCII slug used when generating download filenames (PDFs, exports).
    | Spaces are omitted so filenames remain portable.
    |
    */

    'filename_prefix' => env('BRAND_FILENAME_PREFIX', 'KonnectNex'),

];
