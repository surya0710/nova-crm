<?php

/**
 * HRMS Platform catalogs and defaults.
 *
 * Business services must read from this config rather than hardcoding values.
 * Workflow trigger keys under workflow_triggers are defined in
 * config/hrms_workflow_triggers.php and registered with the Workflow Platform
 * via config/workflows.php (Phase 10.7).
 */
return [
    'employment_statuses' => [
        'active' => 'Active',
        'probation' => 'Probation',
        'notice_period' => 'Notice Period',
        'resigned' => 'Resigned',
        'terminated' => 'Terminated',
        'retired' => 'Retired',
        'inactive' => 'Inactive',
    ],

    'employment_types' => [
        'full_time' => 'Full time',
        'part_time' => 'Part time',
        'contract' => 'Contract',
        'intern' => 'Intern',
        'consultant' => 'Consultant',
    ],

    'attendance_statuses' => [
        'present' => 'Present',
        'absent' => 'Absent',
        'late' => 'Late',
        'half_day' => 'Half day',
        'on_leave' => 'On leave',
        'holiday' => 'Holiday',
        'weekend' => 'Weekend',
        'pending' => 'Pending',
    ],

    'attendance_sources' => [
        'manual' => 'Manual',
        'biometric' => 'Biometric',
        'mobile' => 'Mobile',
        'api' => 'API',
        'import' => 'Import',
    ],

    'attendance_verification_modes' => [
        'none' => 'None',
        'gps' => 'GPS',
        'geofence' => 'Geofence',
        'biometric' => 'Biometric',
        'gps_and_biometric' => 'GPS and Biometric',
    ],

    'attendance_verification_modes_default' => 'none',

    'attendance_verification_statuses' => [
        'not_required' => 'Not required',
        'verified' => 'Verified',
        'failed' => 'Failed',
        'pending' => 'Pending',
    ],

    'wfh_policy_types' => [
        'none' => 'None',
        'permanent' => 'Permanent WFH',
        'daily' => 'Daily WFH',
        'selected_days' => 'Selected days',
    ],

    'wfh_default_policy_type' => 'none',

    'wfh_enabled_default' => false,

    'wfh_default_allowed_weekdays' => [1, 2, 3, 4, 5],

    'wfh_cancellation_cutoff_days' => 0,

    'wfh_max_request_days' => 31,

    'wfh_request_statuses' => [
        'draft' => 'Draft',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ],

    'wfh_approval_step_statuses' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'skipped' => 'Skipped',
    ],

    'wfh_weekdays' => [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ],

    'attendance_geofence' => [
        'min_radius_meters' => 10,
        'max_radius_meters' => 50000,
        'default_max_accuracy_meters' => 100,
    ],

    'clockable_employee_statuses' => [
        'active',
        'probation',
        'notice_period',
    ],

    'leave_statuses' => [
        'draft' => 'Draft',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ],

    'leave_approval_step_statuses' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'skipped' => 'Skipped',
    ],

    'default_leave_types' => [
        'annual' => [
            'name' => 'Annual Leave',
            'code' => 'AL',
            'is_paid' => true,
            'requires_approval' => true,
            'requires_hr_approval' => false,
            'allow_half_day' => true,
            'max_days_per_year' => 18,
        ],
        'sick' => [
            'name' => 'Sick Leave',
            'code' => 'SL',
            'is_paid' => true,
            'requires_approval' => true,
            'requires_hr_approval' => false,
            'allow_half_day' => true,
            'max_days_per_year' => 12,
        ],
        'casual' => [
            'name' => 'Casual Leave',
            'code' => 'CL',
            'is_paid' => true,
            'requires_approval' => true,
            'requires_hr_approval' => false,
            'allow_half_day' => true,
            'max_days_per_year' => 6,
        ],
        'unpaid' => [
            'name' => 'Unpaid Leave',
            'code' => 'UL',
            'is_paid' => false,
            'requires_approval' => true,
            'requires_hr_approval' => true,
            'allow_half_day' => true,
            'max_days_per_year' => null,
        ],
    ],

    'document_categories' => [
        'aadhaar' => 'Aadhaar',
        'pan' => 'PAN',
        'passport' => 'Passport',
        'driving_license' => 'Driving License',
        'offer_letter' => 'Offer Letter',
        'appointment_letter' => 'Appointment Letter',
        'experience_letter' => 'Experience Letter',
        'certificate' => 'Educational Certificate',
        'salary_document' => 'Salary Document',
        'other' => 'Other HR Document',
    ],

    'documents' => [
        'disk' => env('HRMS_DOCUMENT_DISK', 'local'),
        'max_size_kb' => (int) env('HRMS_DOCUMENT_MAX_SIZE_KB', 10240),
        'allowed_mimes' => [
            'pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp',
            'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'zip',
        ],
        'expiring_soon_days' => (int) env('HRMS_DOCUMENT_EXPIRING_SOON_DAYS', 30),
    ],

    'document_verification_statuses' => [
        'pending' => 'Pending',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
    ],

    'identity_document_types' => [
        'aadhaar' => 'Aadhaar',
        'pan' => 'PAN',
        'passport' => 'Passport',
        'driving_license' => 'Driving License',
        'voter_id' => 'Voter ID',
        'other' => 'Other',
    ],

    'shift_presets' => [
        'general' => [
            'name' => 'General Shift',
            'code' => 'GEN',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'break_minutes' => 60,
            'grace_period_minutes' => 15,
            'minimum_working_minutes' => 420,
            'overtime_threshold_minutes' => 480,
            'is_overnight' => false,
        ],
        'morning' => [
            'name' => 'Morning Shift',
            'code' => 'MOR',
            'start_time' => '06:00',
            'end_time' => '14:00',
            'break_minutes' => 30,
            'grace_period_minutes' => 10,
            'minimum_working_minutes' => 420,
            'overtime_threshold_minutes' => 450,
            'is_overnight' => false,
        ],
        'evening' => [
            'name' => 'Evening Shift',
            'code' => 'EVE',
            'start_time' => '14:00',
            'end_time' => '22:00',
            'break_minutes' => 30,
            'grace_period_minutes' => 10,
            'minimum_working_minutes' => 420,
            'overtime_threshold_minutes' => 450,
            'is_overnight' => false,
        ],
        'night' => [
            'name' => 'Night Shift',
            'code' => 'NGT',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'break_minutes' => 30,
            'grace_period_minutes' => 10,
            'minimum_working_minutes' => 420,
            'overtime_threshold_minutes' => 450,
            'is_overnight' => true,
        ],
    ],

    'probation' => [
        'default_days' => 90,
        'reminder_days_before_end' => [14, 7, 1],
    ],

    'employee_code' => [
        'prefix' => env('HRMS_EMPLOYEE_CODE_PREFIX', 'EMP'),
        'padding' => (int) env('HRMS_EMPLOYEE_CODE_PADDING', 5),
    ],

    'working_days' => [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
    ],

    'weekend_days' => [
        'saturday',
        'sunday',
    ],

    'attendance_calendar' => [
        'year_range_before' => (int) env('HRMS_CALENDAR_YEAR_RANGE_BEFORE', 5),
        'year_range_after' => (int) env('HRMS_CALENDAR_YEAR_RANGE_AFTER', 5),
    ],

    'attendance_reports' => [
        'types' => [
            'monthly_attendance' => 'Monthly Attendance',
            'late_report' => 'Late Report',
            'absent_report' => 'Absent Report',
            'leave_summary' => 'Leave Summary',
        ],
        'export_formats' => [
            'csv' => 'CSV',
            'xlsx' => 'Excel',
            'pdf' => 'PDF',
        ],
    ],

    'half_day_periods' => [
        'first_half' => 'First half',
        'second_half' => 'Second half',
    ],

    'leave_applicable_employee_statuses' => [
        'active',
        'probation',
        'notice_period',
    ],

    'leave_cancellation_cutoff_days' => (int) env('HRMS_LEAVE_CANCELLATION_CUTOFF_DAYS', 0),

    'leave_balance_transaction_types' => [
        'opening_balance' => 'Opening Balance',
        'allocation' => 'Allocation',
        'leave_submitted' => 'Leave Submitted',
        'leave_approved' => 'Leave Approved',
        'leave_rejected' => 'Leave Rejected',
        'leave_cancelled' => 'Leave Cancelled',
        'manual_adjustment' => 'Manual Adjustment',
        'carry_forward' => 'Carry Forward',
        'expiry' => 'Expiry',
        'encashment' => 'Encashment',
    ],

    'ess' => [
        'self_editable_fields' => [
            'phone',
            'mobile',
            'personal_email',
            'address_line_1',
            'address_line_2',
            'city',
            'state',
            'postal_code',
            'country',
        ],
        'self_editable_profile_sections' => [
            'emergency_contacts',
            'skills',
            'educations',
            'experiences',
            'certifications',
        ],
    ],

    'skill_proficiencies' => [
        'beginner' => 'Beginner',
        'intermediate' => 'Intermediate',
        'advanced' => 'Advanced',
        'expert' => 'Expert',
    ],

    'certification_statuses' => [
        'active' => 'Active',
        'expired' => 'Expired',
        'revoked' => 'Revoked',
    ],

    'certification_display_statuses' => [
        'active' => 'Active',
        'expiring_soon' => 'Expiring Soon',
        'expired' => 'Expired',
    ],

    'certification_expiring_soon_days' => 60,

    'profile_completion' => [
        'sections' => [
            'personal' => [
                'label' => 'Personal Information',
                'weight' => 20,
            ],
            'emergency_contact' => [
                'label' => 'Emergency Contact',
                'weight' => 15,
            ],
            'skills' => [
                'label' => 'Skills',
                'weight' => 15,
            ],
            'education' => [
                'label' => 'Education',
                'weight' => 15,
            ],
            'experience' => [
                'label' => 'Experience',
                'weight' => 15,
            ],
            'certifications' => [
                'label' => 'Certifications',
                'weight' => 10,
            ],
            'identity' => [
                'label' => 'Identity Documents',
                'weight' => 10,
            ],
        ],
    ],

    'announcement_audiences' => [
        'everyone' => 'Everyone',
        'employees' => 'Employees',
        'managers' => 'Managers',
        'hr' => 'HR',
    ],

    'asset_categories' => [
        'laptop' => 'Laptop',
        'desktop' => 'Desktop',
        'phone' => 'Phone',
        'sim' => 'SIM',
        'id_card' => 'ID Card',
        'access_card' => 'Access Card',
        'monitor' => 'Monitor',
        'headset' => 'Headset',
        'software_license' => 'Software License',
        'other' => 'Other',
    ],

    'asset_statuses' => [
        'available' => 'Available',
        'assigned' => 'Assigned',
        'returned' => 'Returned',
        'lost' => 'Lost',
        'damaged' => 'Damaged',
        'retired' => 'Retired',
    ],

    'asset_code' => [
        'prefix' => env('HRMS_ASSET_CODE_PREFIX', 'AST'),
        'padding' => (int) env('HRMS_ASSET_CODE_PADDING', 5),
    ],

    'exit_types' => [
        'resignation' => 'Resignation',
        'termination' => 'Termination',
        'retirement' => 'Retirement',
        'end_of_contract' => 'End of Contract',
    ],

    'exit_type_status_map' => [
        'resignation' => 'resigned',
        'termination' => 'terminated',
        'retirement' => 'retired',
        'end_of_contract' => 'inactive',
    ],

    'exit_process_statuses' => [
        'in_progress' => 'In Progress',
        'pending_approval' => 'Pending Approval',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'directory_visible_statuses' => [
        'active',
        'probation',
        'notice_period',
    ],

    'document_expiring_soon_days' => (int) env('HRMS_ESS_DOCUMENT_EXPIRING_DAYS', 30),

    'attendance_correction_statuses' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    'attendance_overtime_rule_types' => [
        'daily' => 'Daily overtime',
        'holiday' => 'Holiday overtime',
        'weekly_off' => 'Weekly off overtime',
    ],

    'attendance_overtime_entry_statuses' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    'attendance_period_statuses' => [
        'open' => 'Open',
        'frozen' => 'Frozen',
        'locked' => 'Locked',
    ],

    'attendance_approval_statuses' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    'attendance_snapshot_statuses' => [
        'active' => 'Active',
        'superseded' => 'Superseded',
    ],

    'attendance_validation' => [
        'long_working_minutes' => (int) env('HRMS_ATTENDANCE_LONG_WORKING_MINUTES', 720),
    ],

    'default_week_off_days' => [0, 6],

    'salary_component_types' => [
        'earning' => 'Earning',
        'deduction' => 'Deduction',
    ],

    'salary_calculation_types' => [
        'fixed' => 'Fixed Amount',
        'percentage' => 'Percentage',
        'formula' => 'Formula (future)',
    ],

    'payroll_period_statuses' => [
        'draft' => 'Draft',
        'open' => 'Open',
        'locked' => 'Locked',
        'processed' => 'Processed',
    ],

    'payroll_frequencies' => [
        'monthly' => 'Monthly',
        'biweekly' => 'Bi-weekly',
        'weekly' => 'Weekly',
    ],

    'payroll_overtime_handling' => [
        'pay' => 'Pay overtime',
        'comp_off' => 'Compensatory off',
        'ignore' => 'Ignore',
    ],

    'payroll_rounding_policies' => [
        'nearest' => 'Nearest',
        'up' => 'Round up',
        'down' => 'Round down',
        'none' => 'No rounding',
    ],

    'performance_cycle_types' => [
        'annual' => 'Annual',
        'half_yearly' => 'Half-Yearly',
        'quarterly' => 'Quarterly',
        'custom' => 'Custom',
    ],

    'performance_cycle_statuses' => [
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'active' => 'Active',
        'closed' => 'Closed',
        'archived' => 'Archived',
    ],

    'performance_review_frequencies' => [
        'annual' => 'Annual',
        'half_yearly' => 'Half-Yearly',
        'quarterly' => 'Quarterly',
        'custom' => 'Custom',
    ],

    'performance_review_visibilities' => [
        'manager_only' => 'Manager only',
        'employee_and_manager' => 'Employee and manager',
        'hr_and_manager' => 'HR and manager',
        'employee_manager_hr' => 'Employee, manager, and HR',
    ],

    'performance' => [
        'default_review_frequency' => 'annual',
        'default_goal_weighting' => 50,
        'default_competency_weighting' => 50,
        'default_review_visibility' => 'employee_and_manager',
        'default_calibration_enabled' => false,
        'default_rating_scale_levels' => [
            ['value' => 1, 'label' => 'Needs Improvement', 'description' => 'Performance is below expectations.'],
            ['value' => 2, 'label' => 'Developing', 'description' => 'Performance is approaching expectations.'],
            ['value' => 3, 'label' => 'Meets Expectations', 'description' => 'Performance meets role expectations.'],
            ['value' => 4, 'label' => 'Exceeds Expectations', 'description' => 'Performance exceeds role expectations.'],
            ['value' => 5, 'label' => 'Outstanding', 'description' => 'Performance is exceptional.'],
        ],
    ],

    'goal_types' => [
        'individual' => 'Individual',
        'team' => 'Team',
        'department' => 'Department',
        'organization' => 'Organization',
    ],

    'goal_assignee_types' => [
        'employee' => 'Employee',
        'team' => 'Team',
        'department' => 'Department',
        'organization' => 'Organization',
    ],

    'goal_measurement_types' => [
        'percentage' => 'Percentage',
        'numeric' => 'Numeric',
        'currency' => 'Currency',
        'boolean' => 'Boolean',
        'milestone' => 'Milestone',
    ],

    'goal_statuses' => [
        'draft' => 'Draft',
        'assigned' => 'Assigned',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'goal_weighting' => [
        'required_total' => 100,
        'tolerance' => 0.01,
        'statuses_included_in_total' => ['draft', 'assigned', 'in_progress', 'completed'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance review engine (Phase 10.4.3)
    |--------------------------------------------------------------------------
    |
    | Review types are string catalogs so peer / upward / customer / external
    | can be added later without schema changes.
    |
    */
    'performance_review_types' => [
        'self' => 'Self',
        'manager' => 'Manager',
        // Future (architecture reserved): peer, upward, customer, external
    ],

    'performance_review_assignment_statuses' => [
        'planned' => 'Planned',
        'assigned' => 'Assigned',
        'in_progress' => 'In Progress',
        'submitted' => 'Submitted',
        'reviewed' => 'Reviewed',
        'closed' => 'Closed',
        'cancelled' => 'Cancelled',
    ],

    'performance_review_statuses' => [
        'draft' => 'Draft',
        'in_progress' => 'In Progress',
        'submitted' => 'Submitted',
        'reviewed' => 'Reviewed',
        'closed' => 'Closed',
    ],

    'performance_review' => [
        'goal_snapshot_statuses' => ['assigned', 'in_progress', 'completed'],
        'editable_statuses' => ['draft', 'in_progress'],
        'immutable_assignment_statuses' => ['closed', 'cancelled'],
    ],

    'feedback_campaign_statuses' => [
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'active' => 'Active',
        'closed' => 'Closed',
        'archived' => 'Archived',
    ],

    'feedback_participant_types' => [
        'self' => 'Self',
        'manager' => 'Manager',
        'peer' => 'Peer',
        'direct_report' => 'Direct Report',
        'skip_level_manager' => 'Skip-Level Manager',
        'external' => 'External',
    ],

    'feedback_participant_statuses' => [
        'pending' => 'Pending',
        'active' => 'Active',
        'removed' => 'Removed',
    ],

    'feedback_request_statuses' => [
        'pending' => 'Pending',
        'started' => 'Started',
        'submitted' => 'Submitted',
        'expired' => 'Expired',
        'cancelled' => 'Cancelled',
    ],

    'feedback_question_types' => [
        'competency' => 'Competency',
        'rating' => 'Rating',
        'text' => 'Free Text',
        'scale' => 'Scale',
    ],

    'feedback' => [
        'editable_campaign_statuses' => ['draft', 'scheduled'],
        'activatable_campaign_statuses' => ['draft', 'scheduled'],
        'closable_campaign_statuses' => ['active'],
        'immutable_request_statuses' => ['submitted', 'expired', 'cancelled'],
        'submittable_request_statuses' => ['pending', 'started'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Appraisal & Talent Decisions Platform (Phase 10.4.5)
    |--------------------------------------------------------------------------
    */
    'appraisal_session_statuses' => [
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'active' => 'Active',
        'closed' => 'Closed',
        'archived' => 'Archived',
    ],

    'employee_appraisal_statuses' => [
        'generated' => 'Generated',
        'in_progress' => 'In Progress',
        'submitted' => 'Submitted',
        'hr_reviewed' => 'HR Reviewed',
        'calibrated' => 'Calibrated',
        'closed' => 'Closed',
    ],

    'appraisal_calibration_statuses' => [
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
    ],

    'promotion_recommendation_levels' => [
        'strongly_recommended' => 'Strongly Recommended',
        'recommended' => 'Recommended',
        'not_recommended' => 'Not Recommended',
        'deferred' => 'Deferred',
    ],

    'succession_readiness_levels' => [
        'ready_now' => 'Ready Now',
        'ready_in_1_year' => 'Ready in 1 Year',
        'ready_in_2_years' => 'Ready in 2 Years',
        'long_term' => 'Long Term',
    ],

    'appraisal_recommendation_types' => [
        'promotion' => 'Promotion',
        'compensation' => 'Compensation',
        'succession' => 'Succession',
    ],

    'appraisal' => [
        'editable_session_statuses' => ['draft', 'scheduled'],
        'activatable_session_statuses' => ['draft', 'scheduled'],
        'closable_session_statuses' => ['active'],
        'editable_appraisal_statuses' => ['generated', 'in_progress', 'submitted', 'hr_reviewed', 'calibrated'],
        'immutable_appraisal_statuses' => ['closed'],
        'manager_submittable_statuses' => ['generated', 'in_progress'],
        'default_rating_weights' => [
            'goals' => 40,
            'competencies' => 30,
            'manager_review' => 20,
            'feedback_360' => 10,
        ],
        'rating_weight_keys' => ['goals', 'competencies', 'manager_review', 'self_review', 'feedback_360'],
        'rating_weight_tolerance' => 0.01,
        'rating_scale_max' => 5,
        'default_talent_matrix' => [
            'grid_size' => 3,
            'performance_axis' => 'Performance',
            'potential_axis' => 'Potential',
            'classifications' => [
                '1-1' => 'Needs Support',
                '1-2' => 'Needs Support',
                '1-3' => 'Emerging Talent',
                '2-1' => 'Core Contributor',
                '2-2' => 'Core Contributor',
                '2-3' => 'High Performer',
                '3-1' => 'Core Contributor',
                '3-2' => 'Future Leader',
                '3-3' => 'Future Leader',
            ],
        ],
    ],

    'payroll' => [
        'default_frequency' => 'monthly',
        'default_currency' => env('HRMS_PAYROLL_CURRENCY', 'INR'),
        'default_working_days_per_month' => 26,
        'default_overtime_handling' => 'pay',
        'default_rounding_policy' => 'nearest',
        'default_salary_mode' => 'calendar',
        'default_salary_credit_day' => null,
        'default_reminder_days_before_credit' => 3,
        'salary_modes' => [
            'calendar' => 'Calendar based',
            'attendance' => 'Attendance based',
            'leave' => 'Leave based',
        ],
        'adjustment_types' => [
            'bonus' => 'Bonus',
            'incentive' => 'Incentive',
            'penalty' => 'Penalty',
            'arrears' => 'Arrears',
            'misc' => 'Miscellaneous',
        ],
        'adjustment_statuses' => [
            'draft' => 'Draft',
            'approved' => 'Approved',
            'applied' => 'Applied',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
        ],
        'statutory_component_codes' => ['PF', 'ESI', 'PT', 'IT', 'TDS'],
        'run_statuses' => [
            'draft' => 'Draft',
            'running' => 'Running',
            'calculated' => 'Calculated',
            'approved' => 'Approved',
            'published' => 'Published',
            'paid' => 'Paid',
            'reversed' => 'Reversed',
        ],
        'approval_types' => [
            'hr' => 'HR Approval',
            'finance' => 'Finance Approval',
        ],
        'default_components' => [
            ['name' => 'Basic', 'code' => 'BASIC', 'component_type' => 'earning', 'is_taxable' => true, 'is_recurring' => true],
            ['name' => 'HRA', 'code' => 'HRA', 'component_type' => 'earning', 'is_taxable' => true, 'is_recurring' => true],
            ['name' => 'Special Allowance', 'code' => 'SA', 'component_type' => 'earning', 'is_taxable' => true, 'is_recurring' => true],
            ['name' => 'Conveyance', 'code' => 'CONV', 'component_type' => 'earning', 'is_taxable' => true, 'is_recurring' => true],
            ['name' => 'Bonus', 'code' => 'BONUS', 'component_type' => 'earning', 'is_taxable' => true, 'is_recurring' => false],
            ['name' => 'Incentive', 'code' => 'INCENT', 'component_type' => 'earning', 'is_taxable' => true, 'is_recurring' => false],
            ['name' => 'PF', 'code' => 'PF', 'component_type' => 'deduction', 'is_taxable' => false, 'is_recurring' => true],
            ['name' => 'ESI', 'code' => 'ESI', 'component_type' => 'deduction', 'is_taxable' => false, 'is_recurring' => true],
            ['name' => 'Professional Tax', 'code' => 'PT', 'component_type' => 'deduction', 'is_taxable' => false, 'is_recurring' => true],
            ['name' => 'Income Tax', 'code' => 'IT', 'component_type' => 'deduction', 'is_taxable' => false, 'is_recurring' => true],
            ['name' => 'Loan Recovery', 'code' => 'LOAN', 'component_type' => 'deduction', 'is_taxable' => false, 'is_recurring' => true],
        ],
    ],

    'statutory' => [
        'jurisdictions' => [
            'IN' => 'India',
        ],
        'tax_regimes' => [
            'old' => 'Old Regime',
            'new' => 'New Regime',
        ],
        'professional_tax_states' => [
            'MH' => 'Maharashtra',
            'KA' => 'Karnataka',
            'WB' => 'West Bengal',
            'GJ' => 'Gujarat',
            'TN' => 'Tamil Nadu',
        ],
        'default_india_configuration' => [
            'pf' => [
                'enabled' => true,
                'employee_contribution_percent' => 12,
                'employer_contribution_percent' => 12,
                'wage_ceiling' => 15000,
                'wage_component_codes' => ['BASIC'],
            ],
            'esi' => [
                'enabled' => true,
                'employee_contribution_percent' => 0.75,
                'employer_contribution_percent' => 3.25,
                'wage_threshold' => 21000,
            ],
            'professional_tax' => [
                'enabled' => true,
                'states' => [
                    'MH' => [
                        'slabs' => [
                            ['min' => 0, 'max' => 7500, 'amount' => 0],
                            ['min' => 7501, 'max' => 10000, 'amount' => 175],
                            ['min' => 10001, 'max' => null, 'amount' => 200],
                        ],
                        'exemption_months' => [2],
                    ],
                    'KA' => [
                        'slabs' => [
                            ['min' => 0, 'max' => 14999, 'amount' => 0],
                            ['min' => 15000, 'max' => null, 'amount' => 200],
                        ],
                        'exemption_months' => [],
                    ],
                    'WB' => [
                        'slabs' => [
                            ['min' => 0, 'max' => 10000, 'amount' => 0],
                            ['min' => 10001, 'max' => 15000, 'amount' => 110],
                            ['min' => 15001, 'max' => 25000, 'amount' => 130],
                            ['min' => 25001, 'max' => 40000, 'amount' => 150],
                            ['min' => 40001, 'max' => null, 'amount' => 200],
                        ],
                        'exemption_months' => [],
                    ],
                    'GJ' => [
                        'slabs' => [
                            ['min' => 0, 'max' => 11999, 'amount' => 0],
                            ['min' => 12000, 'max' => null, 'amount' => 200],
                        ],
                        'exemption_months' => [],
                    ],
                    'TN' => [
                        'slabs' => [
                            ['min' => 0, 'max' => 21000, 'amount' => 0],
                            ['min' => 21001, 'max' => null, 'amount' => 208],
                        ],
                        'exemption_months' => [],
                    ],
                ],
            ],
            'tds' => [
                'enabled' => true,
                'calculation' => 'engine',
                'engine_version' => '10.3.7',
                'cess_percent' => 4,
                'standard_deduction' => [
                    'old' => 50000,
                    'new' => 75000,
                ],
                'rebate_87a' => [
                    'old' => ['max_taxable_income' => 500000, 'max_rebate' => 12500],
                    'new' => ['max_taxable_income' => 700000, 'max_rebate' => 25000],
                ],
                'surcharge_slabs' => [
                    ['min' => 5000000, 'max' => 10000000, 'percent' => 10],
                    ['min' => 10000001, 'max' => 20000000, 'percent' => 15],
                    ['min' => 20000001, 'max' => 50000000, 'percent' => 25],
                    ['min' => 50000001, 'max' => null, 'percent' => 37],
                ],
                'slabs' => [
                    'old' => [
                        ['min' => 0, 'max' => 250000, 'percent' => 0],
                        ['min' => 250001, 'max' => 500000, 'percent' => 5],
                        ['min' => 500001, 'max' => 1000000, 'percent' => 20],
                        ['min' => 1000001, 'max' => null, 'percent' => 30],
                    ],
                    'new' => [
                        ['min' => 0, 'max' => 300000, 'percent' => 0],
                        ['min' => 300001, 'max' => 700000, 'percent' => 5],
                        ['min' => 700001, 'max' => 1000000, 'percent' => 10],
                        ['min' => 1000001, 'max' => 1200000, 'percent' => 15],
                        ['min' => 1200001, 'max' => 1500000, 'percent' => 20],
                        ['min' => 1500001, 'max' => null, 'percent' => 30],
                    ],
                ],
                'section_limits' => [
                    '80C' => 150000,
                    '80CCD' => 50000,
                    '80D' => 75000,
                    'home_loan_interest' => 200000,
                    'education_loan' => null,
                    'nps' => 50000,
                ],
            ],
        ],
    ],

    'income_tax' => [
        'regimes' => [
            'old' => 'Old Regime',
            'new' => 'New Regime',
        ],
        'declaration_statuses' => [
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'verified' => 'Verified',
            'rejected' => 'Rejected',
        ],
        'proof_statuses' => [
            'uploaded' => 'Uploaded',
            'verified' => 'Verified',
            'partial' => 'Partially Approved',
            'rejected' => 'Rejected',
        ],
        'declaration_categories' => [
            '80C' => ['label' => 'Section 80C', 'section' => '80C'],
            '80CCD' => ['label' => 'Section 80CCD (NPS)', 'section' => '80CCD'],
            '80D' => ['label' => 'Section 80D (Medical Insurance)', 'section' => '80D'],
            'home_loan_interest' => ['label' => 'Home Loan Interest (Sec 24)', 'section' => '24'],
            'hra' => ['label' => 'House Rent Allowance', 'section' => '10(13A)'],
            'lta' => ['label' => 'Leave Travel Allowance', 'section' => '10(5)'],
            'nps' => ['label' => 'National Pension System', 'section' => '80CCD'],
            'education_loan' => ['label' => 'Education Loan Interest (Sec 80E)', 'section' => '80E'],
            'other' => ['label' => 'Other Deductions', 'section' => null],
        ],
        'report_types' => [
            'tds_register' => 'TDS Register',
            'tax_projection' => 'Tax Projection',
            'employee_tax_summary' => 'Employee Tax Summary',
            'declaration_status' => 'Declaration Status',
            'proof_verification' => 'Proof Verification',
            'form16_summary' => 'Form 16 Summary',
        ],
        'export_formats' => ['csv', 'xlsx', 'pdf'],
        'default_financial_year' => [
            'code' => 'FY2025-26',
            'label' => 'Financial Year 2025-26',
            'assessment_year' => 'AY2026-27',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'default_regime' => 'new',
        ],
    ],

    'payslips' => [
        'disk' => env('HRMS_PAYSLIP_DISK', env('HRMS_DOCUMENT_DISK', 'local')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Workflow trigger catalog (Phase 10.7)
    |--------------------------------------------------------------------------
    |
    | Canonical definitions live in config/hrms_workflow_triggers.php and are
    | registered into config/workflows.php. Event classes emit matching keys
    | and are listened to by RunTriggeredWorkflows.
    |
    */
    'workflow_triggers' => require __DIR__.'/hrms_workflow_triggers.php',

    /*
    |--------------------------------------------------------------------------
    | Recruitment Platform catalogs
    |--------------------------------------------------------------------------
    */
    'recruitment' => [
        'requisition_statuses' => [
            'draft' => 'Draft',
            'pending_approval' => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            'closed' => 'Closed',
        ],
        'opening_statuses' => [
            'draft' => 'Draft',
            'published' => 'Published',
            'paused' => 'Paused',
            'closed' => 'Closed',
            'filled' => 'Filled',
            'cancelled' => 'Cancelled',
        ],
        'application_stages' => [
            'applied' => 'Applied',
            'screening' => 'Screening',
            'interview' => 'Interview',
            'evaluation' => 'Evaluation',
            'offer' => 'Offer',
            'hired' => 'Hired',
            'rejected' => 'Rejected',
            'withdrawn' => 'Withdrawn',
        ],
        'application_statuses' => [
            'active' => 'Active',
            'closed' => 'Closed',
        ],
        'candidate_sources' => [
            'direct' => 'Direct Application',
            'referral' => 'Referral',
            'job_board' => 'Job Board',
            'agency' => 'Agency',
            'campus' => 'Campus',
            'linkedin' => 'LinkedIn',
            'other' => 'Other',
        ],
        'document_categories' => [
            'resume' => 'Resume',
            'cover_letter' => 'Cover Letter',
            'certificate' => 'Certificate',
            'portfolio' => 'Portfolio',
            'other' => 'Other',
        ],
        'documents' => [
            'disk' => env('HRMS_DOCUMENT_DISK', 'local'),
            'max_size_kb' => (int) env('HRMS_DOCUMENT_MAX_SIZE_KB', 10240),
            'allowed_mimes' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg',
                'image/png',
            ],
        ],
        'default_interview_stages' => [
            'applied' => 'Applied',
            'screening' => 'Screening',
            'technical_interview' => 'Technical Interview',
            'manager_interview' => 'Manager Interview',
            'hr_interview' => 'HR Interview',
            'final_review' => 'Final Review',
            'offer' => 'Offer',
            'hired' => 'Hired',
            'rejected' => 'Rejected',
            'withdrawn' => 'Withdrawn',
        ],
        'interview_round_statuses' => [
            'draft' => 'Draft',
            'scheduled' => 'Scheduled',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'no_show' => 'No Show',
        ],
        'interview_types' => [
            'phone' => 'Phone',
            'video' => 'Video',
            'in_person' => 'In Person',
            'panel' => 'Panel',
            'technical' => 'Technical',
            'hr' => 'HR',
            'final' => 'Final',
        ],
        'participant_types' => [
            'internal' => 'Internal Employee',
            'external' => 'External Interviewer',
        ],
        'participant_roles' => [
            'lead_interviewer' => 'Lead Interviewer',
            'panel_member' => 'Panel Member',
            'observer' => 'Observer',
            'recruiter' => 'Recruiter',
        ],
        'evaluation_question_types' => [
            'rating_1_5' => 'Rating (1–5)',
            'rating_1_10' => 'Rating (1–10)',
            'yes_no' => 'Yes/No',
            'text' => 'Text',
            'multiline' => 'Multi-line Comments',
        ],
        'evaluation_recommendations' => [
            'strong_hire' => 'Strong Hire',
            'hire' => 'Hire',
            'hold' => 'Hold',
            'reject' => 'Reject',
        ],
        'evaluation_statuses' => [
            'draft' => 'Draft',
            'submitted' => 'Submitted',
        ],
        'offer_statuses' => [
            'draft' => 'Draft',
            'pending_approval' => 'Pending Approval',
            'approved' => 'Approved',
            'sent' => 'Sent',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'expired' => 'Expired',
            'withdrawn' => 'Withdrawn',
        ],
        'active_offer_statuses' => [
            'draft', 'pending_approval', 'approved', 'sent',
        ],
        'offer_approval_statuses' => [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'returned' => 'Returned',
        ],
        'negotiation_outcomes' => [
            'pending' => 'Pending',
            'accepted' => 'Accepted',
            'countered' => 'Countered',
            'declined' => 'Declined',
        ],
        'hiring_recommendations' => [
            'hire' => 'Hire',
            'hold' => 'Hold',
            'reject' => 'Reject',
        ],
        'offer_template_placeholders' => [
            'candidate_name' => 'Candidate full name',
            'position' => 'Job opening title',
            'salary' => 'Proposed salary',
            'variable_pay' => 'Variable pay amount',
            'joining_date' => 'Proposed joining date',
            'reporting_manager' => 'Reporting manager name',
            'benefits' => 'Benefits summary',
            'expiry_date' => 'Offer expiry date',
        ],
        'portal_application_statuses' => [
            'applied' => 'Applied',
            'screening' => 'Under Review',
            'interview' => 'Interview Scheduled',
            'evaluation' => 'Under Review',
            'offer' => 'Offer Sent',
            'hired' => 'Offer Accepted',
            'rejected' => 'Rejected',
            'withdrawn' => 'Withdrawn',
        ],
        'portal_timeline_steps' => [
            ['label' => 'Applied'],
            ['label' => 'Under Review'],
            ['label' => 'Interview Scheduled'],
            ['label' => 'Interview Completed'],
            ['label' => 'Offer Sent'],
            ['label' => 'Offer Accepted'],
        ],
        'portal' => [
            'resume_max_kb' => (int) env('CANDIDATE_RESUME_MAX_KB', 5120),
            'resume_mime_types' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ],
        'analytics' => [
            'cache_ttl' => (int) env('RECRUITMENT_ANALYTICS_CACHE_TTL', 120),
            'periods' => [
                'today' => 'Today',
                'week' => 'This Week',
                'month' => 'This Month',
                'quarter' => 'This Quarter',
                'year' => 'This Year',
                'custom' => 'Custom Range',
            ],
            'leaderboard_periods' => [
                'daily' => 'Daily',
                'weekly' => 'Weekly',
                'monthly' => 'Monthly',
                'quarterly' => 'Quarterly',
                'yearly' => 'Yearly',
            ],
            'export_formats' => [
                'csv' => 'CSV',
                'xlsx' => 'Excel',
                'pdf' => 'PDF (Coming Soon)',
            ],
            'funnel_stages' => [
                'applied' => 'Applications',
                'screening' => 'Screening',
                'interview' => 'Interviews',
                'evaluation' => 'Evaluations',
                'offer' => 'Offers',
                'hired' => 'Accepted',
                'onboarding' => 'Onboarding Recommended',
            ],
        ],
        'report_types' => [
            'recruitment_summary' => 'Recruitment Summary',
            'recruiter_performance' => 'Recruiter Performance',
            'hiring_manager_performance' => 'Hiring Manager Performance',
            'department_hiring' => 'Department Hiring',
            'open_positions' => 'Open Positions',
            'pipeline' => 'Pipeline Report',
            'offer' => 'Offer Report',
            'source' => 'Source Report',
            'vacancy_aging' => 'Vacancy Aging',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mobile API (Phase 10.6)
    |--------------------------------------------------------------------------
    */
    'mobile' => [
        'access_token_ttl_minutes' => (int) env('HRMS_MOBILE_ACCESS_TTL', 60),
        'refresh_token_ttl_days' => (int) env('HRMS_MOBILE_REFRESH_TTL_DAYS', 30),
        'access_token_ability' => 'hrms-mobile',
        'refresh_token_ability' => 'hrms-mobile-refresh',
        'virus_scan_hook' => \App\Services\Security\NoopVirusScanHook::class,
        'uploads' => [
            'default' => [
                'max_kb' => 5120,
                'mimes' => ['jpeg', 'jpg', 'png', 'pdf', 'webp'],
            ],
            'profile_photo' => [
                'max_kb' => 2048,
                'mimes' => ['jpeg', 'jpg', 'png', 'webp'],
            ],
            'document' => [
                'max_kb' => 10240,
                'mimes' => ['jpeg', 'jpg', 'png', 'pdf', 'doc', 'docx'],
            ],
            'tax_proof' => [
                'max_kb' => 10240,
                'mimes' => ['jpeg', 'jpg', 'png', 'pdf'],
            ],
            'leave_attachment' => [
                'max_kb' => 5120,
                'mimes' => ['jpeg', 'jpg', 'png', 'pdf'],
            ],
            'recruitment' => [
                'max_kb' => 10240,
                'mimes' => ['jpeg', 'jpg', 'png', 'pdf', 'doc', 'docx'],
            ],
        ],
    ],
];
