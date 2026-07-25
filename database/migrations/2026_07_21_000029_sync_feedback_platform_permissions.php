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

        $feedbackPermissions = Permission::query()
            ->whereIn('slug', [
                'performance.feedback.view',
                'performance.feedback.manage',
                'performance.feedback.submit',
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
                    ? $feedbackPermissions->pluck('id')->all()
                    : $feedbackPermissions->only($configured)->pluck('id')->all();

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
