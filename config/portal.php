<?php

return [
    'deliverable_statuses' => [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'client_review' => 'Client Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'revised' => 'Revised',
        'completed' => 'Completed',
    ],

    'approval_statuses' => [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'client_review' => 'Client Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'revised' => 'Revised',
    ],

    'share_scopes' => [
        'project_summary' => 'Project summary',
        'milestones' => 'Milestones',
        'deliverables' => 'Deliverables',
        'documents' => 'Documents',
        'invoices' => 'Invoices',
        'reports' => 'Reports',
        'discussions' => 'Discussions',
        'tasks' => 'Shared tasks',
    ],

    'default_share_scopes' => [
        'project_summary',
        'milestones',
        'deliverables',
        'documents',
        'discussions',
    ],

    'shared_link_ttl_hours' => 72,

    'upload_request_statuses' => [
        'open' => 'Open',
        'fulfilled' => 'Fulfilled',
        'cancelled' => 'Cancelled',
        'expired' => 'Expired',
    ],
];
