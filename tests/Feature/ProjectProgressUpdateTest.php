<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\ProgressUpdate;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectProgressUpdateTest extends TestCase
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
            'name' => 'Progress CRUD Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_web_store_creates_progress_update(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.progress.store', $project), [
                'progress_percentage' => 55,
                'summary' => 'Web progress posted',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('progress_updates', [
            'project_id' => $project->id,
            'progress_percentage' => 55,
            'summary' => 'Web progress posted',
        ]);
    }

    public function test_api_store_and_update_progress(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $project = $this->createProject($organization, $user, $user);

        Sanctum::actingAs($user, ['*']);

        $create = $this->postJson(
            '/api/v1/projects/'.$project->id.'/progress',
            [
                'progress_percentage' => 30,
                'summary' => 'API progress',
            ],
            $this->apiHeaders($organization),
        );

        $create->assertCreated();
        $updateId = $create->json('data.id');

        $this->patchJson(
            '/api/v1/projects/'.$project->id.'/progress/'.$updateId,
            [
                'progress_percentage' => 35,
                'summary' => 'API progress revised',
            ],
            $this->apiHeaders($organization),
        )->assertOk();

        $this->assertDatabaseHas('progress_updates', [
            'id' => $updateId,
            'progress_percentage' => 35,
            'summary' => 'API progress revised',
        ]);
    }

    public function test_api_destroy_deletes_progress_update(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $project = $this->createProject($organization, $user, $user);

        $update = ProgressUpdate::query()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'updated_by' => $user->id,
            'progress_percentage' => 10,
            'summary' => 'To delete',
        ]);

        Sanctum::actingAs($user, ['*']);

        $this->deleteJson(
            '/api/v1/projects/'.$project->id.'/progress/'.$update->id,
            [],
            $this->apiHeaders($organization),
        )->assertOk();

        $this->assertDatabaseMissing('progress_updates', ['id' => $update->id]);
    }
}
