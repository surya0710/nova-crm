<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\ResourceAllocationService;
use App\Services\SearchService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function createProject(Organization $organization, User $owner, User $actor, string $name): Project
    {
        app(TenantContext::class)->set($organization);

        return app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => $name,
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_search_service_finds_allocation_by_project_name(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Searchable',
            'last_name' => 'ResourcePerson',
        ]);
        $project = $this->createProject($organization, $user, $user, 'UniqueResourceSearchProject');

        app(TenantContext::class)->set($organization);
        app(ResourceAllocationService::class)->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'allocation_type' => 'project',
            'allocation_percentage' => 40,
            'planned_start_date' => '2026-07-20',
            'planned_end_date' => '2026-07-31',
        ], $user);

        $byProject = app(SearchService::class)->search($user, 'UniqueResourceSearchProject');
        $projectSubtitles = $byProject
            ->filter(fn (array $result) => $result['type'] === __('Allocation') || $result['label'] === __('Resource Allocations'))
            ->pluck('subtitle')
            ->all();

        $this->assertContains('UniqueResourceSearchProject', $projectSubtitles);

        $byEmployee = app(SearchService::class)->search($user, 'Searchable');
        $employeeTitles = $byEmployee
            ->filter(fn (array $result) => $result['type'] === __('Allocation') || $result['label'] === __('Resource Allocations'))
            ->pluck('title')
            ->all();

        $this->assertTrue(
            collect($employeeTitles)->contains(fn ($title) => str_contains((string) $title, 'Searchable'))
        );
    }
}
