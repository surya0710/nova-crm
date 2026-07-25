<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectMilestoneProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function apiHeaders(Organization $organization): array
    {
        return [
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ];
    }

    protected function createProject(Organization $organization, User $owner, User $actor): Project
    {
        app(TenantContext::class)->set($organization);

        return app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Milestone Progress Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
            'start_date' => now()->subMonth()->toDateString(),
        ], $actor);
    }

    public function test_api_milestones_progress_returns_metrics(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $project = $this->createProject($organization, $user, $user);

        ProjectMilestone::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'name' => 'Alpha',
            'status' => 'pending',
            'due_date' => now()->addWeek()->toDateString(),
            'sequence' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson(
            '/api/v1/projects/'.$project->id.'/milestones/progress',
            $this->apiHeaders($organization),
        );

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                [
                    'milestone_id',
                    'name',
                    'planned_progress',
                    'actual_progress',
                    'delay_days',
                    'remaining_tasks',
                    'is_delayed',
                ],
            ],
        ]);
    }
}
