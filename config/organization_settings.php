<?php

/**
 * Organization Settings navigation catalog (Phase 11.8.1 / 14.7).
 *
 * Modules own operational pages; Organization Settings owns configuration.
 */
return [

    'sections' => [

        'profile' => [
            'label' => 'Organization Profile',
            'route' => 'organization.edit',
            'permission' => 'settings.manage',
            'group' => 'organization',
        ],
        'subscription' => [
            'label' => 'Subscription',
            'route' => 'organization.settings.subscription',
            'permission' => 'settings.manage',
            'group' => 'organization',
        ],
        'billing' => [
            'label' => 'Billing',
            'route' => 'organization.settings.billing',
            'permission' => 'settings.manage',
            'group' => 'organization',
        ],
        'branding' => [
            'label' => 'Branding',
            'route' => 'administration.branding.edit',
            'permission' => 'settings.manage',
            'group' => 'organization',
        ],
        'modules' => [
            'label' => 'Modules & Features',
            'route' => 'administration.modules.index',
            'permission' => 'settings.manage',
            'group' => 'organization',
        ],
        'users' => [
            'label' => 'Users',
            'route' => 'team.index',
            'permission' => 'users.view',
            'group' => 'organization',
        ],
        'branches' => [
            'label' => 'Branches',
            'route' => 'organization.settings.branches.index',
            'permission' => 'organization.branches.view',
            'fallback_permission' => 'hrms.view',
            'group' => 'structure',
        ],
        'departments' => [
            'label' => 'Departments',
            'route' => 'organization.settings.departments.index',
            'permission' => 'hrms.view',
            'group' => 'structure',
        ],
        'designations' => [
            'label' => 'Designations',
            'route' => 'organization.settings.designations.index',
            'permission' => 'hrms.view',
            'group' => 'structure',
        ],
        'reporting_structure' => [
            'label' => 'Reporting Structure',
            'route' => 'organization.settings.departments.index',
            'permission' => 'hrms.view',
            'group' => 'structure',
        ],
        'working_days' => [
            'label' => 'Working Days',
            'route' => 'organization.settings.working-days.edit',
            'permission' => 'organization.hr_config.manage',
            'fallback_permission' => 'leave.manage',
            'group' => 'hr_config',
        ],
        'business_hours' => [
            'label' => 'Business Hours',
            'route' => 'organization.settings.working-days.edit',
            'permission' => 'organization.hr_config.manage',
            'fallback_permission' => 'leave.manage',
            'group' => 'hr_config',
        ],
        'shifts' => [
            'label' => 'Shift Management',
            'route' => 'organization.settings.shifts.index',
            'permission' => 'organization.shifts.view',
            'fallback_permission' => 'attendance.view',
            'group' => 'hr_config',
        ],
        'holidays' => [
            'label' => 'Holiday Calendar',
            'route' => 'organization.settings.holidays.index',
            'permission' => 'leave.view',
            'group' => 'hr_config',
        ],
        'leave_types' => [
            'label' => 'Leave Types',
            'route' => 'organization.settings.leave-types.index',
            'permission' => 'leave.view',
            'group' => 'hr_config',
        ],
        'leave_policies' => [
            'label' => 'Leave Policies',
            'route' => 'organization.settings.leave-policies.edit',
            'permission' => 'organization.hr_config.manage',
            'fallback_permission' => 'leave.manage',
            'group' => 'hr_config',
        ],
        'leave_approvers' => [
            'label' => 'Leave Approvers',
            'route' => 'organization.settings.leave-approvers.edit',
            'permission' => 'organization.hr_config.manage',
            'fallback_permission' => 'leave.manage',
            'group' => 'hr_config',
        ],
        'attendance_rules' => [
            'label' => 'Attendance Rules',
            'route' => 'organization.settings.attendance-rules.edit',
            'permission' => 'organization.hr_config.manage',
            'fallback_permission' => 'attendance.manage',
            'group' => 'hr_config',
        ],
        'crm_defaults' => [
            'label' => 'CRM Defaults',
            'route' => 'products.index',
            'permission' => 'products.view',
            'group' => 'crm_config',
            'optional' => true,
        ],
        'assignments' => [
            'label' => 'Assignment Settings',
            'route' => 'organization.settings.assignments.index',
            'permission' => 'assignments.view',
            'group' => 'crm_config',
        ],
        'project_defaults' => [
            'label' => 'Project Defaults',
            'route' => 'projects.index',
            'permission' => 'projects.view',
            'group' => 'project_config',
            'optional' => true,
        ],
        'security' => [
            'label' => 'Security',
            'route' => 'administration.security.index',
            'permission' => 'settings.manage',
            'group' => 'security',
        ],
        'access_control' => [
            'label' => 'Access Control',
            'route' => 'rbac.roles.index',
            'permission' => 'rbac.view',
            'group' => 'security',
        ],
        'audit' => [
            'label' => 'Audit Logs',
            'route' => 'audit-logs.index',
            'permission' => 'audit.view',
            'group' => 'security',
        ],
        'developer' => [
            'label' => 'Developer',
            'route' => 'administration.developer.index',
            'permission' => 'settings.manage',
            'fallback_permission' => 'api.tokens',
            'group' => 'platform',
        ],
        'dashboard' => [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'permission' => 'dashboard.view',
            'fallback_permission' => 'settings.manage',
            'group' => 'platform',
            'optional' => true,
        ],
        'notifications' => [
            'label' => 'Notifications',
            'route' => 'organization.settings.notifications.edit',
            'permission' => 'settings.manage',
            'group' => 'platform',
        ],
        'email' => [
            'label' => 'Email',
            'route' => 'organization.edit',
            'permission' => 'settings.manage',
            'group' => 'platform',
            'query' => ['tab' => 'email'],
        ],
        'integrations' => [
            'label' => 'Integrations',
            'route' => 'integrations.index',
            'permission' => 'integrations.view',
            'group' => 'platform',
        ],
        'api' => [
            'label' => 'API',
            'route' => 'api-tokens.index',
            'permission' => 'api.tokens',
            'group' => 'platform',
        ],
    ],

    'groups' => [
        'organization' => 'Organization',
        'structure' => 'Structure',
        'hr_config' => 'HR Configuration',
        'crm_config' => 'CRM Configuration',
        'project_config' => 'Project Configuration',
        'security' => 'Security',
        'platform' => 'Platform',
    ],

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
