<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TaskDefaultsService;
use App\Services\TaskService;
use App\Services\TenantContext;
use App\Services\TimeTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaskTimeLogTest extends TestCase
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
            'name' => 'Time Log Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    protected function createTask(Organization $organization, Project $project, User $actor, array $overrides = []): Task
    {
        app(TenantContext::class)->set($organization);
        app(TaskDefaultsService::class)->seedAll($organization);

        return app(TaskService::class)->createWorkManagement(array_merge([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Time Log Task',
        ], $overrides), $actor);
    }

    public function test_user_can_log_manual_time(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);
        $task = $this->createTask($organization, $project, $user);

        $start = now()->subHour()->toDateTimeString();
        $end = now()->toDateTimeString();

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('tasks.time-logs.store', $task), [
                'start_time' => $start,
                'end_time' => $end,
                'duration_minutes' => 60,
                'description' => 'Implementation work',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('task_time_logs', [
            'organization_id' => $organization->id,
            'task_id' => $task->id,
            'user_id' => $user->id,
            'duration_minutes' => 60,
            'source' => 'manual',
            'description' => 'Implementation work',
        ]);
    }

    public function test_time_log_rejected_on_archived_task(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);
        $task = $this->createTask($organization, $project, $user);

        app(TaskService::class)->archive($task, $user);

        $this->expectException(ValidationException::class);
        app(TimeTrackingService::class)->logManual($task->fresh(), [
            'start_time' => now()->subHour(),
            'end_time' => now(),
            'duration_minutes' => 30,
        ], $user);
    }

    public function test_time_log_rejected_on_closed_task(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);
        $task = $this->createTask($organization, $project, $user);

        $closedStatus = TaskStatus::query()
            ->where('organization_id', $organization->id)
            ->where('slug', 'completed')
            ->firstOrFail();

        app(TaskService::class)->update($task, [
            'status_id' => $closedStatus->id,
        ], $user);

        $this->expectException(ValidationException::class);
        app(TimeTrackingService::class)->logManual($task->fresh(), [
            'start_time' => now()->subHour(),
            'end_time' => now(),
            'duration_minutes' => 30,
        ], $user);
    }
}
