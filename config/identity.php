<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Invitation expiry
    |--------------------------------------------------------------------------
    |
    | How long an invitation link remains valid before it must be re-sent.
    |
    */

    'invitation_expiry_hours' => (int) env('IDENTITY_INVITATION_EXPIRY_HOURS', 72),

    /*
    |--------------------------------------------------------------------------
    | Default employee role slug
    |--------------------------------------------------------------------------
    |
    | Used when provisioning employee login accounts without an explicit role.
    | Must exist in the organization RBAC catalog — never hardcode permissions.
    |
    */

    'default_employee_role' => env('IDENTITY_DEFAULT_EMPLOYEE_ROLE', 'employee'),

    /*
    |--------------------------------------------------------------------------
    | Failed login lock threshold
    |--------------------------------------------------------------------------
    |
    | After this many consecutive failed attempts the account is locked.
    | Set to 0 to disable automatic locking (rate limiting still applies).
    |
    */

    'failed_login_lock_threshold' => (int) env('IDENTITY_FAILED_LOGIN_LOCK_THRESHOLD', 10),

    /*
    |--------------------------------------------------------------------------
    | Bulk provisioning
    |--------------------------------------------------------------------------
    */

    'bulk_chunk_size' => (int) env('IDENTITY_BULK_CHUNK_SIZE', 50),

    'bulk_sync_threshold' => (int) env('IDENTITY_BULK_SYNC_THRESHOLD', 10),

];
