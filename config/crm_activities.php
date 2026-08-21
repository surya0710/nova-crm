<?php

return [
    'types' => [
        'task' => 'Task',
        'call' => 'Call',
        'meeting' => 'Meeting',
        'follow_up' => 'Follow-up',
        'note' => 'Note',
        'email' => 'Email',
    ],

    'statuses' => [
        'open' => 'Open',
        'completed' => 'Completed',
    ],

    'priorities' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ],

    'open_types' => [
        'task',
        'follow_up',
        'meeting',
    ],

    'directions' => [
        'outbound' => 'Outbound',
        'inbound' => 'Inbound',
    ],

    'outcomes' => [
        'connected' => 'Connected',
        'no_answer' => 'No answer',
        'left_voicemail' => 'Left voicemail',
        'busy' => 'Busy',
        'completed' => 'Completed',
        'rescheduled' => 'Rescheduled',
        'cancelled' => 'Cancelled',
        'sent' => 'Sent',
        'received' => 'Received',
    ],
];
