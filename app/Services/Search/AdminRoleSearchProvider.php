<?php

namespace App\Services\Search;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class AdminRoleSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'roles';
    }

    public function label(): string
    {
        return __('Roles');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasPermission('rbac.view')) {
            return collect();
        }

        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        $indexUrl = Route::has('rbac.roles.index') ? route('rbac.roles.index') : null;
        if (! $indexUrl) {
            return collect();
        }

        return Role::query()
            ->where('organization_id', $organization->id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('slug', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get()
            ->map(function (Role $role) use ($indexUrl) {
                $url = $indexUrl;
                if (Route::has('rbac.roles.show')) {
                    $url = route('rbac.roles.show', $role);
                } elseif (Route::has('rbac.roles.edit')) {
                    $url = route('rbac.roles.edit', $role);
                }

                return [
                    'type' => __('Role'),
                    'label' => $this->label(),
                    'title' => $role->name,
                    'subtitle' => $role->slug,
                    'url' => $url,
                    'workspace' => 'administration',
                ];
            });
    }
}
