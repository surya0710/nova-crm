<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectIssue;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectIssueTest extends TestCase
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
            'name' => 'Issue Feature Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_create_issue_on_project(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->from(route('projects.issues.index', $project))
            ->post(route('projects.issues.store', $project), [
                'title' => 'Staging outage',
                'project_id' => $project->id,
                'priority' => 'high',
                'severity' => 'critical',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_issues', [
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Staging outage',
            'status' => 'open',
        ]);
    }

    public function test_resolve_issue_via_update(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user, $user);

        $issue = ProjectIssue::factory()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'owner_id' => $user->id,
            'title' => 'Resolvable feature issue',
            'status' => 'open',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->from(route('projects.issues.index', $project))
            ->patch(route('issues.update', $issue), [
                'title' => 'Resolvable feature issue',
                'status' => 'resolved',
                'resolution' => 'Restarted worker',
                'priority' => 'medium',
                'severity' => 'medium',
            ])
            ->assertRedirect();

        $fresh = $issue->fresh();
        $this->assertSame('resolved', $fresh->status);
        $this->assertNotNull($fresh->resolved_at);
        $this->assertSame('Restarted worker', $fresh->resolution);
    }

    public function test_delete_issue(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user, $user);

        $issue = ProjectIssue::factory()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'owner_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('issues.destroy', $issue))
            ->assertRedirect();

        $this->assertDatabaseMissing('project_issues', ['id' => $issue->id]);
    }
}
