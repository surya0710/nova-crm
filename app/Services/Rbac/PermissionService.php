<?php

namespace App\Services\Rbac;

use App\Events\Rbac\PermissionCreated;
use App\Events\Rbac\PermissionUpdated;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PermissionService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function list(Organization $organization, array $filters = []): Collection
    {
        $query = Permission::query()
            ->forOrganization($organization)
            ->with('group')
            ->orderBy('module')
            ->orderBy('name');

        if (isset($filters['active'])) {
            $query->where('is_active', (bool) $filters['active']);
        }

        if (! empty($filters['group_id'])) {
            $query->where('permission_group_id', $filters['group_id']);
        }

        if (! empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    public function groupedByModule(Organization $organization): Collection
    {
        return $this->list($organization)->groupBy('module');
    }

    public function create(Organization $organization, array $data, ?User $actor = null): Permission
    {
        $slug = $data['slug'] ?? Str::slug($data['name'], '.');
        $this->validateUniqueSlug($organization, $slug);

        $permission = Permission::query()->create([
            'permission_group_id' => $data['permission_group_id'] ?? null,
            'organization_id' => $organization->id,
            'slug' => $slug,
            'name' => $data['name'],
            'module' => $data['module'] ?? explode('.', $slug)[0],
            'description' => $data['description'] ?? null,
            'is_system' => false,
            'is_active' => $data['is_active'] ?? true,
        ]);

        event(new PermissionCreated($permission, $actor));
        $this->auditLogger->log($permission, 'rbac.permission.created', ['slug' => $permission->slug], $actor);

        return $permission;
    }

    public function update(Permission $permission, array $data, ?User $actor = null): Permission
    {
        if ($permission->is_system && isset($data['slug']) && $data['slug'] !== $permission->slug) {
            throw ValidationException::withMessages([
                'slug' => __('System permissions cannot be renamed.'),
            ]);
        }

        $changes = [];
        foreach (['name', 'slug', 'description', 'module', 'permission_group_id', 'is_active'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== $permission->{$field}) {
                $changes[$field] = ['from' => $permission->{$field}, 'to' => $data[$field]];
            }
        }

        $permission->update([
            'name' => $data['name'] ?? $permission->name,
            'slug' => $data['slug'] ?? $permission->slug,
            'description' => $data['description'] ?? $permission->description,
            'module' => $data['module'] ?? $permission->module,
            'permission_group_id' => $data['permission_group_id'] ?? $permission->permission_group_id,
            'is_active' => $data['is_active'] ?? $permission->is_active,
        ]);

        if ($changes !== []) {
            event(new PermissionUpdated($permission, $changes, $actor));
            $this->auditLogger->log($permission, 'rbac.permission.updated', $changes, $actor);
        }

        return $permission->fresh();
    }

    public function activate(Permission $permission, ?User $actor = null): Permission
    {
        return $this->update($permission, ['is_active' => true], $actor);
    }

    public function deactivate(Permission $permission, ?User $actor = null): Permission
    {
        if ($permission->is_system) {
            throw ValidationException::withMessages([
                'permission' => __('System permissions cannot be deactivated.'),
            ]);
        }

        return $this->update($permission, ['is_active' => false], $actor);
    }

    public function findBySlug(Organization $organization, string $slug): ?Permission
    {
        return Permission::query()
            ->forOrganization($organization)
            ->where('slug', $slug)
            ->first();
    }

    public function cloneForOrganization(Organization $organization): void
    {
        $systemPermissions = Permission::query()
            ->whereNull('organization_id')
            ->where('is_system', true)
            ->get();

        foreach ($systemPermissions as $systemPermission) {
            Permission::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'slug' => $systemPermission->slug,
                ],
                [
                    'permission_group_id' => $systemPermission->permission_group_id,
                    'name' => $systemPermission->name,
                    'module' => $systemPermission->module,
                    'description' => $systemPermission->description,
                    'is_system' => true,
                    'is_active' => $systemPermission->is_active,
                ]
            );
        }
    }

    protected function validateUniqueSlug(Organization $organization, string $slug, ?int $exceptId = null): void
    {
        $query = Permission::query()
            ->forOrganization($organization)
            ->where('slug', $slug);

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'slug' => __('A permission with this slug already exists.'),
            ]);
        }
    }
}
