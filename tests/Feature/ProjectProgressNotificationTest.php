<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Services\ProgressTrackingService;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProjectProgressNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function createProject(Organization $organization, User $owner, User $manager, User $actor): Project
    {
        app(TenantContext::class)->set($organization);

        return app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Notify Progress Project',
            'owner_id' => $owner->id,
            'manager_id' => $manager->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_progress_update_notifies_manager(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');
        $manager = User::factory()->create();
        $organization->addMember($manager, 'manager');

        $actor = User::factory()->create();
        $organization->addMember($actor, 'manager');

        $project = $this->createProject($organization, $owner, $manager, $actor);

        Notification::fake();

        app(TenantContext::class)->set($organization);
        app(ProgressTrackingService::class)->create($project, [
            'progress_percentage' => 25,
            'summary' => 'Weekly update',
        ], $actor);

        Notification::assertSentTo($manager, CrmNotification::class);
        Notification::assertSentTo($owner, CrmNotification::class);
        Notification::assertNotSentTo($actor, CrmNotification::class);
    }
}
