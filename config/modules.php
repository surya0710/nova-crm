<?php

/**
 * Central module registry — single source of truth for licensing, navigation,
 * workspaces, and plan availability. Do not duplicate module lists elsewhere.
 */

$modules = [
    'common' => [
        'key' => 'common',
        'name' => 'Common',
        'description' => 'Shared platform capabilities available to every organization.',
        'icon' => 'home',
        'route' => null,
        'permission' => null,
        'any_permissions' => null,
        'workspace' => null,
        'order' => 0,
        'default_landing' => null,
        'enabled_by_default' => true,
        'licensable' => false,
        'plan_availability' => ['starter', 'professional', 'enterprise'],
    ],
    'crm' => [
        'key' => 'crm',
        'name' => 'CRM',
        'description' => 'Leads, customers, pipeline, quotations, invoices, and payments.',
        'icon' => 'users',
        'route' => 'crm.home',
        'permission' => null,
        'any_permissions' => [
            'leads.view', 'customers.view', 'opportunities.view', 'products.view',
            'quotations.view', 'invoices.view', 'payments.view',
        ],
        'workspace' => 'crm',
        'order' => 20,
        'default_landing' => 'crm.home',
        'enabled_by_default' => true,
        'licensable' => true,
        'plan_availability' => ['starter', 'professional', 'enterprise'],
    ],
    'projects' => [
        'key' => 'projects',
        'name' => 'Projects',
        'description' => 'Project delivery, portfolios, programs, resources, and tasks.',
        'icon' => 'task',
        'route' => 'projects.home',
        'permission' => null,
        'any_permissions' => [
            'projects.view', 'resources.view', 'projects.portfolios.view',
            'projects.programs.view', 'tasks.view',
        ],
        'workspace' => 'projects',
        'order' => 30,
        'default_landing' => 'projects.home',
        'enabled_by_default' => true,
        'licensable' => true,
        'plan_availability' => ['professional', 'enterprise'],
    ],
    'hrms' => [
        'key' => 'hrms',
        'name' => 'HRMS',
        'description' => 'Employees, attendance, leave, payroll, and performance.',
        'icon' => 'hr',
        'route' => 'hrms.home',
        'permission' => null,
        'any_permissions' => [
            'hrms.view', 'ess.access', 'hr.dashboard', 'manager.dashboard',
            'employee.directory', 'attendance.view', 'leave.view',
            'payroll.view', 'performance.view',
        ],
        'workspace' => 'hr',
        'order' => 40,
        'default_landing' => 'hrms.home',
        'enabled_by_default' => true,
        'licensable' => true,
        'plan_availability' => ['professional', 'enterprise'],
    ],
    'recruitment' => [
        'key' => 'recruitment',
        'name' => 'Recruitment',
        'description' => 'Hiring pipelines, candidates, interviews, and careers site.',
        'icon' => 'users',
        'route' => null,
        'permission' => 'recruitment.view',
        'any_permissions' => ['recruitment.view'],
        'workspace' => 'hr',
        'order' => 45,
        'default_landing' => null,
        'enabled_by_default' => true,
        'licensable' => true,
        'plan_availability' => ['professional', 'enterprise'],
    ],
    'marketing' => [
        'key' => 'marketing',
        'name' => 'Marketing',
        'description' => 'Campaigns, attribution, and marketing providers.',
        'icon' => 'chart',
        'route' => 'marketing.home',
        'permission' => null,
        'any_permissions' => ['marketing.view', 'marketing.manage', 'integrations.view', 'integrations.manage'],
        'workspace' => 'marketing',
        'order' => 50,
        'default_landing' => 'marketing.home',
        'enabled_by_default' => true,
        'licensable' => true,
        'plan_availability' => ['professional', 'enterprise'],
    ],
    'analytics' => [
        'key' => 'analytics',
        'name' => 'Analytics',
        'description' => 'Cross-module insights, KPIs, and executive reporting.',
        'icon' => 'chart',
        'route' => 'analytics.home',
        'permission' => null,
        'any_permissions' => [
            'reports.view', 'finance.view', 'audit.view',
            'projects.reports.view', 'recruitment.reports.view',
        ],
        'workspace' => 'analytics',
        'order' => 70,
        'default_landing' => 'analytics.home',
        'enabled_by_default' => true,
        'licensable' => true,
        'plan_availability' => ['professional', 'enterprise'],
    ],
    'finance' => [
        'key' => 'finance',
        'name' => 'Finance',
        'description' => 'Financial management beyond CRM revenue documents.',
        'icon' => 'receipt',
        'route' => null,
        'permission' => 'finance.view',
        'any_permissions' => ['finance.view'],
        'workspace' => null,
        'order' => 80,
        'default_landing' => null,
        'enabled_by_default' => true,
        'licensable' => true,
        'plan_availability' => ['professional', 'enterprise'],
    ],
    'support' => [
        'key' => 'support',
        'name' => 'Support',
        'description' => 'Customer support and ticketing.',
        'icon' => 'bell',
        'route' => null,
        'permission' => null,
        'any_permissions' => null,
        'workspace' => null,
        'order' => 90,
        'default_landing' => null,
        'enabled_by_default' => true,
        'licensable' => true,
        'plan_availability' => ['professional', 'enterprise'],
    ],
    'workflow' => [
        'key' => 'workflow',
        'name' => 'Workflow',
        'description' => 'Approvals, automation rules, and workflow engines.',
        'icon' => 'cog',
        'route' => null,
        'permission' => 'workflows.view',
        'any_permissions' => ['workflows.view'],
        'workspace' => null,
        'order' => 100,
        'default_landing' => null,
        'enabled_by_default' => true,
        'licensable' => true,
        'plan_availability' => ['professional', 'enterprise'],
    ],
    'tasks' => [
        'key' => 'tasks',
        'name' => 'Tasks',
        'description' => 'Cross-workspace task management.',
        'icon' => 'task',
        'route' => null,
        'permission' => 'tasks.view',
        'any_permissions' => ['tasks.view'],
        'workspace' => 'operations',
        'order' => 60,
        'default_landing' => null,
        'enabled_by_default' => true,
        'licensable' => true,
        'plan_availability' => ['starter', 'professional', 'enterprise'],
    ],
    'notifications' => [
        'key' => 'notifications',
        'name' => 'Notifications',
        'description' => 'In-app and channel notification delivery.',
        'icon' => 'bell',
        'route' => null,
        'permission' => null,
        'any_permissions' => null,
        'workspace' => null,
        'order' => 110,
        'default_landing' => null,
        'enabled_by_default' => true,
        'licensable' => false,
        'plan_availability' => ['starter', 'professional', 'enterprise'],
    ],
    'calendar' => [
        'key' => 'calendar',
        'name' => 'Calendar',
        'description' => 'Events, deadlines, and scheduling.',
        'icon' => 'calendar',
        'route' => null,
        'permission' => 'tasks.view',
        'any_permissions' => ['tasks.view'],
        'workspace' => null,
        'order' => 120,
        'default_landing' => null,
        'enabled_by_default' => true,
        'licensable' => false,
        'plan_availability' => ['starter', 'professional', 'enterprise'],
    ],
    'customer_portal' => [
        'key' => 'customer_portal',
        'name' => 'Customer Portal',
        'description' => 'External customer self-service portal.',
        'icon' => 'building',
        'route' => null,
        'permission' => null,
        'any_permissions' => null,
        'workspace' => null,
        'order' => 130,
        'default_landing' => null,
        'enabled_by_default' => false,
        'licensable' => true,
        'plan_availability' => ['enterprise'],
    ],
    'inventory' => [
        'key' => 'inventory',
        'name' => 'Inventory',
        'description' => 'Stock levels, warehouses, and inventory movements.',
        'icon' => 'task',
        'route' => null,
        'permission' => null,
        'any_permissions' => null,
        'workspace' => null,
        'order' => 140,
        'default_landing' => null,
        'enabled_by_default' => false,
        'licensable' => true,
        'plan_availability' => ['enterprise'],
    ],
    'assets' => [
        'key' => 'assets',
        'name' => 'Assets',
        'description' => 'IT and organizational asset tracking.',
        'icon' => 'building',
        'route' => null,
        'permission' => null,
        'any_permissions' => null,
        'workspace' => null,
        'order' => 150,
        'default_landing' => null,
        'enabled_by_default' => false,
        'licensable' => true,
        'plan_availability' => ['enterprise'],
    ],
];

$plans = ['starter', 'professional', 'enterprise'];
$planModules = [];

foreach ($plans as $plan) {
    if ($plan === 'enterprise') {
        $planModules[$plan] = '*';

        continue;
    }

    $planModules[$plan] = array_values(array_keys(array_filter(
        $modules,
        fn (array $module) => in_array($plan, $module['plan_availability'] ?? [], true)
    )));
}

$workspaceModuleMap = [];
foreach ($modules as $key => $module) {
    $workspace = $module['workspace'] ?? null;
    if ($workspace && ! isset($workspaceModuleMap[$workspace])) {
        $workspaceModuleMap[$workspace] = $key;
    }
}

return [
    'modules' => $modules,

    'plan_modules' => $planModules,

    /**
     * Workspace IDs that never require a module license.
     */
    'always_available_workspaces' => [
        'home',
        'administration',
    ],

    /**
     * Primary module key for each navigable workspace.
     */
    'workspace_module_map' => $workspaceModuleMap,

    /**
     * Default landing workspace when the user has no last_workspace preference.
     */
    'default_workspace' => 'crm',

    /**
     * Organization settings defaults provisioned during upgrades.
     */
    'default_feature_toggles' => [
        'command_palette' => true,
        'global_search' => true,
        'ai_assist' => false,
        'advanced_workflows' => true,
        'public_api' => true,
        'email_digests' => true,
    ],

    'default_workspace_visibility' => [
        'crm' => true,
        'projects' => true,
        'hr' => true,
        'marketing' => true,
        'operations' => true,
        'analytics' => true,
        'administration' => true,
    ],

    'default_landing_pages' => [
        'default' => 'dashboard',
        'sales' => 'crm.home',
        'employee' => 'ess.dashboard',
        'manager' => 'hrms.manager.dashboard',
        'hr' => 'hrms.home',
        'project_manager' => 'projects.home',
        'admin' => 'administration.home',
        'owner' => 'dashboard',
    ],
];
