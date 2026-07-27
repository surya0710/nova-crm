<?php

return [

    'disk' => env('EXPORT_DISK', 'local'),

    'directory' => env('EXPORT_DIRECTORY', 'exports'),

    /*
    |--------------------------------------------------------------------------
    | Queue large exports
    |--------------------------------------------------------------------------
    */

    'queue_threshold_rows' => (int) env('EXPORT_QUEUE_THRESHOLD_ROWS', 100),

    'chunk_size' => (int) env('EXPORT_CHUNK_SIZE', 250),

    'max_records' => (int) env('EXPORT_MAX_RECORDS', 500000),

    /*
    |--------------------------------------------------------------------------
    | Download security
    |--------------------------------------------------------------------------
    */

    'download_ttl_hours' => (int) env('EXPORT_DOWNLOAD_TTL_HOURS', 72),

    'pdf_max_rows' => (int) env('EXPORT_PDF_MAX_ROWS', 2000),

    /*
    |--------------------------------------------------------------------------
    | Supported formats (architecture allows json/xml/zip later)
    |--------------------------------------------------------------------------
    */

    'formats' => [
        'xlsx' => [
            'label' => 'Excel (.xlsx)',
            'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'extension' => 'xlsx',
        ],
        'csv' => [
            'label' => 'CSV',
            'mime' => 'text/csv; charset=UTF-8',
            'extension' => 'csv',
        ],
        'pdf' => [
            'label' => 'PDF',
            'mime' => 'application/pdf',
            'extension' => 'pdf',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Entity catalog (module grouping + RBAC + licensing)
    |--------------------------------------------------------------------------
    */

    'entities' => [
        'lead' => [
            'module' => 'crm',
            'label' => 'Leads',
            'permission' => 'exports.crm',
            'license_module' => 'crm',
            'model' => \App\Models\Lead::class,
        ],
        'customer' => [
            'module' => 'crm',
            'label' => 'Customers',
            'permission' => 'exports.crm',
            'license_module' => 'crm',
            'model' => \App\Models\Customer::class,
        ],
        'opportunity' => [
            'module' => 'crm',
            'label' => 'Opportunities',
            'permission' => 'exports.crm',
            'license_module' => 'crm',
            'model' => \App\Models\Opportunity::class,
        ],
        'employee' => [
            'module' => 'hrms',
            'label' => 'Employees',
            'permission' => 'exports.hrms',
            'license_module' => 'hrms',
            'model' => \App\Models\Employee::class,
        ],
        'department' => [
            'module' => 'hrms',
            'label' => 'Departments',
            'permission' => 'exports.hrms',
            'license_module' => 'hrms',
            'model' => \App\Models\Department::class,
        ],
        'designation' => [
            'module' => 'hrms',
            'label' => 'Designations',
            'permission' => 'exports.hrms',
            'license_module' => 'hrms',
            'model' => \App\Models\Designation::class,
        ],
        'branch' => [
            'module' => 'hrms',
            'label' => 'Branches',
            'permission' => 'exports.hrms',
            'license_module' => 'hrms',
            'model' => \App\Models\Branch::class,
        ],
        'shift' => [
            'module' => 'hrms',
            'label' => 'Shifts',
            'permission' => 'exports.hrms',
            'license_module' => 'hrms',
            'model' => \App\Models\HrmsShift::class,
        ],
        'leave_type' => [
            'module' => 'hrms',
            'label' => 'Leave Types',
            'permission' => 'exports.hrms',
            'license_module' => 'hrms',
            'model' => \App\Models\LeaveType::class,
        ],
        'holiday' => [
            'module' => 'hrms',
            'label' => 'Holidays',
            'permission' => 'exports.hrms',
            'license_module' => 'hrms',
            'model' => \App\Models\Holiday::class,
        ],
        'project' => [
            'module' => 'projects',
            'label' => 'Projects',
            'permission' => 'exports.projects',
            'license_module' => 'projects',
            'model' => \App\Models\Project::class,
        ],
        'milestone' => [
            'module' => 'projects',
            'label' => 'Milestones',
            'permission' => 'exports.projects',
            'license_module' => 'projects',
            'model' => \App\Models\ProjectMilestone::class,
        ],
        'task' => [
            'module' => 'projects',
            'label' => 'Tasks',
            'permission' => 'exports.projects',
            'license_module' => 'projects',
            'model' => \App\Models\Task::class,
        ],
        'campaign' => [
            'module' => 'marketing',
            'label' => 'Campaigns',
            'permission' => 'exports.marketing',
            'license_module' => 'marketing',
            'model' => \App\Models\MarketingCampaign::class,
        ],
        'user' => [
            'module' => 'administration',
            'label' => 'Users',
            'permission' => 'exports.administration',
            'license_module' => null,
            'model' => \App\Models\User::class,
        ],
        'role' => [
            'module' => 'administration',
            'label' => 'Roles',
            'permission' => 'exports.administration',
            'license_module' => null,
            'model' => \App\Models\Role::class,
        ],
    ],

    'module_labels' => [
        'crm' => 'CRM',
        'hrms' => 'HRMS',
        'projects' => 'Projects',
        'marketing' => 'Marketing',
        'administration' => 'Administration',
    ],

];
