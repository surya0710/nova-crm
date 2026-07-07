<?php

return [
    'statuses' => [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'priorities' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ],

    'taskable' => [
        'lead' => \App\Models\Lead::class,
        'customer' => \App\Models\Customer::class,
        'opportunity' => \App\Models\Opportunity::class,
    ],
];
