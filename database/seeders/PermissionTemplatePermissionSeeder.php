<?php

namespace Database\Seeders;

use App\Models\PermissionTemplateRole;
use App\Models\PermissionTemplatePermission;
use Illuminate\Database\Seeder;

class PermissionTemplatePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PermissionTemplateRole::query()->with('template')->get() as $templateRole) {
            $systemRole = config("dynamic_rbac.system_roles.{$templateRole->role_slug}", []);
            $permissions = $this->resolvePermissions($templateRole->role_slug, $systemRole);

            PermissionTemplatePermission::query()
                ->where('permission_template_role_id', $templateRole->id)
                ->delete();

            foreach ($permissions as $slug) {
                PermissionTemplatePermission::query()->create([
                    'permission_template_role_id' => $templateRole->id,
                    'permission_slug' => $slug,
                ]);
            }
        }
    }

    protected function resolvePermissions(string $roleSlug, array $systemRole): array
    {
        $permissions = $systemRole['permissions'] ?? [];

        if ($permissions === '*') {
            return ['*'];
        }

        if (is_string($permissions)) {
            $legacyRole = config("rbac.roles.{$permissions}");
            if ($legacyRole) {
                return $legacyRole['permissions'] === '*'
                    ? ['*']
                    : $legacyRole['permissions'];
            }

            return config("dynamic_rbac.system_roles.{$permissions}.permissions", []);
        }

        if (is_array($permissions) && $permissions !== []) {
            return $permissions;
        }

        $legacyRole = config("rbac.roles.{$roleSlug}");
        if ($legacyRole) {
            return $legacyRole['permissions'] === '*'
                ? ['*']
                : $legacyRole['permissions'];
        }

        return [];
    }
}
