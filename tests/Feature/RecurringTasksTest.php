<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskRecurrence;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TaskDefaultsService;
use App\Services\TaskRecurrenceService;
use App\Services\TaskService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringTasksTest extends TestCase
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
            'name' => 'Recurrence Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    protected function createTask(Organization $organization, Project $project, User $actor): Task
    {
        app(TenantContext::class)->set($organization);
        app(TaskDefaultsService::class)->seedAll($organization);

        return app(TaskService::class)->createWorkManagement([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Weekly Sync',
            'due_date' => now()->toDateString(),
        ], $actor);
    }

    public function test_create_recurrence_via_web(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user, $user);
        $task = $this->createTask($organization, $project, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->from(route('tasks.show', $task))
            ->post(route('tasks.recurrence.store', $task), [
                'recurrence_type' => 'weekly',
                'interval' => 1,
                'end_type' => 'never',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('task_recurrences', [
            'task_id' => $task->id,
            'organization_id' => $organization->id,
            'recurrence_type' => 'weekly',
            'is_active' => true,
        ]);
    }

    public function test_artisan_generate_command_creates_task(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user, $user);
        $task = $this->createTask($organization, $project, $user);

        app(TenantContext::class)->set($organization);
        $recurrence = app(TaskRecurrenceService::class)->create($task, [
            'recurrence_type' => 'daily',
            'interval' => 1,
            'end_type' => 'never',
        ], $user);

        $recurrence->update(['next_run_at' => now()->subMinute()]);

        $beforeCount = Task::query()->where('organization_id', $organization->id)->count();

        $this->artisan('projects:generate-recurring-tasks')->assertSuccessful();

        $this->assertGreaterThan($beforeCount, Task::query()->where('organization_id', $organization->id)->count());
        $this->assertDatabaseHas('tasks', [
            'organization_id' => $organization->id,
            'parent_task_id' => $task->id,
            'title' => 'Weekly Sync',
        ]);

        $this->assertGreaterThan(0, (int) $recurrence->fresh()->generated_count);
    }
}
