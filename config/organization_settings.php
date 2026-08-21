<?php

/**
 * Configuration Hub registry (module-aware).
 *
 * Presentation groups live here. Licensed product modules live in config/modules.php.
 * Controllers, services, and routes are unchanged; this catalog only decides what
 * the hub shows. Do not create a parallel settings system per product module.
 *
 * Visibility (all must pass):
 * 1. The section route exists.
 * 2. The required license module is allowed by plan and enabled for the org
 *    (null license = always available).
 * 3. The current user has the section permission (or fallback).
 */

$modules = [
    'organization' => [
        'key' => 'organization',
        'name' => 'Organization',
        'description' => 'Profile, branding, subscription, users, and tenant identity.',
        'icon' => 'building',
        'license' => null,
        'permission' => 'settings.manage',
        'order' => 10,
        'sections' => [
            'profile' => [
                'label' => 'Organization Profile',
                'description' => 'Name, legal details, timezone, currency, and locale.',
                'route' => 'organization.edit',
                'permission' => 'settings.manage',
                'order' => 10,
            ],
            'subscription' => [
                'label' => 'Subscription',
                'description' => 'Plan, seats, and licensed modules.',
                'route' => 'organization.settings.subscription',
                'permission' => 'settings.manage',
                'order' => 20,
            ],
            'billing' => [
                'label' => 'Billing',
                'description' => 'Invoices and payment method for the subscription.',
                'route' => 'organization.settings.billing',
                'permission' => 'settings.manage',
                'order' => 30,
            ],
            'branding' => [
                'label' => 'Branding',
                'description' => 'Logo, colors, and customer-facing copy.',
                'route' => 'administration.branding.edit',
                'permission' => 'settings.manage',
                'order' => 40,
            ],
            'modules' => [
                'label' => 'Modules & Features',
                'description' => 'Workspace visibility and feature toggles within the plan.',
                'route' => 'administration.modules.index',
                'permission' => 'settings.manage',
                'order' => 50,
            ],
            'users' => [
                'label' => 'Users',
                'description' => 'Invite and manage organization members.',
                'route' => 'team.index',
                'permission' => 'users.view',
                'order' => 60,
            ],
            'email' => [
                'label' => 'Email',
                'description' => 'Outbound SMTP, from address, signature, and test delivery.',
                'route' => 'organization.edit',
                'permission' => 'settings.manage',
                'query' => ['tab' => 'email'],
                'order' => 70,
            ],
            'email_templates' => [
                'label' => 'Email Templates',
                'description' => 'Reusable CRM email subjects, bodies, and merge variables.',
                'route' => 'organization.settings.email-templates.index',
                'permission' => 'settings.manage',
                'order' => 75,
            ],
        ],
    ],

    'crm' => [
        'key' => 'crm',
        'name' => 'CRM',
        'description' => 'Lead, customer, contact, pipeline, and sales routing configuration.',
        'icon' => 'users',
        'license' => 'crm',
        'permission' => null,
        'order' => 20,
        'sections' => [
            'leads' => [
                'label' => 'Lead Settings',
                'description' => 'Lead records, statuses, and follow-up workflow.',
                'route' => 'leads.index',
                'permission' => 'leads.view',
                'order' => 10,
            ],
            'customers' => [
                'label' => 'Customer Settings',
                'description' => 'Customer records, contacts, lifecycle, tags, and commercial party profiles.',
                'keywords' => ['contacts', 'lifecycle', 'tags', 'segment', 'owner', 'accounts'],
                'route' => 'customers.index',
                'permission' => 'customers.view',
                'order' => 20,
            ],
            'contacts' => [
                'label' => 'Contacts',
                'description' => 'People at each customer company, including primary and decision makers.',
                'keywords' => ['people', 'decision maker', 'whatsapp'],
                'route' => 'contacts.index',
                'permission' => 'customers.view',
                'order' => 25,
            ],
            'pipeline' => [
                'label' => 'Pipeline',
                'description' => 'Opportunity stages and pipeline board.',
                'route' => 'pipeline.index',
                'permission' => 'opportunities.view',
                'order' => 30,
            ],
            'sales' => [
                'label' => 'Sales',
                'description' => 'Assignment pools and routing rules for sales work.',
                'route' => 'organization.settings.assignments.index',
                'permission' => 'assignments.view',
                'order' => 40,
            ],
        ],
    ],

    'commercial' => [
        'key' => 'commercial',
        'name' => 'Commercial',
        'description' => 'Tax, catalog, quotations, invoices, payments, and reminders.',
        'icon' => 'receipt',
        'license' => 'crm',
        'permission' => null,
        'order' => 30,
        'sections' => [
            'tax' => [
                'label' => 'Tax / GST',
                'description' => 'Seller GST state used to split CGST/SGST/IGST on documents.',
                'keywords' => ['gst', 'cgst', 'sgst', 'igst', 'tax', 'hsn'],
                'route' => 'organization.edit',
                'permission' => 'settings.manage',
                'query' => ['tab' => 'preferences'],
                'order' => 10,
            ],
            'products' => [
                'label' => 'Products',
                'description' => 'Product catalog, HSN/SAC, and tax rates.',
                'route' => 'products.index',
                'permission' => 'products.view',
                'order' => 20,
            ],
            'price_lists' => [
                'label' => 'Price Lists',
                'description' => 'Customer and currency price lists for the catalog.',
                'route' => 'price-lists.index',
                'permission' => 'price_lists.view',
                'fallback_permission' => 'products.view',
                'order' => 25,
            ],
            'quotations' => [
                'label' => 'Quotations',
                'description' => 'Quotation documents and conversion to sales orders.',
                'route' => 'quotations.index',
                'permission' => 'quotations.view',
                'order' => 30,
            ],
            'invoices' => [
                'label' => 'Invoices',
                'description' => 'Invoice documents, numbering, and receivable status.',
                'route' => 'invoices.index',
                'permission' => 'invoices.view',
                'order' => 40,
            ],
            'payments' => [
                'label' => 'Payments',
                'description' => 'Payment methods, receipts, and invoice allocation.',
                'route' => 'payments.index',
                'permission' => 'payments.view',
                'order' => 50,
            ],
            'automation' => [
                'label' => 'Automation',
                'description' => 'Due, overdue, expiry, and payment reminder defaults.',
                'route' => 'organization.settings.commercial-automation.edit',
                'permission' => 'settings.manage',
                'order' => 60,
            ],
        ],
    ],

    'hrms' => [
        'key' => 'hrms',
        'name' => 'HRMS',
        'description' => 'People, structure, time, leave, attendance, payroll, and hiring setup.',
        'icon' => 'hr',
        'license' => 'hrms',
        'permission' => null,
        'order' => 40,
        'sections' => [
            'employees' => [
                'label' => 'Employee',
                'description' => 'Employee directory and provisioning.',
                'route' => 'hrms.employees.index',
                'permission' => 'employee.directory',
                'fallback_permission' => 'hrms.view',
                'order' => 10,
            ],
            'branches' => [
                'label' => 'Branches',
                'description' => 'Offices, default branch, and branch managers.',
                'route' => 'organization.settings.branches.index',
                'permission' => 'organization.branches.view',
                'fallback_permission' => 'hrms.view',
                'order' => 20,
            ],
            'departments' => [
                'label' => 'Departments',
                'description' => 'Department catalog and reporting structure.',
                'route' => 'organization.settings.departments.index',
                'permission' => 'hrms.view',
                'order' => 30,
            ],
            'designations' => [
                'label' => 'Designations',
                'description' => 'Job titles used on employee records.',
                'route' => 'organization.settings.designations.index',
                'permission' => 'hrms.view',
                'order' => 40,
            ],
            'working_days' => [
                'label' => 'Working Days',
                'description' => 'Working days and business hours for the organization.',
                'route' => 'organization.settings.working-days.edit',
                'permission' => 'organization.hr_config.manage',
                'fallback_permission' => 'leave.manage',
                'order' => 50,
            ],
            'shifts' => [
                'label' => 'Shifts',
                'description' => 'Shift definitions, grace time, and overtime thresholds.',
                'route' => 'organization.settings.shifts.index',
                'permission' => 'organization.shifts.view',
                'fallback_permission' => 'attendance.view',
                'order' => 60,
            ],
            'leave_types' => [
                'label' => 'Leave',
                'description' => 'Leave types used by policies and applications.',
                'route' => 'organization.settings.leave-types.index',
                'permission' => 'leave.view',
                'order' => 70,
            ],
            'leave_policies' => [
                'label' => 'Leave Policies',
                'description' => 'Accrual, carry-forward, and entitlement rules.',
                'route' => 'organization.settings.leave-policies.edit',
                'permission' => 'organization.hr_config.manage',
                'fallback_permission' => 'leave.manage',
                'order' => 80,
            ],
            'leave_approvers' => [
                'label' => 'Leave Approvers',
                'description' => 'Default approvers for leave requests.',
                'route' => 'organization.settings.leave-approvers.edit',
                'permission' => 'organization.hr_config.manage',
                'fallback_permission' => 'leave.manage',
                'order' => 90,
            ],
            'holidays' => [
                'label' => 'Holidays',
                'description' => 'Organization holiday calendar.',
                'route' => 'organization.settings.holidays.index',
                'permission' => 'leave.view',
                'order' => 100,
            ],
            'attendance' => [
                'label' => 'Attendance',
                'description' => 'Grace, lateness, geofence, and verification rules.',
                'route' => 'organization.settings.attendance-rules.edit',
                'permission' => 'organization.hr_config.manage',
                'fallback_permission' => 'attendance.manage',
                'order' => 110,
            ],
            'wfh' => [
                'label' => 'WFH',
                'description' => 'Work-from-home eligibility and policy defaults.',
                'route' => 'organization.settings.wfh-policies.edit',
                'permission' => 'organization.hr_config.manage',
                'fallback_permission' => 'wfh.manage',
                'order' => 120,
            ],
            'payroll' => [
                'label' => 'Payroll',
                'description' => 'Pay frequency, rounding, overtime, and salary modes.',
                'route' => 'hrms.payroll.configuration.edit',
                'permission' => 'payroll.configuration',
                'fallback_permission' => 'payroll.view',
                'order' => 130,
            ],
            'recruitment' => [
                'label' => 'Recruitment',
                'description' => 'Public careers site branding and content.',
                'route' => 'hrms.recruitment.careers.settings.edit',
                'permission' => 'recruitment.careers.manage',
                'fallback_permission' => 'recruitment.manage',
                'license' => 'recruitment',
                'order' => 140,
            ],
            'recruitment_portal' => [
                'label' => 'Candidate Portal',
                'description' => 'Candidate portal access rules.',
                'route' => 'hrms.recruitment.portal.settings.edit',
                'permission' => 'recruitment.portal.settings',
                'fallback_permission' => 'recruitment.manage',
                'license' => 'recruitment',
                'order' => 150,
            ],
        ],
    ],

    'projects' => [
        'key' => 'projects',
        'name' => 'Projects',
        'description' => 'Project catalogs, templates, and task defaults.',
        'icon' => 'task',
        'license' => 'projects',
        'permission' => null,
        'order' => 50,
        'sections' => [
            'project_categories' => [
                'label' => 'Categories',
                'description' => 'Project category catalog.',
                'route' => 'project-categories.index',
                'permission' => 'projects.view',
                'order' => 10,
            ],
            'project_types' => [
                'label' => 'Types',
                'description' => 'Project type catalog.',
                'route' => 'project-types.index',
                'permission' => 'projects.view',
                'order' => 20,
            ],
            'project_statuses' => [
                'label' => 'Statuses',
                'description' => 'Project lifecycle statuses.',
                'route' => 'project-statuses.index',
                'permission' => 'projects.view',
                'order' => 30,
            ],
            'project_templates' => [
                'label' => 'Templates',
                'description' => 'Reusable project templates.',
                'route' => 'project-templates.index',
                'permission' => 'projects.view',
                'order' => 40,
            ],
            'task_statuses' => [
                'label' => 'Task Statuses',
                'description' => 'Task status catalog.',
                'route' => 'task-statuses.index',
                'permission' => 'tasks.view',
                'order' => 50,
            ],
            'task_priorities' => [
                'label' => 'Task Priorities',
                'description' => 'Task priority catalog.',
                'route' => 'task-priorities.index',
                'permission' => 'tasks.view',
                'order' => 60,
            ],
        ],
    ],

    'marketing' => [
        'key' => 'marketing',
        'name' => 'Marketing',
        'description' => 'Attribution defaults and connected marketing providers.',
        'icon' => 'chart',
        'license' => 'marketing',
        'permission' => null,
        'order' => 60,
        'sections' => [
            'providers' => [
                'label' => 'Providers',
                'description' => 'Marketing provider connections and attribution.',
                'route' => 'marketing.home',
                'permission' => 'marketing.view',
                'fallback_permission' => 'integrations.view',
                'order' => 10,
            ],
        ],
    ],

    'security' => [
        'key' => 'security',
        'name' => 'Security',
        'description' => 'Access control, security policies, and audit history.',
        'icon' => 'shield',
        'license' => null,
        'permission' => null,
        'order' => 70,
        'sections' => [
            'policies' => [
                'label' => 'Security',
                'description' => 'Password, session, and login policy defaults.',
                'route' => 'administration.security.index',
                'permission' => 'settings.manage',
                'order' => 10,
            ],
            'access_control' => [
                'label' => 'Access Control',
                'description' => 'Roles and permissions for this organization.',
                'route' => 'rbac.roles.index',
                'permission' => 'rbac.view',
                'order' => 20,
            ],
            'audit' => [
                'label' => 'Audit Logs',
                'description' => 'Organization activity history.',
                'route' => 'audit-logs.index',
                'permission' => 'audit.view',
                'order' => 30,
            ],
        ],
    ],

    'platform' => [
        'key' => 'platform',
        'name' => 'Platform',
        'description' => 'Notifications, integrations, automation, and developer tools.',
        'icon' => 'cog',
        'license' => null,
        'permission' => null,
        'order' => 80,
        'sections' => [
            'notifications' => [
                'label' => 'Notifications',
                'description' => 'Organization notification channel defaults.',
                'route' => 'organization.settings.notifications.edit',
                'permission' => 'settings.manage',
                'order' => 10,
            ],
            'workflows' => [
                'label' => 'Workflows',
                'description' => 'Approval and automation rules.',
                'route' => 'workflows.index',
                'permission' => 'workflows.view',
                'license' => 'workflow',
                'order' => 20,
            ],
            'metadata' => [
                'label' => 'Custom Fields',
                'description' => 'Metadata field definitions and layouts.',
                'route' => 'metadata-fields.index',
                'permission' => 'metadata.view',
                'fallback_permission' => 'metadata.manage',
                'order' => 30,
            ],
            'integrations' => [
                'label' => 'Integrations',
                'description' => 'Connected apps and external providers.',
                'route' => 'integrations.index',
                'permission' => 'integrations.view',
                'order' => 40,
            ],
            'api' => [
                'label' => 'API',
                'description' => 'Personal API tokens for the public API.',
                'route' => 'api-tokens.index',
                'permission' => 'api.tokens',
                'order' => 50,
            ],
            'developer' => [
                'label' => 'Developer',
                'description' => 'API, webhooks, and integration notes.',
                'route' => 'administration.developer.index',
                'permission' => 'settings.manage',
                'fallback_permission' => 'api.tokens',
                'order' => 60,
            ],
        ],
    ],
];

$sections = [];
$groups = [];

foreach ($modules as $moduleKey => $module) {
    $groups[$moduleKey] = $module['name'];

    foreach ($module['sections'] as $sectionKey => $section) {
        $sections[$sectionKey] = array_merge($section, [
            'group' => $moduleKey,
            'module' => $moduleKey,
            'license' => $section['license'] ?? ($module['license'] ?? null),
        ]);
    }
}

return [
    'modules' => $modules,

    /*
    | Flat aliases for older readers. Prefer ConfigurationRegistry.
    */
    'sections' => $sections,
    'groups' => $groups,

    /*
    | Future modules hidden from navigation but retained in architecture.
    */
    'future_modules' => [
        'assets' => [
            'label' => 'Assets',
            'status' => 'future',
            'reason' => 'Operational but incomplete for production HR asset lifecycle; kept for future integrations.',
            'routes_retained' => true,
            'database_retained' => true,
            'hidden_from_navigation' => true,
        ],
    ],
];
