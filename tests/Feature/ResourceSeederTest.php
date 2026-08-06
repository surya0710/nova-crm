<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Permission;
use Database\Seeders\ResourcePlanningSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_planning_seeder_is_idempotent(): void
    {
        Organization::factory()->count(2)->create();

        $this->seed(ResourcePlanningSeeder::class);

        $firstPermissionCount = Permission::query()
            ->where(fn ($q) => $q->where('module', 'resources')->orWhere('slug', 'like', 'resources.%'))
            ->count();

        $this->assertGreaterThan(0, $firstPermissionCount);

        $this->seed(ResourcePlanningSeeder::class);

        $this->assertSame(
            $firstPermissionCount,
            Permission::query()
                ->where(fn ($q) => $q->where('module', 'resources')->orWhere('slug', 'like', 'resources.%'))
                ->count()
        );
    }
}
