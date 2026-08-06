<?php

namespace App\Services\CommandPalette;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class AdminCommandProvider implements CommandProviderInterface
{
    public function commands(User $user, ?Organization $organization): Collection
    {
        if (! $organization) {
            return collect();
        }

        $commands = collect();
        $group = __('Administration');

        $adminHomePerms = [
            'settings.manage', 'users.view', 'rbac.view', 'workflows.view',
            'metadata.view', 'metadata.manage', 'integrations.view', 'integrations.manage',
            'api.tokens', 'audit.view',
        ];

        if (Route::has('administration.home') && $user->hasAnyPermission($adminHomePerms)) {
            $commands->push([
                'id' => 'admin.home',
                'label' => __('Open Admin Home'),
                'group' => $group,
                'href' => route('administration.home'),
                'keywords' => ['admin', 'administration', 'home', 'settings'],
            ]);
        }

        if ($user->hasAnyPermission(['users.create', 'users.view']) && Route::has('team.index')) {
            $commands->push([
                'id' => 'admin.invite-user',
                'label' => __('Invite User'),
                'group' => $group,
                'href' => route('team.index').'#invite',
                'keywords' => ['invite', 'user', 'team', 'member'],
            ]);
        }

        if ($user->hasAnyPermission(['hrms.view', 'organization.branches.view']) && Route::has('hrms.departments.index')) {
            $commands->push([
                'id' => 'admin.create-department',
                'label' => __('Create Department'),
                'group' => $group,
                'href' => route('hrms.departments.index'),
                'keywords' => ['department', 'structure', 'org'],
            ]);
        }

        if ($user->hasAnyPermission(['hrms.view', 'organization.branches.view']) && Route::has('hrms.branches.index')) {
            $commands->push([
                'id' => 'admin.create-branch',
                'label' => __('Create Branch'),
                'group' => $group,
                'href' => route('hrms.branches.index'),
                'keywords' => ['branch', 'office', 'location'],
            ]);
        }

        if ($user->hasPermission('rbac.view') && Route::has('rbac.roles.index')) {
            $commands->push([
                'id' => 'admin.open-roles',
                'label' => __('Open Roles'),
                'group' => $group,
                'href' => route('rbac.roles.index'),
                'keywords' => ['roles', 'rbac', 'access'],
            ]);
        }

        if ($user->hasPermission('settings.manage') && Route::has('organization.settings.hub')) {
            $commands->push([
                'id' => 'admin.open-settings-hub',
                'label' => __('Open Settings Hub'),
                'group' => $group,
                'href' => route('organization.settings.hub'),
                'keywords' => ['settings', 'hub', 'configuration'],
            ]);
        }

        if ($user->hasPermission('settings.manage') && Route::has('administration.security.index')) {
            $commands->push([
                'id' => 'admin.open-security',
                'label' => __('Open Security'),
                'group' => $group,
                'href' => route('administration.security.index'),
                'keywords' => ['security', 'password', 'mfa', 'session'],
            ]);
        }

        if ($user->hasPermission('settings.manage') && Route::has('administration.modules.index')) {
            $commands->push([
                'id' => 'admin.open-modules',
                'label' => __('Open Modules'),
                'group' => $group,
                'href' => route('administration.modules.index'),
                'keywords' => ['modules', 'features', 'plan'],
            ]);
        }

        if ($user->hasPermission('settings.manage') && Route::has('administration.branding.edit')) {
            $commands->push([
                'id' => 'admin.open-branding',
                'label' => __('Open Branding'),
                'group' => $group,
                'href' => route('administration.branding.edit'),
                'keywords' => ['branding', 'logo', 'colors', 'theme'],
            ]);
        }

        if ($user->hasPermission('users.view') && Route::has('team.index')) {
            $commands->push([
                'id' => 'admin.search-users',
                'label' => __('Search Users'),
                'group' => $group,
                'href' => route('team.index'),
                'keywords' => ['users', 'search', 'team'],
            ]);
        }

        if ($user->hasAnyPermission(['hrms.view', 'organization.branches.view']) && Route::has('hrms.departments.index')) {
            $commands->push([
                'id' => 'admin.search-departments',
                'label' => __('Search Departments'),
                'group' => $group,
                'href' => route('hrms.departments.index'),
                'keywords' => ['departments', 'search', 'structure'],
            ]);
        }

        return $commands;
    }
}
