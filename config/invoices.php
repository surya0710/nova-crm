<?php

return [
    'statuses' => [
        'draft' => 'Draft',
        'issued' => 'Issued',
        'partially_paid' => 'Partially Paid',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled',
    ],

    /*
    | Allowed manual status transitions. Empty = final.
    | partially_paid and paid are system-managed via payment sync.
    */
    'transitions' => [
        'draft' => ['issued', 'cancelled'],
        'issued' => ['cancelled'],
        'partially_paid' => ['cancelled'],
        'paid' => [],
        'cancelled' => [],
    ],

    'system_managed_statuses' => ['partially_paid', 'paid'],

    'fully_editable_statuses' => ['draft'],

    'deletable_statuses' => ['draft'],

    'currencies' => [
        'USD' => 'USD',
        'EUR' => 'EUR',
        'GBP' => 'GBP',
        'INR' => 'INR',
        'AUD' => 'AUD',
        'CAD' => 'CAD',
    ],
];
