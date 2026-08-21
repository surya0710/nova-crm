<?php

return [
    'defaults' => [
        'invoice_due_reminders' => true,
        'invoice_due_days_before' => 3,
        'invoice_overdue_reminders' => true,
        'payment_confirmation' => true,
        'payment_receipt' => true,
        'quotation_expiry_reminders' => true,
        'quotation_expiry_days_before' => 2,
        'sales_order_notifications' => true,
        'payment_gateway' => '',
    ],

    'gateways' => [
        '' => 'Not configured',
        'test' => 'Test gateway (records payment immediately)',
    ],
];
