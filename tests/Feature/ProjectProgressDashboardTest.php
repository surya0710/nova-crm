<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\Dashboard\Widgets\ProjectHealthWidgetProvider;
use App\Services\Dashboard\Widgets\ProjectsAtRiskWidgetProvider;
use App\Services\Dashboard\Widgets\RecentlyUpdatedProjectsWidgetProvider;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectProgressDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['plan' => 'professional']);
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function createProject(Organization $organization, User $owner, User $actor): Project
    {
        app(TenantContext::class)->set($organization);

        return app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Dashboard Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_progress_dashboard_route_is_accessible(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('projects.progress.dashboard', $project))
            ->assertOk();
    }

    public function test_executive_dashboard_route_is_accessible(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('projects.executive'))
            ->assertOk();
    }

    public function test_progress_widget_providers_authorize_and_return_arrays(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $providers = [
            app(ProjectHealthWidgetProvider::class),
            app(ProjectsAtRiskWidgetProvider::class),
            app(RecentlyUpdatedProjectsWidgetProvider::class),
        ];

        foreach ($providers as $provider) {
            $this->assertTrue($provider->authorize($user, $organization));
            $this->assertIsArray($provider->load($user, $organization));
        }
    }

    public function test_progress_widgets_are_registered_in_dashboard_config(): void
    {
        $widgets = config('dashboard.widgets', []);

        $this->assertArrayHasKey('project_health', $widgets);
        $this->assertArrayHasKey('projects_at_risk', $widgets);
        $this->assertArrayHasKey('recently_updated_projects', $widgets);
    }
}
