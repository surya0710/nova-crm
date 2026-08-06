<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectWatchersTest extends TestCase
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
            'name' => 'Watcher Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_watch_and_unwatch_project(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->from(route('projects.show', $project))
            ->post(route('projects.watch.store', $project))
            ->assertRedirect();

        $this->assertDatabaseHas('project_watchers', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->from(route('projects.show', $project))
            ->delete(route('projects.watch.destroy', $project))
            ->assertRedirect();

        $this->assertDatabaseMissing('project_watchers', [
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
    }
}
