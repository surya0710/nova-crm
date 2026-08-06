<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResourceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setupApiUser(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function apiHeaders(Organization $organization): array
    {
        return [
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ];
    }

    protected function createProject(Organization $organization, User $owner, User $actor): Project
    {
        app(TenantContext::class)->set($organization);

        return app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'API Resource Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_api_index_and_store_allocations(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $project = $this->createProject($organization, $user, $user);

        Sanctum::actingAs($user, ['*']);

        $store = $this->postJson('/api/v1/resource-allocations', [
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'allocation_type' => 'project',
            'allocation_percentage' => 35,
            'planned_start_date' => '2026-07-20',
            'planned_end_date' => '2026-07-31',
        ], $this->apiHeaders($organization));

        $store->assertCreated();
        $store->assertJsonFragment([
            'allocation_percentage' => 35,
            'allocation_type' => 'project',
        ]);

        $index = $this->getJson('/api/v1/resource-allocations', $this->apiHeaders($organization));
        $index->assertOk();
        $index->assertJsonFragment(['allocation_percentage' => 35]);

        $this->assertDatabaseHas('resource_allocations', [
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_api_index_and_store_calendars(): void
    {
        [$user, $organization] = $this->setupApiUser('organization-owner');
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);

        Sanctum::actingAs($user, ['*']);

        $store = $this->postJson('/api/v1/resource-calendars', [
            'employee_id' => $employee->id,
            'working_hours_per_day' => 8,
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'timezone' => 'UTC',
            'effective_from' => '2026-07-01',
        ], $this->apiHeaders($organization));

        $store->assertCreated();
        $store->assertJsonPath('data.employee_id', $employee->id);
        $this->assertEquals(8, (float) $store->json('data.working_hours_per_day'));

        $index = $this->getJson('/api/v1/resource-calendars', $this->apiHeaders($organization));
        $index->assertOk();
        $index->assertJsonFragment(['employee_id' => $employee->id]);
    }

    public function test_api_workload_team_and_employee_endpoints(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);

        Sanctum::actingAs($user, ['*']);

        $team = $this->getJson('/api/v1/workload/team?from=2026-07-20&to=2026-07-24', $this->apiHeaders($organization));
        $team->assertOk();
        $team->assertJsonStructure([
            'organization_id',
            'from',
            'to',
            'employees',
        ]);

        $employeeWorkload = $this->getJson(
            '/api/v1/workload/employees/'.$employee->id.'?from=2026-07-20&to=2026-07-24',
            $this->apiHeaders($organization)
        );
        $employeeWorkload->assertOk();
        $employeeWorkload->assertJsonFragment(['employee_id' => $employee->id]);
        $employeeWorkload->assertJsonStructure([
            'employee_id',
            'capacity',
            'allocated',
            'available',
            'utilization',
            'status',
            'days',
        ]);
    }

    public function test_api_capacity_forecast_endpoint(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');
        Employee::factory()->create(['organization_id' => $organization->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson(
            '/api/v1/capacity/forecast?from=2026-07-20&to=2026-08-03',
            $this->apiHeaders($organization)
        );

        $response->assertOk();
        $response->assertJsonStructure([
            'organization_id',
            'from',
            'to',
            'employees',
            'summary' => [
                'employee_count',
                'total_available_hours',
                'total_allocated_hours',
            ],
        ]);
    }

    public function test_api_forbidden_without_permission(): void
    {
        [$user, $organization] = $this->setupApiUser('hr');

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/resource-allocations', $this->apiHeaders($organization))
            ->assertForbidden();

        $this->getJson('/api/v1/workload/team', $this->apiHeaders($organization))
            ->assertForbidden();

        $this->getJson('/api/v1/capacity/forecast', $this->apiHeaders($organization))
            ->assertForbidden();
    }
}
