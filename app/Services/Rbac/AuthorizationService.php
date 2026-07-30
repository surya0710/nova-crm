<?php

namespace App\Services\Rbac;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Services\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AuthorizationService
{
    protected int $cacheTtl = 300;

    public function can(User $user, string $permission, Organization|int|null $organization = null): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        $organization = $this->resolveOrganization($user, $organization);

        if (! $organization || ! $user->belongsToActiveOrganization($organization)) {
            return false;
        }

        $primaryRole = $user->getRoleInOrganization($organization);
        if ($primaryRole && $this->isOwnerRole($primaryRole)) {
            return true;
        }

        $permissions = $this->cachedPermissions($user, $organization);

        return $permissions->contains($permission);
    }

    public function canAny(User $user, array $permissions, Organization|int|null $organization = null): bool
    {
        foreach ($permissions as $permission) {
            if ($this->can($user, $permission, $organization)) {
                return true;
            }
        }

        return false;
    }

    public function canAll(User $user, array $permissions, Organization|int|null $organization = null): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->can($user, $permission, $organization)) {
                return false;
            }
        }

        return true;
    }

    public function effectivePermissions(User $user, Organization|int $organization): Collection
    {
        return $this->cachedPermissions($user, $organization);
    }

    public function forgetUserCache(User $user, Organization|int $organization): void
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;
        Cache::forget($this->cacheKey($user->id, $organizationId));
    }

    public function forgetOrganizationCache(Organization|int $organization): void
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        foreach (UserRole::query()->where('organization_id', $organizationId)->pluck('user_id')->unique() as $userId) {
            Cache::forget($this->cacheKey($userId, $organizationId));
        }
    }

    protected function cachedPermissions(User $user, Organization $organization): Collection
    {
        return Cache::remember(
            $this->cacheKey($user->id, $organization->id),
            $this->cacheTtl,
            fn () => $this->resolvePermissions($user, $organization)
        );
    }

    protected function resolvePermissions(User $user, Organization $organization): Collection
    {
        $roleIds = collect();

        $primaryRole = $user->getRoleInOrganization($organization);
        if ($primaryRole) {
            if ($this->isOwnerRole($primaryRole)) {
                return Permission::query()
                    ->forOrganization($organization)
                    ->where('is_active', true)
                    ->pluck('slug');
            }
            $roleIds->push($primaryRole->id);
        }

        $additionalRoleIds = UserRole::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->pluck('role_id');

        $roleIds = $roleIds->merge($additionalRoleIds)->unique();

        if ($roleIds->isEmpty()) {
            return collect();
        }

        $roles = Role::query()
            ->where('organization_id', $organization->id)
            ->whereIn('id', $roleIds)
            ->where('is_active', true)
            ->with(['permissions' => fn ($q) => $q->where('is_active', true)])
            ->get();

        return $roles
            ->flatMap(fn (Role $role) => $role->permissions->pluck('slug'))
            ->unique()
            ->values();
    }

    protected function isOwnerRole(Role $role): bool
    {
        return in_array($role->slug, [
            'organization-owner',
            'organization-administrator',
            'platform-administrator',
        ], true);
    }

    protected function resolveOrganization(User $user, Organization|int|null $organization): ?Organization
    {
        if ($organization instanceof Organization) {
            return $organization;
        }

        if (is_int($organization)) {
            return Organization::query()->find($organization);
        }

        return app(TenantContext::class)->get() ?? $user->organizations()->first();
    }

    protected function cacheKey(int $userId, int $organizationId): string
    {
        return "rbac:permissions:{$organizationId}:{$userId}";
    }
}
