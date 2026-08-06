<?php

namespace App\Services\Rbac;

use App\Models\Organization;
use App\Models\PermissionGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PermissionGroupService
{
    public function list(Organization $organization, array $filters = []): Collection
    {
        $query = PermissionGroup::query()
            ->forOrganization($organization)
            ->orderBy('sort_order')
            ->orderBy('name');

        if (isset($filters['active'])) {
            $query->where('is_active', (bool) $filters['active']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    public function create(Organization $organization, array $data): PermissionGroup
    {
        $slug = $data['slug'] ?? Str::slug($data['name']);
        $this->validateUniqueSlug($organization, $slug);

        return PermissionGroup::query()->create([
            'organization_id' => $organization->id,
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_system' => false,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(PermissionGroup $group, array $data): PermissionGroup
    {
        if ($group->is_system && isset($data['slug']) && $data['slug'] !== $group->slug) {
            throw ValidationException::withMessages([
                'slug' => __('System permission groups cannot be renamed.'),
            ]);
        }

        if (isset($data['slug']) && $data['slug'] !== $group->slug) {
            $this->validateUniqueSlug($group->organization ?? $group->organization_id, $data['slug'], $group->id);
        }

        $group->update([
            'name' => $data['name'] ?? $group->name,
            'slug' => $data['slug'] ?? $group->slug,
            'description' => $data['description'] ?? $group->description,
            'sort_order' => $data['sort_order'] ?? $group->sort_order,
            'is_active' => $data['is_active'] ?? $group->is_active,
        ]);

        return $group->fresh();
    }

    public function activate(PermissionGroup $group): PermissionGroup
    {
        $group->update(['is_active' => true]);

        return $group->fresh();
    }

    public function deactivate(PermissionGroup $group): PermissionGroup
    {
        if ($group->is_system) {
            throw ValidationException::withMessages([
                'group' => __('System permission groups cannot be deactivated.'),
            ]);
        }

        $group->update(['is_active' => false]);

        return $group->fresh();
    }

    public function archive(PermissionGroup $group): PermissionGroup
    {
        return $this->deactivate($group);
    }

    protected function validateUniqueSlug(Organization|int|null $organization, string $slug, ?int $exceptId = null): void
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        $query = PermissionGroup::query()->where('slug', $slug);

        if ($organizationId) {
            $query->where(function ($q) use ($organizationId) {
                $q->whereNull('organization_id')
                    ->orWhere('organization_id', $organizationId);
            });
        } else {
            $query->whereNull('organization_id');
        }

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'slug' => __('A permission group with this slug already exists.'),
            ]);
        }
    }
}
