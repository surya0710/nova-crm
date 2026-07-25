<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProjectNotificationTest extends TestCase
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
            'name' => 'Notify Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_assigning_project_member_notifies_assignee(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');
        $assignee = User::factory()->create();
        $organization->addMember($assignee, 'employee');

        $project = $this->createProject($organization, $owner, $owner);

        Notification::fake();

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.members.store', $project), [
                'user_id' => $assignee->id,
                'project_role' => 'team_member',
            ]);

        Notification::assertSentTo($assignee, CrmNotification::class);
    }
}
