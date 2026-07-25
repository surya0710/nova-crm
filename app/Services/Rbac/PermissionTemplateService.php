<?php

namespace App\Services\Rbac;

use App\Events\Rbac\PermissionTemplateInstalled;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\PermissionTemplate;
use App\Models\Role;
use App\Models\User;
use App\Notifications\RbacNotification;
use App\Services\AuditLogger;
use App\Services\OrganizationRoleService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PermissionTemplateService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected PermissionService $permissionService,
        protected RoleService $roleService,
        protected RolePermissionService $rolePermissionService,
        protected OrganizationRoleService $organizationRoleService,
        protected AuthorizationService $authorizationService,
    ) {}

    public function list(): Collection
    {
        return PermissionTemplate::query()
            ->with(['templateRoles.templatePermissions'])
            ->orderBy('name')
            ->get();
    }

    public function preview(PermissionTemplate $template): array
    {
        $template->load(['templateRoles.templatePermissions']);

        return [
            'template' => $template,
            'roles' => $template->templateRoles,
            'permission_count' => $template->templateRoles
                ->flatMap(fn ($role) => $role->templatePermissions)
                ->unique('permission_slug')
                ->count(),
        ];
    }

    public function install(PermissionTemplate $template, Organization $organization, ?User $actor = null): void
    {
        DB::transaction(function () use ($template, $organization, $actor) {
            $this->permissionService->cloneForOrganization($organization);
            $this->cloneGroupsForOrganization($organization);

            $template->load(['templateRoles.templatePermissions']);

            foreach ($template->templateRoles as $templateRole) {
                $systemRoleDef = config("dynamic_rbac.system_roles.{$templateRole->role_slug}", []);

                $role = Role::query()->updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'slug' => $templateRole->role_slug,
                    ],
                    [
                        'name' => $templateRole->role_name,
                        'description' => $templateRole->role_description ?? ($systemRoleDef['description'] ?? null),
                        'color' => $templateRole->color ?? ($systemRoleDef['color'] ?? '#6366f1'),
                        'hierarchy_level' => $templateRole->hierarchy_level,
                        'is_system' => true,
                        'is_default' => ($systemRoleDef['is_default'] ?? false),
                        'is_active' => true,
                    ]
                );

                $permissionSlugs = $templateRole->templatePermissions->pluck('permission_slug')->all();

                if ($permissionSlugs === ['*'] || ($systemRoleDef['permissions'] ?? null) === '*') {
                    $permissionIds = Permission::query()
                        ->forOrganization($organization)
                        ->where('is_active', true)
                        ->pluck('id')
                        ->all();
                } else {
                    $permissionIds = Permission::query()
                        ->forOrganization($organization)
                        ->whereIn('slug', $permissionSlugs)
                        ->pluck('id')
                        ->all();
                }

                $this->rolePermissionService->sync($organization, $role, $permissionIds, $actor);
            }

            event(new PermissionTemplateInstalled($template, $organization->id, $actor));
            $this->auditLogger->log($organization, 'rbac.template.installed', [
                'template_slug' => $template->slug,
            ], $actor);

            $this->authorizationService->forgetOrganizationCache($organization);
        });

        if ($actor) {
            $actor->notify(new RbacNotification(
                title: __('Permission template installed'),
                message: __('The :template template was installed for your organization.', ['template' => $template->name]),
                actionUrl: route('rbac.templates.index'),
                organizationId: $organization->id,
                type: 'template_installed',
            ));
        }
    }

    public function applyDefault(Organization $organization, ?User $actor = null): void
    {
        $template = PermissionTemplate::query()->where('is_default', true)->first();

        if ($template) {
            $this->install($template, $organization, $actor);
        }
    }

    public function reset(Organization $organization, ?User $actor = null): void
    {
        DB::transaction(function () use ($organization, $actor) {
            Role::query()
                ->where('organization_id', $organization->id)
                ->where('is_system', false)
                ->delete();

            $this->applyDefault($organization, $actor);

            $this->auditLogger->log($organization, 'rbac.template.reset', [], $actor);
            $this->authorizationService->forgetOrganizationCache($organization);
        });
    }

    public function cloneTemplate(PermissionTemplate $source, string $name, string $slug): PermissionTemplate
    {
        $source->load(['templateRoles.templatePermissions']);

        $copy = PermissionTemplate::query()->create([
            'name' => $name,
            'slug' => $slug,
            'description' => $source->description,
            'is_default' => false,
        ]);

        foreach ($source->templateRoles as $templateRole) {
            $newRole = $copy->templateRoles()->create([
                'role_name' => $templateRole->role_name,
                'role_slug' => $templateRole->role_slug,
                'role_description' => $templateRole->role_description,
                'hierarchy_level' => $templateRole->hierarchy_level,
                'color' => $templateRole->color,
                'sort_order' => $templateRole->sort_order,
            ]);

            foreach ($templateRole->templatePermissions as $permission) {
                $newRole->templatePermissions()->create([
                    'permission_slug' => $permission->permission_slug,
                ]);
            }
        }

        return $copy->fresh(['templateRoles.templatePermissions']);
    }

    protected function cloneGroupsForOrganization(Organization $organization): void
    {
        $groups = PermissionGroup::query()->whereNull('organization_id')->get();

        foreach ($groups as $group) {
            PermissionGroup::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'slug' => $group->slug,
                ],
                [
                    'name' => $group->name,
                    'description' => $group->description,
                    'sort_order' => $group->sort_order,
                    'is_system' => true,
                    'is_active' => $group->is_active,
                ]
            );
        }
    }
}
