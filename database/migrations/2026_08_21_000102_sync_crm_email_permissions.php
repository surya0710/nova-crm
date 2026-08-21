<?php

use App\Models\Organization;
use App\Services\OrganizationRoleService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $roleService = app(OrganizationRoleService::class);
        $roleService->seedPermissions();

        foreach (Organization::query()->cursor() as $organization) {
            $roleService->seedDefaultRoles($organization);
        }
    }

    public function down(): void
    {
        //
    }
};
