<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\ProjectCategory;
use App\Models\ProjectLifecycleStage;
use App\Models\ProjectStatus;
use App\Models\ProjectType;
use Illuminate\Support\Facades\Schema;

class ProjectDefaultsService
{
    public function seedAll(Organization $organization): void
    {
        if (! Schema::hasTable('project_categories')) {
            return;
        }

        $this->seedCategories($organization);
        $this->seedTypes($organization);
        $this->seedStatuses($organization);
        $this->seedLifecycleStages($organization);
    }

    public function seedCategories(Organization $organization): void
    {
        foreach (config('projects.default_categories', []) as $definition) {
            ProjectCategory::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'slug' => $definition['slug'],
                ],
                [
                    'name' => $definition['name'],
                    'color' => $definition['color'] ?? null,
                    'icon' => $definition['icon'] ?? null,
                    'sort_order' => $definition['sort_order'] ?? 0,
                    'is_system' => true,
                    'is_active' => true,
                ],
            );
        }
    }

    public function seedTypes(Organization $organization): void
    {
        foreach (config('projects.default_types', []) as $definition) {
            ProjectType::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'slug' => $definition['slug'],
                ],
                [
                    'name' => $definition['name'],
                    'default_duration' => $definition['default_duration'] ?? null,
                    'color' => $definition['color'] ?? null,
                    'sort_order' => $definition['sort_order'] ?? 0,
                    'is_system' => true,
                    'is_active' => true,
                ],
            );
        }
    }

    public function seedStatuses(Organization $organization): void
    {
        foreach (config('projects.default_statuses', []) as $definition) {
            ProjectStatus::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'slug' => $definition['slug'],
                ],
                [
                    'name' => $definition['name'],
                    'color' => $definition['color'] ?? null,
                    'is_default' => (bool) ($definition['is_default'] ?? false),
                    'is_closed' => (bool) ($definition['is_closed'] ?? false),
                    'sort_order' => $definition['sort_order'] ?? 0,
                ],
            );
        }

        $this->ensureSingleDefaultStatus($organization);
    }

    public function seedLifecycleStages(Organization $organization): void
    {
        foreach (config('projects.default_lifecycle_stages', []) as $definition) {
            ProjectLifecycleStage::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'slug' => $definition['slug'],
                ],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'] ?? null,
                    'sequence' => $definition['sequence'] ?? 0,
                    'color' => $definition['color'] ?? null,
                    'is_default' => (bool) ($definition['is_default'] ?? false),
                ],
            );
        }

        $this->ensureSingleDefaultLifecycleStage($organization);
    }

    protected function ensureSingleDefaultStatus(Organization $organization): void
    {
        $defaults = ProjectStatus::query()
            ->where('organization_id', $organization->id)
            ->where('is_default', true)
            ->orderBy('id')
            ->get();

        if ($defaults->count() <= 1) {
            return;
        }

        $defaults->slice(1)->each(function (ProjectStatus $status): void {
            $status->update(['is_default' => false]);
        });
    }

    protected function ensureSingleDefaultLifecycleStage(Organization $organization): void
    {
        $defaults = ProjectLifecycleStage::query()
            ->where('organization_id', $organization->id)
            ->where('is_default', true)
            ->orderBy('id')
            ->get();

        if ($defaults->count() <= 1) {
            return;
        }

        $defaults->slice(1)->each(function (ProjectLifecycleStage $stage): void {
            $stage->update(['is_default' => false]);
        });
    }
}
