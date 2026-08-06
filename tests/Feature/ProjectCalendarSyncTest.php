<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCalendarSyncTest extends TestCase
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
            'name' => 'Calendar Sync Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
            'planned_end_date' => now()->addWeek()->toDateString(),
        ], $actor);
    }

    public function test_sync_endpoint_creates_calendar_links(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->from(route('projects.show', $project))
            ->post(route('projects.calendar.sync', $project), [
                'provider' => 'internal',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_calendar_links', [
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'provider' => 'internal',
            'event_type' => 'project_deadline',
        ]);
    }
}
