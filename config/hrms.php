<?php

/**
 * HRMS Platform catalogs and defaults.
 *
 * Business services must read from this config rather than hardcoding values.
 * Workflow trigger keys listed under workflow_triggers are placeholders only —
 * they are not registered with the Workflow Platform until a later phase.
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
        'statutory_component_codes' => ['PF', 'ESI', 'PT', 'IT', 'TDS'],
        'run_statuses' => [
            'draft' => 'Draft',
            'running' => 'Running',
            'calculated' => 'Calculated',
            'approved' => 'Approved',
            'published' => 'Published',
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
                'calculation' => 'deferred',
            ],
        ],
    ],

    'payslips' => [
        'disk' => env('HRMS_PAYSLIP_DISK', env('HRMS_DOCUMENT_DISK', 'local')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Future workflow trigger placeholders
    |--------------------------------------------------------------------------
    |
    | These keys document intended Workflow Platform integrations. They are NOT
    | registered in config/workflows.php and have no runtime listeners yet.
    |
    */
    'workflow_triggers' => [
        'employee.created' => [
            'entity' => 'employee',
            'label' => 'Employee created',
            'description' => 'Runs when an employee master record is created.',
        ],
        'employee.exited' => [
            'entity' => 'employee',
            'label' => 'Employee exited',
            'description' => 'Runs when an employee exit is recorded.',
        ],
        'employee.probation_ending' => [
            'entity' => 'employee',
            'label' => 'Probation ending',
            'description' => 'Runs when an employee probation end date is approaching.',
        ],
        'leave.submitted' => [
            'entity' => 'leave_application',
            'label' => 'Leave submitted',
            'description' => 'Runs when a leave application is submitted for approval.',
        ],
        'leave.approved' => [
            'entity' => 'leave_application',
            'label' => 'Leave approved',
            'description' => 'Runs when a leave application is fully approved.',
        ],
        'leave.rejected' => [
            'entity' => 'leave_application',
            'label' => 'Leave rejected',
            'description' => 'Runs when a leave application is rejected.',
        ],
        'leave.cancelled' => [
            'entity' => 'leave_application',
            'label' => 'Leave cancelled',
            'description' => 'Runs when a leave application is cancelled.',
        ],
        'leave.balance_adjusted' => [
            'entity' => 'leave_balance',
            'label' => 'Leave balance adjusted',
            'description' => 'Runs when a leave balance is manually adjusted.',
        ],
        'employee.profile_updated' => [
            'entity' => 'employee',
            'label' => 'Employee profile updated',
            'description' => 'Runs when an employee updates their self-service profile.',
        ],
        'announcement.created' => [
            'entity' => 'hrms_announcement',
            'label' => 'Announcement created',
            'description' => 'Runs when an HR announcement is created.',
        ],
        'announcement.updated' => [
            'entity' => 'hrms_announcement',
            'label' => 'Announcement updated',
            'description' => 'Runs when an HR announcement is updated.',
        ],
        'announcement.deleted' => [
            'entity' => 'hrms_announcement',
            'label' => 'Announcement deleted',
            'description' => 'Runs when an HR announcement is deleted.',
        ],
        'attendance.clocked_in' => [
            'entity' => 'attendance_record',
            'label' => 'Attendance clocked in',
            'description' => 'Runs when an employee clocks in.',
        ],
        'attendance.clocked_out' => [
            'entity' => 'attendance_record',
            'label' => 'Attendance clocked out',
            'description' => 'Runs when an employee clocks out.',
        ],
        'attendance.correction_submitted' => [
            'entity' => 'attendance_correction',
            'label' => 'Attendance correction submitted',
            'description' => 'Runs when an attendance correction request is submitted.',
        ],
        'attendance.correction_approved' => [
            'entity' => 'attendance_correction',
            'label' => 'Attendance correction approved',
            'description' => 'Runs when an attendance correction is approved.',
        ],
        'attendance.correction_rejected' => [
            'entity' => 'attendance_correction',
            'label' => 'Attendance correction rejected',
            'description' => 'Runs when an attendance correction is rejected.',
        ],
        'attendance.overtime_recorded' => [
            'entity' => 'attendance_record',
            'label' => 'Attendance overtime recorded',
            'description' => 'Runs when overtime is recorded on clock out.',
        ],
        'employee_document.uploaded' => [
            'entity' => 'employee_document',
            'label' => 'Employee document uploaded',
            'description' => 'Runs when a new employee document is uploaded.',
        ],
        'employee_document.updated' => [
            'entity' => 'employee_document',
            'label' => 'Employee document updated',
            'description' => 'Runs when employee document metadata or version is updated.',
        ],
        'employee_document.deleted' => [
            'entity' => 'employee_document',
            'label' => 'Employee document deleted',
            'description' => 'Runs when an employee document is deleted.',
        ],
        'employee_document.verified' => [
            'entity' => 'employee_document',
            'label' => 'Employee document verified',
            'description' => 'Runs when an employee document verification status changes.',
        ],
        'employee_document.expiring' => [
            'entity' => 'employee_document',
            'label' => 'Employee document expiring',
            'description' => 'Runs when an employee document approaches expiry.',
        ],
        'asset.assigned' => [
            'entity' => 'employee_asset',
            'label' => 'Asset assigned',
            'description' => 'Runs when an asset is assigned to an employee.',
        ],
        'asset.returned' => [
            'entity' => 'employee_asset',
            'label' => 'Asset returned',
            'description' => 'Runs when an assigned asset is returned.',
        ],
        'asset.lost' => [
            'entity' => 'employee_asset',
            'label' => 'Asset lost',
            'description' => 'Runs when an asset is marked as lost.',
        ],
        'employee.exit.started' => [
            'entity' => 'employee_exit_process',
            'label' => 'Employee exit started',
            'description' => 'Runs when an employee exit process is initiated.',
        ],
        'employee.exit.completed' => [
            'entity' => 'employee_exit_process',
            'label' => 'Employee exit completed',
            'description' => 'Runs when an employee exit process is completed.',
        ],
        'employee.exit.cancelled' => [
            'entity' => 'employee_exit_process',
            'label' => 'Employee exit cancelled',
            'description' => 'Runs when an employee exit process is cancelled.',
        ],
        'salary_structure.created' => [
            'entity' => 'salary_structure',
            'label' => 'Salary structure created',
            'description' => 'Runs when a salary structure template is created.',
        ],
        'salary_structure.updated' => [
            'entity' => 'salary_structure',
            'label' => 'Salary structure updated',
            'description' => 'Runs when a salary structure template is updated.',
        ],
        'employee.salary_assigned' => [
            'entity' => 'employee_salary_assignment',
            'label' => 'Employee salary assigned',
            'description' => 'Runs when a salary structure is assigned to an employee.',
        ],
        'payroll.period.created' => [
            'entity' => 'payroll_period',
            'label' => 'Payroll period created',
            'description' => 'Runs when a payroll period is created.',
        ],
        'payroll.period.locked' => [
            'entity' => 'payroll_period',
            'label' => 'Payroll period locked',
            'description' => 'Runs when a payroll period is locked.',
        ],
        'payroll.run.started' => [
            'entity' => 'payroll_run',
            'label' => 'Payroll run started',
            'description' => 'Runs when a payroll calculation run starts.',
        ],
        'payroll.run.completed' => [
            'entity' => 'payroll_run',
            'label' => 'Payroll run completed',
            'description' => 'Runs when a payroll calculation run completes.',
        ],
        'payroll.employee.calculated' => [
            'entity' => 'payroll_result',
            'label' => 'Employee payroll calculated',
            'description' => 'Runs when an employee payroll result is calculated.',
        ],
        'payroll.validation.failed' => [
            'entity' => 'payroll_validation_error',
            'label' => 'Payroll validation failed',
            'description' => 'Runs when payroll validation fails for an employee.',
        ],
        'statutory.profile.updated' => [
            'entity' => 'employee_statutory_profile',
            'label' => 'Statutory profile updated',
            'description' => 'Runs when an employee statutory profile is created or updated.',
        ],
        'statutory.rule.changed' => [
            'entity' => 'statutory_rule_set',
            'label' => 'Statutory rule changed',
            'description' => 'Runs when a statutory rule set is created, activated, or versioned.',
        ],
        'payroll.statutory.calculated' => [
            'entity' => 'payroll_result',
            'label' => 'Payroll statutory calculated',
            'description' => 'Runs when statutory components are calculated for an employee payroll result.',
        ],
        'payroll.compliance.failed' => [
            'entity' => 'statutory_compliance_error',
            'label' => 'Payroll compliance failed',
            'description' => 'Runs when statutory compliance validation fails.',
        ],
        'payroll.approved' => [
            'entity' => 'payroll_run',
            'label' => 'Payroll approved',
            'description' => 'Runs when a calculated payroll run is approved.',
        ],
        'payroll.published' => [
            'entity' => 'payroll_run',
            'label' => 'Payroll published',
            'description' => 'Runs when an approved payroll run is published.',
        ],
        'payslip.generated' => [
            'entity' => 'payslip',
            'label' => 'Payslip generated',
            'description' => 'Runs when an immutable payslip is generated.',
        ],
        'payslip.emailed' => [
            'entity' => 'payslip',
            'label' => 'Payslip emailed',
            'description' => 'Runs when a payslip email is delivered.',
        ],
        'payroll.ledger.generated' => [
            'entity' => 'payroll_run',
            'label' => 'Payroll ledger generated',
            'description' => 'Runs when ledger entries and a journal are generated for a published payroll run.',
        ],
        'payroll.bank.exported' => [
            'entity' => 'payroll_bank_export',
            'label' => 'Payroll bank exported',
            'description' => 'Runs when a salary bank payment export file is generated.',
        ],
        'employee.loan.created' => [
            'entity' => 'employee_loan',
            'label' => 'Employee loan created',
            'description' => 'Runs when a new employee loan is recorded.',
        ],
        'employee.loan.closed' => [
            'entity' => 'employee_loan',
            'label' => 'Employee loan closed',
            'description' => 'Runs when an employee loan is closed.',
        ],
        'employee.settlement.completed' => [
            'entity' => 'employee_settlement',
            'label' => 'Employee settlement completed',
            'description' => 'Runs when a final settlement is generated for an exiting employee.',
        ],
        'payroll.reversed' => [
            'entity' => 'payroll_run',
            'label' => 'Payroll reversed',
            'description' => 'Runs when a published payroll run is reversed.',
        ],
        'performance.cycle.created' => [
            'entity' => 'performance_cycle',
            'label' => 'Performance cycle created',
            'description' => 'Runs when a performance review cycle is created.',
        ],
        'performance.cycle.activated' => [
            'entity' => 'performance_cycle',
            'label' => 'Performance cycle activated',
            'description' => 'Runs when a performance review cycle becomes active.',
        ],
        'performance.template.created' => [
            'entity' => 'performance_review_template',
            'label' => 'Performance template created',
            'description' => 'Runs when a review template is created.',
        ],
        'performance.configuration.updated' => [
            'entity' => 'performance_configuration',
            'label' => 'Performance configuration updated',
            'description' => 'Runs when organization performance configuration is updated.',
        ],
        'goal.created' => [
            'entity' => 'goal',
            'label' => 'Goal created',
            'description' => 'Runs when a goal is created in draft or assigned status.',
        ],
        'goal.assigned' => [
            'entity' => 'goal',
            'label' => 'Goal assigned',
            'description' => 'Runs when a goal is assigned to an employee, team, or department.',
        ],
        'goal.progress.updated' => [
            'entity' => 'goal',
            'label' => 'Goal progress updated',
            'description' => 'Runs when goal progress is recorded.',
        ],
        'goal.completed' => [
            'entity' => 'goal',
            'label' => 'Goal completed',
            'description' => 'Runs when a goal is marked completed.',
        ],
        'goal.cancelled' => [
            'entity' => 'goal',
            'label' => 'Goal cancelled',
            'description' => 'Runs when a goal is cancelled.',
        ],
        'performance.review.assigned' => [
            'entity' => 'performance_review_assignment',
            'label' => 'Performance review assigned',
            'description' => 'Runs when a performance review assignment is issued and a review is initialized.',
        ],
        'performance.review.started' => [
            'entity' => 'performance_review',
            'label' => 'Performance review started',
            'description' => 'Runs when a performance review moves into in-progress.',
        ],
        'performance.review.submitted' => [
            'entity' => 'performance_review',
            'label' => 'Performance review submitted',
            'description' => 'Runs when a self or manager review is submitted.',
        ],
        'performance.review.reviewed' => [
            'entity' => 'performance_review',
            'label' => 'Performance review reviewed',
            'description' => 'Runs when a submitted manager review is marked reviewed.',
        ],
        'performance.review.closed' => [
            'entity' => 'performance_review',
            'label' => 'Performance review closed',
            'description' => 'Runs when a performance review is closed and becomes immutable.',
        ],
        'feedback.campaign.created' => [
            'entity' => 'feedback_campaign',
            'label' => 'Feedback campaign created',
            'description' => 'Runs when a 360° feedback campaign is created.',
        ],
        'feedback.request.sent' => [
            'entity' => 'feedback_request',
            'label' => 'Feedback request sent',
            'description' => 'Runs when feedback requests are generated and sent to participants.',
        ],
        'feedback.started' => [
            'entity' => 'feedback_request',
            'label' => 'Feedback started',
            'description' => 'Runs when a participant starts a feedback request.',
        ],
        'feedback.submitted' => [
            'entity' => 'feedback_request',
            'label' => 'Feedback submitted',
            'description' => 'Runs when a participant submits feedback responses.',
        ],
        'feedback.closed' => [
            'entity' => 'feedback_campaign',
            'label' => 'Feedback campaign closed',
            'description' => 'Runs when a 360° feedback campaign is closed.',
        ],
        'appraisal.session.created' => [
            'entity' => 'appraisal_session',
            'label' => 'Appraisal session created',
            'description' => 'Runs when an appraisal session is created.',
        ],
        'appraisal.generated' => [
            'entity' => 'employee_appraisal',
            'label' => 'Employee appraisal generated',
            'description' => 'Runs when employee appraisals are generated for a session.',
        ],
        'appraisal.submitted' => [
            'entity' => 'employee_appraisal',
            'label' => 'Appraisal submitted',
            'description' => 'Runs when a manager submits an employee appraisal.',
        ],
        'appraisal.calibrated' => [
            'entity' => 'appraisal_calibration',
            'label' => 'Appraisal calibrated',
            'description' => 'Runs when calibration adjustments are approved.',
        ],
        'appraisal.closed' => [
            'entity' => 'employee_appraisal',
            'label' => 'Appraisal closed',
            'description' => 'Runs when an employee appraisal is closed and becomes immutable.',
        ],
        'promotion.recommended' => [
            'entity' => 'appraisal_recommendation',
            'label' => 'Promotion recommended',
            'description' => 'Runs when a promotion recommendation is recorded.',
        ],
        'compensation.recommended' => [
            'entity' => 'appraisal_recommendation',
            'label' => 'Compensation recommended',
            'description' => 'Runs when a compensation recommendation is recorded.',
        ],
        'recruitment.requisition_approved' => [
            'entity' => 'job_requisition',
            'label' => 'Requisition approved',
            'description' => 'Runs when a job requisition is approved for hiring.',
        ],
        'recruitment.opening_published' => [
            'entity' => 'job_opening',
            'label' => 'Opening published',
            'description' => 'Runs when a job opening is published internally.',
        ],
        'recruitment.candidate_created' => [
            'entity' => 'candidate',
            'label' => 'Candidate created',
            'description' => 'Runs when a candidate profile is created.',
        ],
        'recruitment.application_submitted' => [
            'entity' => 'job_application',
            'label' => 'Application submitted',
            'description' => 'Runs when a candidate applies for a job opening.',
        ],
        'recruitment.candidate_registered' => [
            'entity' => 'candidate_account',
            'label' => 'Candidate registered',
            'description' => 'Runs when a candidate creates a portal account.',
        ],
        'recruitment.candidate_logged_in' => [
            'entity' => 'candidate_account',
            'label' => 'Candidate logged in',
            'description' => 'Runs when a candidate logs into the portal.',
        ],
        'recruitment.resume_uploaded' => [
            'entity' => 'candidate_resume',
            'label' => 'Resume uploaded',
            'description' => 'Runs when a candidate uploads a resume.',
        ],
        'recruitment.job_applied' => [
            'entity' => 'job_application',
            'label' => 'Job applied',
            'description' => 'Runs when a public portal application is submitted.',
        ],
        'recruitment.application_withdrawn' => [
            'entity' => 'job_application',
            'label' => 'Application withdrawn',
            'description' => 'Runs when a candidate withdraws an application.',
        ],
        'recruitment.candidate_profile_updated' => [
            'entity' => 'candidate',
            'label' => 'Candidate profile updated',
            'description' => 'Runs when a candidate updates their portal profile.',
        ],
        'recruitment.interview_scheduled' => [
            'entity' => 'interview_round',
            'label' => 'Interview scheduled',
            'description' => 'Runs when an interview round is scheduled.',
        ],
        'recruitment.interview_cancelled' => [
            'entity' => 'interview_round',
            'label' => 'Interview cancelled',
            'description' => 'Runs when an interview round is cancelled.',
        ],
        'recruitment.interview_completed' => [
            'entity' => 'interview_round',
            'label' => 'Interview completed',
            'description' => 'Runs when an interview round is marked completed.',
        ],
        'recruitment.evaluation_submitted' => [
            'entity' => 'candidate_evaluation',
            'label' => 'Evaluation submitted',
            'description' => 'Runs when an interviewer submits a candidate evaluation.',
        ],
        'recruitment.candidate_recommended' => [
            'entity' => 'candidate_evaluation',
            'label' => 'Candidate recommended',
            'description' => 'Runs when an evaluation recommends hiring the candidate.',
        ],
        'recruitment.offer_generated' => [
            'entity' => 'offer_letter',
            'label' => 'Offer generated',
            'description' => 'Runs when an offer letter is generated for a candidate.',
        ],
        'recruitment.offer_approved' => [
            'entity' => 'offer_letter',
            'label' => 'Offer approved',
            'description' => 'Runs when all required offer approvals are completed.',
        ],
        'recruitment.offer_sent' => [
            'entity' => 'offer_letter',
            'label' => 'Offer sent',
            'description' => 'Runs when an approved offer is sent to the candidate.',
        ],
        'recruitment.offer_accepted' => [
            'entity' => 'offer_letter',
            'label' => 'Offer accepted',
            'description' => 'Runs when a candidate accepts an offer.',
        ],
        'recruitment.offer_rejected' => [
            'entity' => 'offer_letter',
            'label' => 'Offer rejected',
            'description' => 'Runs when a candidate rejects an offer.',
        ],
        'recruitment.offer_expired' => [
            'entity' => 'offer_letter',
            'label' => 'Offer expired',
            'description' => 'Runs when an offer passes its expiry date.',
        ],
        'recruitment.hiring_approved' => [
            'entity' => 'hiring_decision',
            'label' => 'Hiring approved',
            'description' => 'Runs when a hire hiring decision is recorded.',
        ],
    ],

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
];
