<?php

use App\Workflow\Actions\AddNoteAction;
use App\Workflow\Actions\AssignOwnerAction;
use App\Workflow\Actions\ChangeLeadStatusAction;
use App\Workflow\Actions\CreateActivityAction;
use App\Workflow\Actions\CreateTaskAction;
use App\Workflow\Actions\NotifyUserAction;
use App\Workflow\Actions\ReassignOwnerAction;
use App\Workflow\Actions\UpdateMetadataAction;

return [
    'max_depth' => 10,

    'triggers' => [
        'lead.created' => ['entity' => 'lead', 'label' => 'Lead created', 'description' => 'Runs when a lead is created.'],
        'lead.updated' => ['entity' => 'lead', 'label' => 'Lead updated', 'description' => 'Runs after lead details change.'],
        'lead.assigned' => ['entity' => 'lead', 'label' => 'Lead assigned', 'description' => 'Runs when a lead owner changes.'],
        'lead.converted' => ['entity' => 'lead', 'label' => 'Lead converted', 'description' => 'Runs when a lead is converted to a customer.'],
        'customer.created' => ['entity' => 'customer', 'label' => 'Customer created', 'description' => 'Runs when a customer is created.'],
        'customer.updated' => ['entity' => 'customer', 'label' => 'Customer updated', 'description' => 'Runs after customer details change.'],
        'opportunity.created' => ['entity' => 'opportunity', 'label' => 'Opportunity created', 'description' => 'Runs when an opportunity is created.'],
        'opportunity.stage_changed' => ['entity' => 'opportunity', 'label' => 'Opportunity stage changed', 'description' => 'Runs when an opportunity moves stage.'],
        'invoice.created' => ['entity' => 'invoice', 'label' => 'Invoice created', 'description' => 'Runs when an invoice is created.'],
        'payment.received' => ['entity' => 'payment', 'label' => 'Payment received', 'description' => 'Runs when a payment is recorded.'],
        'marketing.lead_imported' => ['entity' => 'lead', 'label' => 'Marketing lead imported', 'description' => 'Runs when a marketing provider imports a lead.'],
    ],

    'operators' => [
        'equals', 'not_equals', 'contains', 'does_not_contain', 'starts_with',
        'ends_with', 'greater_than', 'greater_than_equal', 'less_than',
        'less_than_equal', 'between', 'in_list', 'not_in_list', 'empty', 'not_empty',
    ],

    'operator_definitions' => [
        'equals' => ['label' => 'Equals', 'value_type' => 'single'],
        'not_equals' => ['label' => 'Does not equal', 'value_type' => 'single'],
        'contains' => ['label' => 'Contains', 'value_type' => 'single'],
        'does_not_contain' => ['label' => 'Does not contain', 'value_type' => 'single'],
        'starts_with' => ['label' => 'Starts with', 'value_type' => 'single'],
        'ends_with' => ['label' => 'Ends with', 'value_type' => 'single'],
        'greater_than' => ['label' => 'Greater than', 'value_type' => 'single'],
        'greater_than_equal' => ['label' => 'Greater than or equal', 'value_type' => 'single'],
        'less_than' => ['label' => 'Less than', 'value_type' => 'single'],
        'less_than_equal' => ['label' => 'Less than or equal', 'value_type' => 'single'],
        'between' => ['label' => 'Between', 'value_type' => 'between'],
        'in_list' => ['label' => 'In list', 'value_type' => 'list'],
        'not_in_list' => ['label' => 'Not in list', 'value_type' => 'list'],
        'empty' => ['label' => 'Is empty', 'value_type' => 'none'],
        'not_empty' => ['label' => 'Is not empty', 'value_type' => 'none'],
    ],

    'actions' => [
        'assign_owner' => [
            'label' => 'Assign owner automatically', 'description' => 'Uses the configured assignment rules.',
            'handler' => AssignOwnerAction::class, 'entities' => ['lead', 'customer', 'opportunity'], 'fields' => [], 'form_fields' => [],
        ],
        'reassign_owner' => [
            'label' => 'Assign a specific owner', 'description' => 'Assigns the record to an organization member.',
            'handler' => ReassignOwnerAction::class, 'entities' => ['lead', 'customer', 'opportunity'], 'fields' => ['user_id'],
            'form_fields' => ['user_id' => ['label' => 'Owner', 'type' => 'user', 'required' => true]],
        ],
        'create_task' => [
            'label' => 'Create task', 'description' => 'Creates a task related to the triggering record.',
            'handler' => CreateTaskAction::class, 'entities' => ['lead', 'customer', 'opportunity'], 'fields' => ['title'],
            'form_fields' => [
                'title' => ['label' => 'Title', 'type' => 'text', 'required' => true],
                'description' => ['label' => 'Description', 'type' => 'textarea'],
                'priority' => ['label' => 'Priority', 'type' => 'select', 'options' => ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High']],
                'due_at' => ['label' => 'Due date/time', 'type' => 'datetime-local'],
                'assigned_to' => ['label' => 'Assignee', 'type' => 'user'],
            ],
        ],
        'create_activity' => [
            'label' => 'Create activity', 'description' => 'Adds an activity entry to the record timeline.',
            'handler' => CreateActivityAction::class, 'entities' => ['lead', 'customer', 'opportunity', 'invoice', 'payment'], 'fields' => ['event'],
            'form_fields' => [
                'event' => ['label' => 'Event name', 'type' => 'text', 'required' => true],
                'properties' => ['label' => 'Properties', 'type' => 'key_value'],
            ],
        ],
        'add_note' => [
            'label' => 'Add note', 'description' => 'Adds an internal note to the record.',
            'handler' => AddNoteAction::class, 'entities' => ['lead', 'customer', 'opportunity'], 'fields' => ['body'],
            'form_fields' => ['body' => ['label' => 'Note', 'type' => 'textarea', 'required' => true]],
        ],
        'change_lead_status' => [
            'label' => 'Change lead status', 'description' => 'Moves a lead to another status.',
            'handler' => ChangeLeadStatusAction::class, 'entities' => ['lead'], 'fields' => ['status'],
            'form_fields' => ['status' => ['label' => 'Status', 'type' => 'select', 'required' => true, 'options' => [
                'new' => 'New', 'contacted' => 'Contacted', 'qualified' => 'Qualified',
                'converted' => 'Converted', 'won' => 'Won', 'lost' => 'Lost',
            ]]],
        ],
        'update_metadata' => [
            'label' => 'Update metadata', 'description' => 'Sets one or more dynamic metadata values.',
            'handler' => UpdateMetadataAction::class, 'entities' => ['lead', 'customer', 'opportunity'], 'fields' => ['values'],
            'form_fields' => ['values' => ['label' => 'Metadata values', 'type' => 'key_value', 'required' => true]],
        ],
        'notify_user' => [
            'label' => 'Notify user', 'description' => 'Sends an in-app notification to an organization member.',
            'handler' => NotifyUserAction::class, 'entities' => ['lead', 'customer', 'opportunity', 'invoice', 'payment'], 'fields' => ['user_id', 'title', 'message'],
            'form_fields' => [
                'user_id' => ['label' => 'Recipient', 'type' => 'user', 'required' => true],
                'title' => ['label' => 'Title', 'type' => 'text', 'required' => true],
                'message' => ['label' => 'Message', 'type' => 'textarea', 'required' => true],
                'action_url' => ['label' => 'Action URL', 'type' => 'url'],
            ],
        ],
    ],
];
