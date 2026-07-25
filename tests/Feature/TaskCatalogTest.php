<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\TaskPriority;
use App\Models\TaskStatus;
use App\Models\User;
use App\Services\TaskDefaultsService;
use App\Services\TaskPriorityService;
use App\Services\TaskStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaskCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_organization_owner_can_access_status_and_priority_indexes(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        app(TaskDefaultsService::class)->seedAll($organization);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('task-statuses.index'))
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('task-priorities.index'))
            ->assertOk();
    }

    public function test_organization_owner_can_create_status_and_priority(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        app(TaskDefaultsService::class)->seedAll($organization);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('task-statuses.store'), [
                'name' => 'Waiting',
                'color' => '#abcdef',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('task_statuses', [
            'organization_id' => $organization->id,
            'name' => 'Waiting',
            'slug' => 'waiting',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('task-priorities.store'), [
                'name' => 'Urgent',
                'level' => 5,
                'color' => '#ff0000',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('task_priorities', [
            'organization_id' => $organization->id,
            'name' => 'Urgent',
            'slug' => 'urgent',
            'level' => 5,
        ]);
    }

    public function test_default_status_cannot_be_deleted(): void
    {
        $organization = Organization::factory()->create();
        app(TaskDefaultsService::class)->seedAll($organization);

        $default = TaskStatus::query()
            ->where('organization_id', $organization->id)
            ->where('is_default', true)
            ->firstOrFail();

        $this->expectException(ValidationException::class);
        app(TaskStatusService::class)->delete($default);
    }

    public function test_non_default_status_and_priority_can_be_deleted(): void
    {
        $organization = Organization::factory()->create();
        app(TaskDefaultsService::class)->seedAll($organization);

        $status = app(TaskStatusService::class)->create($organization, [
            'name' => 'Temporary Status',
            'is_closed' => false,
        ]);

        app(TaskStatusService::class)->delete($status);
        $this->assertDatabaseMissing('task_statuses', ['id' => $status->id]);

        $priority = app(TaskPriorityService::class)->create($organization, [
            'name' => 'Temporary Priority',
            'level' => 9,
        ]);

        app(TaskPriorityService::class)->delete($priority);
        $this->assertDatabaseMissing('task_priorities', ['id' => $priority->id]);
    }

    public function test_organization_owner_can_update_catalog_entries(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        app(TaskDefaultsService::class)->seedAll($organization);

        $status = TaskStatus::query()
            ->where('organization_id', $organization->id)
            ->where('slug', 'review')
            ->firstOrFail();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->put(route('task-statuses.update', $status), [
                'name' => 'In Review',
                'color' => '#123456',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('task_statuses', [
            'id' => $status->id,
            'name' => 'In Review',
        ]);

        $priority = TaskPriority::query()
            ->where('organization_id', $organization->id)
            ->where('slug', 'high')
            ->firstOrFail();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->put(route('task-priorities.update', $priority), [
                'name' => 'High Priority',
                'level' => 3,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('task_priorities', [
            'id' => $priority->id,
            'name' => 'High Priority',
        ]);
    }
}
