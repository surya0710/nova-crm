<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\ResourceAllocationService;
use App\Services\TaskDefaultsService;
use App\Services\TaskService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_creating_allocation_does_not_break_task_or_project_create(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        app(TenantContext::class)->set($organization);

        $employee = Employee::factory()->create(['organization_id' => $organization->id]);

        $project = app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Regression Project',
            'owner_id' => $user->id,
            'manager_id' => $user->id,
            'priority' => 'medium',
        ], $user);

        app(ResourceAllocationService::class)->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'allocation_type' => 'project',
            'allocation_percentage' => 25,
            'planned_start_date' => '2026-07-20',
            'planned_end_date' => '2026-07-31',
        ], $user);

        $secondProject = app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Post Allocation Project',
            'owner_id' => $user->id,
            'manager_id' => $user->id,
            'priority' => 'medium',
        ], $user);

        app(TaskDefaultsService::class)->seedAll($organization);
        $task = app(TaskService::class)->createWorkManagement([
            'organization_id' => $organization->id,
            'project_id' => $secondProject->id,
            'title' => 'Post Allocation Task',
        ], $user);

        $this->assertInstanceOf(Project::class, $secondProject);
        $this->assertInstanceOf(Task::class, $task);
        $this->assertDatabaseHas('projects', ['id' => $secondProject->id, 'name' => 'Post Allocation Project']);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Post Allocation Task']);
    }
}
