<?php

return [

    'session_cookie' => env('PLATFORM_SESSION_COOKIE', 'nova_crm_platform_session'),

    'roles' => [
        'platform-owner' => [
            'name' => 'Platform Owner',
            'permissions' => '*',
        ],
        'platform-administrator' => [
            'name' => 'Platform Administrator',
            'permissions' => [
                'platform.dashboard',
                'platform.organizations.view',
                'platform.organizations.manage',
                'platform.users.manage',
                'platform.reports.view',
                'platform.audit.view',
                'platform.impersonate',
            ],
        ],
        'platform-support' => [
            'name' => 'Platform Support',
            'permissions' => [
                'platform.dashboard',
                'platform.organizations.view',
                'platform.reports.view',
                'platform.audit.view',
            ],
        ],
        'platform-finance' => [
            'name' => 'Platform Finance',
            'permissions' => [
                'platform.dashboard',
                'platform.organizations.view',
                'platform.reports.view',
                'platform.audit.view',
            ],
        ],
        'platform-read-only' => [
            'name' => 'Platform Read Only',
            'permissions' => [
                'platform.dashboard',
                'platform.organizations.view',
                'platform.reports.view',
                'platform.audit.view',
            ],
        ],
    ],

    'permissions' => [
        'platform.dashboard' => 'View platform dashboard',
        'platform.organizations.view' => 'View organizations',
        'platform.organizations.manage' => 'Manage organizations',
        'platform.users.manage' => 'Manage platform users',
        'platform.reports.view' => 'View platform reports',
        'platform.audit.view' => 'View platform audit logs',
        'platform.impersonate' => 'Impersonate organization users',
    ],

    'organization_statuses' => [
        'active' => 'Active',
        'suspended' => 'Suspended',
        'archived' => 'Archived',
    ],

    'plans' => [
        'starter' => 'Starter',
        'professional' => 'Professional',
        'enterprise' => 'Enterprise',
    ],

    'dashboard_cache_ttl' => (int) env('PLATFORM_DASHBOARD_CACHE_TTL', 300),

];
