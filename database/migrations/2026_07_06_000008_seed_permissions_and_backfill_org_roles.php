<?php

use App\Models\Organization;
use App\Services\OrganizationRoleService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roleService = app(OrganizationRoleService::class);

        $roleService->seedPermissions();

        foreach (Organization::query()->cursor() as $organization) {
            $roleService->seedDefaultRoles($organization);
        }

        $legacyMap = config('rbac.legacy_role_map', []);

        $pivots = DB::table('organization_user')->orderBy('id')->get();

        foreach ($pivots as $pivot) {
            $roleSlug = 'employee';

            if ($pivot->is_owner || $pivot->role === 'owner') {
                $roleSlug = 'organization-owner';
            } elseif (isset($legacyMap[$pivot->role])) {
                $roleSlug = $legacyMap[$pivot->role];
            }

            $role = DB::table('roles')
                ->where('organization_id', $pivot->organization_id)
                ->where('slug', $roleSlug)
                ->first();

            if (! $role) {
                continue;
            }

            DB::table('organization_user')
                ->where('id', $pivot->id)
                ->update([
                    'role_id' => $role->id,
                    'role' => $roleSlug,
                    'is_owner' => $roleSlug === 'organization-owner',
                ]);
        }
    }

    public function down(): void
    {
        DB::table('organization_user')->update(['role_id' => null]);

        DB::table('role_permission')->delete();
        DB::table('roles')->delete();
        DB::table('permissions')->delete();
    }
};
