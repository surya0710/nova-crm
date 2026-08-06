<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Services\Dashboard\DashboardCache;
use App\Services\Dashboard\ModuleSubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardPlatformUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_subscription_allows_common_module(): void
    {
        $organization = Organization::factory()->create(['plan' => 'starter']);
        $service = app(ModuleSubscriptionService::class);

        $this->assertTrue($service->moduleAllowed($organization, 'common'));
        $this->assertTrue($service->moduleAllowed($organization, null));
    }

    public function test_dashboard_cache_bump_invalidates_version(): void
    {
        Cache::flush();
        $cache = app(DashboardCache::class);
        $organization = Organization::factory()->create();

        $first = $cache->remember('test', $organization->id, 1, fn () => 'value-a');
        $cache->bump($organization->id);
        $second = $cache->remember('test', $organization->id, 1, fn () => 'value-b');

        $this->assertSame('value-a', $first);
        $this->assertSame('value-b', $second);
    }

    public function test_enterprise_plan_allows_all_modules(): void
    {
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $service = app(ModuleSubscriptionService::class);

        $this->assertTrue($service->moduleAllowed($organization, 'hrms'));
        $this->assertTrue($service->moduleAllowed($organization, 'recruitment'));
    }
}
