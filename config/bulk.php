<?php

return [

    'queue_threshold' => (int) env('BULK_QUEUE_THRESHOLD', 25),

    'chunk_size' => (int) env('BULK_CHUNK_SIZE', 50),

    'max_selection' => (int) env('BULK_MAX_SELECTION', 10000),

    /*
    |--------------------------------------------------------------------------
    | Module labels (UI grouping)
    |--------------------------------------------------------------------------
    */

    'module_labels' => [
        'crm' => 'CRM',
        'hrms' => 'HRMS',
        'projects' => 'Projects',
        'administration' => 'Administration',
        'marketing' => 'Marketing',
        'analytics' => 'Analytics',
    ],

    /*
    |--------------------------------------------------------------------------
    | Entity metadata for selection UI
    |--------------------------------------------------------------------------
    */

    'entities' => [
        'lead' => [
            'module' => 'crm',
            'label' => 'Leads',
            'model' => \App\Models\Lead::class,
            'bulk_permission' => 'bulk.crm',
            'license_module' => 'crm',
        ],
        'customer' => [
            'module' => 'crm',
            'label' => 'Customers',
            'model' => \App\Models\Customer::class,
            'bulk_permission' => 'bulk.crm',
            'license_module' => 'crm',
        ],
        'opportunity' => [
            'module' => 'crm',
            'label' => 'Opportunities',
            'model' => \App\Models\Opportunity::class,
            'bulk_permission' => 'bulk.crm',
            'license_module' => 'crm',
        ],
        'employee' => [
            'module' => 'hrms',
            'label' => 'Employees',
            'model' => \App\Models\Employee::class,
            'bulk_permission' => 'bulk.hrms',
            'license_module' => 'hrms',
        ],
        'department' => [
            'module' => 'hrms',
            'label' => 'Departments',
            'model' => \App\Models\Department::class,
            'bulk_permission' => 'bulk.hrms',
            'license_module' => 'hrms',
        ],
        'designation' => [
            'module' => 'hrms',
            'label' => 'Designations',
            'model' => \App\Models\Designation::class,
            'bulk_permission' => 'bulk.hrms',
            'license_module' => 'hrms',
        ],
        'branch' => [
            'module' => 'hrms',
            'label' => 'Branches',
            'model' => \App\Models\Branch::class,
            'bulk_permission' => 'bulk.hrms',
            'license_module' => 'hrms',
        ],
        'project' => [
            'module' => 'projects',
            'label' => 'Projects',
            'model' => \App\Models\Project::class,
            'bulk_permission' => 'bulk.projects',
            'license_module' => 'projects',
        ],
        'task' => [
            'module' => 'projects',
            'label' => 'Tasks',
            'model' => \App\Models\Task::class,
            'bulk_permission' => 'bulk.projects',
            'license_module' => 'projects',
        ],
        'user' => [
            'module' => 'administration',
            'label' => 'Users',
            'model' => \App\Models\User::class,
            'bulk_permission' => 'bulk.administration',
            'license_module' => null,
        ],
    ],

];
