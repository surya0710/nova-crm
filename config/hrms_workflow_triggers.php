<?php

/**
 * HRMS workflow trigger catalog.
 *
 * Single source of truth consumed by:
 * - config/hrms.php (documentation / catalog mirror)
 * - config/workflows.php (Workflow Platform registry)
 *
 * Trigger keys must match WorkflowDomainEvent::trigger() values.
 */
return [
    'employee.created' => [
        'entity' => 'employee',
        'label' => 'Employee created',
        'description' => 'Runs when an employee master record is created.',
    ],
    'employee.updated' => [
        'entity' => 'employee',
        'label' => 'Employee updated',
        'description' => 'Runs when an employee master record is updated.',
    ],
    'employee.department_changed' => [
        'entity' => 'employee',
        'label' => 'Employee department changed',
        'description' => 'Runs when an employee department assignment changes.',
    ],
    'employee.manager_changed' => [
        'entity' => 'employee',
        'label' => 'Employee manager changed',
        'description' => 'Runs when an employee reporting manager changes.',
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
    'wfh.request_submitted' => [
        'entity' => 'wfh_request',
        'label' => 'WFH request submitted',
        'description' => 'Runs when a work-from-home request is submitted for approval.',
    ],
    'wfh.request_approved' => [
        'entity' => 'wfh_request',
        'label' => 'WFH request approved',
        'description' => 'Runs when a work-from-home request is fully approved (or auto-approved).',
    ],
    'wfh.request_rejected' => [
        'entity' => 'wfh_request',
        'label' => 'WFH request rejected',
        'description' => 'Runs when a work-from-home request is rejected.',
    ],
    'wfh.request_cancelled' => [
        'entity' => 'wfh_request',
        'label' => 'WFH request cancelled',
        'description' => 'Runs when an approved work-from-home request is cancelled.',
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
    'payroll.paid' => [
        'entity' => 'payroll_run',
        'label' => 'Payroll paid',
        'description' => 'Runs when salary payment is confirmed for a published payroll run.',
    ],
    'salary.revised' => [
        'entity' => 'employee_salary_assignment',
        'label' => 'Salary revised',
        'description' => 'Runs when an employee salary assignment revises a prior open assignment.',
    ],
    'payroll.adjustment.approved' => [
        'entity' => 'payroll_adjustment',
        'label' => 'Payroll adjustment approved',
        'description' => 'Runs when a payroll adjustment is approved.',
    ],
    'tax.declaration.submitted' => [
        'entity' => 'tax_declaration',
        'label' => 'Tax declaration submitted',
        'description' => 'Runs when an employee submits an investment declaration.',
    ],
    'tax.declaration.approved' => [
        'entity' => 'tax_declaration',
        'label' => 'Tax declaration approved',
        'description' => 'Runs when HR verifies/approves an investment declaration.',
    ],
    'tax.declaration.rejected' => [
        'entity' => 'tax_declaration',
        'label' => 'Tax declaration rejected',
        'description' => 'Runs when an investment declaration is rejected.',
    ],
    'tax.proof.uploaded' => [
        'entity' => 'tax_proof',
        'label' => 'Tax proof uploaded',
        'description' => 'Runs when an investment proof document is uploaded.',
    ],
    'tax.proof.verified' => [
        'entity' => 'tax_proof',
        'label' => 'Tax proof verified',
        'description' => 'Runs when an investment proof is verified, partially approved, or rejected.',
    ],
    'tds.calculated' => [
        'entity' => 'tds_monthly_calculation',
        'label' => 'TDS calculated',
        'description' => 'Runs when monthly TDS is calculated for an employee.',
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
];
