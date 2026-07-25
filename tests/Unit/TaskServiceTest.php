<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Task;
use App\Models\TaskPriority;
use App\Models\TaskStatus;
use App\Models\User;
use App\Services\ChecklistService;
use App\Services\TaskDefaultsService;
use App\Services\TaskDependencyService;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function makeTask(Organization $organization, User $creator, array $overrides = []): Task
    {
        app(TaskDefaultsService::class)->seedAll($organization);

        $statusId = TaskStatus::query()
            ->where('organization_id', $organization->id)
            ->where('is_default', true)
            ->value('id');
        $priorityId = TaskPriority::query()
            ->where('organization_id', $organization->id)
            ->where('is_default', true)
            ->value('id');

        return Task::query()->create(array_merge([
            'organization_id' => $organization->id,
            'title' => 'Sample Task',
            'slug' => 'sample-task',
            'task_number' => 'TASK-0001',
            'status' => 'pending',
            'priority' => 'medium',
            'status_id' => $statusId,
            'priority_id' => $priorityId,
            'created_by' => $creator->id,
            'is_archived' => false,
            'completion_percentage' => 0,
        ], $overrides));
    }

    public function test_next_task_number_starts_at_one_and_increments(): void
    {
        $organization = Organization::factory()->create();
        $creator = User::factory()->create();
        $organization->addMember($creator, 'organization-owner');
        $service = app(TaskService::class);

        $this->assertSame('TASK-0001', $service->nextTaskNumber($organization));

        $this->makeTask($organization, $creator, [
            'task_number' => 'TASK-0001',
            'slug' => 'first',
            'title' => 'First',
        ]);

        $this->assertSame('TASK-0002', $service->nextTaskNumber($organization));
    }

    public function test_generate_slug_is_unique_within_organization(): void
    {
        $organization = Organization::factory()->create();
        $creator = User::factory()->create();
        $organization->addMember($creator, 'organization-owner');
        $service = app(TaskService::class);

        $this->makeTask($organization, $creator, [
            'task_number' => 'TASK-0099',
            'title' => 'Alpha',
            'slug' => 'alpha',
        ]);

        $this->assertSame('alpha-1', $service->generateSlug('Alpha', $organization->id));
        $this->assertSame('beta', $service->generateSlug('Beta', $organization->id));
    }

    public function test_generate_slug_ignores_current_task_when_updating(): void
    {
        $organization = Organization::factory()->create();
        $creator = User::factory()->create();
        $organization->addMember($creator, 'organization-owner');
        $service = app(TaskService::class);

        $task = $this->makeTask($organization, $creator, [
            'task_number' => 'TASK-0100',
            'title' => 'Alpha',
            'slug' => 'alpha',
        ]);

        $this->assertSame('alpha', $service->generateSlug('Alpha', $organization->id, $task->id));
    }

    public function test_circular_dependency_is_blocked(): void
    {
        $organization = Organization::factory()->create();
        $actor = User::factory()->create();
        $organization->addMember($actor, 'organization-owner');

        $predecessor = $this->makeTask($organization, $actor, [
            'task_number' => 'TASK-0001',
            'slug' => 'task-a',
            'title' => 'Task A',
        ]);
        $successor = $this->makeTask($organization, $actor, [
            'task_number' => 'TASK-0002',
            'slug' => 'task-b',
            'title' => 'Task B',
        ]);

        $dependencies = app(TaskDependencyService::class);
        $dependencies->create($predecessor, $successor, ['dependency_type' => 'finish_to_start'], $actor);

        $this->expectException(ValidationException::class);
        $dependencies->create($successor, $predecessor, ['dependency_type' => 'finish_to_start'], $actor);
    }

    public function test_progress_calculation_from_checklists(): void
    {
        $organization = Organization::factory()->create();
        $actor = User::factory()->create();
        $organization->addMember($actor, 'organization-owner');

        $task = $this->makeTask($organization, $actor, [
            'task_number' => 'TASK-0200',
            'slug' => 'progress-task',
            'title' => 'Progress Task',
        ]);

        $checklists = app(ChecklistService::class);
        $first = $checklists->create($task, ['title' => 'Item one'], $actor);
        $checklists->create($task, ['title' => 'Item two'], $actor);

        $this->assertSame(0, app(TaskService::class)->calculateProgress($task->fresh()));

        $checklists->complete($first, $actor, true);

        $this->assertSame(50, app(TaskService::class)->calculateProgress($task->fresh()));
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'completion_percentage' => 50,
        ]);
    }
}
