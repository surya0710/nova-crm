<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\SprintService;
use App\Services\TaskBoardService;
use App\Services\TaskDefaultsService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskBoardSprintTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);
        app(TenantContext::class)->set($organization);
        app(TaskDefaultsService::class)->seedAll($organization);

        return [$user, $organization];
    }

    protected function createProject(Organization $organization, User $owner): Project
    {
        return app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Board Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $owner);
    }

    public function test_board_renders_enterprise_columns(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user);
        $todo = TaskStatus::query()->where('organization_id', $organization->id)->where('slug', 'to-do')->first();

        Task::factory()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Board card task',
            'status_id' => $todo?->id,
            'assigned_to' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('tasks.board'))
            ->assertOk()
            ->assertSee('Board card task')
            ->assertSee('Todo')
            ->assertSee('In Progress');
    }

    public function test_board_move_updates_status_via_json(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user);
        $todo = TaskStatus::query()->where('organization_id', $organization->id)->where('slug', 'to-do')->firstOrFail();
        $progress = TaskStatus::query()->where('organization_id', $organization->id)->where('slug', 'in-progress')->firstOrFail();

        $task = Task::factory()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Move me',
            'status_id' => $todo->id,
            'assigned_to' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->postJson(route('tasks.board.move', $task), [
                'status_id' => $progress->id,
                'sort_order' => 20,
            ])
            ->assertOk()
            ->assertJsonPath('data.task.status_id', $progress->id);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status_id' => $progress->id,
            'sort_order' => 20,
        ]);
    }

    public function test_sprint_create_and_assign_task(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user);

        $sprint = app(SprintService::class)->create($organization, [
            'name' => 'Sprint 1',
            'goal' => 'Ship board',
            'project_id' => $project->id,
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
        ], $user);

        $task = Task::factory()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Sprint task',
            'assigned_to' => $user->id,
        ]);

        app(SprintService::class)->assignTask($task, $sprint, $user);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'sprint_id' => $sprint->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('sprints.index'))
            ->assertOk()
            ->assertSee('Sprint 1');
    }

    public function test_backlog_page_renders(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('tasks.backlog', ['sprint_id' => 'none']))
            ->assertOk()
            ->assertSee('Backlog');
    }

    public function test_board_preferences_save_view(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->postJson(route('tasks.board.preferences'), [
                'swimlane' => 'assignee',
                'save_view' => [
                    'name' => 'My Tasks',
                    'filters' => ['assigned_to' => $user->id],
                    'swimlane' => 'assignee',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.swimlane', 'assignee');

        $prefs = app(TaskBoardService::class)->preferences($organization, $user);
        $this->assertSame('assignee', $prefs['swimlane']);
        $this->assertNotEmpty($prefs['saved_views']);
    }
}
