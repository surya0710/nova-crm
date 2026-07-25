<?php

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Services\OrganizationRoleService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(OrganizationRoleService::class)->seedPermissions();

        $goalPermissions = Permission::query()
            ->whereIn('slug', [
                'performance.goal.view',
                'performance.goal.manage',
                'performance.goal.update',
            ])
            ->get()
            ->keyBy('slug');

        foreach (Organization::query()->cursor() as $organization) {
            foreach (config('rbac.roles', []) as $slug => $definition) {
                $role = Role::query()
                    ->where('organization_id', $organization->id)
                    ->where('slug', $slug)
                    ->first();

                if (! $role) {
                    continue;
                }

                $configured = $definition['permissions'];
                $ids = $configured === '*'
                    ? $goalPermissions->pluck('id')->all()
                    : $goalPermissions->only($configured)->pluck('id')->all();

                if ($ids !== []) {
                    $role->permissions()->syncWithoutDetaching($ids);
                }
            }
        }
    }

    public function down(): void
    {
        // Permission records and role grants are intentionally preserved.
    }
};
