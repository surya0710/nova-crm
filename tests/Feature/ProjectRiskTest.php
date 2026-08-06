<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectRisk;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectRiskTest extends TestCase
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
            'name' => 'Risk Feature Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_create_risk_on_project(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->from(route('projects.risks.index', $project))
            ->post(route('projects.risks.store', $project), [
                'title' => 'Supply chain risk',
                'project_id' => $project->id,
                'probability' => 4,
                'impact' => 3,
                'category' => 'external',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_risks', [
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Supply chain risk',
            'severity' => 12,
        ]);
    }

    public function test_update_and_delete_risk(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user, $user);

        $risk = ProjectRisk::factory()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'owner_id' => $user->id,
            'title' => 'Editable risk',
            'probability' => 2,
            'impact' => 2,
            'severity' => 4,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->from(route('projects.risks.index', $project))
            ->patch(route('risks.update', $risk), [
                'title' => 'Editable risk updated',
                'probability' => 5,
                'impact' => 5,
                'status' => 'mitigating',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_risks', [
            'id' => $risk->id,
            'title' => 'Editable risk updated',
            'severity' => 25,
            'status' => 'mitigating',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('risks.destroy', $risk))
            ->assertRedirect();

        $this->assertDatabaseMissing('project_risks', ['id' => $risk->id]);
    }

    public function test_org_risk_index(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('risks.index'))
            ->assertOk();
    }
}
