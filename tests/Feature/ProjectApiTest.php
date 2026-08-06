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

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setupApiUser(string $role = 'organization-owner'): array
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

    protected function createProject(Organization $organization, User $owner, User $actor, array $overrides = []): Project
    {
        app(TenantContext::class)->set($organization);

        return app(ProjectService::class)->create(array_merge([
            'organization_id' => $organization->id,
            'name' => 'API Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $overrides), $actor);
    }

    public function test_api_index_returns_projects(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');
        $this->createProject($organization, $user, $user, ['name' => 'Visible API Project']);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/projects', $this->apiHeaders($organization));

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Visible API Project']);
    }

    public function test_api_store_creates_project(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/projects', [
            'name' => 'Created Via API',
            'owner_id' => $user->id,
            'manager_id' => $user->id,
            'priority' => 'high',
        ], $this->apiHeaders($organization));

        $response->assertCreated();
        $response->assertJsonFragment(['name' => 'Created Via API']);

        $this->assertDatabaseHas('projects', [
            'organization_id' => $organization->id,
            'name' => 'Created Via API',
            'priority' => 'high',
        ]);
    }

    public function test_api_show_returns_project(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');
        $project = $this->createProject($organization, $user, $user, ['name' => 'Show Me']);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/projects/'.$project->id, $this->apiHeaders($organization));

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $project->id,
            'name' => 'Show Me',
        ]);
    }

    public function test_api_without_permission_returns_forbidden(): void
    {
        [$user, $organization] = $this->setupApiUser('hr');

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/projects', $this->apiHeaders($organization))
            ->assertForbidden();
    }

    public function test_api_tenant_isolation_hides_foreign_projects(): void
    {
        [$userA, $organizationA] = $this->setupApiUser('manager');
        [$userB, $organizationB] = $this->setupApiUser('manager');

        $foreignProject = $this->createProject($organizationA, $userA, $userA, ['name' => 'Foreign API Project']);

        Sanctum::actingAs($userB, ['*']);

        $this->getJson('/api/v1/projects/'.$foreignProject->id, $this->apiHeaders($organizationB))
            ->assertForbidden();
    }
}
