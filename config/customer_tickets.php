<?php

return [
    'statuses' => [
        'open' => 'Open',
        'pending' => 'Pending',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ],

    'priorities' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],

    'sla_hours' => [
        'urgent' => 4,
        'high' => 8,
        'medium' => 24,
        'low' => 48,
    ],

    'transitions' => [
        'open' => ['pending', 'resolved', 'closed'],
        'pending' => ['open', 'resolved', 'closed'],
        'resolved' => ['closed', 'open'],
        'closed' => ['open'],
    ],
];
