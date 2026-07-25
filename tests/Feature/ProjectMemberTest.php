<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMemberTest extends TestCase
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
            'name' => 'Member Test Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $overrides), $actor);
    }

    public function test_user_can_add_project_member(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');
        $member = User::factory()->create();
        $organization->addMember($member, 'employee');

        $project = $this->createProject($organization, $owner, $owner);

        $response = $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.members.store', $project), [
                'user_id' => $member->id,
                'project_role' => 'team_member',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('project_members', [
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'user_id' => $member->id,
            'project_role' => 'team_member',
            'is_active' => true,
        ]);
    }

    public function test_duplicate_active_member_is_prevented(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');
        $member = User::factory()->create();
        $organization->addMember($member, 'employee');

        $project = $this->createProject($organization, $owner, $owner);

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.members.store', $project), [
                'user_id' => $member->id,
                'project_role' => 'team_member',
            ]);

        $response = $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.members.store', $project), [
                'user_id' => $member->id,
                'project_role' => 'viewer',
            ]);

        $response->assertSessionHasErrors('user_id');
        $this->assertSame(1, ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $member->id)
            ->where('is_active', true)
            ->count());
    }

    public function test_user_can_remove_project_member(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');
        $member = User::factory()->create();
        $organization->addMember($member, 'employee');

        $project = $this->createProject($organization, $owner, $owner);

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.members.store', $project), [
                'user_id' => $member->id,
                'project_role' => 'team_member',
            ]);

        $projectMember = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $member->id)
            ->firstOrFail();

        $response = $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('projects.members.destroy', [$project, $projectMember]));

        $response->assertRedirect();

        $this->assertDatabaseHas('project_members', [
            'id' => $projectMember->id,
            'is_active' => false,
        ]);
    }

    public function test_user_can_change_project_member_role(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');
        $member = User::factory()->create();
        $organization->addMember($member, 'employee');

        $project = $this->createProject($organization, $owner, $owner);

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.members.store', $project), [
                'user_id' => $member->id,
                'project_role' => 'team_member',
            ]);

        $projectMember = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $member->id)
            ->firstOrFail();

        $response = $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('projects.members.update', [$project, $projectMember]), [
                'project_role' => 'delivery_lead',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('project_members', [
            'id' => $projectMember->id,
            'project_role' => 'delivery_lead',
            'is_active' => true,
        ]);
    }

    public function test_member_must_be_organization_member(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');
        $outsider = User::factory()->create();

        $project = $this->createProject($organization, $owner, $owner);

        $response = $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.members.store', $project), [
                'user_id' => $outsider->id,
                'project_role' => 'team_member',
            ]);

        $response->assertSessionHasErrors('user_id');
        $this->assertDatabaseMissing('project_members', [
            'project_id' => $project->id,
            'user_id' => $outsider->id,
        ]);
    }
}
