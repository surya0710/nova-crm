<?php

/**
 * Shared KPI library for Analytics & Marketing workspaces (Phase 14.8).
 * Thresholds are defaults; organizations may override via UserUiPreference meta later.
 */
return [
    'categories' => [
        'crm' => [
            'label' => 'CRM',
            'kpis' => [
                'open_leads' => [
                    'label' => 'Open leads',
                    'description' => 'Active leads not won, lost, or converted',
                    'unit' => 'count',
                    'thresholds' => ['warning' => 50, 'critical' => 100],
                ],
                'pipeline_value' => [
                    'label' => 'Pipeline value',
                    'description' => 'Sum of open opportunity amounts',
                    'unit' => 'currency',
                    'thresholds' => ['warning' => null, 'critical' => null],
                ],
                'win_rate' => [
                    'label' => 'Win rate',
                    'description' => 'Won deals / (won + lost) over the period',
                    'unit' => 'percent',
                    'thresholds' => ['warning' => 25, 'critical' => 15],
                ],
                'lead_sources' => [
                    'label' => 'Lead sources',
                    'description' => 'Distribution of lead acquisition sources',
                    'unit' => 'distribution',
                    'thresholds' => [],
                ],
                'revenue_forecast' => [
                    'label' => 'Revenue forecast',
                    'description' => 'Weighted pipeline outlook',
                    'unit' => 'currency',
                    'thresholds' => [],
                ],
            ],
        ],
        'projects' => [
            'label' => 'Projects',
            'kpis' => [
                'active_projects' => [
                    'label' => 'Active projects',
                    'description' => 'Non-archived projects in progress',
                    'unit' => 'count',
                    'thresholds' => [],
                ],
                'at_risk_projects' => [
                    'label' => 'At-risk projects',
                    'description' => 'Projects with at_risk or delayed health',
                    'unit' => 'count',
                    'thresholds' => ['warning' => 3, 'critical' => 8],
                ],
                'avg_completion' => [
                    'label' => 'Average completion',
                    'description' => 'Mean project completion percentage',
                    'unit' => 'percent',
                    'thresholds' => ['warning' => 40, 'critical' => 25],
                ],
                'budget_variance' => [
                    'label' => 'Budget variance',
                    'description' => 'Actual spend vs planned budget',
                    'unit' => 'percent',
                    'thresholds' => ['warning' => 10, 'critical' => 20],
                ],
                'milestone_on_time' => [
                    'label' => 'Milestones on time',
                    'description' => 'Upcoming milestones not overdue',
                    'unit' => 'percent',
                    'thresholds' => ['warning' => 70, 'critical' => 50],
                ],
            ],
        ],
        'hrms' => [
            'label' => 'HRMS',
            'kpis' => [
                'headcount' => [
                    'label' => 'Headcount',
                    'description' => 'Active employees',
                    'unit' => 'count',
                    'thresholds' => [],
                ],
                'pending_leave' => [
                    'label' => 'Pending leave',
                    'description' => 'Leave requests awaiting approval',
                    'unit' => 'count',
                    'thresholds' => ['warning' => 10, 'critical' => 25],
                ],
                'open_openings' => [
                    'label' => 'Open openings',
                    'description' => 'Active job openings',
                    'unit' => 'count',
                    'thresholds' => [],
                ],
                'attrition' => [
                    'label' => 'Attrition',
                    'description' => 'Exits over trailing period',
                    'unit' => 'percent',
                    'thresholds' => ['warning' => 8, 'critical' => 15],
                ],
                'attendance_exceptions' => [
                    'label' => 'Attendance exceptions',
                    'description' => 'Missing or irregular attendance marks',
                    'unit' => 'count',
                    'thresholds' => ['warning' => 5, 'critical' => 15],
                ],
            ],
        ],
        'marketing' => [
            'label' => 'Marketing',
            'kpis' => [
                'attributed_leads' => [
                    'label' => 'Attributed leads',
                    'description' => 'Leads linked via marketing attribution',
                    'unit' => 'count',
                    'thresholds' => [],
                ],
                'cost_per_lead' => [
                    'label' => 'Cost per lead',
                    'description' => 'Campaign spend / attributed leads',
                    'unit' => 'currency',
                    'thresholds' => ['warning' => 50, 'critical' => 100],
                ],
                'conversion_rate' => [
                    'label' => 'Conversion rate',
                    'description' => 'Conversions / attributed leads',
                    'unit' => 'percent',
                    'thresholds' => ['warning' => 5, 'critical' => 2],
                ],
                'campaign_roi' => [
                    'label' => 'Campaign ROI',
                    'description' => '(Revenue − spend) / spend',
                    'unit' => 'percent',
                    'thresholds' => ['warning' => 0, 'critical' => -20],
                ],
                'provider_health' => [
                    'label' => 'Provider health',
                    'description' => 'Connected providers without errors',
                    'unit' => 'percent',
                    'thresholds' => ['warning' => 80, 'critical' => 50],
                ],
            ],
        ],
        'finance' => [
            'label' => 'Finance',
            'kpis' => [
                'outstanding_ar' => [
                    'label' => 'Outstanding AR',
                    'description' => 'Unpaid invoice balance',
                    'unit' => 'currency',
                    'thresholds' => [],
                ],
                'payments_period' => [
                    'label' => 'Payments (period)',
                    'description' => 'Payments collected in the selected period',
                    'unit' => 'currency',
                    'thresholds' => [],
                ],
                'overdue_invoices' => [
                    'label' => 'Overdue invoices',
                    'description' => 'Invoices past due date',
                    'unit' => 'count',
                    'thresholds' => ['warning' => 5, 'critical' => 15],
                ],
            ],
        ],
        'recruitment' => [
            'label' => 'Recruitment',
            'kpis' => [
                'pipeline_candidates' => [
                    'label' => 'Candidates in pipeline',
                    'description' => 'Active candidates across openings',
                    'unit' => 'count',
                    'thresholds' => [],
                ],
                'interviews_scheduled' => [
                    'label' => 'Interviews scheduled',
                    'description' => 'Upcoming interviews',
                    'unit' => 'count',
                    'thresholds' => [],
                ],
                'offers_pending' => [
                    'label' => 'Offers pending',
                    'description' => 'Offers awaiting decision',
                    'unit' => 'count',
                    'thresholds' => ['warning' => 5, 'critical' => 12],
                ],
            ],
        ],
    ],
];
