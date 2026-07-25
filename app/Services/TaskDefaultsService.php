<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\TaskPriority;
use App\Models\TaskStatus;

class TaskDefaultsService
{
    public function seedAll(Organization $organization): void
    {
        $this->seedStatuses($organization);
        $this->seedPriorities($organization);
    }

    public function seedStatuses(Organization $organization): void
    {
        foreach (config('tasks.default_statuses', []) as $definition) {
            TaskStatus::query()->updateOrCreate(
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

    public function seedPriorities(Organization $organization): void
    {
        foreach (config('tasks.default_priorities', []) as $definition) {
            TaskPriority::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'slug' => $definition['slug'],
                ],
                [
                    'name' => $definition['name'],
                    'color' => $definition['color'] ?? null,
                    'level' => $definition['level'] ?? 1,
                    'is_default' => (bool) ($definition['is_default'] ?? false),
                ],
            );
        }

        $this->ensureSingleDefaultPriority($organization);
    }

    protected function ensureSingleDefaultStatus(Organization $organization): void
    {
        $defaults = TaskStatus::query()
            ->where('organization_id', $organization->id)
            ->where('is_default', true)
            ->orderBy('id')
            ->get();

        if ($defaults->count() <= 1) {
            return;
        }

        $defaults->slice(1)->each(function (TaskStatus $status): void {
            $status->update(['is_default' => false]);
        });
    }

    protected function ensureSingleDefaultPriority(Organization $organization): void
    {
        $defaults = TaskPriority::query()
            ->where('organization_id', $organization->id)
            ->where('is_default', true)
            ->orderBy('id')
            ->get();

        if ($defaults->count() <= 1) {
            return;
        }

        $defaults->slice(1)->each(function (TaskPriority $priority): void {
            $priority->update(['is_default' => false]);
        });
    }
}
