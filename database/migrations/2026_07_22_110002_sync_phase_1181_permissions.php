<?php

use App\Services\OrganizationRoleService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(OrganizationRoleService::class)->seedPermissions();
    }

    public function down(): void
    {
        // Permissions are additive; retain for rollback safety.
    }
};
