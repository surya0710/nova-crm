<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lookup defaults
    |--------------------------------------------------------------------------
    */
    'per_page' => (int) env('LOOKUP_PER_PAGE', 20),
    'max_per_page' => (int) env('LOOKUP_MAX_PER_PAGE', 50),
    'min_search_length' => (int) env('LOOKUP_MIN_SEARCH_LENGTH', 0),
    'debounce_ms' => (int) env('LOOKUP_DEBOUNCE_MS', 300),
    'cache_ttl_seconds' => (int) env('LOOKUP_CACHE_TTL', 60),

    /*
    |--------------------------------------------------------------------------
    | Registered lookup entities
    |--------------------------------------------------------------------------
    |
    | Future entities register here without modifying the lookup platform.
    |
    */
    'entities' => [
        'users' => [
            'label' => 'Users',
            'service' => \App\Services\Lookup\UserLookupService::class,
            'permission' => null,
            'license_module' => null,
        ],
        'employees' => [
            'label' => 'Employees',
            'service' => \App\Services\Lookup\EmployeeLookupService::class,
            'permission' => 'hrms.view',
            'license_module' => 'hrms',
        ],
        'departments' => [
            'label' => 'Departments',
            'service' => \App\Services\Lookup\DepartmentLookupService::class,
            'permission' => 'hrms.view',
            'license_module' => 'hrms',
        ],
        'designations' => [
            'label' => 'Designations',
            'service' => \App\Services\Lookup\DesignationLookupService::class,
            'permission' => 'hrms.view',
            'license_module' => 'hrms',
        ],
        'branches' => [
            'label' => 'Branches',
            'service' => \App\Services\Lookup\BranchLookupService::class,
            'permission' => 'hrms.view',
            'license_module' => 'hrms',
        ],
        'shifts' => [
            'label' => 'Shifts',
            'service' => \App\Services\Lookup\ShiftLookupService::class,
            'permission' => 'hrms.view',
            'license_module' => 'hrms',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bulk field type → lookup entity mapping
    |--------------------------------------------------------------------------
    */
    'bulk_field_types' => [
        'lookup',
        'user',
        'employee',
        'department',
        'designation',
        'branch',
        'shift',
    ],

    'bulk_type_entities' => [
        'user' => 'users',
        'employee' => 'employees',
        'department' => 'departments',
        'designation' => 'designations',
        'branch' => 'branches',
        'shift' => 'shifts',
    ],

];
