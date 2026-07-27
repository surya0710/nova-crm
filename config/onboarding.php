<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Wizard steps (ordered)
    |--------------------------------------------------------------------------
    */

    'steps' => [
        'organization' => [
            'label' => 'Organization Information',
            'description' => 'Name, industry, locale, and contact details',
        ],
        'modules' => [
            'label' => 'Subscription & Modules',
            'description' => 'Plan and licensed modules',
        ],
        'structure' => [
            'label' => 'Organization Structure',
            'description' => 'Branches, departments, designations, and shifts',
        ],
        'users' => [
            'label' => 'Users & Employees',
            'description' => 'Invite administrators and provision accounts',
        ],
        'imports' => [
            'label' => 'Data Import',
            'description' => 'Import master data via Import Center',
        ],
        'branding' => [
            'label' => 'Branding',
            'description' => 'Logo, colors, and document branding',
        ],
        'communication' => [
            'label' => 'Communication Settings',
            'description' => 'SMTP and notification sender',
        ],
        'providers' => [
            'label' => 'Provider Integrations',
            'description' => 'Verify platform providers',
        ],
        'go_live' => [
            'label' => 'Go-Live Checklist',
            'description' => 'Validate readiness and finish',
        ],
    ],

    'selectable_modules' => [
        'crm',
        'hrms',
        'projects',
        'marketing',
        'analytics',
    ],

    'import_entities' => [
        'crm' => ['lead', 'customer'],
        'hrms' => ['employee', 'department', 'branch', 'designation'],
        'projects' => ['project', 'task'],
    ],

];
