<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectCalendarLink;
use App\Models\ProjectMilestone;
use App\Models\User;
use App\Services\CalendarSyncService;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarSyncServiceTest extends TestCase
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

    public function test_sync_project_creates_deadline_and_milestone_links(): void
    {
        [$user, $organization] = $this->setupOrg();

        $project = app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Calendar Project',
            'owner_id' => $user->id,
            'manager_id' => $user->id,
            'priority' => 'medium',
            'planned_end_date' => now()->addDays(10)->toDateString(),
        ], $user);

        $milestone = ProjectMilestone::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'name' => 'Launch',
            'sequence' => 1,
            'status' => 'pending',
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        $links = app(CalendarSyncService::class)->syncProject($project->fresh());

        $this->assertNotEmpty($links);
        $this->assertTrue($links->contains(fn (ProjectCalendarLink $link) => $link->event_type === 'project_deadline'));
        $this->assertTrue($links->contains(fn (ProjectCalendarLink $link) => $link->event_type === 'milestone_due'
            && (int) $link->milestone_id === (int) $milestone->id));

        $this->assertDatabaseHas('project_calendar_links', [
            'project_id' => $project->id,
            'event_type' => 'project_deadline',
            'provider' => 'internal',
        ]);
    }
}
