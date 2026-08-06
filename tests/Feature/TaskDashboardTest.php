<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\Dashboard\Widgets\MyTasksWidgetProvider;
use App\Services\Dashboard\Widgets\OverdueTasksWidgetProvider;
use App\Services\Dashboard\Widgets\TasksDueTodayWidgetProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['plan' => 'professional']);
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_task_widget_providers_authorize_and_return_arrays(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');

        $providers = [
            app(MyTasksWidgetProvider::class),
            app(TasksDueTodayWidgetProvider::class),
            app(OverdueTasksWidgetProvider::class),
        ];

        foreach ($providers as $provider) {
            $this->assertTrue($provider->authorize($user, $organization));

            $data = $provider->load($user, $organization);

            $this->assertIsArray($data);
        }
    }

    public function test_task_widget_providers_deny_unauthorized_users(): void
    {
        $organization = Organization::factory()->create(['plan' => 'professional']);
        $hrUser = User::factory()->create();
        $organization->addMember($hrUser, 'hr');

        $provider = app(MyTasksWidgetProvider::class);

        $this->assertFalse($provider->authorize($hrUser, $organization));
    }
}
