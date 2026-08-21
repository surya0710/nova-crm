<?php

return [
    'types' => [
        'credit' => 'Credit Note',
        'debit' => 'Debit Note',
    ],

    'prefixes' => [
        'credit' => 'CN',
        'debit' => 'DN',
    ],

    'statuses' => [
        'draft' => 'Draft',
        'issued' => 'Issued',
        'applied' => 'Applied',
        'cancelled' => 'Cancelled',
    ],

    'transitions' => [
        'draft' => ['issued', 'cancelled'],
        'issued' => ['applied', 'cancelled'],
        'applied' => [],
        'cancelled' => [],
    ],

    'editable_statuses' => ['draft'],

    'deletable_statuses' => ['draft'],

    'applyable_statuses' => ['issued'],

    'reasons' => [
        'price_adjustment' => 'Price adjustment',
        'goods_returned' => 'Goods returned',
        'discount' => 'Discount / rebate',
        'billing_error' => 'Billing error',
        'tax_adjustment' => 'Tax adjustment',
        'additional_charges' => 'Additional charges',
        'other' => 'Other',
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
