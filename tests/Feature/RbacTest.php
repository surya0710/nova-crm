<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\OrganizationRoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_permissions_are_seeded_globally(): void
    {
        $this->assertGreaterThan(0, Permission::query()->count());
        $this->assertDatabaseHas('permissions', ['slug' => 'settings.manage']);
        $this->assertDatabaseHas('permissions', ['slug' => 'leads.view']);
    }

    public function test_new_organization_gets_default_system_roles(): void
    {
        $organization = Organization::factory()->create();

        $this->assertCount(6, $organization->roles);

        $this->assertDatabaseHas('roles', [
            'organization_id' => $organization->id,
            'slug' => 'organization-owner',
            'is_system' => true,
        ]);

        $this->assertDatabaseHas('roles', [
            'organization_id' => $organization->id,
            'slug' => 'employee',
        ]);
    }

    public function test_organization_owner_has_settings_manage_permission(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');

        $this->assertTrue($user->hasPermission('settings.manage', $organization));
        $this->assertTrue($user->isOwnerOf($organization));
    }

    public function test_employee_cannot_manage_settings(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'employee');

        $this->assertFalse($user->hasPermission('settings.manage', $organization));
        $this->assertFalse($user->isOwnerOf($organization));
    }

    public function test_sales_executive_has_lead_permissions_but_not_settings(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'sales-executive');

        $this->assertTrue($user->hasPermission('leads.view', $organization));
        $this->assertTrue($user->hasPermission('leads.create', $organization));
        $this->assertFalse($user->hasPermission('settings.manage', $organization));
    }

    public function test_super_admin_bypasses_all_permission_checks(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $organization = Organization::factory()->create();

        $this->assertTrue($user->hasPermission('settings.manage', $organization));
        $this->assertTrue($user->isOwnerOf($organization));
    }

    public function test_employee_cannot_access_organization_settings_page(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'employee');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('organization.edit'));

        $response->assertForbidden();
    }

    public function test_owner_can_access_organization_settings_page(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('organization.edit'));

        $response->assertOk();
        $response->assertSee('Roles & Permissions');
    }

    public function test_legacy_is_owner_pivot_maps_to_organization_owner_role(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $ownerRole = $organization->roles()->where('slug', 'organization-owner')->firstOrFail();

        $organization->users()->attach($user->id, [
            'role_id' => $ownerRole->id,
            'role' => 'organization-owner',
            'is_owner' => true,
        ]);

        $this->assertEquals('Organization Owner', $user->getRoleNameInOrganization($organization));
        $this->assertTrue($user->hasPermission('settings.manage', $organization));
    }

    public function test_role_service_can_reassign_member_role(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'employee');

        app(OrganizationRoleService::class)->assignRole($user, $organization, 'manager');

        $user->refresh();

        $this->assertEquals('Manager', $user->getRoleNameInOrganization($organization));
        $this->assertTrue($user->hasPermission('users.create', $organization));
        $this->assertFalse($user->hasPermission('settings.manage', $organization));
    }

    public function test_organization_owner_role_has_all_permissions(): void
    {
        $organization = Organization::factory()->create();
        $ownerRole = $organization->roles()->where('slug', 'organization-owner')->firstOrFail();

        $this->assertTrue($ownerRole->hasPermission('settings.manage'));
        $this->assertTrue($ownerRole->hasPermission('leads.delete'));
        $this->assertTrue($ownerRole->hasPermission('reports.manage'));
    }

    public function test_manager_role_has_expected_permissions(): void
    {
        $organization = Organization::factory()->create();
        $managerRole = $organization->roles()->where('slug', 'manager')->firstOrFail();

        $this->assertTrue($managerRole->hasPermission('leads.manage'));
        $this->assertTrue($managerRole->hasPermission('settings.view'));
        $this->assertFalse($managerRole->hasPermission('settings.manage'));
    }
}
