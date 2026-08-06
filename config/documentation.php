<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Documentation Root
    |--------------------------------------------------------------------------
    |
    | All markdown files must live under this directory. Paths outside the
    | resolved root are never loaded or rendered.
    |
    */

    'root_path' => base_path('docs'),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (seconds)
    |--------------------------------------------------------------------------
    */

    'cache_ttl' => (int) env('DOCUMENTATION_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Cache Store
    |--------------------------------------------------------------------------
    |
    | Documentation indexes can contain the full Markdown corpus and may be
    | too large for a single database-cache row. Use a file or Redis store.
    |
    */

    'cache_store' => env('DOCUMENTATION_CACHE_STORE', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    'search' => [
        'min_length' => 2,
        'max_results' => 30,
        'snippet_length' => 160,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Landing Module
    |--------------------------------------------------------------------------
    |
    | Module slug used when visiting /knowledge without a module segment.
    |
    */

    'default_landing' => 'getting-started',

    /*
    |--------------------------------------------------------------------------
    | Sidebar Order
    |--------------------------------------------------------------------------
    */

    'sidebar_order' => [
        'getting-started',
        'onboarding',
        'crm',
        'hrms',
        'recruitment',
        'marketing',
        'workflow',
        'metadata',
        'finance',
        'deployment',
        'developer',
        'architecture',
        'api',
    ],

    /*
    |--------------------------------------------------------------------------
    | Modules
    |--------------------------------------------------------------------------
    |
    | enabled      — include module in discovery when true
    | name         — display label in navigation
    | icon         — icon key for future UI enhancements
    | searchable   — include module content in documentation search
    |
    */

    'modules' => [
        'getting-started' => [
            'enabled' => true,
            'name' => 'Getting Started',
            'icon' => 'rocket',
            'searchable' => true,
        ],
        'onboarding' => [
            'enabled' => true,
            'name' => 'Onboarding',
            'icon' => 'flag',
            'searchable' => true,
        ],
        'deployment' => [
            'enabled' => true,
            'name' => 'Deployment',
            'icon' => 'server',
            'searchable' => true,
        ],
        /*
         | Program 15 commercial/ops libraries — version-controlled under docs/
         | but hidden from customer Knowledge Center (internal staff use git).
         */
        'sops' => [
            'enabled' => false,
            'name' => 'SOPs',
            'icon' => 'clipboard',
            'searchable' => false,
        ],
        'demos' => [
            'enabled' => false,
            'name' => 'Demos',
            'icon' => 'play',
            'searchable' => false,
        ],
        'sales' => [
            'enabled' => false,
            'name' => 'Sales',
            'icon' => 'currency',
            'searchable' => false,
        ],
        'customer-success' => [
            'enabled' => false,
            'name' => 'Customer Success',
            'icon' => 'heart',
            'searchable' => false,
        ],
        'operations' => [
            'enabled' => false,
            'name' => 'Operations',
            'icon' => 'cog',
            'searchable' => false,
        ],
        'support' => [
            'enabled' => false,
            'name' => 'Support',
            'icon' => 'life-buoy',
            'searchable' => false,
        ],
        'training' => [
            'enabled' => false,
            'name' => 'Training',
            'icon' => 'academic-cap',
            'searchable' => false,
        ],
        'launch' => [
            'enabled' => false,
            'name' => 'Launch',
            'icon' => 'rocket',
            'searchable' => false,
        ],
        'crm' => [
            'enabled' => true,
            'name' => 'CRM',
            'icon' => 'users',
            'searchable' => true,
        ],
        'hrms' => [
            'enabled' => true,
            'name' => 'HRMS',
            'icon' => 'briefcase',
            'searchable' => true,
        ],
        'recruitment' => [
            'enabled' => true,
            'name' => 'Recruitment',
            'icon' => 'user-plus',
            'searchable' => true,
        ],
        'marketing' => [
            'enabled' => true,
            'name' => 'Marketing',
            'icon' => 'megaphone',
            'searchable' => true,
        ],
        'workflow' => [
            'enabled' => true,
            'name' => 'Workflow',
            'icon' => 'git-branch',
            'searchable' => true,
        ],
        'metadata' => [
            'enabled' => true,
            'name' => 'Metadata',
            'icon' => 'database',
            'searchable' => true,
        ],
        'finance' => [
            'enabled' => true,
            'name' => 'Finance',
            'icon' => 'currency',
            'searchable' => true,
        ],
        'developer' => [
            'enabled' => true,
            'name' => 'Developer',
            'icon' => 'code',
            'searchable' => true,
        ],
        'architecture' => [
            'enabled' => true,
            'name' => 'Architecture',
            'icon' => 'layers',
            'searchable' => true,
        ],
        'api' => [
            'enabled' => true,
            'name' => 'API',
            'icon' => 'terminal',
            'searchable' => true,
        ],
        'faq' => [
            'enabled' => true,
            'name' => 'FAQ',
            'icon' => 'help-circle',
            'searchable' => true,
        ],
        'release-notes' => [
            'enabled' => true,
            'name' => 'Release Notes',
            'icon' => 'tag',
            'searchable' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Hidden Documents
    |--------------------------------------------------------------------------
    |
    | Relative paths (from the documentation root) that must never be exposed.
    | Glob patterns are supported.
    |
    */

    'hidden_documents' => [
        'P*.md',
        '*_CONTRACT.md',
        '*_IMPACT_REPORT.md',
        '*_QA_REPORT.md',
        '*_TDS.md',
        '*_BRIEF.md',
        'LEAD_INTAKE_API.md',
        'NEXT_PHASE_PROMPT.md',
        'REVENUE_LIFECYCLE_QA_REPORT.md',
        'STABILIZATION_BUGFIX_*.md',
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Access Permissions
    |--------------------------------------------------------------------------
    |
    | When a module is listed here, the user must hold at least one permission.
    | Modules omitted from this list are available to all authenticated users.
    |
    */

    'module_permissions' => [
        'hrms' => [
            'hrms.view',
            'hr.dashboard',
            'manager.dashboard',
            'ess.access',
        ],
        'onboarding' => [
            'settings.manage',
            'users.manage',
            'rbac.roles.manage',
        ],
        'deployment' => [
            'settings.manage',
            'api.tokens',
        ],
        'developer' => [
            'api.tokens',
            'settings.manage',
            'workflows.manage',
            'metadata.manage',
        ],
        'architecture' => [
            'api.tokens',
            'settings.manage',
            'workflows.manage',
            'metadata.manage',
        ],
        'api' => [
            'api.tokens',
            'settings.manage',
            'workflows.manage',
            'metadata.manage',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Context-Sensitive Help
    |--------------------------------------------------------------------------
    */

    'help' => [
        'enabled' => true,
        'default_category' => 'user-guide',
        'session_key' => 'knowledge.recently_viewed',
        'recently_viewed_limit' => 5,
        'button' => [
            'icon' => 'question-circle',
            'tooltip' => 'Open documentation for this page',
            'show_in_header' => true,
        ],
    ],

    'help_categories' => [
        'user-guide' => 'User Guide',
        'admin-guide' => 'Administrator Guide',
        'api' => 'API Reference',
        'troubleshooting' => 'Troubleshooting',
    ],

    'integrations' => [
        'leads.index',
        'customers.index',
        'pipeline.index',
        'quotations.index',
        'invoices.index',
        'hrms.employees.index',
        'hrms.attendance.index',
        'hrms.leave-applications.index',
        'hrms.payroll.index',
        'hrms.performance.index',
        'integrations.index',
        'workflows.index',
        'workflows.executions.index',
        'metadata-fields.index',
        'api-tokens.index',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Mappings
    |--------------------------------------------------------------------------
    |
    | Maps application route names to documentation slugs. Optional anchor keys
    | support deep links when the heading exists in the target document.
    |
    */

    'route_mappings' => [
        'leads.index' => ['slug' => 'crm/user-guide/leads'],
        'customers.index' => ['slug' => 'crm/user-guide/customers'],
        'pipeline.index' => ['slug' => 'crm/user-guide/opportunities'],
        'quotations.index' => ['slug' => 'crm/user-guide/quotations'],
        'invoices.index' => ['slug' => 'crm/user-guide/invoices'],
        'hrms.employees.index' => ['slug' => 'hrms/user-guide/employees'],
        'hrms.attendance.index' => ['slug' => 'hrms/user-guide/attendance'],
        'hrms.leave-applications.index' => ['slug' => 'hrms/user-guide/leave'],
        'hrms.payroll.index' => ['slug' => 'hrms/user-guide/payroll'],
        'hrms.performance.index' => ['slug' => 'hrms/user-guide/performance'],
        'integrations.index' => ['slug' => 'api/marketing/overview'],
        'workflows.index' => ['slug' => 'developer/workflow'],
        'workflows.executions.index' => ['slug' => 'developer/workflow'],
        'metadata-fields.index' => ['slug' => 'developer/metadata'],
        'api-tokens.index' => ['slug' => 'api/crm/overview'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Help Targets
    |--------------------------------------------------------------------------
    |
    | Optional multi-target help menus keyed by route name. Only existing and
    | accessible documents are exposed to the user interface.
    |
    */

    'route_help_targets' => [
        'leads.index' => [
            'user-guide' => 'crm/user-guide/leads',
            'admin-guide' => 'crm/admin-guide/overview',
            'api' => 'crm/api/overview',
            'troubleshooting' => 'crm/troubleshooting/overview',
        ],
        'customers.index' => [
            'user-guide' => 'crm/user-guide/customers',
            'admin-guide' => 'crm/admin-guide/overview',
            'api' => 'crm/api/overview',
            'troubleshooting' => 'crm/troubleshooting/overview',
        ],
        'pipeline.index' => [
            'user-guide' => 'crm/user-guide/opportunities',
            'admin-guide' => 'crm/admin-guide/overview',
            'api' => 'crm/api/overview',
            'troubleshooting' => 'crm/troubleshooting/overview',
        ],
        'quotations.index' => [
            'user-guide' => 'crm/user-guide/quotations',
            'admin-guide' => 'crm/admin-guide/overview',
            'api' => 'crm/api/overview',
            'troubleshooting' => 'crm/troubleshooting/overview',
        ],
        'invoices.index' => [
            'user-guide' => 'crm/user-guide/invoices',
            'admin-guide' => 'crm/admin-guide/overview',
            'api' => 'crm/api/overview',
            'troubleshooting' => 'crm/troubleshooting/overview',
        ],
        'hrms.employees.index' => [
            'user-guide' => 'hrms/user-guide/employees',
            'admin-guide' => 'hrms/admin-guide/overview',
            'api' => 'hrms/api/overview',
            'troubleshooting' => 'hrms/troubleshooting/overview',
        ],
        'hrms.attendance.index' => [
            'user-guide' => 'hrms/user-guide/attendance',
            'admin-guide' => 'hrms/admin-guide/overview',
            'api' => 'hrms/api/overview',
            'troubleshooting' => 'hrms/troubleshooting/overview',
        ],
        'hrms.leave-applications.index' => [
            'user-guide' => 'hrms/user-guide/leave',
            'admin-guide' => 'hrms/admin-guide/overview',
            'api' => 'hrms/api/overview',
            'troubleshooting' => 'hrms/troubleshooting/overview',
        ],
        'hrms.payroll.index' => [
            'user-guide' => 'hrms/user-guide/payroll',
            'admin-guide' => 'hrms/admin-guide/overview',
            'api' => 'hrms/api/overview',
            'troubleshooting' => 'hrms/troubleshooting/overview',
        ],
        'hrms.performance.index' => [
            'user-guide' => 'hrms/user-guide/performance',
            'admin-guide' => 'hrms/admin-guide/overview',
            'api' => 'hrms/api/overview',
            'troubleshooting' => 'hrms/troubleshooting/overview',
        ],
        'workflows.index' => [
            'user-guide' => 'developer/workflow',
            'api' => 'api/workflow/overview',
        ],
        'metadata-fields.index' => [
            'user-guide' => 'developer/metadata',
            'api' => 'api/metadata/overview',
        ],
        'api-tokens.index' => [
            'user-guide' => 'api/crm/overview',
            'api' => 'api/crm/overview',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Metadata
    |--------------------------------------------------------------------------
    */

    'document_metadata' => [
        'crm/user-guide/leads' => [
            'keywords' => ['leads', 'conversion', 'follow-up', 'import'],
            'icon' => 'people',
            'related' => [
                'crm/user-guide/customers',
                'crm/user-guide/opportunities',
                'developer/workflow',
            ],
        ],
        'crm/user-guide/customers' => [
            'keywords' => ['customers', 'accounts'],
            'icon' => 'building',
            'related' => [
                'crm/user-guide/leads',
                'crm/user-guide/opportunities',
                'crm/user-guide/invoices',
            ],
        ],
        'crm/user-guide/opportunities' => [
            'keywords' => ['opportunities', 'pipeline', 'deals'],
            'icon' => 'funnel',
            'related' => [
                'crm/user-guide/leads',
                'crm/user-guide/quotations',
                'crm/user-guide/customers',
            ],
        ],
        'crm/user-guide/quotations' => [
            'keywords' => ['quotations', 'quotes', 'proposals'],
            'icon' => 'file-text',
            'related' => [
                'crm/user-guide/opportunities',
                'crm/user-guide/invoices',
            ],
        ],
        'crm/user-guide/invoices' => [
            'keywords' => ['invoices', 'billing', 'payments'],
            'icon' => 'receipt',
            'related' => [
                'crm/user-guide/quotations',
                'crm/user-guide/payments',
                'crm/user-guide/customers',
            ],
        ],
        'hrms/user-guide/employees' => [
            'keywords' => ['employees', 'profiles', 'directory'],
            'icon' => 'person-badge',
            'related' => [
                'hrms/user-guide/attendance',
                'hrms/user-guide/leave',
                'hrms/user-guide/payroll',
            ],
        ],
        'hrms/user-guide/attendance' => [
            'keywords' => ['attendance', 'clock-in', 'shifts'],
            'icon' => 'clock',
            'related' => [
                'hrms/user-guide/employees',
                'hrms/user-guide/leave',
            ],
        ],
        'hrms/user-guide/leave' => [
            'keywords' => ['leave', 'time-off', 'approvals'],
            'icon' => 'calendar',
            'related' => [
                'hrms/user-guide/employees',
                'hrms/user-guide/attendance',
            ],
        ],
        'hrms/user-guide/payroll' => [
            'keywords' => ['payroll', 'payslips', 'salary'],
            'icon' => 'cash',
            'related' => [
                'hrms/user-guide/employees',
                'hrms/user-guide/performance',
            ],
        ],
        'hrms/user-guide/performance' => [
            'keywords' => ['performance', 'reviews', 'goals'],
            'icon' => 'graph-up',
            'related' => [
                'hrms/user-guide/reviews',
                'hrms/user-guide/goals',
                'hrms/user-guide/payroll',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy Context Help Alias
    |--------------------------------------------------------------------------
    |
    | Maintained for backward compatibility. Prefer route_mappings.
    |
    */

    'context_help' => [
        'leads.index' => 'crm/user-guide/leads',
        'hrms.attendance.index' => 'hrms/user-guide/attendance',
        'hrms.payroll.index' => 'hrms/user-guide/payroll',
        'hrms.performance.reviews.index' => 'hrms/user-guide/reviews',
        'invoices.index' => 'crm/user-guide/invoices',
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentation Validation
    |--------------------------------------------------------------------------
    |
    | Filesystem-based quality checks for module completeness, metadata,
    | internal links, anchors, related documents, and release notes.
    |
    */

    'validation' => [
        'enabled' => true,

        'health_permission' => 'settings.manage',

        'module_exemptions' => [
            'faq',
            'release-notes',
        ],

        'required_documents' => [
            'Overview' => 'overview.md',
            'User Guide' => 'user-guide',
            'Administrator Guide' => 'admin-guide',
            'Business Processes' => 'business-process',
            'Technical Architecture' => 'architecture',
            'API Reference' => 'api',
            'Configuration' => 'configuration',
            'Troubleshooting' => 'troubleshooting',
            'FAQ' => 'faq',
            'Release Notes' => 'release-notes',
        ],

        'metadata_schema' => [
            'required' => ['title', 'module', 'category'],
            'optional' => ['keywords', 'icon', 'related', 'order'],
        ],

        'ignored_files' => [],

        'ignored_links' => [
            'https://*',
            'http://*',
            'mailto:*',
        ],

        'release' => [
            'paths' => [
                'release-notes/overview.md',
                'release-notes.md',
            ],
            'current_section_headings' => [
                'Version',
                'Current Release',
            ],
            'version_pattern' => '/v?\d+\.\d+\.\d+/',
            'require_entries' => true,
        ],

        'cache_ttl' => (int) env('DOCUMENTATION_VALIDATION_CACHE_TTL', 3600),
    ],

];
