<?php

return [
    // Use attendance.label — __('Attendance') collides with this file on case-insensitive filesystems.
    'label' => 'Attendance',
    'periods' => [
        'created' => 'Attendance period created successfully.',
        'frozen' => 'Attendance period frozen successfully.',
        'locked' => 'Attendance period locked and snapshot generated.',
        'reopened' => 'Attendance period reopened successfully.',
    ],
    'overtime' => [
        'rule_created' => 'Overtime rule created successfully.',
        'rule_updated' => 'Overtime rule updated successfully.',
        'rule_activated' => 'Overtime rule activated successfully.',
        'rule_deactivated' => 'Overtime rule deactivated successfully.',
        'approved' => 'Overtime entry approved successfully.',
        'rejected' => 'Overtime entry rejected successfully.',
        'only_pending_can_be_approved' => 'Only pending overtime entries can be approved.',
        'only_pending_can_be_rejected' => 'Only pending overtime entries can be rejected.',
        'attributes' => [
            'name' => 'name',
            'code' => 'code',
            'rule_type' => 'rule type',
            'minimum_minutes' => 'minimum minutes',
            'maximum_minutes' => 'maximum minutes',
            'round_off_minutes' => 'round-off minutes',
            'multiplier' => 'multiplier',
            'requires_approval' => 'requires approval',
            'is_active' => 'active',
            'review_notes' => 'review notes',
        ],
        'validation' => [
            'rule_type' => 'The selected rule type is invalid.',
            'maximum_gte_minimum' => 'Maximum minutes must be greater than or equal to minimum minutes.',
            'review_notes_max' => 'Review notes may not be greater than 2000 characters.',
        ],
    ],
];
