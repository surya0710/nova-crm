<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Quotation;

return [
    'max_files' => 10,
    'max_size_kb' => 10240,
    'allowed_mimes' => [
        'pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp',
        'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'zip',
    ],

    'attachable' => [
        'lead' => Lead::class,
        'customer' => Customer::class,
        'invoice' => Invoice::class,
        'quotation' => Quotation::class,
        'opportunity' => Opportunity::class,
    ],
];
