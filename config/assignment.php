<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Assignment Strategies
    |--------------------------------------------------------------------------
    |
    | Strategy keys are stored on pools and rules. Implementations are resolved
    | through AssignmentStrategyRegistry.
    |
    */

    'strategies' => [
        'round_robin' => App\Services\Assignment\Strategies\RoundRobinStrategy::class,
        'weighted_round_robin' => App\Services\Assignment\Strategies\WeightedRoundRobinStrategy::class,
        'least_loaded' => App\Services\Assignment\Strategies\LeastLoadedStrategy::class,
        'manual_queue' => App\Services\Assignment\Strategies\ManualQueueStrategy::class,
    ],

    'strategy_labels' => [
        'round_robin' => 'Round Robin',
        'weighted_round_robin' => 'Weighted Round Robin',
        'least_loaded' => 'Least Loaded',
        'manual_queue' => 'Manual Queue',
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Entity Types
    |--------------------------------------------------------------------------
    |
    | Assignment Platform is entity-agnostic. Lead is the first consumer.
    |
    */

    'entity_types' => [
        'lead' => 'Lead',
        'customer' => 'Customer',
        'opportunity' => 'Opportunity',
        'task' => 'Task',
        'support_ticket' => 'Support Ticket',
        'hrms_request' => 'HRMS Request',
    ],

    /*
    |--------------------------------------------------------------------------
    | Assignment Reasons
    |--------------------------------------------------------------------------
    */

    'reasons' => [
        'automatic' => 'Automatic',
        'manual' => 'Manual',
        'reassigned' => 'Reassigned',
        'imported' => 'Imported',
        'api' => 'API',
    ],

    /*
    |--------------------------------------------------------------------------
    | Least Loaded Workload (v1)
    |--------------------------------------------------------------------------
    |
    | For least_loaded strategy on leads: count open (non-terminal) leads only.
    |
    */

    'least_loaded' => [
        'lead' => [
            'model' => App\Models\Lead::class,
            'owner_column' => 'assigned_to',
            'open_statuses_excluded' => ['converted', 'won', 'lost'],
        ],
    ],

];
