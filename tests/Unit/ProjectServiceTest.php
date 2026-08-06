<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProject(Organization $organization, array $overrides = []): Project
    {
        $user = User::factory()->create();

        return Project::query()->create(array_merge([
            'organization_id' => $organization->id,
            'project_number' => 'PRJ-0001',
            'name' => 'First',
            'slug' => 'first',
            'owner_id' => $user->id,
            'manager_id' => $user->id,
            'priority' => 'medium',
            'is_archived' => false,
        ], $overrides));
    }

    public function test_next_project_number_starts_at_one_and_increments(): void
    {
        $organization = Organization::factory()->create();
        $service = app(ProjectService::class);

        $this->assertSame('PRJ-0001', $service->nextProjectNumber($organization));

        $this->makeProject($organization, [
            'project_number' => 'PRJ-0001',
            'name' => 'First',
            'slug' => 'first',
        ]);

        $this->assertSame('PRJ-0002', $service->nextProjectNumber($organization));
    }

    public function test_generate_slug_is_unique_within_organization(): void
    {
        $organization = Organization::factory()->create();
        $service = app(ProjectService::class);

        $this->makeProject($organization, [
            'project_number' => 'PRJ-0099',
            'name' => 'Alpha',
            'slug' => 'alpha',
        ]);

        $this->assertSame('alpha-1', $service->generateSlug('Alpha', $organization->id));
        $this->assertSame('beta', $service->generateSlug('Beta', $organization->id));
    }

    public function test_generate_slug_ignores_current_project_when_updating(): void
    {
        $organization = Organization::factory()->create();
        $service = app(ProjectService::class);

        $project = $this->makeProject($organization, [
            'project_number' => 'PRJ-0100',
            'name' => 'Alpha',
            'slug' => 'alpha',
        ]);

        $this->assertSame('alpha', $service->generateSlug('Alpha', $organization->id, $project->id));
    }
}
