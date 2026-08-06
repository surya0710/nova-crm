<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\TaskPriority;
use App\Models\TaskStatus;
use App\Services\TaskDefaultsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_defaults_service_seeds_six_statuses_and_four_priorities(): void
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

        $this->assertSame(6, TaskStatus::query()->where('organization_id', $organization->id)->count());
        $this->assertSame(4, TaskPriority::query()->where('organization_id', $organization->id)->count());

        $this->assertDatabaseHas('task_statuses', [
            'organization_id' => $organization->id,
            'slug' => 'to-do',
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('task_priorities', [
            'organization_id' => $organization->id,
            'slug' => 'medium',
            'is_default' => true,
        ]);
    }
}
