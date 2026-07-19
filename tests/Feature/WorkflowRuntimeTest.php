<?php

namespace Tests\Feature;

use App\Events\LeadCreated;
use App\Events\LeadUpdated;
use App\Listeners\RunTriggeredWorkflows;
use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowAction;
use App\Models\WorkflowExecution;
use App\Models\WorkflowExecutionLog;
use App\Services\TenantContext;
use App\Workflow\ActionDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;
use Tests\TestCase;

class WorkflowRuntimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_visibility_timeout_exceeds_listener_timeout_and_retry_budget(): void
    {
        $listener = app(RunTriggeredWorkflows::class);

        $this->assertSame(330, $listener->timeout);
        $this->assertSame(100, $listener->tries);
        $this->assertGreaterThan($listener->timeout, config('queue.connections.database.retry_after'));
        $this->assertGreaterThan($listener->timeout, config('queue.connections.redis.retry_after'));
    }

    public function test_event_execution_is_tenant_safe_idempotent_and_logs_action_outcome(): void
    {
        [$organization, $actor] = $this->organizationWithOwner();
        [$other] = $this->organizationWithOwner();
        app(TenantContext::class)->set($organization);

        $workflow = Workflow::factory()->active()->create([
            'organization_id' => $organization->id,
            'trigger_type' => 'lead.created',
            'created_by' => $actor->id,
        ]);
        WorkflowAction::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'type' => 'create_activity',
            'configuration' => ['event' => 'workflow_test', 'properties' => ['source' => 'test']],
        ]);
        Workflow::factory()->active()->create([
            'organization_id' => $other->id,
            'trigger_type' => 'lead.created',
        ]);

        $lead = Lead::factory()->create(['organization_id' => $organization->id, 'created_by' => $actor->id]);
        $event = LeadCreated::forModel($lead, ['actor_id' => $actor->id], eventId: 'event-1');
        $listener = app(RunTriggeredWorkflows::class);

        $listener->handle($event);
        $workflow->increment('version');
        $listener->handle($event);

        $this->assertDatabaseCount('workflow_executions', 1);
        $execution = WorkflowExecution::query()->firstOrFail();
        $this->assertSame(WorkflowExecution::STATUS_COMPLETED, $execution->status);
        $this->assertSame($lead->id, $execution->trigger_subject_id);
        $this->assertDatabaseHas('workflow_execution_logs', ['event' => 'action.completed', 'status' => 'completed']);
        $this->assertDatabaseHas('audit_logs', ['organization_id' => $organization->id, 'event' => 'workflow_test']);
        $this->assertSame($organization->id, app(TenantContext::class)->id());
    }

    public function test_event_snapshot_refreshes_complete_persisted_state(): void
    {
        [$organization, $actor] = $this->organizationWithOwner();
        app(TenantContext::class)->set($organization);
        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $actor->id,
            'status' => 'new',
            'custom_fields' => ['score' => 10],
        ]);
        Lead::query()->whereKey($lead->id)->update([
            'status' => 'contacted',
            'custom_fields' => ['score' => 90],
        ]);

        $event = LeadCreated::forModel($lead);

        $this->assertSame('contacted', $event->subjectSnapshot['status']);
        $this->assertSame(['score' => 90], $event->subjectSnapshot['custom_fields']);
    }

    public function test_concurrency_limit_defers_execution_without_losing_it(): void
    {
        [$organization, $actor] = $this->organizationWithOwner();
        app(TenantContext::class)->set($organization);
        $workflow = Workflow::factory()->active()->create([
            'organization_id' => $organization->id,
            'trigger_type' => 'lead.created',
            'created_by' => $actor->id,
            'concurrency_limit' => 1,
        ]);
        WorkflowAction::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'type' => 'create_activity',
            'configuration' => ['event' => 'should_not_run'],
        ]);
        WorkflowExecution::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'workflow_version' => $workflow->version,
            'status' => WorkflowExecution::STATUS_RUNNING,
        ]);
        $lead = Lead::factory()->create(['organization_id' => $organization->id, 'created_by' => $actor->id]);

        app(RunTriggeredWorkflows::class)->handle(LeadCreated::forModel($lead, ['actor_id' => $actor->id], eventId: 'event-2'));

        $this->assertDatabaseHas('workflow_executions', [
            'workflow_id' => $workflow->id,
            'status' => WorkflowExecution::STATUS_PENDING,
        ]);
        $this->assertDatabaseMissing('audit_logs', ['event' => 'should_not_run']);
    }

    public function test_deferred_execution_acquires_capacity_on_redelivery_and_completes(): void
    {
        [$organization, $actor] = $this->organizationWithOwner();
        app(TenantContext::class)->set($organization);
        $workflow = Workflow::factory()->active()->create([
            'organization_id' => $organization->id,
            'trigger_type' => 'lead.created',
            'created_by' => $actor->id,
            'concurrency_limit' => 1,
        ]);
        WorkflowAction::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'type' => 'create_activity',
            'configuration' => ['event' => 'ran_after_capacity_freed'],
        ]);
        $blocking = WorkflowExecution::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'status' => WorkflowExecution::STATUS_RUNNING,
            'heartbeat_at' => now(),
            'lock_acquired_at' => now(),
        ]);
        $lead = Lead::factory()->create(['organization_id' => $organization->id, 'created_by' => $actor->id]);
        $event = LeadCreated::forModel($lead, ['actor_id' => $actor->id], eventId: 'deferred-event');
        $listener = app(RunTriggeredWorkflows::class);

        $listener->handle($event);
        $this->assertDatabaseHas('workflow_executions', [
            'idempotency_key' => hash('sha256', "deferred-event|{$workflow->id}"),
            'status' => WorkflowExecution::STATUS_PENDING,
            'attempt' => 0,
        ]);

        $blocking->update(['status' => WorkflowExecution::STATUS_COMPLETED, 'finished_at' => now()]);
        $listener->handle($event);

        $this->assertDatabaseHas('workflow_executions', [
            'idempotency_key' => hash('sha256', "deferred-event|{$workflow->id}"),
            'status' => WorkflowExecution::STATUS_COMPLETED,
            'attempt' => 1,
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'ran_after_capacity_freed']);
    }

    public function test_redelivery_recovers_stale_lease_for_same_event(): void
    {
        [$organization, $actor] = $this->organizationWithOwner();
        app(TenantContext::class)->set($organization);
        $workflow = Workflow::factory()->active()->create([
            'organization_id' => $organization->id,
            'trigger_type' => 'lead.created',
            'created_by' => $actor->id,
            'execution_timeout_seconds' => 300,
        ]);
        WorkflowAction::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'type' => 'create_activity',
            'configuration' => ['event' => 'stale_lease_recovered'],
        ]);
        $lead = Lead::factory()->create(['organization_id' => $organization->id, 'created_by' => $actor->id]);
        $event = LeadCreated::forModel($lead, ['actor_id' => $actor->id], eventId: 'stale-event');
        $execution = WorkflowExecution::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'workflow_version' => $workflow->version,
            'trigger_subject_type' => $lead->getMorphClass(),
            'trigger_subject_id' => $lead->id,
            'trigger_subject_snapshot' => $event->subjectSnapshot,
            'trigger_payload' => ['actor_id' => $actor->id, '_event' => ['id' => 'stale-event']],
            'idempotency_key' => hash('sha256', "stale-event|{$workflow->id}"),
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
    }

    public function test_failed_execution_can_retry_without_repeating_completed_actions(): void
    {
        [$organization, $actor] = $this->organizationWithOwner();
        app(TenantContext::class)->set($organization);
        $workflow = Workflow::factory()->active()->create([
            'organization_id' => $organization->id,
            'trigger_type' => 'lead.created',
            'created_by' => $actor->id,
        ]);
        WorkflowAction::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'type' => 'create_activity',
            'position' => 0,
            'configuration' => ['event' => 'completed_once'],
        ]);
        $failing = WorkflowAction::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'type' => 'notify_user',
            'position' => 1,
            'configuration' => ['user_id' => $actor->id, 'title' => '', 'message' => 'Retry me'],
        ]);
        $lead = Lead::factory()->create(['organization_id' => $organization->id, 'created_by' => $actor->id]);
        $event = LeadCreated::forModel($lead, ['actor_id' => $actor->id], eventId: 'retry-event');
        $listener = app(RunTriggeredWorkflows::class);

        try {
            $listener->handle($event);
            $this->fail('The invalid action should fail.');
        } catch (\Throwable) {
            $this->assertDatabaseHas('workflow_executions', ['status' => WorkflowExecution::STATUS_FAILED, 'attempt' => 1]);
        }

        $failing->update(['configuration' => [
            'user_id' => $actor->id,
            'title' => 'Retried',
            'message' => 'Retry me',
        ]]);
        $listener->handle($event);

        $this->assertDatabaseHas('workflow_executions', ['status' => WorkflowExecution::STATUS_COMPLETED, 'attempt' => 2]);
        $this->assertSame(1, AuditLog::query()->where('event', 'completed_once')->count());
        $execution = WorkflowExecution::query()->firstOrFail();
        $this->assertSame(
            ['audit_log_id' => AuditLog::query()->where('event', 'completed_once')->value('id')],
            $execution->result['actions'][(string) $workflow->actions()->orderBy('position')->firstOrFail()->id],
        );
        $this->assertSame(1, $execution->logs()
            ->where('event', 'action.completed')
            ->where('workflow_action_id', $workflow->actions()->orderBy('position')->firstOrFail()->id)
            ->count());
        $this->assertSame(1, $execution->logs()
            ->where('event', 'action.started')
            ->where('workflow_action_id', $workflow->actions()->orderBy('position')->firstOrFail()->id)
            ->count());
    }

    public function test_failed_action_rolls_back_its_database_side_effect_but_keeps_diagnostic_logs(): void
    {
        [$organization, $actor] = $this->organizationWithOwner();
        app(TenantContext::class)->set($organization);
        $workflow = Workflow::factory()->active()->create([
            'organization_id' => $organization->id,
            'trigger_type' => 'lead.created',
            'created_by' => $actor->id,
        ]);
        $action = WorkflowAction::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'type' => 'create_activity',
            'configuration' => ['event' => 'rolled_back_action'],
        ]);
        $lead = Lead::factory()->create(['organization_id' => $organization->id, 'created_by' => $actor->id]);
        $this->mock(ActionDispatcher::class, function (MockInterface $mock) use ($organization, $actor, $lead): void {
            $mock->shouldReceive('dispatch')->once()->andReturnUsing(function () use ($organization, $actor, $lead): array {
                AuditLog::query()->create([
                    'organization_id' => $organization->id,
                    'user_id' => $actor->id,
                    'auditable_type' => $lead->getMorphClass(),
                    'auditable_id' => $lead->id,
                    'event' => 'rolled_back_action',
                ]);

                throw new \RuntimeException('Fail after the local side effect.');
            });
        });

        try {
            app(RunTriggeredWorkflows::class)->handle(
                LeadCreated::forModel($lead, ['actor_id' => $actor->id], eventId: 'atomic-action-event')
            );
            $this->fail('The action should fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Fail after the local side effect.', $exception->getMessage());
        }

        $execution = WorkflowExecution::query()->firstOrFail();
        $this->assertDatabaseMissing('audit_logs', ['event' => 'rolled_back_action']);
        $this->assertSame(1, WorkflowExecutionLog::query()
            ->where('workflow_execution_id', $execution->id)
            ->where('workflow_action_id', $action->id)
            ->where('event', 'action.started')
            ->count());
        $this->assertSame(1, WorkflowExecutionLog::query()
            ->where('workflow_execution_id', $execution->id)
            ->where('workflow_action_id', $action->id)
            ->where('event', 'action.failed')
            ->count());
        $this->assertSame(0, WorkflowExecutionLog::query()
            ->where('workflow_execution_id', $execution->id)
            ->where('workflow_action_id', $action->id)
            ->where('event', 'action.completed')
            ->count());
    }

    public function test_metadata_action_validates_and_updates_projection_through_form_service(): void
    {
        [$organization, $actor] = $this->organizationWithOwner();
        app(TenantContext::class)->set($organization);
        MetadataFieldDefinition::query()->create([
            'organization_id' => $organization->id,
            'entity_type' => 'lead',
            'key' => 'workflow_score',
            'label' => 'Workflow score',
            'type' => 'number',
            'status' => 'active',
            'published_at' => now(),
            'activated_at' => now(),
        ]);
        $workflow = Workflow::factory()->active()->create([
            'organization_id' => $organization->id,
            'trigger_type' => 'lead.created',
            'created_by' => $actor->id,
        ]);
        WorkflowAction::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'type' => 'update_metadata',
            'configuration' => ['values' => ['workflow_score' => '42']],
        ]);
        $lead = Lead::factory()->create(['organization_id' => $organization->id, 'created_by' => $actor->id]);

        Event::fake([LeadUpdated::class]);
        $transactionManager = app('db.transactions');
        app()->offsetUnset('db.transactions');
        try {
            app(RunTriggeredWorkflows::class)->handle(
                LeadCreated::forModel($lead, ['actor_id' => $actor->id], eventId: 'metadata-action-event')
            );
        } finally {
            app()->instance('db.transactions', $transactionManager);
        }

        $this->assertSame(42, $lead->fresh()->custom_fields['workflow_score']);
        $this->assertDatabaseHas('metadata_value_projections', [
            'organization_id' => $organization->id,
            'entity_type' => 'lead',
            'entity_id' => $lead->id,
            'field_key' => 'workflow_score',
            'value_number' => 42,
        ]);
        $this->assertDatabaseHas('workflow_execution_logs', [
            'workflow_action_id' => $workflow->actions()->firstOrFail()->id,
            'event' => 'action.completed',
        ]);
        Event::assertDispatched(LeadUpdated::class, fn (LeadUpdated $event): bool => $event->subjectId === $lead->id
            && data_get($event->subjectSnapshot, 'custom_fields.workflow_score') === 42
            && $event->payload['actor_id'] === $actor->id);

        $action = $workflow->actions()->firstOrFail();
        $action->update(['configuration' => ['values' => ['workflow_score' => 'not-a-number']]]);
        $invalidLead = Lead::factory()->create(['organization_id' => $organization->id, 'created_by' => $actor->id]);
        try {
            app(RunTriggeredWorkflows::class)->handle(
                LeadCreated::forModel($invalidLead, ['actor_id' => $actor->id], eventId: 'invalid-metadata-action')
            );
            $this->fail('Invalid metadata should fail the action.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('custom_fields.workflow_score', $exception->errors());
        }
        $this->assertNull(data_get($invalidLead->fresh()->custom_fields, 'workflow_score'));
    }

    /** @return array{Organization, User} */
    protected function organizationWithOwner(): array
    {
        $organization = Organization::factory()->create();
        $actor = User::factory()->create();
        $organization->addMember($actor, 'organization-owner');

        return [$organization, $actor];
    }
}
