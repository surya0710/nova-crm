<?php

namespace Tests\Feature;

use App\Events\ApplicationSubmitted;
use App\Events\AttendanceCorrectionApproved;
use App\Events\AttendanceCorrectionRejected;
use App\Events\AttendanceCorrectionSubmitted;
use App\Events\EmployeeCreated;
use App\Events\EmployeeDocumentExpiring;
use App\Events\EmployeeDocumentUploaded;
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
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowAction;
use App\Services\TenantContext;
use App\Services\WorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class HrmsWorkflowTriggerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Phase 10.7 WP2 priority triggers (canonical keys matching event::trigger()).
     *
     * @return list<array{0: class-string, 1: string}>
     */
    private function priorityTriggers(): array
    {
        return [
            [EmployeeCreated::class, 'employee.created'],
            [EmployeeUpdated::class, 'employee.updated'],
            [EmployeeSalaryAssigned::class, 'employee.salary_assigned'],
            [EmployeeDocumentUploaded::class, 'employee_document.uploaded'],
            [EmployeeDocumentExpiring::class, 'employee_document.expiring'],
            [LeaveSubmitted::class, 'leave.submitted'],
            [LeaveApproved::class, 'leave.approved'],
            [LeaveRejected::class, 'leave.rejected'],
            [LeaveCancelled::class, 'leave.cancelled'],
            [WfhRequestSubmitted::class, 'wfh.request_submitted'],
            [WfhRequestApproved::class, 'wfh.request_approved'],
            [WfhRequestRejected::class, 'wfh.request_rejected'],
            [WfhRequestCancelled::class, 'wfh.request_cancelled'],
            [AttendanceCorrectionSubmitted::class, 'attendance.correction_submitted'],
            [AttendanceCorrectionApproved::class, 'attendance.correction_approved'],
            [AttendanceCorrectionRejected::class, 'attendance.correction_rejected'],
            [PayrollPeriodLocked::class, 'payroll.period.locked'],
            [PayrollRunCompleted::class, 'payroll.run.completed'],
            [PayrollPublished::class, 'payroll.published'],
            [PayrollPaid::class, 'payroll.paid'],
            [TaxDeclarationSubmitted::class, 'tax.declaration.submitted'],
            [TaxDeclarationApproved::class, 'tax.declaration.approved'],
            [TaxDeclarationRejected::class, 'tax.declaration.rejected'],
            [TaxProofUploaded::class, 'tax.proof.uploaded'],
            [TaxProofVerified::class, 'tax.proof.verified'],
            [ApplicationSubmitted::class, 'recruitment.application_submitted'],
            [InterviewScheduled::class, 'recruitment.interview_scheduled'],
            [OfferGenerated::class, 'recruitment.offer_generated'],
            [OfferAccepted::class, 'recruitment.offer_accepted'],
        ];
    }

    public function test_hrms_triggers_are_registered_in_workflow_catalog(): void
    {
        $hrms = config('hrms.workflow_triggers');
        $workflows = config('workflows.triggers');

        $this->assertNotEmpty($hrms);
        $this->assertSame($hrms, array_intersect_key($workflows, $hrms));

        foreach ($this->priorityTriggers() as [, $trigger]) {
            $this->assertArrayHasKey($trigger, $workflows, "Missing workflow registry entry for {$trigger}");
            $this->assertArrayHasKey($trigger, $hrms, "Missing HRMS catalog entry for {$trigger}");
            $this->assertSame($hrms[$trigger], $workflows[$trigger]);
        }
    }

    public function test_priority_hrms_events_are_wired_to_workflow_listener(): void
    {
        foreach ($this->priorityTriggers() as [$eventClass, $trigger]) {
            $this->assertTrue(Event::hasListeners($eventClass), "Missing RunTriggeredWorkflows for {$eventClass}");
            $this->assertSame($trigger, (new $eventClass(1, Employee::class, 1, [], []))->trigger());
        }
    }

    public function test_notify_user_supports_hrms_entities(): void
    {
        $entities = config('workflows.actions.notify_user.entities');
        foreach (['employee', 'leave_application', 'payroll_run', 'tax_declaration', 'offer_letter'] as $entity) {
            $this->assertContains($entity, $entities);
        }
    }

    public function test_workflow_service_accepts_hrms_trigger_with_notify_user(): void
    {
        $actor = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($actor, 'organization-owner');
        app(TenantContext::class)->set($organization);

        $workflow = app(WorkflowService::class)->create($organization, [
            'name' => 'Notify on employee created',
            'trigger_type' => 'employee.created',
            'trigger_config' => [],
            'conditions' => [],
            'actions' => [[
                'type' => 'notify_user',
                'name' => 'Notify HR',
                'status' => WorkflowAction::STATUS_ACTIVE,
                'configuration' => [
                    'user_id' => $actor->id,
                    'title' => 'New employee',
                    'message' => 'An employee was created.',
                ],
            ]],
            'concurrency_limit' => 1,
            'execution_timeout_seconds' => 300,
        ], $actor);

        $this->assertInstanceOf(Workflow::class, $workflow);
        $this->assertSame('employee.created', $workflow->trigger_type);
        $this->assertTrue(Event::hasListeners(EmployeeCreated::class));
    }
}
