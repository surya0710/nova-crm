<?php

return [

    'disk' => env('IMPORT_DISK', 'local'),

    'allowed_extensions' => ['csv', 'xlsx'],

    'max_upload_kilobytes' => (int) env('IMPORT_MAX_UPLOAD_KB', 10240),

    /*
    |--------------------------------------------------------------------------
    | Queue large imports
    |--------------------------------------------------------------------------
    */

    'queue_threshold_rows' => (int) env('IMPORT_QUEUE_THRESHOLD_ROWS', 100),

    'chunk_size' => (int) env('IMPORT_CHUNK_SIZE', 100),

    /*
    |--------------------------------------------------------------------------
    | Entity catalog (module grouping + RBAC)
    |--------------------------------------------------------------------------
    |
    | Adapters still register at runtime. This catalog drives the Import Center
    | UI, permissions, and licensing checks.
    |
    */

    'entities' => [
        'lead' => [
            'module' => 'crm',
            'label' => 'Leads',
            'permission' => 'imports.crm',
            'license_module' => 'crm',
        ],
        'customer' => [
            'module' => 'crm',
            'label' => 'Customers',
            'permission' => 'imports.crm',
            'license_module' => 'crm',
        ],
        'opportunity' => [
            'module' => 'crm',
            'label' => 'Opportunities',
            'permission' => 'imports.crm',
            'license_module' => 'crm',
        ],
        'employee' => [
            'module' => 'hrms',
            'label' => 'Employees',
            'permission' => 'imports.hrms',
            'license_module' => 'hrms',
        ],
        'department' => [
            'module' => 'hrms',
            'label' => 'Departments',
            'permission' => 'imports.hrms',
            'license_module' => 'hrms',
        ],
        'designation' => [
            'module' => 'hrms',
            'label' => 'Designations',
            'permission' => 'imports.hrms',
            'license_module' => 'hrms',
        ],
        'branch' => [
            'module' => 'hrms',
            'label' => 'Branches',
            'permission' => 'imports.hrms',
            'license_module' => 'hrms',
        ],
        'shift' => [
            'module' => 'hrms',
            'label' => 'Shifts',
            'permission' => 'imports.hrms',
            'license_module' => 'hrms',
        ],
        'leave_type' => [
            'module' => 'hrms',
            'label' => 'Leave Types',
            'permission' => 'imports.hrms',
            'license_module' => 'hrms',
        ],
        'holiday' => [
            'module' => 'hrms',
            'label' => 'Holiday Calendar',
            'permission' => 'imports.hrms',
            'license_module' => 'hrms',
        ],
        'project' => [
            'module' => 'projects',
            'label' => 'Projects',
            'permission' => 'imports.projects',
            'license_module' => 'projects',
        ],
        'milestone' => [
            'module' => 'projects',
            'label' => 'Milestones',
            'permission' => 'imports.projects',
            'license_module' => 'projects',
        ],
        'task' => [
            'module' => 'projects',
            'label' => 'Tasks',
            'permission' => 'imports.projects',
            'license_module' => 'projects',
        ],
        'user' => [
            'module' => 'administration',
            'label' => 'Users',
            'permission' => 'imports.administration',
            'license_module' => null,
        ],
    ],

    'module_labels' => [
        'crm' => 'CRM',
        'hrms' => 'HRMS',
        'projects' => 'Projects',
        'administration' => 'Administration',
    ],

];
