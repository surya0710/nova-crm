<?php

return [
    'methods' => [
        'bank_transfer' => 'Bank Transfer',
        'cash' => 'Cash',
        'card' => 'Card',
        'cheque' => 'Cheque',
        'upi' => 'UPI',
        'online' => 'Online',
        'other' => 'Other',
    ],

    'methods_requiring_bank_details' => [
        'bank_transfer',
        'cheque',
    ],

    'payable_invoice_statuses' => [
        'issued',
        'partially_paid',
        'paid',
        'overpaid',
    ],

    'invoice_statuses' => [
        'unpaid' => 'Unpaid',
        'partial' => 'Partial',
        'paid' => 'Paid',
        'overpaid' => 'Overpaid',
    ],
];
