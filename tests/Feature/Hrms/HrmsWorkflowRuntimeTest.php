<?php

namespace Tests\Feature\Hrms;

use App\Events\ApplicationSubmitted;
use App\Events\AttendanceCorrectionApproved;
use App\Events\AttendanceCorrectionRejected;
use App\Events\AttendanceCorrectionSubmitted;
use App\Events\EmployeeCreated;
use App\Events\EmployeeDepartmentChanged;
use App\Events\EmployeeDocumentUploaded;
use App\Events\EmployeeManagerChanged;
use App\Events\EmployeeSalaryAssigned;
use App\Events\EmployeeUpdated;
use App\Events\InterviewScheduled;
use App\Events\LeaveApproved;
use App\Events\LeaveCancelled;
use App\Events\LeaveRejected;
use App\Events\LeaveSubmitted;
use App\Events\WfhRequestApproved;
use App\Events\WfhRequestCancelled;
use App\Events\WfhRequestRejected;
use App\Events\WfhRequestSubmitted;
use App\Events\OfferAccepted;
use App\Events\OfferGenerated;
use App\Events\PayrollPaid;
use App\Events\PayrollPeriodLocked;
use App\Events\PayrollPublished;
use App\Events\PayrollRunCompleted;
use App\Events\TaxDeclarationApproved;
use App\Events\TaxDeclarationRejected;
use App\Events\TaxDeclarationSubmitted;
use App\Events\TaxProofUploaded;
use App\Events\TaxProofVerified;
use App\Events\TdsCalculated;
use App\Events\WorkflowDomainEvent;
use App\Listeners\RunTriggeredWorkflows;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeSalaryAssignment;
use App\Models\HrmsShift;
use App\Models\InterviewRound;
use App\Models\InterviewStage;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\WfhRequest;
use App\Models\OfferLetter;
use App\Models\OfferTemplate;
use App\Models\Organization;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\SalaryStructure;
use App\Models\TaxDeclaration;
use App\Models\TaxFinancialYear;
use App\Models\TaxProof;
use App\Models\TdsMonthlyCalculation;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowAction;
use App\Models\WorkflowCondition;
use App\Models\WorkflowExecution;
use App\Notifications\CrmNotification;
use App\Services\TenantContext;
use App\Workflow\ActionContext;
use App\Workflow\ActionDispatcher;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class HrmsWorkflowRuntimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_workflows_execute_through_platform_runtime(): void
    {
        Notification::fake();
        [$organization, $actor] = $this->organizationWithOwner();
        app(TenantContext::class)->set($organization);

        $department = Department::factory()->create(['organization_id' => $organization->id]);
        $manager = Employee::factory()->create(['organization_id' => $organization->id]);
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'reporting_manager_id' => $manager->id,
            'status' => 'active',
        ]);
        $document = EmployeeDocument::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
        ]);
        $structure = SalaryStructure::factory()->create(['organization_id' => $organization->id]);
        $salary = EmployeeSalaryAssignment::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'salary_structure_id' => $structure->id,
            'assigned_by' => $actor->id,
        ]);

        $cases = [
            [EmployeeCreated::class, 'employee.created', $employee],
            [EmployeeUpdated::class, 'employee.updated', $employee],
            [EmployeeSalaryAssigned::class, 'employee.salary_assigned', $salary],
            [EmployeeDocumentUploaded::class, 'employee_document.uploaded', $document],
            [EmployeeDepartmentChanged::class, 'employee.department_changed', $employee],
            [EmployeeManagerChanged::class, 'employee.manager_changed', $employee],
        ];

        foreach ($cases as [$eventClass, $trigger, $subject]) {
            $recipient = User::factory()->create();
            $organization->addMember($recipient, 'organization-owner');
            $this->createNotifyWorkflow($organization, $actor, $trigger, $recipient, "Employee {$trigger}");

            $event = $eventClass::forModel($subject, ['actor_id' => $actor->id], eventId: "emp-{$trigger}");
            $this->assertSame($trigger, $event->trigger());
            $this->assertInstanceOf(ShouldDispatchAfterCommit::class, $event);

            app(RunTriggeredWorkflows::class)->handle($event);

            $this->assertExecutionCompleted($organization, $trigger, $subject);
            Notification::assertSentTo($recipient, CrmNotification::class, function (CrmNotification $notification) use ($organization, $trigger): bool {
                return $notification->organizationId === $organization->id
                    && $notification->title === "Employee {$trigger}";
            });
        }

        $this->assertArrayNotHasKey('employee.document_uploaded', config('workflows.triggers'));
        $this->assertArrayHasKey('employee_document.uploaded', config('workflows.triggers'));
    }

    public function test_leave_workflows_notify_user_with_tenant_isolation(): void
    {
        Notification::fake();
        [$organization, $actor] = $this->organizationWithOwner();
        [$otherOrganization, $otherActor] = $this->organizationWithOwner();
        app(TenantContext::class)->set($organization);

        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $leaveType = LeaveType::factory()->create(['organization_id' => $organization->id]);
        $leave = LeaveApplication::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'pending',
        ]);

        $otherEmployee = Employee::factory()->create(['organization_id' => $otherOrganization->id]);
        $otherLeaveType = LeaveType::factory()->create(['organization_id' => $otherOrganization->id]);
        $otherLeave = LeaveApplication::factory()->create([
            'organization_id' => $otherOrganization->id,
            'employee_id' => $otherEmployee->id,
            'leave_type_id' => $otherLeaveType->id,
            'status' => 'pending',
        ]);

        foreach ([
            [LeaveSubmitted::class, 'leave.submitted'],
            [LeaveApproved::class, 'leave.approved'],
            [LeaveRejected::class, 'leave.rejected'],
            [LeaveCancelled::class, 'leave.cancelled'],
        ] as [$eventClass, $trigger]) {
            $this->createNotifyWorkflow($organization, $actor, $trigger, $actor, "Leave {$trigger}");
            $this->createNotifyWorkflow($otherOrganization, $otherActor, $trigger, $otherActor, "Other leave {$trigger}");

            app(TenantContext::class)->set($organization);
            app(RunTriggeredWorkflows::class)->handle(
                $eventClass::forModel($leave, ['actor_id' => $actor->id], eventId: "leave-{$trigger}")
            );

            $this->assertExecutionCompleted($organization, $trigger, $leave);
            $this->assertSame(
                0,
                WorkflowExecution::withoutGlobalScopes()
                    ->where('organization_id', $otherOrganization->id)
                    ->where('trigger_subject_id', $leave->id)
                    ->count()
            );

            Notification::assertSentTo($actor, CrmNotification::class, fn (CrmNotification $n): bool => $n->title === "Leave {$trigger}"
                && $n->organizationId === $organization->id);
            Notification::assertNotSentTo($otherActor, CrmNotification::class, fn (CrmNotification $n): bool => $n->title === "Leave {$trigger}");
        }

        app(TenantContext::class)->set($otherOrganization);
        app(RunTriggeredWorkflows::class)->handle(
            LeaveSubmitted::forModel($otherLeave, ['actor_id' => $otherActor->id], eventId: 'leave-other-submitted')
        );
        Notification::assertSentTo($otherActor, CrmNotification::class, fn (CrmNotification $n): bool => $n->title === 'Other leave leave.submitted'
            && $n->organizationId === $otherOrganization->id);
    }

    public function test_wfh_workflows_notify_user_with_tenant_isolation(): void
    {
        Notification::fake();
        [$organization, $actor] = $this->organizationWithOwner();
        [$otherOrganization, $otherActor] = $this->organizationWithOwner();
        app(TenantContext::class)->set($organization);

        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $wfh = WfhRequest::factory()->pending()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'work_date' => '2026-08-13',
            'start_date' => '2026-08-13',
            'end_date' => '2026-08-13',
        ]);

        $otherEmployee = Employee::factory()->create(['organization_id' => $otherOrganization->id]);
        $otherWfh = WfhRequest::factory()->pending()->create([
            'organization_id' => $otherOrganization->id,
            'employee_id' => $otherEmployee->id,
            'work_date' => '2026-08-13',
            'start_date' => '2026-08-13',
            'end_date' => '2026-08-13',
        ]);

        foreach ([
            [WfhRequestSubmitted::class, 'wfh.request_submitted'],
            [WfhRequestApproved::class, 'wfh.request_approved'],
            [WfhRequestRejected::class, 'wfh.request_rejected'],
            [WfhRequestCancelled::class, 'wfh.request_cancelled'],
        ] as [$eventClass, $trigger]) {
            $this->createNotifyWorkflow($organization, $actor, $trigger, $actor, "WFH {$trigger}");
            $this->createNotifyWorkflow($otherOrganization, $otherActor, $trigger, $otherActor, "Other WFH {$trigger}");

            app(TenantContext::class)->set($organization);
            app(RunTriggeredWorkflows::class)->handle(
                $eventClass::forModel($wfh, ['actor_id' => $actor->id], eventId: "wfh-{$trigger}")
            );

            $this->assertExecutionCompleted($organization, $trigger, $wfh);
            $this->assertSame(
                0,
                WorkflowExecution::withoutGlobalScopes()
                    ->where('organization_id', $otherOrganization->id)
                    ->where('trigger_subject_id', $wfh->id)
                    ->count()
            );

            Notification::assertSentTo($actor, CrmNotification::class, fn (CrmNotification $n): bool => $n->title === "WFH {$trigger}"
                && $n->organizationId === $organization->id);
            Notification::assertNotSentTo($otherActor, CrmNotification::class, fn (CrmNotification $n): bool => $n->title === "WFH {$trigger}");
        }

        app(TenantContext::class)->set($otherOrganization);
        app(RunTriggeredWorkflows::class)->handle(
            WfhRequestSubmitted::forModel($otherWfh, ['actor_id' => $otherActor->id], eventId: 'wfh-other-submitted')
        );
        Notification::assertSentTo($otherActor, CrmNotification::class, fn (CrmNotification $n): bool => $n->title === 'Other WFH wfh.request_submitted'
            && $n->organizationId === $otherOrganization->id);
    }

    public function test_attendance_correction_workflows_queue_without_mutating_attendance(): void
    {
        Notification::fake();
        [$organization, $actor] = $this->organizationWithOwner();
        app(TenantContext::class)->set($organization);

        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $shift = HrmsShift::factory()->create(['organization_id' => $organization->id]);
        $record = AttendanceRecord::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'status' => 'present',
        ]);
        $correction = AttendanceCorrection::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'attendance_record_id' => $record->id,
            'status' => 'pending',
        ]);
        $recordStatus = $record->fresh()->status;
        $recordSource = $record->fresh()->source;
        $recordDate = $record->fresh()->attendance_date?->toDateString();
        $correctionFingerprint = $correction->fresh()->only(['status', 'reason', 'attendance_record_id']);

        $listener = app(RunTriggeredWorkflows::class);
        $this->assertSame('workflows', $listener->queue);
        $this->assertInstanceOf(ShouldQueueAfterCommit::class, $listener);

        foreach ([
            [AttendanceCorrectionSubmitted::class, 'attendance.correction_submitted'],
            [AttendanceCorrectionApproved::class, 'attendance.correction_approved'],
            [AttendanceCorrectionRejected::class, 'attendance.correction_rejected'],
        ] as [$eventClass, $trigger]) {
            $this->createNotifyWorkflow($organization, $actor, $trigger, $actor, "Attendance {$trigger}");
            app($listener::class)->handle(
                $eventClass::forModel($correction, ['actor_id' => $actor->id], eventId: "att-{$trigger}")
            );
            $this->assertExecutionCompleted($organization, $trigger, $correction);
        }

        $this->assertSame($recordStatus, $record->fresh()->status);
        $this->assertSame($recordSource, $record->fresh()->source);
        $this->assertSame($recordDate, $record->fresh()->attendance_date?->toDateString());
        $this->assertSame($correctionFingerprint, $correction->fresh()->only(array_keys($correctionFingerprint)));
    }

    public function test_payroll_workflows_notify_without_changing_payroll_state_or_actions(): void
    {
        Notification::fake();
        [$organization, $actor] = $this->organizationWithOwner();
        app(TenantContext::class)->set($organization);

        $period = PayrollPeriod::factory()->locked()->create(['organization_id' => $organization->id]);
        $run = PayrollRun::factory()->calculated()->create([
            'organization_id' => $organization->id,
            'payroll_period_id' => $period->id,
            'triggered_by' => $actor->id,
            'status' => 'published',
            'employee_count' => 3,
            'success_count' => 3,
            'error_count' => 0,
        ]);
        $runBefore = $run->only([
            'status', 'employee_count', 'success_count', 'error_count', 'engine_version', 'payroll_period_id',
        ]);
        $periodStatus = $period->status;
        $periodName = $period->name;
        $periodStart = $period->start_date->toDateString();
        $periodEnd = $period->end_date->toDateString();

        foreach ([
            [PayrollPeriodLocked::class, 'payroll.period.locked', $period],
            [PayrollRunCompleted::class, 'payroll.run.completed', $run],
            [PayrollPublished::class, 'payroll.published', $run],
            [PayrollPaid::class, 'payroll.paid', $run],
        ] as [$eventClass, $trigger, $subject]) {
            $this->createNotifyWorkflow($organization, $actor, $trigger, $actor, "Payroll {$trigger}");
            app(RunTriggeredWorkflows::class)->handle(
                $eventClass::forModel($subject, ['actor_id' => $actor->id], eventId: "pay-{$trigger}")
            );
            $this->assertExecutionCompleted($organization, $trigger, $subject);
        }

        $this->assertSame($runBefore, $run->fresh()->only(array_keys($runBefore)));
        $this->assertSame($periodStatus, $period->fresh()->status);
        $this->assertSame($periodName, $period->fresh()->name);
        $this->assertSame($periodStart, $period->fresh()->start_date->toDateString());
        $this->assertSame($periodEnd, $period->fresh()->end_date->toDateString());

        $this->assertNotContains('payroll_run', config('workflows.actions.create_activity.entities'));
        $this->assertNotContains('payroll_period', config('workflows.actions.update_metadata.entities'));
        $this->assertContains('payroll_run', config('workflows.actions.notify_user.entities'));

        $workflow = Workflow::factory()->active()->create([
            'organization_id' => $organization->id,
            'trigger_type' => 'payroll.paid',
            'created_by' => $actor->id,
        ]);
        $action = WorkflowAction::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'type' => 'create_activity',
            'configuration' => ['event' => 'must_not_touch_payroll'],
        ]);
        $execution = WorkflowExecution::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'workflow_version' => $workflow->version,
            'trigger_subject_type' => $run->getMorphClass(),
            'trigger_subject_id' => $run->id,
            'status' => WorkflowExecution::STATUS_RUNNING,
        ]);
        $execution->setRelation('workflow', $workflow);

        try {
            app(ActionDispatcher::class)->dispatch(new ActionContext($execution, $action, $run, $actor));
            $this->fail('Mutating CRM actions must not support payroll subjects.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('does not support', $exception->getMessage());
        }

        $this->assertSame($runBefore, $run->fresh()->only(array_keys($runBefore)));
    }

    public function test_tax_and_tds_workflows_execute_without_tax_mutation_actions(): void
    {
        Notification::fake();
        [$organization, $actor] = $this->organizationWithOwner();
        app(TenantContext::class)->set($organization);

        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $fy = TaxFinancialYear::query()->create([
            'organization_id' => $organization->id,
            'code' => 'FY2026-27',
            'label' => 'FY 2026-27',
            'assessment_year' => '2027-28',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'default_regime' => 'new',
            'is_active' => true,
            'version' => 1,
            'created_by' => $actor->id,
        ]);
        $declaration = TaxDeclaration::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'tax_financial_year_id' => $fy->id,
            'declaration_number' => 'DEC-001',
            'status' => TaxDeclaration::STATUS_SUBMITTED,
            'declared_total' => 50000,
            'approved_total' => 0,
            'submitted_by' => $actor->id,
            'submitted_at' => now(),
        ]);
        $proof = TaxProof::query()->create([
            'organization_id' => $organization->id,
            'tax_declaration_id' => $declaration->id,
            'employee_id' => $employee->id,
            'proof_number' => 'PRF-001',
            'category' => '80C',
            'title' => 'ELSS',
            'file_path' => 'tax/proofs/elss.pdf',
            'original_filename' => 'elss.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'claimed_amount' => 50000,
            'status' => TaxProof::STATUS_UPLOADED,
            'uploaded_by' => $actor->id,
        ]);
        $tds = TdsMonthlyCalculation::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'tax_financial_year_id' => $fy->id,
            'month' => 7,
            'year' => 2026,
            'regime' => 'new',
            'gross_salary' => 100000,
            'taxable_income_annual' => 1200000,
            'annual_tax_liability' => 80000,
            'tds_ytd' => 20000,
            'tds_amount' => 6667,
            'status' => 'calculated',
            'calculated_at' => now(),
        ]);

        $declarationBefore = $declaration->only(['status', 'declared_total', 'approved_total']);
        $proofBefore = $proof->only(['status', 'claimed_amount', 'approved_amount']);
        $tdsBefore = $tds->only(['tds_amount', 'status', 'annual_tax_liability']);

        foreach ([
            [TaxDeclarationSubmitted::class, 'tax.declaration.submitted', $declaration],
            [TaxDeclarationApproved::class, 'tax.declaration.approved', $declaration],
            [TaxDeclarationRejected::class, 'tax.declaration.rejected', $declaration],
            [TaxProofUploaded::class, 'tax.proof.uploaded', $proof],
            [TaxProofVerified::class, 'tax.proof.verified', $proof],
            [TdsCalculated::class, 'tds.calculated', $tds],
        ] as [$eventClass, $trigger, $subject]) {
            $this->createNotifyWorkflow($organization, $actor, $trigger, $actor, "Tax {$trigger}");
            app(RunTriggeredWorkflows::class)->handle(
                $eventClass::forModel($subject, ['actor_id' => $actor->id], eventId: "tax-{$trigger}")
            );
            $this->assertExecutionCompleted($organization, $trigger, $subject);
        }

        $this->assertSame($declarationBefore, $declaration->fresh()->only(array_keys($declarationBefore)));
        $this->assertSame($proofBefore, $proof->fresh()->only(array_keys($proofBefore)));
        $this->assertSame($tdsBefore, $tds->fresh()->only(array_keys($tdsBefore)));

        foreach (['create_activity', 'update_metadata', 'change_lead_status'] as $action) {
            $this->assertNotContains('tax_declaration', config("workflows.actions.{$action}.entities", []));
            $this->assertNotContains('tds_monthly_calculation', config("workflows.actions.{$action}.entities", []));
        }
    }

    public function test_recruitment_workflows_use_canonical_triggers_without_aliases(): void
    {
        Notification::fake();
        [$organization, $actor] = $this->organizationWithOwner();
        app(TenantContext::class)->set($organization);

        $department = Department::factory()->create(['organization_id' => $organization->id]);
        $designation = Designation::factory()->create(['organization_id' => $organization->id]);
        $requisition = JobRequisition::factory()->approved()->create([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
        ]);
        $opening = JobOpening::factory()->published()->create([
            'organization_id' => $organization->id,
            'job_requisition_id' => $requisition->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
        ]);
        $candidate = Candidate::factory()->create(['organization_id' => $organization->id]);
        $application = JobApplication::factory()->create([
            'organization_id' => $organization->id,
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
        ]);
        $stage = InterviewStage::factory()->create(['organization_id' => $organization->id]);
        $round = InterviewRound::factory()->create([
            'organization_id' => $organization->id,
            'job_application_id' => $application->id,
            'interview_stage_id' => $stage->id,
        ]);
        $template = OfferTemplate::factory()->create([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
        $offer = OfferLetter::factory()->create([
            'organization_id' => $organization->id,
            'job_application_id' => $application->id,
            'candidate_id' => $candidate->id,
            'offer_template_id' => $template->id,
            'status' => 'accepted',
        ]);

        foreach ([
            [ApplicationSubmitted::class, 'recruitment.application_submitted', $application],
            [InterviewScheduled::class, 'recruitment.interview_scheduled', $round],
            [OfferGenerated::class, 'recruitment.offer_generated', $offer],
            [OfferAccepted::class, 'recruitment.offer_accepted', $offer],
        ] as [$eventClass, $trigger, $subject]) {
            $this->createNotifyWorkflow($organization, $actor, $trigger, $actor, "Recruit {$trigger}");
            $event = $eventClass::forModel($subject, ['actor_id' => $actor->id], eventId: "rec-{$trigger}");
            $this->assertSame($trigger, $event->trigger());
            app(RunTriggeredWorkflows::class)->handle($event);
            $this->assertExecutionCompleted($organization, $trigger, $subject);
        }

        foreach ([
            'recruitment.application.created',
            'recruitment.interview.scheduled',
            'recruitment.offer.created',
            'recruitment.offer.accepted',
        ] as $alias) {
            $this->assertArrayNotHasKey($alias, config('workflows.triggers'));
            $this->assertArrayNotHasKey($alias, config('hrms.workflow_triggers'));
        }
    }

    public function test_notification_runtime_is_scoped_to_recipient_org_entity_and_is_not_duplicated(): void
    {
        Notification::fake();
        [$organization, $actor] = $this->organizationWithOwner();
        [$otherOrganization, $outsider] = $this->organizationWithOwner();
        app(TenantContext::class)->set($organization);

        $recipient = User::factory()->create();
        $organization->addMember($recipient, 'organization-owner');
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);

        $workflow = $this->createNotifyWorkflow(
            $organization,
            $actor,
            'employee.created',
            $recipient,
            'New hire',
            'Employee master created.',
            '/hrms/employees/'.$employee->id,
        );

        $event = EmployeeCreated::forModel($employee, [
            'actor_id' => $actor->id,
            'source' => 'runtime-test',
        ], eventId: 'notify-once');

        $listener = app(RunTriggeredWorkflows::class);
        $listener->handle($event);
        $listener->handle($event);

        $this->assertDatabaseCount('workflow_executions', 1);
        $execution = WorkflowExecution::query()->firstOrFail();
        $this->assertSame(WorkflowExecution::STATUS_COMPLETED, $execution->status);
        $this->assertSame($organization->id, $execution->organization_id);
        $this->assertSame($employee->id, $execution->trigger_subject_id);
        $this->assertSame($employee->getMorphClass(), $execution->trigger_subject_type);
        $this->assertSame($actor->id, data_get($execution->trigger_payload, 'actor_id'));
        $this->assertSame('runtime-test', data_get($execution->trigger_payload, 'source'));
        $this->assertSame($workflow->id, $execution->workflow_id);

        Notification::assertSentToTimes($recipient, CrmNotification::class, 1);
        Notification::assertSentTo($recipient, CrmNotification::class, function (CrmNotification $notification) use ($organization, $employee): bool {
            return $notification->title === 'New hire'
                && $notification->message === 'Employee master created.'
                && $notification->organizationId === $organization->id
                && $notification->actionUrl === '/hrms/employees/'.$employee->id;
        });
        Notification::assertNotSentTo($outsider, CrmNotification::class);
        Notification::assertNotSentTo($actor, CrmNotification::class);
        $this->assertSame($organization->id, app(TenantContext::class)->id());
        $this->assertNotSame($otherOrganization->id, app(TenantContext::class)->id());
    }

    public function test_condition_evaluator_supports_hrms_snapshot_fields(): void
    {
        Notification::fake();
        [$organization, $actor] = $this->organizationWithOwner();
        app(TenantContext::class)->set($organization);

        $department = Department::factory()->create(['organization_id' => $organization->id]);
        $branch = Branch::factory()->create(['organization_id' => $organization->id]);
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);
        $leaveType = LeaveType::factory()->create(['organization_id' => $organization->id, 'code' => 'ANNUAL']);
        $leave = LeaveApplication::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'approved',
        ]);
        $record = AttendanceRecord::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'shift_id' => HrmsShift::factory()->create(['organization_id' => $organization->id])->id,
            'status' => 'present',
        ]);
        $period = PayrollPeriod::factory()->locked()->create(['organization_id' => $organization->id]);
        $fy = TaxFinancialYear::query()->create([
            'organization_id' => $organization->id,
            'code' => 'FY-COND',
            'label' => 'FY Cond',
            'assessment_year' => '2027-28',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'default_regime' => 'new',
            'is_active' => true,
            'version' => 1,
            'created_by' => $actor->id,
        ]);
        $declaration = TaxDeclaration::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'tax_financial_year_id' => $fy->id,
            'declaration_number' => 'DEC-COND',
            'status' => TaxDeclaration::STATUS_SUBMITTED,
            'declared_total' => 1000,
            'submitted_by' => $actor->id,
        ]);
        $document = EmployeeDocument::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'category' => 'passport',
            'expires_at' => now()->addDays(10),
        ]);

        $matchingCases = [
            ['employee.updated', $employee, 'department_id', 'equals', $department->id],
            ['employee.updated', $employee, 'branch_id', 'equals', $branch->id],
            ['employee.updated', $employee, 'status', 'equals', 'active'],
            ['leave.approved', $leave, 'status', 'equals', 'approved'],
            ['leave.approved', $leave, 'leave_type_id', 'equals', $leaveType->id],
            ['attendance.correction_submitted', null, 'status', 'equals', 'pending'],
            ['payroll.period.locked', $period, 'status', 'equals', 'locked'],
            ['tax.declaration.submitted', $declaration, 'status', 'equals', TaxDeclaration::STATUS_SUBMITTED],
            ['employee_document.uploaded', $document, 'category', 'equals', 'passport'],
            ['employee_document.uploaded', $document, 'expires_at', 'not_empty', null],
        ];

        foreach ($matchingCases as $index => [$trigger, $subject, $field, $operator, $value]) {
            $eventClass = match ($trigger) {
                'employee.updated' => EmployeeUpdated::class,
                'leave.approved' => LeaveApproved::class,
                'attendance.correction_submitted' => AttendanceCorrectionSubmitted::class,
                'payroll.period.locked' => PayrollPeriodLocked::class,
                'tax.declaration.submitted' => TaxDeclarationSubmitted::class,
                'employee_document.uploaded' => EmployeeDocumentUploaded::class,
            };

            if ($trigger === 'attendance.correction_submitted') {
                $subject = AttendanceCorrection::factory()->create([
                    'organization_id' => $organization->id,
                    'employee_id' => $employee->id,
                    'attendance_record_id' => $record->id,
                    'status' => 'pending',
                ]);
            }

            $workflow = $this->createNotifyWorkflow($organization, $actor, $trigger, $actor, "Cond match {$index}");
            WorkflowCondition::factory()->create([
                'organization_id' => $organization->id,
                'workflow_id' => $workflow->id,
                'workflow_version' => $workflow->version,
                'type' => WorkflowCondition::TYPE_CONDITION,
                'field' => $field,
                'operator' => $operator,
                'value' => $value,
            ]);

            app(RunTriggeredWorkflows::class)->handle(
                $eventClass::forModel($subject, ['actor_id' => $actor->id], eventId: "cond-match-{$index}")
            );

            $this->assertDatabaseHas('workflow_executions', [
                'workflow_id' => $workflow->id,
                'status' => WorkflowExecution::STATUS_COMPLETED,
            ]);
        }

        $skipWorkflow = $this->createNotifyWorkflow($organization, $actor, 'employee.updated', $actor, 'Should skip');
        WorkflowCondition::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $skipWorkflow->id,
            'workflow_version' => $skipWorkflow->version,
            'type' => WorkflowCondition::TYPE_CONDITION,
            'field' => 'status',
            'operator' => 'equals',
            'value' => 'exited',
        ]);
        app(RunTriggeredWorkflows::class)->handle(
            EmployeeUpdated::forModel($employee, ['actor_id' => $actor->id], eventId: 'cond-skip')
        );
        $this->assertDatabaseHas('workflow_executions', [
            'workflow_id' => $skipWorkflow->id,
            'status' => WorkflowExecution::STATUS_SKIPPED,
        ]);
        Notification::assertNotSentTo($actor, CrmNotification::class, fn (CrmNotification $n): bool => $n->title === 'Should skip');
    }

    public function test_after_commit_and_queue_safety_for_hrms_events(): void
    {
        $listener = app(RunTriggeredWorkflows::class);
        $this->assertInstanceOf(ShouldQueueAfterCommit::class, $listener);
        $this->assertSame('workflows', $listener->queue);
        $this->assertTrue(is_a(EmployeeCreated::class, WorkflowDomainEvent::class, true));
        $this->assertTrue(is_a(EmployeeCreated::class, ShouldDispatchAfterCommit::class, true));
        $this->assertTrue(is_a(LeaveSubmitted::class, ShouldDispatchAfterCommit::class, true));
        $this->assertTrue(is_a(PayrollPeriodLocked::class, ShouldDispatchAfterCommit::class, true));

        Event::fake([EmployeeCreated::class]);
        $originalConnection = (string) config('database.default');
        $isolatedConnection = 'hrms_workflow_after_commit_test';
        config([
            "database.connections.{$isolatedConnection}" => config("database.connections.{$originalConnection}"),
            'database.default' => $isolatedConnection,
        ]);
        DB::purge($isolatedConnection);

        try {
            DB::connection()->transaction(function (): void {
                $organization = Organization::factory()->create();
                $actor = User::factory()->create();
                $organization->addMember($actor, 'organization-owner');
                app(TenantContext::class)->set($organization);

                $employee = Employee::factory()->create([
                    'organization_id' => $organization->id,
                    'first_name' => 'Rollback',
                    'last_name' => 'Employee',
                    'email' => 'rollback-employee@example.test',
                ]);

                // Emit inside the open transaction — after-commit events must not flush on rollback.
                event(EmployeeCreated::forModel($employee, ['actor_id' => $actor->id]));

                throw new RuntimeException('Rollback HRMS producer transaction.');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Rollback HRMS producer transaction.', $exception->getMessage());
        } finally {
            config(['database.default' => $originalConnection]);
            DB::purge($isolatedConnection);
            app(TenantContext::class)->set(null);
        }

        Event::assertNotDispatched(EmployeeCreated::class);
    }

    public function test_hrms_event_execution_is_idempotent_and_retries_without_repeating_completed_actions(): void
    {
        Notification::fake();
        [$organization, $actor] = $this->organizationWithOwner();
        app(TenantContext::class)->set($organization);

        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $workflow = Workflow::factory()->active()->create([
            'organization_id' => $organization->id,
            'trigger_type' => 'employee.created',
            'created_by' => $actor->id,
        ]);
        WorkflowAction::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'type' => 'notify_user',
            'position' => 0,
            'configuration' => [
                'user_id' => $actor->id,
                'title' => 'Completed once',
                'message' => 'First action',
            ],
        ]);
        $failing = WorkflowAction::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'type' => 'notify_user',
            'position' => 1,
            'configuration' => [
                'user_id' => $actor->id,
                'title' => '',
                'message' => 'Broken second action',
            ],
        ]);

        $event = EmployeeCreated::forModel($employee, ['actor_id' => $actor->id], eventId: 'hrms-retry-event');
        $listener = app(RunTriggeredWorkflows::class);

        try {
            $listener->handle($event);
            $this->fail('Invalid notify_user action should fail.');
        } catch (\Throwable) {
            $this->assertDatabaseHas('workflow_executions', [
                'status' => WorkflowExecution::STATUS_FAILED,
                'attempt' => 1,
            ]);
        }

        Notification::assertSentToTimes($actor, CrmNotification::class, 1);

        $failing->update([
            'configuration' => [
                'user_id' => $actor->id,
                'title' => 'Retried',
                'message' => 'Second action recovered',
            ],
        ]);
        $listener->handle($event);
        $listener->handle($event);

        $this->assertDatabaseCount('workflow_executions', 1);
        $this->assertDatabaseHas('workflow_executions', [
            'status' => WorkflowExecution::STATUS_COMPLETED,
            'attempt' => 2,
        ]);
        Notification::assertSentToTimes($actor, CrmNotification::class, 2);

        $execution = WorkflowExecution::query()->firstOrFail();
        $this->assertSame(1, $execution->logs()
            ->where('event', 'action.completed')
            ->where('workflow_action_id', $workflow->actions()->orderBy('position')->firstOrFail()->id)
            ->count());
    }

    public function test_stale_lease_redelivery_recovers_hrms_execution(): void
    {
        Notification::fake();
        [$organization, $actor] = $this->organizationWithOwner();
        app(TenantContext::class)->set($organization);

        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $workflow = Workflow::factory()->active()->create([
            'organization_id' => $organization->id,
            'trigger_type' => 'employee.updated',
            'created_by' => $actor->id,
            'execution_timeout_seconds' => 300,
        ]);
        WorkflowAction::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'type' => 'notify_user',
            'configuration' => [
                'user_id' => $actor->id,
                'title' => 'Stale lease recovered',
                'message' => 'HRMS redelivery',
            ],
        ]);

        $event = EmployeeUpdated::forModel($employee, ['actor_id' => $actor->id], eventId: 'hrms-stale-event');
        $execution = WorkflowExecution::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'workflow_version' => $workflow->version,
            'trigger_subject_type' => $employee->getMorphClass(),
            'trigger_subject_id' => $employee->id,
            'trigger_subject_snapshot' => $event->subjectSnapshot,
            'trigger_payload' => ['actor_id' => $actor->id, '_event' => ['id' => 'hrms-stale-event']],
            'idempotency_key' => hash('sha256', "hrms-stale-event|{$workflow->id}"),
            'status' => WorkflowExecution::STATUS_RUNNING,
            'attempt' => 1,
            'heartbeat_at' => now()->subSeconds(301),
            'lock_acquired_at' => now()->subSeconds(301),
        ]);

        app(RunTriggeredWorkflows::class)->handle($event);

        $this->assertSame(WorkflowExecution::STATUS_COMPLETED, $execution->fresh()->status);
        $this->assertSame(2, $execution->fresh()->attempt);
        $this->assertDatabaseHas('workflow_execution_logs', [
            'workflow_execution_id' => $execution->id,
            'event' => 'execution.lease_recovered',
        ]);
        Notification::assertSentTo($actor, CrmNotification::class, fn (CrmNotification $n): bool => $n->title === 'Stale lease recovered');
    }

    /** @return array{Organization, User} */
    protected function organizationWithOwner(): array
    {
        $organization = Organization::factory()->create();
        $actor = User::factory()->create();
        $organization->addMember($actor, 'organization-owner');

        return [$organization, $actor];
    }

    protected function createNotifyWorkflow(
        Organization $organization,
        User $actor,
        string $trigger,
        User $recipient,
        string $title,
        string $message = 'HRMS workflow notification',
        ?string $actionUrl = null,
    ): Workflow {
        $previous = app(TenantContext::class)->get();
        app(TenantContext::class)->set($organization);

        $workflow = Workflow::factory()->active()->create([
            'organization_id' => $organization->id,
            'trigger_type' => $trigger,
            'created_by' => $actor->id,
        ]);

        $configuration = [
            'user_id' => $recipient->id,
            'title' => $title,
            'message' => $message,
        ];
        if ($actionUrl !== null) {
            $configuration['action_url'] = $actionUrl;
        }

        WorkflowAction::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'workflow_version' => $workflow->version,
            'type' => 'notify_user',
            'configuration' => $configuration,
        ]);

        app(TenantContext::class)->set($previous);

        return $workflow;
    }

    protected function assertExecutionCompleted(Organization $organization, string $trigger, Model $subject): void
    {
        $workflowId = Workflow::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('trigger_type', $trigger)
            ->orderByDesc('id')
            ->value('id');

        $this->assertNotNull($workflowId);
        $this->assertDatabaseHas('workflow_executions', [
            'organization_id' => $organization->id,
            'workflow_id' => $workflowId,
            'trigger_subject_id' => $subject->getKey(),
            'status' => WorkflowExecution::STATUS_COMPLETED,
        ]);
    }
}
