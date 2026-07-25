<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ResourceAllocation;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function createProject(Organization $organization, User $owner, User $actor): Project
    {
        app(TenantContext::class)->set($organization);

        return app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Audit Allocation Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_audit_log_created_on_allocation_create(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('resources.allocations.store'), [
                'employee_id' => $employee->id,
                'project_id' => $project->id,
                'allocation_type' => 'project',
                'allocation_percentage' => 30,
                'planned_start_date' => '2026-07-20',
                'planned_end_date' => '2026-07-31',
            ]);

        $allocation = ResourceAllocation::query()
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'auditable_type' => $allocation->getMorphClass(),
            'auditable_id' => $allocation->id,
            'event' => 'created',
            'user_id' => $user->id,
        ]);

        $audit = AuditLog::query()
            ->where('auditable_id', $allocation->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($audit);
    }
}
