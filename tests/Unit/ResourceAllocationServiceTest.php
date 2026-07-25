<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\ResourceAllocationService;
use App\Services\TaskDefaultsService;
use App\Services\TaskService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ResourceAllocationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function createProject(Organization $organization, User $owner, User $actor, array $overrides = []): Project
    {
        app(TenantContext::class)->set($organization);

        return app(ProjectService::class)->create(array_merge([
            'organization_id' => $organization->id,
            'name' => 'Allocation Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $overrides), $actor);
    }

    protected function createTask(Organization $organization, Project $project, User $actor, array $overrides = []): Task
    {
        app(TenantContext::class)->set($organization);
        app(TaskDefaultsService::class)->seedAll($organization);

        return app(TaskService::class)->createWorkManagement(array_merge([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Allocation Task',
        ], $overrides), $actor);
    }

    public function test_overlapping_percentage_beyond_max_is_rejected(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $project = $this->createProject($organization, $user, $user);
        $service = app(ResourceAllocationService::class);

        $service->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'allocation_type' => 'project',
            'allocation_percentage' => 60,
            'planned_start_date' => '2026-07-20',
            'planned_end_date' => '2026-07-24',
        ], $user);

        $this->expectException(ValidationException::class);

        $service->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'allocation_type' => 'project',
            'allocation_percentage' => 50,
            'planned_start_date' => '2026-07-22',
            'planned_end_date' => '2026-07-26',
        ], $user);
    }

    public function test_archived_project_is_rejected(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $project = $this->createProject($organization, $user, $user, ['name' => 'Archived Target']);
        $project->update(['is_archived' => true]);

        $this->expectException(ValidationException::class);

        app(ResourceAllocationService::class)->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'allocation_type' => 'project',
            'allocation_percentage' => 40,
            'planned_start_date' => '2026-07-20',
            'planned_end_date' => '2026-07-24',
        ], $user);
    }

    public function test_closed_task_is_rejected(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $project = $this->createProject($organization, $user, $user);
        $task = $this->createTask($organization, $project, $user);

        $closedStatus = TaskStatus::query()
            ->where('organization_id', $organization->id)
            ->where('is_closed', true)
            ->first();

        if ($closedStatus) {
            $task->update(['status_id' => $closedStatus->id]);
        } else {
            $task->update(['status' => 'completed', 'is_archived' => false]);
        }

        $this->assertFalse($task->fresh(['taskStatus'])->isOpen());

        $this->expectException(ValidationException::class);

        app(ResourceAllocationService::class)->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'task_id' => $task->id,
            'allocation_type' => 'task',
            'allocation_percentage' => 40,
            'planned_start_date' => '2026-07-20',
            'planned_end_date' => '2026-07-24',
        ], $user);
    }
}
