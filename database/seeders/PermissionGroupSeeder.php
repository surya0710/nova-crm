<?php

namespace Database\Seeders;

use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionGroupSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('dynamic_rbac.permission_groups', []) as $group) {
            PermissionGroup::query()->updateOrCreate(
                ['slug' => $group['slug'], 'organization_id' => null],
                [
                    'name' => $group['name'],
                    'description' => $group['description'] ?? null,
                    'sort_order' => $group['sort_order'],
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}
