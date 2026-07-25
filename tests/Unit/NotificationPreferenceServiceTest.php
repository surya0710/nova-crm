<?php

namespace Tests\Unit;

use App\Models\NotificationPreference;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\NotificationPreferenceService;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferenceServiceTest extends TestCase
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

    public function test_get_or_create_returns_defaults(): void
    {
        [$user, $organization] = $this->setupOrg();

        $preference = app(NotificationPreferenceService::class)->getOrCreate($user, $organization->id);

        $this->assertInstanceOf(NotificationPreference::class, $preference);
        $this->assertTrue($preference->in_app_enabled);
        $this->assertTrue($preference->email_enabled);
        $this->assertSame($organization->id, $preference->organization_id);
        $this->assertSame($user->id, $preference->user_id);
    }

    public function test_mute_project_via_update_and_is_muted(): void
    {
        [$user, $organization] = $this->setupOrg();
        $service = app(NotificationPreferenceService::class);

        $project = app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Muted Project',
            'owner_id' => $user->id,
            'manager_id' => $user->id,
            'priority' => 'medium',
        ], $user);

        $service->update($user, [
            'muted_projects' => [$project->id],
        ], $organization->id);

        $this->assertTrue($service->isMuted($user, $project));
    }

    public function test_should_notify_respects_event_preferences(): void
    {
        [$user, $organization] = $this->setupOrg();
        $service = app(NotificationPreferenceService::class);

        $service->update($user, [
            'in_app_enabled' => true,
            'email_enabled' => true,
            'event_preferences' => [
                'project.delayed' => false,
            ],
        ], $organization->id);

        $this->assertFalse($service->shouldNotify($user, 'project.delayed', $organization->id));
        $this->assertTrue($service->shouldNotify($user, 'task.overdue', $organization->id));
    }
}
