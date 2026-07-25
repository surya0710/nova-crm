<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\TaskDefaultsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_hr_without_tasks_view_is_forbidden(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('tasks.index'));

        $response->assertForbidden();
    }

    public function test_sales_executive_without_manage_status_cannot_create_status(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        app(TaskDefaultsService::class)->seedAll($organization);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('task-statuses.store'), [
                'name' => 'Blocked Create',
                'color' => '#000000',
            ]);

        $response->assertForbidden();
    }

    public function test_organization_owner_with_manage_status_can_create_status(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        app(TaskDefaultsService::class)->seedAll($organization);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('task-statuses.store'), [
                'name' => 'Owner Status',
                'color' => '#111111',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('task_statuses', [
            'organization_id' => $organization->id,
            'name' => 'Owner Status',
            'slug' => 'owner-status',
        ]);
    }
}
