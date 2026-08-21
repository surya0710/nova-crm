<?php

return [
    'statuses' => [
        'draft' => 'Draft',
        'confirmed' => 'Confirmed',
        'processing' => 'Processing',
        'partially_fulfilled' => 'Partially Fulfilled',
        'fulfilled' => 'Fulfilled',
        'cancelled' => 'Cancelled',
    ],

    /*
    | Allowed status transitions. Empty array = final status (no further changes).
    */
    'transitions' => [
        'draft' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['partially_fulfilled', 'fulfilled', 'cancelled'],
        'partially_fulfilled' => ['fulfilled', 'cancelled'],
        'fulfilled' => [],
        'cancelled' => [],
    ],

    'editable_statuses' => ['draft'],

    'deletable_statuses' => ['draft'],

    'convertible_statuses' => [
        'draft',
        'confirmed',
        'processing',
        'partially_fulfilled',
        'fulfilled',
    ],

    'currencies' => [
        'USD' => 'USD',
        'EUR' => 'EUR',
        'GBP' => 'GBP',
        'INR' => 'INR',
        'AUD' => 'AUD',
        'CAD' => 'CAD',
    ],
];
