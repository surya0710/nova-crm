<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectDependency;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectDependencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function createProject(Organization $organization, User $owner, User $actor, string $name = 'Dep Project'): Project
    {
        app(TenantContext::class)->set($organization);

        return app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => $name,
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_create_dependency_between_projects(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $predecessor = $this->createProject($organization, $user, $user, 'Predecessor');
        $successor = $this->createProject($organization, $user, $user, 'Successor');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('project-dependencies.store'), [
                'predecessor_project_id' => $predecessor->id,
                'successor_project_id' => $successor->id,
                'dependency_type' => 'finish_to_start',
                'lag_days' => 2,
            ])
            ->assertRedirect(route('project-dependencies.index'));

        $this->assertDatabaseHas('project_dependencies', [
            'organization_id' => $organization->id,
            'predecessor_project_id' => $predecessor->id,
            'successor_project_id' => $successor->id,
            'dependency_type' => 'finish_to_start',
            'lag_days' => 2,
        ]);
    }

    public function test_delete_dependency(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $predecessor = $this->createProject($organization, $user, $user, 'Pred Delete');
        $successor = $this->createProject($organization, $user, $user, 'Succ Delete');

        $dependency = ProjectDependency::factory()->create([
            'organization_id' => $organization->id,
            'predecessor_project_id' => $predecessor->id,
            'successor_project_id' => $successor->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('project-dependencies.destroy', $dependency))
            ->assertRedirect(route('project-dependencies.index'));

        $this->assertDatabaseMissing('project_dependencies', ['id' => $dependency->id]);
    }

    public function test_index_shows_graph_page(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('project-dependencies.index'))
            ->assertOk();
    }
}
