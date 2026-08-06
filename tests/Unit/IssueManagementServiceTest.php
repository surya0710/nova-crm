<?php

namespace Tests\Unit;

use App\Events\ProjectIssueCreated;
use App\Events\ProjectIssueResolved;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectIssue;
use App\Models\User;
use App\Services\IssueManagementService;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class IssueManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setupOrg(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');
        app(TenantContext::class)->set($organization);

        return [$user, $organization];
    }

    protected function createProject(Organization $organization, User $actor): Project
    {
        return app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Issue Project',
            'owner_id' => $actor->id,
            'manager_id' => $actor->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_create_issue(): void
    {
        Event::fake([ProjectIssueCreated::class]);

        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);

        $issue = app(IssueManagementService::class)->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Blocked environment',
            'priority' => 'high',
            'severity' => 'medium',
        ], $user);

        $this->assertInstanceOf(ProjectIssue::class, $issue);
        $this->assertSame('open', $issue->status);
        Event::assertDispatched(ProjectIssueCreated::class);
    }

    public function test_resolve_sets_resolved_at(): void
    {
        Event::fake([ProjectIssueResolved::class]);

        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);
        $service = app(IssueManagementService::class);

        $issue = $service->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Resolvable issue',
        ], $user);

        Event::fake([ProjectIssueResolved::class]);

        $resolved = $service->resolve($issue, $user, 'Fixed in prod');
        $this->assertSame('resolved', $resolved->status);
        $this->assertNotNull($resolved->resolved_at);
        $this->assertSame('Fixed in prod', $resolved->resolution);
        Event::assertDispatched(ProjectIssueResolved::class);
    }

    public function test_update_to_resolved_fires_event(): void
    {
        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);
        $service = app(IssueManagementService::class);

        $issue = $service->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Update resolve',
        ], $user);

        Event::fake([ProjectIssueResolved::class]);

        $updated = $service->update($issue, [
            'status' => 'resolved',
            'resolution' => 'Closed via update',
        ], $user);

        $this->assertSame('resolved', $updated->status);
        $this->assertNotNull($updated->resolved_at);
        Event::assertDispatched(ProjectIssueResolved::class);
    }

    public function test_delete_issue(): void
    {
        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);
        $service = app(IssueManagementService::class);

        $issue = $service->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Delete me',
        ], $user);

        $service->delete($issue, $user);
        $this->assertDatabaseMissing('project_issues', ['id' => $issue->id]);
    }
}
