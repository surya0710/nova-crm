<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\ProjectCategory;
use App\Models\ProjectLifecycleStage;
use App\Models\ProjectStatus;
use App\Models\ProjectType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_organization_gets_project_defaults_on_create(): void
    {
        $organization = Organization::factory()->create();

        $this->assertGreaterThanOrEqual(
            count(config('projects.default_categories')),
            ProjectCategory::query()->where('organization_id', $organization->id)->count()
        );
        $this->assertGreaterThanOrEqual(
            count(config('projects.default_types')),
            ProjectType::query()->where('organization_id', $organization->id)->count()
        );
        $this->assertGreaterThanOrEqual(
            count(config('projects.default_statuses')),
            ProjectStatus::query()->where('organization_id', $organization->id)->count()
        );
        $this->assertGreaterThanOrEqual(
            count(config('projects.default_lifecycle_stages')),
            ProjectLifecycleStage::query()->where('organization_id', $organization->id)->count()
        );

        $this->assertDatabaseHas('project_categories', [
            'organization_id' => $organization->id,
            'slug' => 'software-development',
            'is_system' => true,
        ]);

        $this->assertDatabaseHas('project_types', [
            'organization_id' => $organization->id,
            'slug' => 'fixed-cost',
            'is_system' => true,
        ]);
    }
}
