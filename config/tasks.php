<?php

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Project;

return [
    /*
    |--------------------------------------------------------------------------
    | Legacy CRM task statuses / priorities (string columns on tasks)
    |--------------------------------------------------------------------------
    */
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
        'lead' => Lead::class,
        'customer' => Customer::class,
        'opportunity' => Opportunity::class,
        'project' => Project::class,
    ],

    'number_prefix' => 'TASK',
    'number_padding' => 4,

    'default_statuses' => [
        ['name' => 'To Do', 'slug' => 'to-do', 'color' => '#94a3b8', 'is_default' => true, 'is_closed' => false, 'sort_order' => 10],
        ['name' => 'In Progress', 'slug' => 'in-progress', 'color' => '#0ea5e9', 'is_default' => false, 'is_closed' => false, 'sort_order' => 20],
        ['name' => 'Review', 'slug' => 'review', 'color' => '#a855f7', 'is_default' => false, 'is_closed' => false, 'sort_order' => 30],
        ['name' => 'Blocked', 'slug' => 'blocked', 'color' => '#f59e0b', 'is_default' => false, 'is_closed' => false, 'sort_order' => 40],
        ['name' => 'Completed', 'slug' => 'completed', 'color' => '#22c55e', 'is_default' => false, 'is_closed' => true, 'sort_order' => 50],
        ['name' => 'Cancelled', 'slug' => 'cancelled', 'color' => '#ef4444', 'is_default' => false, 'is_closed' => true, 'sort_order' => 60],
    ],

    'default_priorities' => [
        ['name' => 'Low', 'slug' => 'low', 'color' => '#94a3b8', 'level' => 1, 'is_default' => false],
        ['name' => 'Medium', 'slug' => 'medium', 'color' => '#0ea5e9', 'level' => 2, 'is_default' => true],
        ['name' => 'High', 'slug' => 'high', 'color' => '#f59e0b', 'level' => 3, 'is_default' => false],
        ['name' => 'Critical', 'slug' => 'critical', 'color' => '#ef4444', 'level' => 4, 'is_default' => false],
    ],

    'dependency_types' => [
        'finish_to_start' => 'Finish to Start',
        'start_to_start' => 'Start to Start',
        'finish_to_finish' => 'Finish to Finish',
        'start_to_finish' => 'Start to Finish',
    ],

    'time_log_sources' => [
        'manual' => 'Manual',
        'timer' => 'Timer',
        'import' => 'Import',
    ],

    /*
    |--------------------------------------------------------------------------
    | Map legacy CRM status strings → work-management TaskStatus slugs
    |--------------------------------------------------------------------------
    */
    'legacy_status_slug_map' => [
        'pending' => 'to-do',
        'in_progress' => 'in-progress',
        'completed' => 'completed',
        'cancelled' => 'cancelled',
    ],

    /*
    |--------------------------------------------------------------------------
    | Map work-management TaskStatus slugs → legacy CRM status strings
    |--------------------------------------------------------------------------
    */
    'status_slug_legacy_map' => [
        'to-do' => 'pending',
        'in-progress' => 'in_progress',
        'review' => 'in_progress',
        'blocked' => 'in_progress',
        'completed' => 'completed',
        'cancelled' => 'cancelled',
    ],

    /*
    |--------------------------------------------------------------------------
    | Map work-management TaskPriority slugs → legacy CRM priority strings
    |--------------------------------------------------------------------------
    */
    'priority_slug_legacy_map' => [
        'low' => 'low',
        'medium' => 'medium',
        'high' => 'high',
        'critical' => 'high',
    ],
];
