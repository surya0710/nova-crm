<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Services\ProjectService;
use App\Services\ResourceAllocationService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ResourceNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function createProject(Organization $organization, User $owner, User $actor): Project
    {
        app(TenantContext::class)->set($organization);

        return app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Notify Allocation Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_notifies_assignee_user_when_allocation_created(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');
        $assignee = User::factory()->create();
        $organization->addMember($assignee, 'employee');

        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $assignee->id,
        ]);
        $project = $this->createProject($organization, $owner, $owner);

        Notification::fake();

        app(TenantContext::class)->set($organization);
        app(ResourceAllocationService::class)->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'allocation_type' => 'project',
            'allocation_percentage' => 40,
            'planned_start_date' => '2026-07-20',
            'planned_end_date' => '2026-07-31',
        ], $owner);

        Notification::assertSentTo($assignee, CrmNotification::class);
    }
}
