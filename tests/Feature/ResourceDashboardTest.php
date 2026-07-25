<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\Dashboard\Widgets\OverallocatedEmployeesWidgetProvider;
use App\Services\Dashboard\Widgets\ResourceAvailabilityWidgetProvider;
use App\Services\Dashboard\Widgets\TeamWorkloadWidgetProvider;
use App\Services\Dashboard\Widgets\UpcomingCapacityRisksWidgetProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['plan' => 'professional']);
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_resource_widget_providers_return_arrays(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $providers = [
            app(TeamWorkloadWidgetProvider::class),
            app(ResourceAvailabilityWidgetProvider::class),
            app(OverallocatedEmployeesWidgetProvider::class),
            app(UpcomingCapacityRisksWidgetProvider::class),
        ];

        foreach ($providers as $provider) {
            $this->assertTrue($provider->authorize($user, $organization));

            $data = $provider->load($user, $organization);

            $this->assertIsArray($data);
        }
    }
}
