<?php

return [
    'max_files' => 10,
    'max_size_kb' => 10240,
    'allowed_mimes' => [
        'pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp',
        'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'zip',
    ],

    'attachable' => [
        'lead' => \App\Models\Lead::class,
        'customer' => \App\Models\Customer::class,
        'invoice' => \App\Models\Invoice::class,
        'quotation' => \App\Models\Quotation::class,
        'opportunity' => \App\Models\Opportunity::class,
    ],
];
