<?php

return [
    'schema_version' => 1,

    'statuses' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'inactive' => 'Inactive',
        'archived' => 'Archived',
    ],

    'version_statuses' => [
        'published' => 'Published',
        'superseded' => 'Superseded',
        'archived' => 'Archived',
    ],

    'visibility' => [
        'internal' => 'Internal',
        'public' => 'Public',
        'private' => 'Private',
    ],

    'application_types' => [
        'initial_onboarding' => 'Initial onboarding',
    ],

    'dashboard_widgets' => [
        'lead_counts',
        'recent_leads',
        'upcoming_tasks',
        'pipeline_value',
        'revenue_summary',
        'outstanding_receivables',
    ],

    'report_types' => [
        'lead_status_summary',
        'pipeline_value',
        'conversion_rate',
        'revenue_collected',
        'outstanding_receivables',
        'customer_statement',
    ],
];
