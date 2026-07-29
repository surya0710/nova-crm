<?php

namespace Tests\Feature;

use App\Models\Holiday;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPlanningCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'manager'): array
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
            'name' => 'Planning Calendar Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_project_calendar_renders_live_events(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);
        $project = $this->createProject($organization, $user, $user);

        Task::factory()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Due this month',
            'assigned_to' => $user->id,
            'due_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        ProjectMilestone::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'name' => 'Kickoff',
            'status' => 'pending',
            'due_date' => now()->addDays(2)->toDateString(),
            'sequence' => 1,
        ]);

        Holiday::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Org Day',
            'holiday_date' => now()->addDays(3)->toDateString(),
            'is_optional' => false,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('projects.calendar', ['view' => 'month']))
            ->assertOk()
            ->assertSee('Due this month')
            ->assertSee('Kickoff')
            ->assertSee('Org Day');
    }
}
