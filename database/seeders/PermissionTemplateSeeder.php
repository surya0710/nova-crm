<?php

namespace Database\Seeders;

use App\Models\PermissionTemplate;
use Illuminate\Database\Seeder;

class PermissionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('dynamic_rbac.templates', []) as $slug => $template) {
            PermissionTemplate::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $template['name'],
                    'description' => $template['description'] ?? null,
                    'is_default' => $template['is_default'] ?? false,
                ]
            );
        }
    }
}
