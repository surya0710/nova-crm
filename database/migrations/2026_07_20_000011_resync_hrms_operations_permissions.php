<?php

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $operationsPermissions = Permission::query()
            ->whereIn('slug', [
                'assets.view',
                'assets.manage',
                'employee.exit.manage',
                'employee.directory',
                'organization.calendar',
            ])
            ->get()
            ->keyBy('slug');

        if ($operationsPermissions->isEmpty()) {
            return;
        }

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
                    ? $operationsPermissions->pluck('id')->all()
                    : $operationsPermissions->only($configured)->pluck('id')->all();

                if ($ids !== []) {
                    $role->permissions()->syncWithoutDetaching($ids);
                }
            }
        }
    }

    public function down(): void
    {
        // Role grants are intentionally preserved.
    }
};
