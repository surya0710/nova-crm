<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\ProjectCategory;
use App\Models\ProjectLifecycleStage;
use App\Models\ProjectStatus;
use App\Models\ProjectType;
use App\Services\ProjectDefaultsService;
use Database\Seeders\ProjectFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_defaults_service_seeds_catalog_for_organization(): void
    {
        $organization = Organization::factory()->create();

        ProjectCategory::query()->where('organization_id', $organization->id)->delete();
        ProjectType::query()->where('organization_id', $organization->id)->delete();
        ProjectStatus::query()->where('organization_id', $organization->id)->delete();
        ProjectLifecycleStage::query()->where('organization_id', $organization->id)->delete();

        app(ProjectDefaultsService::class)->seedAll($organization);

        $this->assertSame(
            count(config('projects.default_categories')),
            ProjectCategory::query()->where('organization_id', $organization->id)->count()
        );
        $this->assertSame(
            count(config('projects.default_types')),
            ProjectType::query()->where('organization_id', $organization->id)->count()
        );
        $this->assertSame(
            count(config('projects.default_statuses')),
            ProjectStatus::query()->where('organization_id', $organization->id)->count()
        );
        $this->assertSame(
            count(config('projects.default_lifecycle_stages')),
            ProjectLifecycleStage::query()->where('organization_id', $organization->id)->count()
        );

        $this->assertDatabaseHas('project_statuses', [
            'organization_id' => $organization->id,
            'slug' => 'draft',
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('project_lifecycle_stages', [
            'organization_id' => $organization->id,
            'slug' => 'planning',
            'is_default' => true,
        ]);
    }

    public function test_project_foundation_seeder_runs_without_error(): void
    {
        Organization::factory()->count(2)->create();

        $this->seed(ProjectFoundationSeeder::class);

        $this->assertGreaterThan(0, ProjectCategory::query()->count());
        $this->assertGreaterThan(0, ProjectType::query()->count());
        $this->assertGreaterThan(0, ProjectStatus::query()->count());
        $this->assertGreaterThan(0, ProjectLifecycleStage::query()->count());
    }
}
