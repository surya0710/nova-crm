<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectNotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_update_notification_preferences(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->put(route('notification-preferences.update'), [
                'in_app_enabled' => '1',
                'email_enabled' => '0',
                'digest_enabled' => '1',
                'digest_frequency' => 'weekly',
            ])
            ->assertRedirect(route('notification-preferences.edit'));

        $this->assertDatabaseHas('notification_preferences', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'in_app_enabled' => true,
            'email_enabled' => false,
            'digest_enabled' => true,
            'digest_frequency' => 'weekly',
        ]);
    }
}
