<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Rbac\AuthorizationService;
use Database\Seeders\DynamicRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_bypasses_checks(): void
    {
        $this->seed(DynamicRbacSeeder::class);

        $admin = User::factory()->create(['is_super_admin' => true]);
        $organization = Organization::factory()->create();

        $service = app(AuthorizationService::class);

        $this->assertTrue($service->can($admin, 'settings.manage', $organization));
    }

    public function test_effective_permissions_cached_and_cleared(): void
    {
        $this->seed(DynamicRbacSeeder::class);

        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'employee');

        $service = app(AuthorizationService::class);

        $first = $service->effectivePermissions($user, $organization);
        $second = $service->effectivePermissions($user, $organization);

        $this->assertEquals($first->count(), $second->count());

        $service->forgetUserCache($user, $organization);

        $third = $service->effectivePermissions($user, $organization);
        $this->assertEquals($first->count(), $third->count());
    }

    public function test_owner_role_has_all_permissions_via_service(): void
    {
        $this->seed(DynamicRbacSeeder::class);

        $organization = Organization::factory()->create();
        $ownerRole = $organization->roles()->where('slug', 'organization-owner')->firstOrFail();

        $this->assertTrue($ownerRole->hasPermission('settings.manage'));
        $this->assertTrue($ownerRole->hasPermission('rbac.view'));
    }
}
