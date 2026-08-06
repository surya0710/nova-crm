<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectCollaborationPin;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCollaborationCenterTest extends TestCase
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
            'name' => 'Collaboration Center Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_collaboration_page_loads(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('projects.collaboration.show', $project))
            ->assertOk()
            ->assertSee('Collaboration');
    }

    public function test_pin_from_collaboration_center(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.collaboration.pins.store', $project), [
                'source_type' => 'comment',
                'source_id' => 11,
                'title' => 'Pinned comment',
            ])
            ->assertRedirect(route('projects.collaboration.show', $project));

        $this->assertDatabaseHas('project_collaboration_pins', [
            'project_id' => $project->id,
            'source_type' => 'comment',
            'source_id' => 11,
            'title' => 'Pinned comment',
        ]);

        $this->assertInstanceOf(
            ProjectCollaborationPin::class,
            ProjectCollaborationPin::query()->where('project_id', $project->id)->first(),
        );
    }
}
