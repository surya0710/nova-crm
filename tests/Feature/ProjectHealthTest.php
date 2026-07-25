<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectHealthService;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectHealthTest extends TestCase
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
            'name' => 'Health Feature Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_web_health_page_returns_ok(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('projects.health.show', $project))
            ->assertOk();
    }

    public function test_api_health_endpoint_returns_snapshot(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $project = $this->createProject($organization, $user, $user);

        app(TenantContext::class)->set($organization);
        app(ProjectHealthService::class)->calculate($project, $user);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson(
            '/api/v1/projects/'.$project->id.'/health',
            $this->apiHeaders($organization),
        );

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'health_status',
                'completion_percentage',
                'calculated_at',
            ],
        ]);
    }
}
