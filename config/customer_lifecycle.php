<?php

return [
    'stages' => [
        'subscriber',
        'lead',
        'marketing_qualified',
        'sales_qualified',
        'opportunity',
        'customer',
        'evangelist',
    ],

    'milestones' => [
        'customer.created' => null,
        'opportunity.created' => 'opportunity',
        'opportunity.won' => 'customer',
        'opportunity.lost' => null,
        'quotation.accepted' => 'opportunity',
        'sales_order.confirmed' => 'customer',
        'customer.first_invoice' => 'customer',
        'customer.first_payment' => 'evangelist',
    ],
];
