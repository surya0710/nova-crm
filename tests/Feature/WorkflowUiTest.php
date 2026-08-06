<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowAction;
use App\Models\WorkflowExecution;
use App\Models\WorkflowExecutionLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_workflow_routes_enforce_rbac(): void
    {
        [$organization, $owner] = $this->member('organization-owner');
        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('workflows.index'))
            ->assertOk();

        $this->actingAs($employee)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('workflows.index'))
            ->assertForbidden();

        $this->actingAs($employee)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('workflows.store'), $this->definition())
            ->assertForbidden();
    }

    public function test_owner_can_create_update_enable_disable_and_delete_nested_workflow(): void
    {
        [$organization, $owner] = $this->member('organization-owner');
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($owner)
            ->withSession($session)
            ->get(route('workflows.create'))
            ->assertOk()
            ->assertSee('Condition');

        $response = $this->actingAs($owner)
            ->withSession($session)
            ->post(route('workflows.store'), $this->definition($owner));

        $workflow = Workflow::query()->firstOrFail();
        $response->assertRedirect(route('workflows.show', $workflow));
        $this->assertSame(4, $workflow->conditions()->count());
        $this->assertSame(['north', 'south'], $workflow->conditions()->where('field', 'custom_fields.region')->firstOrFail()->value);
        $this->assertDatabaseHas('workflow_actions', [
            'workflow_id' => $workflow->id,
            'type' => 'notify_user',
            'position' => 1,
        ]);
        $this->actingAs($owner)->withSession($session)
            ->get(route('workflows.show', $workflow))
            ->assertOk()
            ->assertSee('Sequential actions');
        $this->actingAs($owner)->withSession($session)
            ->get(route('workflows.edit', $workflow))
            ->assertOk()
            ->assertSee('custom_fields.region');

        $updated = $this->definition($owner);
        $updated['name'] = 'Updated lead workflow';
        $updated['actions'] = [$updated['actions'][0]];

        $this->actingAs($owner)
            ->withSession($session)
            ->put(route('workflows.update', $workflow), $updated)
            ->assertRedirect(route('workflows.show', $workflow));

        $this->assertDatabaseHas('workflows', ['id' => $workflow->id, 'name' => 'Updated lead workflow', 'version' => 2]);
        $this->assertSame(1, $workflow->actions()->count());

        $this->actingAs($owner)
            ->withSession($session)
            ->post(route('workflows.enable', $workflow))
            ->assertRedirect();
        $this->assertSame(Workflow::STATUS_ACTIVE, $workflow->refresh()->status);

        $this->actingAs($owner)
            ->withSession($session)
            ->post(route('workflows.disable', $workflow))
            ->assertRedirect();
        $this->assertSame(Workflow::STATUS_DISABLED, $workflow->refresh()->status);

        $this->actingAs($owner)
            ->withSession($session)
            ->delete(route('workflows.destroy', $workflow))
            ->assertRedirect(route('workflows.index'));
        $this->assertSoftDeleted($workflow);
    }

    public function test_workflow_binding_is_tenant_isolated(): void
    {
        [$organization, $owner] = $this->member('organization-owner');
        [$otherOrganization] = $this->member('organization-owner');
        $otherWorkflow = Workflow::withoutGlobalScopes()->create([
            'organization_id' => $otherOrganization->id,
            'name' => 'Other tenant workflow',
            'trigger_type' => 'lead.created',
            'trigger_config' => [],
            'status' => Workflow::STATUS_DRAFT,
        ]);

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('workflows.show', $otherWorkflow->id))
            ->assertNotFound();
    }

    public function test_execution_history_and_detail_require_view_permission_and_tenant_match(): void
    {
        [$organization, $owner] = $this->member('organization-owner');
        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');

        $workflow = Workflow::factory()->create(['organization_id' => $organization->id]);
        $action = WorkflowAction::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
        ]);
        $execution = WorkflowExecution::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'status' => WorkflowExecution::STATUS_FAILED,
            'trigger_subject_snapshot' => ['status' => 'new'],
            'trigger_payload' => ['event_id' => 'event-9'],
            'error_message' => 'Action failed.',
        ]);
        WorkflowExecutionLog::factory()->create([
            'organization_id' => $organization->id,
            'workflow_execution_id' => $execution->id,
            'workflow_action_id' => $action->id,
            'event' => 'action.failed',
            'status' => 'failed',
            'context' => ['exception' => 'RuntimeException'],
        ]);

        $session = ['current_organization_id' => $organization->id];
        $this->actingAs($owner)->withSession($session)
            ->get(route('workflows.executions.index', $workflow))
            ->assertOk()
            ->assertSee('Execution history');
        $this->actingAs($owner)->withSession($session)
            ->get(route('workflows.executions.show', [$workflow, $execution]))
            ->assertOk()
            ->assertSee('action.failed')
            ->assertSee('event-9')
            ->assertSee('Action failed.');

        $this->actingAs($employee)->withSession($session)
            ->get(route('workflows.executions.index', $workflow))
            ->assertForbidden();
        $this->actingAs($employee)->withSession($session)
            ->get(route('workflows.executions.show', [$workflow, $execution]))
            ->assertForbidden();

        [$otherOrganization, $otherOwner] = $this->member('organization-owner');
        $this->actingAs($otherOwner)
            ->withSession(['current_organization_id' => $otherOrganization->id])
            ->get(route('workflows.executions.show', [$workflow->id, $execution->id]))
            ->assertNotFound();
    }

    public function test_builder_json_decodes_large_nested_definition_and_value_shapes(): void
    {
        [$organization, $owner] = $this->member('organization-owner');
        $definition = $this->definition($owner);
        $definition['trigger_config'] = [];
        $definition['conditions'] = [[
            'type' => 'group',
            'boolean_operator' => 'all',
            'negated' => true,
            'conditions' => [
                ['type' => 'condition', 'field' => 'status', 'operator' => 'equals', 'value' => 'new'],
                ['type' => 'condition', 'field' => 'source', 'operator' => 'in_list', 'value' => ['web', 'api']],
                ['type' => 'condition', 'field' => 'score', 'operator' => 'between', 'value' => ['10', '90']],
                ['type' => 'condition', 'field' => 'email', 'operator' => 'empty', 'value' => null],
                ...array_map(fn (int $index): array => [
                    'type' => 'condition',
                    'field' => "custom_fields.large_{$index}",
                    'operator' => 'not_equals',
                    'value' => "value-{$index}",
                ], range(1, 80)),
            ],
        ]];

        $response = $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('workflows.store'), $this->builderPayload($definition));

        $response->assertRedirect();
        $workflow = Workflow::query()->firstOrFail();
        $this->assertSame(85, $workflow->conditions()->count());
        $this->assertSame(['web', 'api'], $workflow->conditions()->where('operator', 'in_list')->firstOrFail()->value);
        $this->assertSame(['10', '90'], $workflow->conditions()->where('operator', 'between')->firstOrFail()->value);
        $this->assertNull($workflow->conditions()->where('operator', 'empty')->firstOrFail()->value);
        $this->assertTrue($workflow->conditions()->where('type', 'group')->firstOrFail()->negated);
    }

    public function test_builder_json_rejects_missing_marker_and_unsafe_map_keys(): void
    {
        [$organization, $owner] = $this->member('organization-owner');
        $session = ['current_organization_id' => $organization->id];
        $payload = $this->builderPayload($this->definition($owner));
        unset($payload['workflow_payload_complete']);

        $this->actingAs($owner)->withSession($session)
            ->post(route('workflows.store'), $payload)
            ->assertSessionHasErrors('workflow_payload_complete');

        $duplicate = $this->builderPayload($this->definition($owner));
        $duplicate['workflow_payload_complete'] = 'invalid:Duplicate configuration key: region';
        $this->actingAs($owner)->withSession($session)
            ->post(route('workflows.store'), $duplicate)
            ->assertSessionHasErrors('workflow_payload_complete');

        foreach (['bad[key]', '__proto__'] as $unsafeKey) {
            $definition = $this->definition($owner);
            $definition['actions'][0] = [
                'type' => 'create_activity',
                'configuration' => ['event' => 'safe', 'properties' => [$unsafeKey => 'value']],
            ];
            $this->actingAs($owner)->withSession($session)
                ->post(route('workflows.store'), $this->builderPayload($definition))
                ->assertSessionHasErrors('actions.0.configuration.properties');
        }
    }

    public function test_trigger_config_and_external_notification_urls_are_rejected(): void
    {
        [$organization, $owner] = $this->member('organization-owner');
        $session = ['current_organization_id' => $organization->id];
        $definition = $this->definition($owner);
        $definition['trigger_config'] = ['source' => 'ignored'];

        $this->actingAs($owner)->withSession($session)
            ->post(route('workflows.store'), $definition)
            ->assertSessionHasErrors('trigger_config');

        $definition = $this->definition($owner);
        $definition['actions'][1]['configuration']['action_url'] = 'https://example.test/phish';
        $this->actingAs($owner)->withSession($session)
            ->post(route('workflows.store'), $definition)
            ->assertSessionHasErrors('actions.1.configuration.action_url');

        $definition['actions'][1]['configuration']['action_url'] = '//example.test/phish';
        $this->actingAs($owner)->withSession($session)
            ->post(route('workflows.store'), $definition)
            ->assertSessionHasErrors('actions.1.configuration.action_url');

        $definition['actions'][1]['configuration']['action_url'] = '/\\example.test/phish';
        $this->actingAs($owner)->withSession($session)
            ->post(route('workflows.store'), $definition)
            ->assertSessionHasErrors('actions.1.configuration.action_url');
    }

    private function definition(?User $recipient = null): array
    {
        return [
            'name' => 'Qualify regional leads',
            'description' => 'Nested UI definition',
            'trigger_type' => 'lead.created',
            'trigger_config' => [],
            'concurrency_limit' => 2,
            'execution_timeout_seconds' => 300,
            'conditions' => [[
                'type' => 'group',
                'boolean_operator' => 'all',
                'conditions' => [
                    ['type' => 'condition', 'field' => 'status', 'operator' => 'equals', 'value' => 'new'],
                    [
                        'type' => 'group',
                        'boolean_operator' => 'any',
                        'conditions' => [[
                            'type' => 'condition',
                            'field' => 'custom_fields.region',
                            'operator' => 'in_list',
                            'value' => ['north', 'south'],
                        ]],
                    ],
                ],
            ]],
            'actions' => [
                [
                    'type' => 'create_task',
                    'name' => 'Follow up',
                    'configuration' => ['title' => 'Contact new lead', 'priority' => 'high'],
                    'status' => 'active',
                    'position' => 0,
                ],
                [
                    'type' => 'notify_user',
                    'configuration' => [
                        'user_id' => $recipient?->id,
                        'title' => 'New regional lead',
                        'message' => 'A lead needs review.',
                    ],
                    'status' => 'active',
                    'position' => 1,
                ],
            ],
        ];
    }

    private function builderPayload(array $definition): array
    {
        $payload = collect($definition)->except(['trigger_config', 'conditions', 'actions'])->all();

        return [
            ...$payload,
            'workflow_payload_complete' => 'workflow-builder-v1',
            'trigger_config_json' => json_encode($definition['trigger_config'] ?? [], JSON_THROW_ON_ERROR),
            'conditions_json' => json_encode($definition['conditions'] ?? [], JSON_THROW_ON_ERROR),
            'actions_json' => json_encode($definition['actions'] ?? [], JSON_THROW_ON_ERROR),
        ];
    }

    /** @return array{Organization, User} */
    private function member(string $role): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, $role);

        return [$organization, $user];
    }
}
