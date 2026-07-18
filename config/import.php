<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Import Platform
    |--------------------------------------------------------------------------
    |
    | Entity adapters are registered at runtime through ImportEntityRegistry.
    | Phase 8.1 ships the foundation only — no production entity adapters.
    |
    */

    'disk' => env('IMPORT_DISK', 'local'),

    'allowed_extensions' => ['csv', 'xlsx'],

    'max_upload_kilobytes' => (int) env('IMPORT_MAX_UPLOAD_KB', 10240),
];
