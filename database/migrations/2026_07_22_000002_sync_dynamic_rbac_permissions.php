<?php

use App\Services\OrganizationRoleService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new \Database\Seeders\DynamicRbacSeeder)->run();
    }

    public function down(): void
    {
        //
    }
};
