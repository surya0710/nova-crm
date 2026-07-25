<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\Dashboard\Widgets\ActiveProjectsWidgetProvider;
use App\Services\Dashboard\Widgets\MentionsWidgetProvider;
use App\Services\Dashboard\Widgets\MyProjectsWidgetProvider;
use App\Services\Dashboard\Widgets\ProjectDeadlinesWidgetProvider;
use App\Services\Dashboard\Widgets\ProjectMilestonesWidgetProvider;
use App\Services\Dashboard\Widgets\RecentCollaborationWidgetProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['plan' => 'professional']);
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_projects_dashboard_route_is_accessible(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('projects.dashboard'));

        $response->assertOk();
        $response->assertSee('Projects Dashboard');
    }

    public function test_project_widget_providers_authorize_and_return_arrays(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $providers = [
            app(MyProjectsWidgetProvider::class),
            app(ActiveProjectsWidgetProvider::class),
            app(ProjectDeadlinesWidgetProvider::class),
            app(ProjectMilestonesWidgetProvider::class),
        ];

        foreach ($providers as $provider) {
            $this->assertTrue($provider->authorize($user, $organization));

            $data = $provider->load($user, $organization);

            $this->assertIsArray($data);
        }
    }

    public function test_project_widget_providers_deny_unauthorized_users(): void
    {
        $organization = Organization::factory()->create(['plan' => 'professional']);
        $hrUser = User::factory()->create();
        $organization->addMember($hrUser, 'hr');

        $provider = app(MyProjectsWidgetProvider::class);

        $this->assertFalse($provider->authorize($hrUser, $organization));
    }

    public function test_collaboration_widget_providers_return_arrays(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        foreach ([
            app(RecentCollaborationWidgetProvider::class),
            app(MentionsWidgetProvider::class),
        ] as $provider) {
            $this->assertTrue($provider->authorize($user, $organization));
            $this->assertIsArray($provider->load($user, $organization));
        }
    }
}
