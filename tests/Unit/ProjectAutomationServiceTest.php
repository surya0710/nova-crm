<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Services\ProjectAutomationService;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProjectAutomationServiceTest extends TestCase
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

    public function test_notify_manager_on_milestone_complete(): void
    {
        [$owner, $organization] = $this->setupOrg();
        $manager = User::factory()->create();
        $organization->addMember($manager, 'manager');

        $project = app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Automation Project',
            'owner_id' => $owner->id,
            'manager_id' => $manager->id,
            'priority' => 'medium',
        ], $owner);

        $milestone = ProjectMilestone::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'name' => 'Phase 1',
            'sequence' => 1,
            'status' => 'completed',
            'due_date' => now()->toDateString(),
        ]);

        Notification::fake();

        app(ProjectAutomationService::class)->notifyManagerOnMilestoneComplete($milestone, $owner);

        Notification::assertSentTo($manager, CrmNotification::class);
    }
}
