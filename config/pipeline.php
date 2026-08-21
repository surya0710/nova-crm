<?php

return [
    'stages' => [
        'qualification' => 'Qualification',
        'proposal' => 'Proposal',
        'negotiation' => 'Negotiation',
        'closed_won' => 'Closed Won',
        'closed_lost' => 'Closed Lost',
    ],

    'open_stages' => [
        'qualification',
        'proposal',
        'negotiation',
    ],

    'closed_stages' => [
        'closed_won',
        'closed_lost',
    ],

    'lost_reasons' => [
        'Budget constraints',
        'Chose a competitor',
        'No decision / postponed',
        'Requirements not met',
        'Lost contact',
        'Other',
    ],

    'stage_probabilities' => [
        'qualification' => 20,
        'proposal' => 50,
        'negotiation' => 75,
        'closed_won' => 100,
        'closed_lost' => 0,
    ],

    'contact_roles' => [
        'decision_maker' => 'Decision maker',
        'champion' => 'Champion',
        'influencer' => 'Influencer',
        'evaluator' => 'Evaluator',
        'other' => 'Other',
    ],
];
