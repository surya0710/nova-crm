<?php

return [
    'modules' => [
        'users',
        'leads',
        'customers',
        'opportunities',
        'quotations',
        'invoices',
        'payments',
        'products',
        'reports',
        'tasks',
        'audit',
        'attachments',
        'api',
        'settings',
    ],

    'actions' => [
        'view',
        'create',
        'update',
        'delete',
        'manage',
    ],

    'permissions' => [
        'users.view' => ['Users', 'View team members'],
        'users.create' => ['Users', 'Invite new users'],
        'users.update' => ['Users', 'Edit user profiles and roles'],
        'users.delete' => ['Users', 'Remove users from organization'],
        'users.manage' => ['Users', 'Full user management'],

        'leads.view' => ['Leads', 'View leads'],
        'leads.create' => ['Leads', 'Create leads'],
        'leads.update' => ['Leads', 'Edit leads'],
        'leads.delete' => ['Leads', 'Delete leads'],
        'leads.manage' => ['Leads', 'Full lead management'],

        'customers.view' => ['Customers', 'View customers'],
        'customers.create' => ['Customers', 'Create customers'],
        'customers.update' => ['Customers', 'Edit customers'],
        'customers.delete' => ['Customers', 'Delete customers'],
        'customers.manage' => ['Customers', 'Full customer management'],

        'opportunities.view' => ['Pipeline', 'View opportunities'],
        'opportunities.create' => ['Pipeline', 'Create opportunities'],
        'opportunities.update' => ['Pipeline', 'Edit opportunities'],
        'opportunities.delete' => ['Pipeline', 'Delete opportunities'],
        'opportunities.manage' => ['Pipeline', 'Full pipeline management'],

        'quotations.view' => ['Quotations', 'View quotations'],
        'quotations.create' => ['Quotations', 'Create quotations'],
        'quotations.update' => ['Quotations', 'Edit quotations'],
        'quotations.delete' => ['Quotations', 'Delete quotations'],
        'quotations.manage' => ['Quotations', 'Full quotation management'],

        'invoices.view' => ['Invoices', 'View invoices'],
        'invoices.create' => ['Invoices', 'Create invoices'],
        'invoices.update' => ['Invoices', 'Edit invoices'],
        'invoices.delete' => ['Invoices', 'Delete invoices'],
        'invoices.manage' => ['Invoices', 'Full invoice management'],

        'payments.view' => ['Payments', 'View payments'],
        'payments.create' => ['Payments', 'Record payments'],
        'payments.update' => ['Payments', 'Edit payments'],
        'payments.delete' => ['Payments', 'Delete payments'],
        'payments.manage' => ['Payments', 'Full payment management'],

        'products.view' => ['Products', 'View products'],
        'products.create' => ['Products', 'Create products'],
        'products.update' => ['Products', 'Edit products'],
        'products.delete' => ['Products', 'Delete products'],
        'products.manage' => ['Products', 'Full product management'],

        'reports.view' => ['Reports', 'View reports and analytics'],
        'reports.manage' => ['Reports', 'Manage reports and exports'],

        'tasks.view' => ['Tasks', 'View tasks and follow-ups'],
        'tasks.create' => ['Tasks', 'Create tasks'],
        'tasks.update' => ['Tasks', 'Edit tasks'],
        'tasks.delete' => ['Tasks', 'Delete tasks'],
        'tasks.manage' => ['Tasks', 'Full task management'],

        'audit.view' => ['Audit Log', 'View organization activity and audit trail'],

        'attachments.view' => ['Attachments', 'View file attachments'],
        'attachments.create' => ['Attachments', 'Upload file attachments'],
        'attachments.delete' => ['Attachments', 'Delete file attachments'],

        'api.access' => ['API', 'Access REST API endpoints'],
        'api.tokens' => ['API', 'Manage personal API tokens'],

        'settings.view' => ['Settings', 'View organization settings'],
        'settings.manage' => ['Settings', 'Manage organization settings'],
    ],

    'roles' => [
        'organization-owner' => [
            'name' => 'Organization Owner',
            'description' => 'Full access to all organization features and settings.',
            'permissions' => '*',
        ],
        'manager' => [
            'name' => 'Manager',
            'description' => 'Manage sales, team, and day-to-day operations.',
            'permissions' => [
                'users.view', 'users.create', 'users.update', 'users.delete',
                'leads.view', 'leads.create', 'leads.update', 'leads.delete', 'leads.manage',
                'customers.view', 'customers.create', 'customers.update', 'customers.delete', 'customers.manage',
                'opportunities.view', 'opportunities.create', 'opportunities.update', 'opportunities.delete', 'opportunities.manage',
                'quotations.view', 'quotations.create', 'quotations.update', 'quotations.delete', 'quotations.manage',
                'invoices.view', 'invoices.create', 'invoices.update',
                'payments.view', 'payments.create', 'payments.delete', 'payments.manage',
                'products.view', 'products.create', 'products.update', 'products.delete', 'products.manage',
                'reports.view',
                'tasks.view', 'tasks.create', 'tasks.update', 'tasks.delete', 'tasks.manage',
                'audit.view',
                'attachments.view', 'attachments.create', 'attachments.delete',
                'api.tokens', 'api.access',
                'settings.view',
            ],
        ],
        'sales-executive' => [
            'name' => 'Sales Executive',
            'description' => 'Manage leads, customers, and sales documents.',
            'permissions' => [
                'leads.view', 'leads.create', 'leads.update', 'leads.delete',
                'customers.view', 'customers.create', 'customers.update',
                'opportunities.view', 'opportunities.create', 'opportunities.update', 'opportunities.delete',
                'quotations.view', 'quotations.create', 'quotations.update',
                'products.view',
                'reports.view',
                'tasks.view', 'tasks.create', 'tasks.update', 'tasks.delete',
                'audit.view',
                'attachments.view', 'attachments.create',
            ],
        ],
        'hr' => [
            'name' => 'HR',
            'description' => 'Manage team members and HR-related reports.',
            'permissions' => [
                'users.view', 'users.create', 'users.update',
                'reports.view',
                'audit.view',
            ],
        ],
        'support' => [
            'name' => 'Support',
            'description' => 'View and assist with customer records.',
            'permissions' => [
                'customers.view', 'customers.update',
                'leads.view',
                'opportunities.view',
                'invoices.view',
                'payments.view',
                'reports.view',
                'tasks.view', 'tasks.create', 'tasks.update',
                'audit.view',
                'attachments.view',
            ],
        ],
        'employee' => [
            'name' => 'Employee',
            'description' => 'Basic read access to CRM data.',
            'permissions' => [
                'leads.view',
                'customers.view',
                'products.view',
                'tasks.view',
            ],
        ],
    ],

    'legacy_role_map' => [
        'owner' => 'organization-owner',
        'organization-owner' => 'organization-owner',
        'manager' => 'manager',
        'sales-executive' => 'sales-executive',
        'sales_executive' => 'sales-executive',
        'hr' => 'hr',
        'support' => 'support',
        'employee' => 'employee',
    ],
];
