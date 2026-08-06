<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\Dashboard\Widgets\MentionsWidgetProvider;
use App\Services\Dashboard\Widgets\ProjectCalendarWidgetProvider;
use App\Services\Dashboard\Widgets\RecentCollaborationWidgetProvider;
use App\Services\Dashboard\Widgets\TemplateUsageWidgetProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCollaborationDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['plan' => 'professional']);
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_collaboration_widget_providers_return_data_shape(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $providers = [
            app(RecentCollaborationWidgetProvider::class),
            app(MentionsWidgetProvider::class),
            app(TemplateUsageWidgetProvider::class),
            app(ProjectCalendarWidgetProvider::class),
        ];

        foreach ($providers as $provider) {
            $this->assertTrue($provider->authorize($user, $organization));

            $data = $provider->load($user, $organization);

            $this->assertIsArray($data);
            $this->assertArrayHasKey('count', $data);
        }
    }
}
