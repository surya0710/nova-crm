<?php

namespace App\Services\Import;

use App\Models\Organization;
use App\Models\User;
use App\Services\Dashboard\ModuleSubscriptionService;

/**
 * Builds the Import Center catalog with licensing + RBAC filtering.
 */
class ImportCatalogService
{
    public function __construct(
        protected ImportEntityRegistry $registry,
        protected ModuleSubscriptionService $modules,
    ) {}

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function groupedFor(User $user, Organization $organization): array
    {
        $groups = [];

        foreach (config('import.entities', []) as $type => $meta) {
            if (! $this->registry->has($type)) {
                continue;
            }

            if (! $this->userCanAccess($user, $organization, $meta)) {
                continue;
            }

            $module = $meta['module'] ?? 'other';
            $entity = $this->registry->resolve($type);

            $groups[$module][] = [
                'type' => $type,
                'label' => $meta['label'] ?? $entity->entityLabel(),
                'field_count' => count($entity->fieldDefinitions()),
                'permission' => $meta['permission'] ?? 'imports.view',
            ];
        }

        $ordered = [];
        foreach (array_keys(config('import.module_labels', [])) as $module) {
            if (! empty($groups[$module])) {
                $ordered[$module] = $groups[$module];
            }
        }

        foreach ($groups as $module => $items) {
            if (! isset($ordered[$module])) {
                $ordered[$module] = $items;
            }
        }

        return $ordered;
    }

    public function userCanAccessEntity(User $user, Organization $organization, string $entityType): bool
    {
        $meta = config('import.entities.'.$entityType);
        if (! is_array($meta) || ! $this->registry->has($entityType)) {
            return false;
        }

        return $this->userCanAccess($user, $organization, $meta);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function userCanAccess(User $user, Organization $organization, array $meta): bool
    {
        if ($user->is_super_admin || $user->isOwnerOf($organization)) {
            // Still respect module license for non-null license modules.
        } elseif (! $user->hasPermission('imports.view', $organization)
            && ! $user->hasPermission('imports.manage', $organization)
            && ! $user->hasPermission('imports.create', $organization)) {
            return false;
        }

        $permission = $meta['permission'] ?? null;
        if ($permission
            && ! $user->is_super_admin
            && ! $user->isOwnerOf($organization)
            && ! $user->hasPermission($permission, $organization)
            && ! $user->hasPermission('imports.manage', $organization)) {
            return false;
        }

        $license = $meta['license_module'] ?? null;
        if ($license && ! $this->modules->moduleAllowed($organization, $license)) {
            return false;
        }

        return true;
    }
}
