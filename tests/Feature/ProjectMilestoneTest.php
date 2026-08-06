<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMilestoneTest extends TestCase
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
            'name' => 'Milestone Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_user_can_create_milestone(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);
        $dueDate = now()->addWeek()->format('Y-m-d');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.milestones.store', $project), [
                'name' => 'Phase 1 Complete',
                'due_date' => $dueDate,
                'status' => 'pending',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('project_milestones', [
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'name' => 'Phase 1 Complete',
            'status' => 'pending',
            'sequence' => 1,
        ]);
    }

    public function test_user_can_complete_milestone(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.milestones.store', $project), [
                'name' => 'Go Live',
                'status' => 'in_progress',
            ]);

        $milestone = ProjectMilestone::query()->where('project_id', $project->id)->firstOrFail();

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('projects.milestones.complete', [$project, $milestone]));

        $response->assertRedirect();

        $milestone->refresh();
        $this->assertSame('completed', $milestone->status);
        $this->assertNotNull($milestone->completed_at);
    }

    public function test_milestone_sequence_auto_increments(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.milestones.store', $project), [
                'name' => 'First',
            ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.milestones.store', $project), [
                'name' => 'Second',
            ]);

        $this->assertDatabaseHas('project_milestones', [
            'project_id' => $project->id,
            'name' => 'First',
            'sequence' => 1,
        ]);

        $this->assertDatabaseHas('project_milestones', [
            'project_id' => $project->id,
            'name' => 'Second',
            'sequence' => 2,
        ]);
    }

    public function test_milestone_due_date_is_persisted(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);
        $dueDate = now()->addDays(10)->format('Y-m-d');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.milestones.store', $project), [
                'name' => 'Deadline Milestone',
                'due_date' => $dueDate,
            ]);

        $milestone = ProjectMilestone::query()->where('project_id', $project->id)->firstOrFail();

        $this->assertSame($dueDate, $milestone->due_date->format('Y-m-d'));
    }
}
