<?php

return [
    'entities' => [
        'lead' => 'Lead',
        'customer' => 'Customer',
        'opportunity' => 'Opportunity',
        'organization' => 'Organization',
    ],

    'field_types' => [
        'text' => 'Text',
        'textarea' => 'Textarea',
        'number' => 'Number',
        'decimal' => 'Decimal',
        'currency' => 'Currency',
        'percentage' => 'Percentage',
        'date' => 'Date',
        'datetime' => 'Date & Time',
        'time' => 'Time',
        'boolean' => 'Boolean',
        'select' => 'Select',
        'multi_select' => 'Multi Select',
        'radio' => 'Radio',
        'email' => 'Email',
        'url' => 'URL',
        'phone' => 'Phone',
        'user' => 'User',
        'team' => 'Team',
    ],

    'option_field_types' => [
        'select',
        'multi_select',
        'radio',
    ],

    'statuses' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'archived' => 'Archived',
    ],

    'sources' => [
        'manual' => 'Manual',
        'industry_template' => 'Industry Template',
        'system' => 'System',
        'import' => 'Import',
    ],

    'layout_contexts' => [
        'create' => 'Create Form',
        'edit' => 'Edit Form',
        'detail' => 'Detail View',
        'quick_create' => 'Quick Create',
        'import' => 'Import Mapping',
        'api' => 'API Contract',
    ],

    'permission_actions' => [
        'view' => 'View value',
        'edit' => 'Edit value',
        'export' => 'Export value',
        'report' => 'Use in reports',
        'api_read' => 'Read through API',
        'api_write' => 'Write through API',
        'view_sensitive' => 'View sensitive value',
    ],
];
