<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function createProject(Organization $organization, User $owner, User $actor, array $overrides = []): Project
    {
        app(TenantContext::class)->set($organization);

        return app(ProjectService::class)->create(array_merge([
            'organization_id' => $organization->id,
            'name' => 'Test Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $overrides), $actor);
    }

    public function test_user_with_projects_view_can_access_index(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('projects.index'));

        $response->assertOk();
        $response->assertSee('Projects');
    }

    public function test_user_without_permission_cannot_access(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('projects.index'));

        $response->assertForbidden();
    }

    public function test_user_can_create_project(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.store'), [
                'name' => 'Alpha',
                'owner_id' => $user->id,
                'manager_id' => $user->id,
                'priority' => 'medium',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'organization_id' => $organization->id,
            'name' => 'Alpha',
            'owner_id' => $user->id,
            'manager_id' => $user->id,
            'priority' => 'medium',
        ]);

        $this->assertGreaterThan(0, ProjectStatus::query()->where('organization_id', $organization->id)->count());
    }

    public function test_project_number_and_slug_generated(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.store'), [
                'name' => 'Alpha Launch',
                'owner_id' => $user->id,
                'manager_id' => $user->id,
                'priority' => 'medium',
            ]);

        $project = Project::query()->where('organization_id', $organization->id)->first();

        $this->assertNotNull($project);
        $this->assertMatchesRegularExpression('/^PRJ-\d{4}$/', $project->project_number);
        $this->assertSame('alpha-launch', $project->slug);
    }

    public function test_archived_project_is_read_only(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user, ['name' => 'Read Only']);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.archive', $project));

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->put(route('projects.update', $project), [
                'name' => 'Changed Name',
                'owner_id' => $user->id,
                'manager_id' => $user->id,
                'priority' => 'medium',
            ]);

        $response->assertSessionHasErrors('project');
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Read Only',
        ]);
    }

    public function test_completed_project_cannot_be_deleted(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user, ['name' => 'Completed Work']);

        $completedStatus = ProjectStatus::query()
            ->where('organization_id', $organization->id)
            ->where('slug', 'completed')
            ->firstOrFail();

        $project->update(['status_id' => $completedStatus->id]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('projects.destroy', $project));

        $response->assertSessionHasErrors('project');
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_user_can_archive_and_restore(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.archive', $project))
            ->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'is_archived' => true,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.restore', $project))
            ->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'is_archived' => false,
        ]);
    }

    public function test_slug_unique_within_organization(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $this->createProject($organization, $user, $user, ['name' => 'Alpha']);
        $second = $this->createProject($organization, $user, $user, ['name' => 'Alpha']);

        $this->assertSame('alpha', Project::query()->where('name', 'Alpha')->orderBy('id')->value('slug'));
        $this->assertSame('alpha-1', $second->slug);
    }

    public function test_organization_scoping_hides_other_org_projects(): void
    {
        [$userA, $organizationA] = $this->setupUserWithOrg('organization-owner');
        [$userB, $organizationB] = $this->setupUserWithOrg('organization-owner');

        $foreignProject = $this->createProject($organizationA, $userA, $userA, ['name' => 'Org A Secret']);

        $response = $this->actingAs($userB)
            ->withSession(['current_organization_id' => $organizationB->id])
            ->get(route('projects.show', $foreignProject));

        $response->assertForbidden();

        $index = $this->actingAs($userB)
            ->withSession(['current_organization_id' => $organizationB->id])
            ->get(route('projects.index'));

        $index->assertOk();
        $index->assertDontSee('Org A Secret');
    }
}
