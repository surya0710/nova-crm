<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectTimelineGanttTest extends TestCase
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
            'name' => 'Timeline Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
            'start_date' => now()->subWeek()->toDateString(),
            'planned_end_date' => now()->addMonth()->toDateString(),
        ], $actor);
    }

    public function test_api_timeline_returns_structure(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $project = $this->createProject($organization, $user, $user);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson(
            '/api/v1/projects/'.$project->id.'/timeline',
            $this->apiHeaders($organization),
        );

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'project',
                'milestones',
                'tasks',
                'dependencies',
                'resource_allocations',
            ],
        ]);
    }

    public function test_api_gantt_returns_bars(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $project = $this->createProject($organization, $user, $user);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson(
            '/api/v1/projects/'.$project->id.'/gantt',
            $this->apiHeaders($organization),
        );

        $response->assertOk();
        $response->assertJsonStructure(['data']);

        $bars = $response->json('data');
        $this->assertNotEmpty($bars);
        $this->assertArrayHasKey('type', $bars[0]);
    }
}
