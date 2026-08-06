<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\PermissionTemplate;
use App\Models\Role;
use App\Models\User;
use App\Services\Rbac\AuthorizationService;
use App\Services\Rbac\RolePermissionService;
use App\Services\Rbac\RoleService;
use App\Services\Rbac\UserRoleService;
use Database\Seeders\DynamicRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicRbacTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DynamicRbacSeeder::class);

        $this->owner = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->addMember($this->owner, 'organization-owner');
    }

    public function test_permission_groups_are_seeded(): void
    {
        $this->assertGreaterThanOrEqual(22, PermissionGroup::query()->whereNull('organization_id')->count());
        $this->assertDatabaseHas('permission_groups', ['slug' => 'crm', 'is_system' => true]);
    }

    public function test_permission_templates_are_seeded(): void
    {
        $this->assertDatabaseHas('permission_templates', ['slug' => 'corporate', 'is_default' => true]);
        $this->assertDatabaseHas('permission_templates', ['slug' => 'startup']);
    }

    public function test_rbac_permissions_exist(): void
    {
        $this->assertDatabaseHas('permissions', ['slug' => 'rbac.view']);
        $this->assertDatabaseHas('permissions', ['slug' => 'rbac.roles.manage']);
    }

    public function test_organization_gets_provisioned_roles(): void
    {
        $this->assertTrue(
            $this->organization->roles()->whereIn('slug', ['organization-owner', 'organization-administrator'])->exists()
        );
    }

    public function test_authorization_service_grants_owner_all_permissions(): void
    {
        $service = app(AuthorizationService::class);

        $this->assertTrue($service->can($this->owner, 'settings.manage', $this->organization));
        $this->assertTrue($service->can($this->owner, 'rbac.view', $this->organization));
    }

    public function test_employee_cannot_access_rbac_ui(): void
    {
        $employee = User::factory()->create();
        $this->organization->addMember($employee, 'employee');

        $response = $this->actingAs($employee)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->get(route('rbac.roles.index'));

        $response->assertForbidden();
    }

    public function test_owner_can_access_rbac_roles_page(): void
    {
        $this->assertTrue($this->owner->isOwnerOf($this->organization));
        $this->assertTrue($this->owner->hasPermission('rbac.view', $this->organization));

        $response = $this->actingAs($this->owner)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->get(route('rbac.roles.index'));

        $response->assertOk();
        $response->assertSee('Roles');
    }

    public function test_custom_role_can_be_created(): void
    {
        $role = app(RoleService::class)->create($this->organization, [
            'name' => 'Custom Analyst',
            'slug' => 'custom-analyst',
            'hierarchy_level' => 30,
        ], $this->owner);

        $this->assertDatabaseHas('roles', [
            'organization_id' => $this->organization->id,
            'slug' => 'custom-analyst',
            'is_system' => false,
        ]);

        $this->assertEquals('Custom Analyst', $role->name);
    }

    public function test_role_permission_matrix_sync(): void
    {
        $role = $this->organization->roles()->where('slug', 'employee')->firstOrFail();
        $permission = Permission::query()->where('slug', 'leads.view')->firstOrFail();

        app(RolePermissionService::class)->sync($this->organization, $role, [$permission->id], $this->owner);

        $this->assertDatabaseHas('role_permissions', [
            'organization_id' => $this->organization->id,
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);
    }

    public function test_user_can_have_multiple_roles(): void
    {
        $employee = User::factory()->create();
        $this->organization->addMember($employee, 'employee');

        $managerRole = $this->organization->roles()->where('slug', 'manager')->firstOrFail();

        app(UserRoleService::class)->assign($employee, $this->organization, $managerRole, $this->owner);

        $roles = app(UserRoleService::class)->rolesForUser($employee, $this->organization);

        $this->assertTrue($roles->contains('slug', 'manager'));
    }

    public function test_authorization_lookup_api(): void
    {
        $response = $this->actingAs($this->owner)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->getJson(route('rbac.authorization.lookup', ['permission' => 'leads.view']));

        $response->assertOk();
        $response->assertJson(['permission' => 'leads.view', 'allowed' => true]);
    }

    public function test_permission_template_preview(): void
    {
        $template = PermissionTemplate::query()->where('slug', 'startup')->firstOrFail();

        $response = $this->actingAs($this->owner)
            ->withSession(['current_organization_id' => $this->organization->id])
            ->get(route('rbac.templates.show', $template));

        $response->assertOk();
    }
}
