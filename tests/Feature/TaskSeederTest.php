<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\TaskPriority;
use App\Models\TaskStatus;
use App\Services\TaskDefaultsService;
use Database\Seeders\TaskFoundationSeeder;
use Database\Seeders\TaskPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_defaults_service_seeds_catalog_for_organization(): void
    {
        $organization = Organization::factory()->create();

        TaskStatus::query()->where('organization_id', $organization->id)->delete();
        TaskPriority::query()->where('organization_id', $organization->id)->delete();

        app(TaskDefaultsService::class)->seedAll($organization);

        $this->assertSame(
            count(config('tasks.default_statuses')),
            TaskStatus::query()->where('organization_id', $organization->id)->count()
        );
        $this->assertSame(
            count(config('tasks.default_priorities')),
            TaskPriority::query()->where('organization_id', $organization->id)->count()
        );
    }

    public function test_task_foundation_seeder_is_idempotent(): void
    {
        Organization::factory()->count(2)->create();

        $this->seed(TaskFoundationSeeder::class);
        $firstStatusCount = TaskStatus::query()->count();
        $firstPriorityCount = TaskPriority::query()->count();
        $firstPermissionCount = Permission::query()
            ->where(fn ($q) => $q->where('module', 'tasks')->orWhere('slug', 'like', 'tasks.%'))
            ->count();

        $this->seed(TaskFoundationSeeder::class);

        $this->assertSame($firstStatusCount, TaskStatus::query()->count());
        $this->assertSame($firstPriorityCount, TaskPriority::query()->count());
        $this->assertSame(
            $firstPermissionCount,
            Permission::query()
                ->where(fn ($q) => $q->where('module', 'tasks')->orWhere('slug', 'like', 'tasks.%'))
                ->count()
        );
        $this->assertGreaterThan(0, $firstStatusCount);
        $this->assertGreaterThan(0, $firstPriorityCount);
    }

    public function test_task_permission_seeder_runs_without_error(): void
    {
        Organization::factory()->create();

        $this->seed(TaskPermissionSeeder::class);

        $this->assertGreaterThan(
            0,
            Permission::query()
                ->where(fn ($q) => $q->where('module', 'tasks')->orWhere('slug', 'like', 'tasks.%'))
                ->count()
        );
    }
}
