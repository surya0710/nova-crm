<?php

namespace Tests\Feature;

use App\Jobs\ProcessBulkOperationJob;
use App\Models\BulkOperation;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use App\Services\Bulk\BulkActionRegistry;
use App\Services\Bulk\BulkOperationsService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BulkOperationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Organization}
     */
    protected function setupOwner(string $plan = 'enterprise'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['plan' => $plan]);
        $organization->addMember($user, 'organization-owner');
        app(TenantContext::class)->set($organization);

        return [$user, $organization];
    }

    public function test_actions_are_registered(): void
    {
        $registry = app(BulkActionRegistry::class);

        $this->assertTrue($registry->has('lead.change_status'));
        $this->assertTrue($registry->has('employee.generate_login'));
        $this->assertTrue($registry->has('user.lock'));
    }

    public function test_lead_status_bulk_updates_records_and_audits(): void
    {
        [$user, $organization] = $this->setupOwner();

        $leads = Lead::factory()->count(3)->create([
            'organization_id' => $organization->id,
            'status' => 'new',
        ]);

        $operation = app(BulkOperationsService::class)->start(
            $organization,
            $user,
            'lead.change_status',
            ['mode' => 'ids', 'ids' => $leads->pluck('id')->all()],
            ['status' => 'contacted'],
            true,
        );

        $this->assertSame(BulkOperation::STATUS_COMPLETED, $operation->status);
        $this->assertSame(3, $operation->success_count);
        $this->assertSame(0, $operation->failed_count);

        foreach ($leads as $lead) {
            $this->assertSame('contacted', $lead->fresh()->status);
        }

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $operation->id,
            'event' => 'bulk_completed',
        ]);
    }

    public function test_large_bulk_is_queued(): void
    {
        Queue::fake();
        config(['bulk.queue_threshold' => 2]);

        [$user, $organization] = $this->setupOwner();
        $leads = Lead::factory()->count(3)->create([
            'organization_id' => $organization->id,
            'status' => 'new',
        ]);

        $operation = app(BulkOperationsService::class)->start(
            $organization,
            $user,
            'lead.change_status',
            ['mode' => 'ids', 'ids' => $leads->pluck('id')->all()],
            ['status' => 'qualified'],
            true,
        );

        $this->assertSame(BulkOperation::STATUS_QUEUED, $operation->status);
        Queue::assertPushed(ProcessBulkOperationJob::class);
    }

    public function test_requires_confirmation(): void
    {
        [$user, $organization] = $this->setupOwner();
        $lead = Lead::factory()->create(['organization_id' => $organization->id]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(BulkOperationsService::class)->start(
            $organization,
            $user,
            'lead.change_status',
            ['mode' => 'ids', 'ids' => [$lead->id]],
            ['status' => 'contacted'],
            false,
        );
    }

    public function test_organization_isolation_on_show(): void
    {
        [$userA, $orgA] = $this->setupOwner();
        $lead = Lead::factory()->create(['organization_id' => $orgA->id, 'status' => 'new']);

        $operation = app(BulkOperationsService::class)->start(
            $orgA,
            $userA,
            'lead.change_status',
            ['mode' => 'ids', 'ids' => [$lead->id]],
            ['status' => 'contacted'],
            true,
        );

        $userB = User::factory()->create();
        $orgB = Organization::factory()->create(['plan' => 'enterprise']);
        $orgB->addMember($userB, 'organization-owner');
        app(TenantContext::class)->set($orgB);

        $this->actingAs($userB)
            ->withSession(['current_organization_id' => $orgB->id])
            ->get(route('administration.bulk.show', $operation))
            ->assertNotFound();
    }

    public function test_api_execute_and_status(): void
    {
        [$user, $organization] = $this->setupOwner();
        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'new',
        ]);

        \Laravel\Sanctum\Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/bulk/execute', [
            'action_key' => 'lead.change_status',
            'selection_mode' => 'ids',
            'ids' => [$lead->id],
            'input' => ['status' => 'contacted'],
            'confirm' => true,
        ], [
            'X-Organization-Id' => (string) $organization->id,
        ]);

        $response->assertCreated();
        $id = $response->json('operation.id');
        $this->assertNotNull($id);

        $this->getJson("/api/v1/bulk/operations/{$id}", [
            'X-Organization-Id' => (string) $organization->id,
        ])->assertOk()
            ->assertJsonPath('operation.success_count', 1);
    }

    public function test_lead_assign_owner_bulk_uses_assignment_service_and_audit(): void
    {
        [$user, $organization] = $this->setupOwner();
        $owner = User::factory()->create(['name' => 'New Owner']);
        $organization->addMember($owner, 'employee');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'assigned_to' => null,
        ]);

        $operation = app(BulkOperationsService::class)->start(
            $organization,
            $user,
            'lead.assign_owner',
            ['mode' => 'ids', 'ids' => [$lead->id]],
            ['owner_id' => $owner->id],
            true,
        );

        $this->assertSame(1, $operation->success_count);
        $this->assertSame($owner->id, $lead->fresh()->assigned_to);

        $this->assertDatabaseHas('assignment_histories', [
            'organization_id' => $organization->id,
            'entity_type' => 'lead',
            'entity_id' => $lead->id,
            'new_owner_id' => $owner->id,
        ]);
    }

    public function test_assign_owner_bulk_action_exposes_user_lookup_field(): void
    {
        $registry = app(\App\Services\Bulk\BulkActionRegistry::class);
        $action = $registry->resolve('lead.assign_owner');
        $fields = $action->inputFields();

        $this->assertSame('user', $fields[0]['type']);
        $this->assertSame('owner_id', $fields[0]['key']);
        $this->assertSame('Assign Owner', $fields[0]['label']);
    }

    public function test_employee_assign_department_exposes_lookup_field(): void
    {
        $registry = app(\App\Services\Bulk\BulkActionRegistry::class);
        $action = $registry->resolve('employee.assign_department');
        $fields = $action->inputFields();

        $this->assertSame('department', $fields[0]['type']);
        $this->assertSame('department_id', $fields[0]['key']);
    }
}
