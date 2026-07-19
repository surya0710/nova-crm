<?php

namespace Tests\Feature;

use App\Events\CustomerCreated;
use App\Events\CustomerUpdated;
use App\Events\InvoiceCreated;
use App\Events\LeadAssigned;
use App\Events\LeadConverted;
use App\Events\LeadCreated;
use App\Events\LeadUpdated;
use App\Events\MarketingLeadImported;
use App\Events\OpportunityCreated;
use App\Events\OpportunityStageChanged;
use App\Events\PaymentReceived;
use App\Listeners\RunTriggeredWorkflows;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowAction;
use App\Models\WorkflowCondition;
use App\Models\WorkflowExecution;
use App\Models\WorkflowExecutionLog;
use App\Services\NotificationService;
use App\Services\TenantContext;
use App\Services\WorkflowService;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkflowFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_listener_is_registered_and_queues_after_commit(): void
    {
        foreach ([
            LeadCreated::class,
            LeadUpdated::class,
            LeadAssigned::class,
            LeadConverted::class,
            CustomerCreated::class,
            CustomerUpdated::class,
            OpportunityCreated::class,
            OpportunityStageChanged::class,
            InvoiceCreated::class,
            PaymentReceived::class,
            MarketingLeadImported::class,
        ] as $event) {
            $this->assertTrue(Event::hasListeners($event), "Missing workflow listener for {$event}.");
        }
        $this->assertInstanceOf(ShouldQueueAfterCommit::class, app(RunTriggeredWorkflows::class));
    }

    public function test_service_persists_nested_definition_and_controls_lifecycle(): void
    {
        $actor = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($actor, 'organization-owner');
        app(TenantContext::class)->set($organization);

        $service = app(WorkflowService::class);
        $workflow = $service->create($organization, [
            'name' => 'Qualify new leads',
            'trigger_type' => 'lead.created',
            'trigger_config' => [],
            'conditions' => [[
                'type' => WorkflowCondition::TYPE_GROUP,
                'boolean_operator' => 'all',
                'conditions' => [
                    [
                        'type' => WorkflowCondition::TYPE_CONDITION,
                        'field' => 'status',
                        'operator' => 'equals',
                        'value' => 'new',
                    ],
                    [
                        'type' => WorkflowCondition::TYPE_GROUP,
                        'boolean_operator' => 'any',
                        'conditions' => [[
                            'type' => WorkflowCondition::TYPE_CONDITION,
                            'field' => 'source',
                            'operator' => 'equals',
                            'value' => 'website',
                        ]],
                    ],
                ],
            ]],
            'actions' => [[
                'type' => 'create_activity',
                'configuration' => ['event' => 'lead_qualified'],
            ]],
        ], $actor);

        $this->assertSame(Workflow::STATUS_DRAFT, $workflow->status);
        $this->assertSame(4, $workflow->conditions()->count());
        $this->assertSame(1, $workflow->actions()->count());
        $this->assertTrue($actor->hasPermission('workflows.manage', $organization));

        $workflow = $service->enable($workflow, $actor);
        $this->assertSame(Workflow::STATUS_ACTIVE, $workflow->status);
        $this->assertNotNull($workflow->enabled_at);

        $workflow = $service->update($workflow, [
            'name' => 'Qualify inbound leads',
            'conditions' => [],
            'actions' => [[
                'type' => 'notify_user',
                'configuration' => [
                    'user_id' => $actor->id,
                    'title' => 'Lead updated',
                    'message' => 'The lead workflow was updated.',
                ],
                'status' => WorkflowAction::STATUS_ACTIVE,
            ]],
        ], $actor);

        $this->assertSame(2, $workflow->version);
        $this->assertSame('Qualify inbound leads', $workflow->name);
        $this->assertSame(0, $workflow->conditions()->count());
        $this->assertSame('notify_user', $workflow->actions()->first()->type);

        $workflow = $service->disable($workflow, $actor);
        $this->assertSame(Workflow::STATUS_DISABLED, $workflow->status);
        $this->assertNull($workflow->enabled_at);

        $service->delete($workflow, $actor);
        foreach (['workflow_created', 'workflow_enabled', 'workflow_updated', 'workflow_disabled', 'workflow_deleted'] as $event) {
            $this->assertDatabaseHas('audit_logs', [
                'organization_id' => $organization->id,
                'user_id' => $actor->id,
                'auditable_type' => $workflow->getMorphClass(),
                'auditable_id' => $workflow->id,
                'event' => $event,
            ]);
        }
        $this->assertSame(5, AuditLog::query()->where('auditable_id', $workflow->id)->count());
    }

    public function test_tenant_scope_is_applied_to_all_workflow_models(): void
    {
        $first = Organization::factory()->create();
        $second = Organization::factory()->create();
        app(TenantContext::class)->set($first);

        Workflow::factory()->create(['organization_id' => $first->id]);
        Workflow::factory()->create(['organization_id' => $second->id]);

        $this->assertSame(1, Workflow::query()->count());
        $this->assertSame($first->id, Workflow::query()->firstOrFail()->organization_id);
    }

    public function test_partial_update_carries_complete_active_definition_into_an_immutable_version(): void
    {
        $actor = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($actor, 'organization-owner');
        app(TenantContext::class)->set($organization);
        $service = app(WorkflowService::class);
        $workflow = $service->create($organization, [
            'name' => 'Versioned lead workflow',
            'trigger_type' => 'lead.created',
            'trigger_config' => [],
            'conditions' => [[
                'type' => WorkflowCondition::TYPE_GROUP,
                'boolean_operator' => 'all',
                'conditions' => [
                    [
                        'type' => WorkflowCondition::TYPE_CONDITION,
                        'field' => 'status',
                        'operator' => 'equals',
                        'value' => 'new',
                    ],
                    [
                        'type' => WorkflowCondition::TYPE_GROUP,
                        'boolean_operator' => 'any',
                        'conditions' => [[
                            'type' => WorkflowCondition::TYPE_CONDITION,
                            'field' => 'source',
                            'operator' => 'equals',
                            'value' => 'website',
                        ]],
                    ],
                ],
            ]],
            'actions' => [
                [
                    'type' => 'create_activity',
                    'configuration' => ['event' => 'carried_activity'],
                ],
                [
                    'type' => 'change_lead_status',
                    'configuration' => ['status' => 'qualified'],
                ],
            ],
        ], $actor);
        $workflow = $service->enable($workflow, $actor);
        $oldActions = $workflow->actions()->get();
        $oldConditions = $workflow->conditions()->get();
        $execution = WorkflowExecution::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'workflow_version' => 1,
        ]);
        $actionLog = WorkflowExecutionLog::factory()->create([
            'organization_id' => $organization->id,
            'workflow_execution_id' => $execution->id,
            'workflow_action_id' => $oldActions->first()->id,
            'event' => 'action.completed',
        ]);
        $conditionLog = WorkflowExecutionLog::factory()->create([
            'organization_id' => $organization->id,
            'workflow_execution_id' => $execution->id,
            'workflow_condition_id' => $oldConditions->firstWhere('type', WorkflowCondition::TYPE_CONDITION)->id,
            'event' => 'condition.evaluated',
        ]);

        $workflow = $service->update($workflow, ['name' => 'Renamed workflow'], $actor);

        $this->assertSame(2, $workflow->version);
        $this->assertSame(Workflow::STATUS_ACTIVE, $workflow->status);
        $this->assertSame([], $workflow->trigger_config);
        $this->assertCount(2, $workflow->actions);
        $this->assertCount(4, $workflow->conditions);
        $this->assertSame(2, $workflow->actions->where('status', WorkflowAction::STATUS_ACTIVE)->count());
        $this->assertSame([2], $workflow->actions->pluck('workflow_version')->unique()->values()->all());
        $this->assertSame([2], $workflow->conditions->pluck('workflow_version')->unique()->values()->all());
        $this->assertEmpty($workflow->actions->pluck('id')->intersect($oldActions->pluck('id')));
        $this->assertEmpty($workflow->conditions->pluck('id')->intersect($oldConditions->pluck('id')));
        foreach ($oldActions as $oldAction) {
            $this->assertSoftDeleted('workflow_actions', ['id' => $oldAction->id]);
        }
        foreach ($oldConditions as $oldCondition) {
            $this->assertSoftDeleted('workflow_conditions', ['id' => $oldCondition->id]);
        }
        $this->assertSame('carried_activity', $actionLog->fresh()->action->configuration['event']);
        $this->assertSame('status', $conditionLog->fresh()->condition->field);
        $this->assertSame(1, $actionLog->fresh()->execution->workflow_version);

        try {
            $service->update($workflow, ['trigger_type' => 'invoice.created'], $actor);
            $this->fail('Carried-forward actions must be validated against the effective trigger.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('actions.1.type', $exception->errors());
        }

        $this->assertSame(2, $workflow->fresh()->version);
        $this->assertSame(2, $workflow->fresh()->actions()->count());
    }

    public function test_definition_updates_preserve_historical_log_references(): void
    {
        $actor = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($actor, 'organization-owner');
        app(TenantContext::class)->set($organization);
        $workflow = Workflow::factory()->create([
            'organization_id' => $organization->id,
            'trigger_type' => 'lead.created',
        ]);
        $oldAction = WorkflowAction::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'type' => 'create_activity',
            'configuration' => ['event' => 'historical'],
        ]);
        $execution = WorkflowExecution::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'workflow_version' => 1,
        ]);
        $log = WorkflowExecutionLog::factory()->create([
            'organization_id' => $organization->id,
            'workflow_execution_id' => $execution->id,
            'workflow_action_id' => $oldAction->id,
            'event' => 'action.completed',
        ]);

        app(WorkflowService::class)->update($workflow, [
            'actions' => [[
                'type' => 'create_activity',
                'configuration' => ['event' => 'replacement'],
            ]],
        ], $actor);

        $this->assertSoftDeleted('workflow_actions', ['id' => $oldAction->id]);
        $this->assertSame('historical', $log->fresh()->action->configuration['event']);
    }

    public function test_service_and_notification_runtime_reject_external_action_urls(): void
    {
        $actor = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($actor, 'organization-owner');
        app(TenantContext::class)->set($organization);

        try {
            app(WorkflowService::class)->create($organization, [
                'name' => 'Unsafe URL workflow',
                'trigger_type' => 'lead.created',
                'trigger_config' => [],
                'actions' => [[
                    'type' => 'notify_user',
                    'configuration' => [
                        'user_id' => $actor->id,
                        'title' => 'Unsafe',
                        'message' => 'Unsafe action URL',
                        'action_url' => 'https://example.test/phish',
                    ],
                ]],
            ], $actor);
            $this->fail('WorkflowService should reject an external action URL.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('actions.0.configuration.action_url', $exception->errors());
        }

        try {
            app(NotificationService::class)->send(
                $organization->id,
                $actor->id,
                'Unsafe',
                'Unsafe action URL',
                '//example.test/phish',
            );
            $this->fail('NotificationService should reject a protocol-relative URL.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('action_url', $exception->errors());
        }
    }
}
