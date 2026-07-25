<?php

namespace Database\Seeders;

use App\Models\PermissionTemplate;
use App\Models\PermissionTemplateRole;
use Illuminate\Database\Seeder;

class PermissionTemplateRoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('dynamic_rbac.templates', []) as $templateSlug => $templateDef) {
            $template = PermissionTemplate::query()->where('slug', $templateSlug)->first();

            if (! $template) {
                continue;
            }

            foreach ($templateDef['roles'] as $index => $roleDef) {
                $systemRole = config("dynamic_rbac.system_roles.{$roleDef['role_slug']}", []);

                PermissionTemplateRole::query()->updateOrCreate(
                    [
                        'permission_template_id' => $template->id,
                        'role_slug' => $roleDef['role_slug'],
                    ],
                    [
                        'role_name' => $systemRole['name'] ?? ucfirst(str_replace('-', ' ', $roleDef['role_slug'])),
                        'role_description' => $systemRole['description'] ?? null,
                        'hierarchy_level' => $roleDef['hierarchy_level'] ?? ($systemRole['hierarchy_level'] ?? 0),
                        'color' => $systemRole['color'] ?? '#6366f1',
                        'sort_order' => $index,
                    ]
                );
            }
        }
    }
}
