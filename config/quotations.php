<?php

return [
    'statuses' => [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
        'expired' => 'Expired',
        'converted' => 'Converted',
    ],

    /*
    | Allowed status transitions. Empty array = final status (no further changes).
    */
    'transitions' => [
        'draft' => ['sent'],
        'sent' => ['accepted', 'rejected', 'expired'],
        'accepted' => ['converted'],
        'converted' => [],
        'rejected' => [],
        'expired' => [],
    ],

    /*
    | Statuses that allow content editing (line items, customer, totals, etc.).
    */
    'editable_statuses' => ['draft', 'sent'],

    /*
    | Statuses that allow deletion.
    */
    'deletable_statuses' => ['draft', 'sent'],

    'currencies' => [
        'USD' => 'USD',
        'EUR' => 'EUR',
        'GBP' => 'GBP',
        'INR' => 'INR',
        'AUD' => 'AUD',
        'CAD' => 'CAD',
    ],
];
