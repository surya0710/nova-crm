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

class ProjectProgressRbacTest extends TestCase
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
            'name' => 'RBAC Progress Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_hr_cannot_view_progress_index(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('manager');
        $hrUser = User::factory()->create();
        $organization->addMember($hrUser, 'hr');

        $project = $this->createProject($organization, $owner, $owner);

        $this->actingAs($hrUser)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('projects.progress.index', $project))
            ->assertForbidden();
    }

    public function test_hr_cannot_create_progress_via_api(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('manager');
        $hrUser = User::factory()->create();
        $organization->addMember($hrUser, 'hr');

        $project = $this->createProject($organization, $owner, $owner);

        Sanctum::actingAs($hrUser, ['*']);

        $this->postJson(
            '/api/v1/projects/'.$project->id.'/progress',
            [
                'progress_percentage' => 10,
                'summary' => 'Blocked',
            ],
            $this->apiHeaders($organization),
        )->assertForbidden();
    }

    public function test_hr_cannot_view_health(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('manager');
        $hrUser = User::factory()->create();
        $organization->addMember($hrUser, 'hr');

        $project = $this->createProject($organization, $owner, $owner);

        Sanctum::actingAs($hrUser, ['*']);

        $this->getJson(
            '/api/v1/projects/'.$project->id.'/health',
            $this->apiHeaders($organization),
        )->assertForbidden();
    }
}
