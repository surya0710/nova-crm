<?php

return [
    'priorities' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ],

    'milestone_statuses' => [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'roles' => [
        'owner' => 'Project Owner',
        'manager' => 'Project Manager',
        'delivery_lead' => 'Delivery Lead',
        'team_lead' => 'Team Lead',
        'team_member' => 'Team Member',
        'stakeholder' => 'Stakeholder',
        'viewer' => 'Viewer',
    ],

    'number_prefix' => 'PRJ',
    'number_padding' => 4,

    /*
    |--------------------------------------------------------------------------
    | Progress & health (Phase 12.4)
    |--------------------------------------------------------------------------
    */
    'completion_weights' => [
        'task' => 0.5,
        'milestone' => 0.3,
        'manual' => 0.2,
    ],

    'health_statuses' => [
        'on_track' => 'On Track',
        'at_risk' => 'At Risk',
        'delayed' => 'Delayed',
        'completed' => 'Completed',
        'archived' => 'Archived',
    ],

    'health_thresholds' => [
        'overdue_tasks_at_risk' => 1,
        'overdue_tasks_delayed' => 3,
        'missed_milestones_at_risk' => 1,
        'missed_milestones_delayed' => 2,
        'schedule_variance_at_risk_days' => 3,
        'schedule_variance_delayed_days' => 7,
        'completion_trend_decline_at_risk' => -5,
    ],

    'report_types' => [
        'summary' => 'Project Summary',
        'task_progress' => 'Task Progress',
        'resource_utilization' => 'Resource Utilization',
        'milestone_status' => 'Milestone Status',
        'time_tracking' => 'Time Tracking',
        'timeline' => 'Project Timeline',
        'executive' => 'Executive Summary',
    ],

    'report_formats' => [
        'pdf' => 'PDF',
        'excel' => 'Excel',
        'csv' => 'CSV',
    ],

    'default_categories' => [
        ['name' => 'Software Development', 'slug' => 'software-development', 'color' => '#4f46e5', 'icon' => 'code', 'sort_order' => 10],
        ['name' => 'Implementation', 'slug' => 'implementation', 'color' => '#0ea5e9', 'icon' => 'rocket', 'sort_order' => 20],
        ['name' => 'Migration', 'slug' => 'migration', 'color' => '#14b8a6', 'icon' => 'arrows', 'sort_order' => 30],
        ['name' => 'Support', 'slug' => 'support', 'color' => '#22c55e', 'icon' => 'lifebuoy', 'sort_order' => 40],
        ['name' => 'Internal', 'slug' => 'internal', 'color' => '#64748b', 'icon' => 'building', 'sort_order' => 50],
        ['name' => 'Consulting', 'slug' => 'consulting', 'color' => '#a855f7', 'icon' => 'chat', 'sort_order' => 60],
        ['name' => 'Research', 'slug' => 'research', 'color' => '#f59e0b', 'icon' => 'beaker', 'sort_order' => 70],
        ['name' => 'Marketing', 'slug' => 'marketing', 'color' => '#ec4899', 'icon' => 'megaphone', 'sort_order' => 80],
        ['name' => 'HR', 'slug' => 'hr', 'color' => '#f97316', 'icon' => 'users', 'sort_order' => 90],
        ['name' => 'Operations', 'slug' => 'operations', 'color' => '#6366f1', 'icon' => 'cog', 'sort_order' => 100],
    ],

    'default_types' => [
        ['name' => 'Fixed Cost', 'slug' => 'fixed-cost', 'default_duration' => 90, 'color' => '#4f46e5', 'sort_order' => 10],
        ['name' => 'Time & Material', 'slug' => 'time-material', 'default_duration' => 60, 'color' => '#0ea5e9', 'sort_order' => 20],
        ['name' => 'Internal', 'slug' => 'internal', 'default_duration' => 30, 'color' => '#64748b', 'sort_order' => 30],
        ['name' => 'Maintenance', 'slug' => 'maintenance', 'default_duration' => 365, 'color' => '#22c55e', 'sort_order' => 40],
        ['name' => 'Sprint', 'slug' => 'sprint', 'default_duration' => 14, 'color' => '#14b8a6', 'sort_order' => 50],
        ['name' => 'Client Implementation', 'slug' => 'client-implementation', 'default_duration' => 120, 'color' => '#a855f7', 'sort_order' => 60],
    ],

    'default_statuses' => [
        ['name' => 'Draft', 'slug' => 'draft', 'color' => '#94a3b8', 'is_default' => true, 'is_closed' => false, 'sort_order' => 10],
        ['name' => 'Planned', 'slug' => 'planned', 'color' => '#0ea5e9', 'is_default' => false, 'is_closed' => false, 'sort_order' => 20],
        ['name' => 'Active', 'slug' => 'active', 'color' => '#22c55e', 'is_default' => false, 'is_closed' => false, 'sort_order' => 30],
        ['name' => 'On Hold', 'slug' => 'on-hold', 'color' => '#f59e0b', 'is_default' => false, 'is_closed' => false, 'sort_order' => 40],
        ['name' => 'Completed', 'slug' => 'completed', 'color' => '#4f46e5', 'is_default' => false, 'is_closed' => true, 'sort_order' => 50],
        ['name' => 'Cancelled', 'slug' => 'cancelled', 'color' => '#ef4444', 'is_default' => false, 'is_closed' => true, 'sort_order' => 60],
        ['name' => 'Archived', 'slug' => 'archived', 'color' => '#64748b', 'is_default' => false, 'is_closed' => true, 'sort_order' => 70],
    ],

    'default_lifecycle_stages' => [
        ['name' => 'Planning', 'slug' => 'planning', 'description' => 'Define scope, objectives, and plan.', 'sequence' => 1, 'color' => '#94a3b8', 'is_default' => true],
        ['name' => 'Initiation', 'slug' => 'initiation', 'description' => 'Kick off and mobilize the team.', 'sequence' => 2, 'color' => '#0ea5e9', 'is_default' => false],
        ['name' => 'Execution', 'slug' => 'execution', 'description' => 'Deliver project work packages.', 'sequence' => 3, 'color' => '#22c55e', 'is_default' => false],
        ['name' => 'Monitoring', 'slug' => 'monitoring', 'description' => 'Track progress and manage risks.', 'sequence' => 4, 'color' => '#f59e0b', 'is_default' => false],
        ['name' => 'Closing', 'slug' => 'closing', 'description' => 'Handover, review, and close.', 'sequence' => 5, 'color' => '#4f46e5', 'is_default' => false],
    ],

    /*
    |--------------------------------------------------------------------------
    | Collaboration & automation (Phase 12.5)
    |--------------------------------------------------------------------------
    */
    'default_labels' => [
        ['name' => 'Urgent', 'color' => '#ef4444'],
        ['name' => 'Backend', 'color' => '#3b82f6'],
        ['name' => 'Frontend', 'color' => '#06b6d4'],
        ['name' => 'Bug', 'color' => '#dc2626'],
        ['name' => 'UI', 'color' => '#8b5cf6'],
        ['name' => 'Enhancement', 'color' => '#22c55e'],
        ['name' => 'Research', 'color' => '#f59e0b'],
        ['name' => 'Blocked', 'color' => '#64748b'],
        ['name' => 'QA', 'color' => '#ec4899'],
    ],

    'recurrence_types' => [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'yearly' => 'Yearly',
        'custom' => 'Custom',
    ],

    'recurrence_end_types' => [
        'never' => 'Never',
        'date' => 'On date',
        'occurrences' => 'After occurrences',
    ],

    'template_categories' => [
        'department' => 'Department',
        'industry' => 'Industry',
        'general' => 'General',
        'system' => 'System',
    ],

    'calendar_providers' => [
        'internal' => 'Internal',
        'google' => 'Google Calendar',
        'outlook' => 'Outlook Calendar',
    ],

    'notification_digest_frequencies' => [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
    ],

    /*
    |--------------------------------------------------------------------------
    | Portfolio / EPM (Phase 12.6)
    |--------------------------------------------------------------------------
    */
    'portfolio_statuses' => [
        'draft' => 'Draft',
        'active' => 'Active',
        'on_hold' => 'On Hold',
        'completed' => 'Completed',
        'archived' => 'Archived',
    ],

    'program_statuses' => [
        'draft' => 'Draft',
        'active' => 'Active',
        'on_hold' => 'On Hold',
        'completed' => 'Completed',
        'archived' => 'Archived',
    ],

    'dependency_types' => [
        'finish_to_start' => 'Finish to Start',
        'start_to_start' => 'Start to Start',
        'finish_to_finish' => 'Finish to Finish',
        'start_to_finish' => 'Start to Finish',
    ],

    'risk_categories' => [
        'schedule' => 'Schedule',
        'budget' => 'Budget',
        'resource' => 'Resource',
        'technical' => 'Technical',
        'external' => 'External',
        'compliance' => 'Compliance',
        'other' => 'Other',
    ],

    'risk_statuses' => [
        'open' => 'Open',
        'mitigating' => 'Mitigating',
        'monitoring' => 'Monitoring',
        'closed' => 'Closed',
        'escalated' => 'Escalated',
    ],

    'issue_priorities' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ],

    'issue_severities' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ],

    'issue_statuses' => [
        'open' => 'Open',
        'in_progress' => 'In Progress',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
        'deferred' => 'Deferred',
    ],

    'budget_statuses' => [
        'draft' => 'Draft',
        'approved' => 'Approved',
        'active' => 'Active',
        'closed' => 'Closed',
    ],

    'default_budget_categories' => [
        ['name' => 'Labor', 'slug' => 'labor', 'color' => '#4f46e5', 'sort_order' => 10],
        ['name' => 'Materials', 'slug' => 'materials', 'color' => '#0ea5e9', 'sort_order' => 20],
        ['name' => 'Software', 'slug' => 'software', 'color' => '#14b8a6', 'sort_order' => 30],
        ['name' => 'Travel', 'slug' => 'travel', 'color' => '#f59e0b', 'sort_order' => 40],
        ['name' => 'Contingency', 'slug' => 'contingency', 'color' => '#64748b', 'sort_order' => 50],
        ['name' => 'Other', 'slug' => 'other', 'color' => '#94a3b8', 'sort_order' => 60],
    ],

    'portfolio_report_types' => [
        'portfolio' => 'Portfolio Summary',
        'program' => 'Program Summary',
        'risk' => 'Risk Report',
        'budget' => 'Budget Report',
        'executive' => 'Executive Report',
        'variance' => 'Variance Report',
        'forecast' => 'Forecast Report',
    ],

    'portfolio_report_formats' => [
        'pdf' => 'PDF',
        'excel' => 'Excel',
        'csv' => 'CSV',
    ],

    'budget_variance_threshold_percent' => 10,
];
