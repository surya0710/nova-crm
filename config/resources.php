<?php

/**
 * Resource Planning & Workload Management catalogs and defaults.
 *
 * Concurrent allocations for the same employee are allowed, but the sum of
 * allocation_percentage values on any overlapping calendar day cannot exceed
 * max_allocation_percentage (default 100, configurable).
 */
return [
    'allocation_types' => [
        'project' => 'Project',
        'task' => 'Task',
        'support' => 'Support',
        'internal' => 'Internal',
        'leave' => 'Leave',
        'training' => 'Training',
    ],

    'default_working_hours_per_day' => 8,

    // Match HRMS / org settings format (full weekday names).
    'default_working_days' => [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
    ],

    'max_allocation_percentage' => 100,

    'overallocation_threshold' => 100,

    'underutilization_threshold' => 50,

    'utilization_statuses' => [
        'underutilized' => 'Underutilized',
        'optimal' => 'Optimal',
        'overallocated' => 'Overallocated',
    ],

    'capacity_risk_days' => 14,

    /*
    |--------------------------------------------------------------------------
    | Workflow trigger catalog (registered in config/workflows.php)
    |--------------------------------------------------------------------------
    */
    'workflow_triggers' => [
        'resource.allocated' => [
            'entity' => 'resource_allocation',
            'label' => 'Resource allocated',
            'description' => 'Runs when a resource allocation is created.',
        ],
        'resource.allocation_updated' => [
            'entity' => 'resource_allocation',
            'label' => 'Resource allocation updated',
            'description' => 'Runs when a resource allocation is updated.',
        ],
        'resource.released' => [
            'entity' => 'resource_allocation',
            'label' => 'Resource released',
            'description' => 'Runs when a resource allocation is deleted/released.',
        ],
        'resource.capacity_exceeded' => [
            'entity' => 'resource_allocation',
            'label' => 'Capacity exceeded',
            'description' => 'Runs when an allocation pushes an employee over capacity.',
        ],
        'resource.overallocation_detected' => [
            'entity' => 'resource_allocation',
            'label' => 'Overallocation detected',
            'description' => 'Runs when overallocation is detected for an employee.',
        ],
    ],
];
