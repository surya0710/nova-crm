<?php

namespace App\Services\Rbac;

use App\Events\Rbac\RoleCreated;
use App\Events\Rbac\RoleDeleted;
use App\Events\Rbac\RoleUpdated;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RoleService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected RolePermissionService $rolePermissionService,
    ) {}

    public function list(Organization $organization, array $filters = []): Collection
    {
        $query = Role::query()
            ->where('organization_id', $organization->id)
            ->orderByDesc('hierarchy_level')
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

    public function create(Organization $organization, array $data, ?User $actor = null): Role
    {
        $slug = $data['slug'] ?? Str::slug($data['name']);
        $this->validateUniqueSlug($organization, $slug);
        $this->validateHierarchy($organization, $data['hierarchy_level'] ?? 0);

        $role = Role::query()->create([
            'organization_id' => $organization->id,
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? '#6366f1',
            'hierarchy_level' => $data['hierarchy_level'] ?? 0,
            'is_system' => $data['is_system'] ?? false,
            'is_default' => $data['is_default'] ?? false,
            'is_active' => $data['is_active'] ?? true,
        ]);

        if ($role->is_default) {
            $this->clearDefaultRole($organization, $role->id);
        }

        if (! empty($data['permission_ids'])) {
            $this->rolePermissionService->sync($organization, $role, $data['permission_ids'], $actor);
        }

        event(new RoleCreated($role, $actor));
        $this->auditLogger->log($role, 'rbac.role.created', ['slug' => $role->slug], $actor);

        return $role->fresh(['permissions']);
    }

    public function update(Role $role, array $data, ?User $actor = null): Role
    {
        if ($role->is_system && isset($data['slug']) && $data['slug'] !== $role->slug) {
            throw ValidationException::withMessages([
                'slug' => __('System roles cannot be renamed.'),
            ]);
        }

        if (isset($data['hierarchy_level'])) {
            $this->validateHierarchy($role->organization, $data['hierarchy_level'], $role->id);
        }

        $changes = [];
        foreach (['name', 'slug', 'description', 'color', 'hierarchy_level', 'is_default', 'is_active'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== $role->{$field}) {
                $changes[$field] = ['from' => $role->{$field}, 'to' => $data[$field]];
            }
        }

        $role->update([
            'name' => $data['name'] ?? $role->name,
            'slug' => $data['slug'] ?? $role->slug,
            'description' => $data['description'] ?? $role->description,
            'color' => $data['color'] ?? $role->color,
            'hierarchy_level' => $data['hierarchy_level'] ?? $role->hierarchy_level,
            'is_default' => $data['is_default'] ?? $role->is_default,
            'is_active' => $data['is_active'] ?? $role->is_active,
        ]);

        if ($role->is_default) {
            $this->clearDefaultRole($role->organization, $role->id);
        }

        if ($changes !== []) {
            event(new RoleUpdated($role, $changes, $actor));
            $this->auditLogger->log($role, 'rbac.role.updated', $changes, $actor);
        }

        return $role->fresh(['permissions']);
    }

    public function duplicate(Role $role, ?User $actor = null): Role
    {
        $copy = $this->create($role->organization, [
            'name' => $role->name.' (Copy)',
            'slug' => $role->slug.'-copy-'.Str::random(4),
            'description' => $role->description,
            'color' => $role->color,
            'hierarchy_level' => $role->hierarchy_level,
            'is_system' => false,
            'is_default' => false,
            'permission_ids' => $role->permissions()->pluck('permissions.id')->all(),
        ], $actor);

        return $copy;
    }

    public function delete(Role $role, ?User $actor = null): void
    {
        if ($role->is_system) {
            throw ValidationException::withMessages([
                'role' => __('System roles cannot be deleted.'),
            ]);
        }

        event(new RoleDeleted($role, $actor));
        $this->auditLogger->log($role, 'rbac.role.deleted', ['slug' => $role->slug], $actor);

        $role->delete();
    }

    public function activate(Role $role, ?User $actor = null): Role
    {
        return $this->update($role, ['is_active' => true], $actor);
    }

    public function deactivate(Role $role, ?User $actor = null): Role
    {
        if ($role->is_system) {
            throw ValidationException::withMessages([
                'role' => __('System roles cannot be deactivated.'),
            ]);
        }

        return $this->update($role, ['is_active' => false], $actor);
    }

    public function getDefaultRole(Organization $organization): ?Role
    {
        return Role::query()
            ->where('organization_id', $organization->id)
            ->where('is_default', true)
            ->first();
    }

    protected function clearDefaultRole(Organization $organization, int $exceptRoleId): void
    {
        Role::query()
            ->where('organization_id', $organization->id)
            ->where('id', '!=', $exceptRoleId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    protected function validateUniqueSlug(Organization $organization, string $slug, ?int $exceptId = null): void
    {
        $query = Role::query()
            ->where('organization_id', $organization->id)
            ->where('slug', $slug);

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'slug' => __('A role with this slug already exists in this organization.'),
            ]);
        }
    }

    protected function validateHierarchy(Organization $organization, int $level, ?int $exceptId = null): void
    {
        if ($level < 0 || $level > 100) {
            throw ValidationException::withMessages([
                'hierarchy_level' => __('Hierarchy level must be between 0 and 100.'),
            ]);
        }
    }
}
