<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectLabel;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TaskDefaultsService;
use App\Services\TaskService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectLabelsTest extends TestCase
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
            'name' => 'Labels Project',
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
            'title' => 'Labeled Task',
        ], $actor);
    }

    public function test_web_crud_for_project_labels(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('project-labels.store'), [
                'name' => 'Hotfix',
                'color' => '#dc2626',
                'description' => 'Priority work',
            ])
            ->assertRedirect(route('project-labels.index'));

        $label = ProjectLabel::query()->where('organization_id', $organization->id)->where('name', 'Hotfix')->firstOrFail();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('project-labels.index'))
            ->assertOk()
            ->assertSee('Hotfix');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->put(route('project-labels.update', $label), [
                'name' => 'Hotfix Updated',
                'color' => '#ea580c',
            ])
            ->assertRedirect(route('project-labels.index'));

        $this->assertDatabaseHas('project_labels', [
            'id' => $label->id,
            'name' => 'Hotfix Updated',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('project-labels.destroy', $label))
            ->assertRedirect(route('project-labels.index'));

        $this->assertDatabaseMissing('project_labels', ['id' => $label->id]);
    }

    public function test_attach_label_to_task(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user, $user);
        $task = $this->createTask($organization, $project, $user);

        $label = ProjectLabel::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'QA',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->from(route('tasks.show', $task))
            ->post(route('tasks.labels.attach', [$task, $label]))
            ->assertRedirect();

        $this->assertDatabaseHas('task_labels', [
            'task_id' => $task->id,
            'label_id' => $label->id,
        ]);
    }
}
