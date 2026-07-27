<?php

namespace App\Services\Export;

use App\Models\Organization;
use App\Models\User;
use App\Services\Dashboard\ModuleSubscriptionService;

/**
 * Builds the Export Center catalog with licensing + RBAC filtering.
 */
class ExportCatalogService
{
    public function __construct(
        protected ExportDefinitionRegistry $registry,
        protected ModuleSubscriptionService $modules,
    ) {}

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function groupedFor(User $user, Organization $organization): array
    {
        $groups = [];

        foreach (config('export.entities', []) as $type => $meta) {
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
                'column_count' => count($entity->columnDefinitions()),
                'default_columns' => $entity->defaultColumns(),
                'permission' => $meta['permission'] ?? 'exports.view',
                'formats' => array_keys(config('export.formats', [])),
            ];
        }

        $ordered = [];
        foreach (array_keys(config('export.module_labels', [])) as $module) {
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
        $meta = config('export.entities.'.$entityType);
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
            // Still respect module license below.
        } elseif (! $user->hasPermission('exports.view', $organization)
            && ! $user->hasPermission('exports.manage', $organization)
            && ! $user->hasPermission('exports.create', $organization)) {
            return false;
        }

        $permission = $meta['permission'] ?? null;
        if ($permission
            && ! $user->is_super_admin
            && ! $user->isOwnerOf($organization)
            && ! $user->hasPermission($permission, $organization)
            && ! $user->hasPermission('exports.manage', $organization)) {
            return false;
        }

        $license = $meta['license_module'] ?? null;
        if ($license && ! $this->modules->moduleAllowed($organization, $license)) {
            return false;
        }

        return true;
    }
}
