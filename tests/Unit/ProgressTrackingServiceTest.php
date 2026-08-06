<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\ProgressUpdate;
use App\Models\Project;
use App\Models\User;
use App\Services\ProgressTrackingService;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProgressTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function createProject(Organization $organization, User $owner, User $actor, array $overrides = []): Project
    {
        app(TenantContext::class)->set($organization);

        return app(ProjectService::class)->create(array_merge([
            'organization_id' => $organization->id,
            'name' => 'Progress Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $overrides), $actor);
    }

    public function test_create_progress_update(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $project = $this->createProject($organization, $user, $user);
        $service = app(ProgressTrackingService::class);

        $update = $service->create($project, [
            'progress_percentage' => 45,
            'summary' => 'Sprint complete',
            'blockers' => 'Waiting on client',
        ], $user);

        $this->assertDatabaseHas('progress_updates', [
            'id' => $update->id,
            'project_id' => $project->id,
            'progress_percentage' => 45,
            'summary' => 'Sprint complete',
        ]);

        $this->assertSame(45, $project->fresh()->completion_percentage);
    }

    public function test_create_does_not_overwrite_history(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $project = $this->createProject($organization, $user, $user);
        $service = app(ProgressTrackingService::class);

        $first = $service->create($project, [
            'progress_percentage' => 20,
            'summary' => 'First update',
        ], $user);

        $second = $service->create($project, [
            'progress_percentage' => 40,
            'summary' => 'Second update',
        ], $user);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, ProgressUpdate::query()->where('project_id', $project->id)->count());
        $this->assertDatabaseHas('progress_updates', [
            'id' => $first->id,
            'summary' => 'First update',
            'progress_percentage' => 20,
        ]);
        $this->assertSame(40, $project->fresh()->completion_percentage);
    }

    public function test_validate_percentage_rejects_out_of_bounds(): void
    {
        $service = app(ProgressTrackingService::class);

        $this->expectException(ValidationException::class);
        $service->validatePercentage(101);
    }

    public function test_create_requires_summary(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $project = $this->createProject($organization, $user, $user);
        $service = app(ProgressTrackingService::class);

        $this->expectException(ValidationException::class);

        $service->create($project, [
            'progress_percentage' => 10,
            'summary' => '   ',
        ], $user);
    }
}
