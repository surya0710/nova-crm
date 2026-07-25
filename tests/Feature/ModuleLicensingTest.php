<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationModule;
use App\Models\PlatformUser;
use App\Models\User;
use App\Models\UserUiPreference;
use App\Services\Dashboard\ModuleSubscriptionService;
use App\Services\Navigation\WorkspaceResolver;
use App\Services\Platform\OrganizationUpgradeService;
use App\Services\Platform\PlatformLicensingService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ModuleLicensingTest extends TestCase
{
    use RefreshDatabase;

    protected function createPlatformUser(string $role = 'platform-administrator'): PlatformUser
    {
        return PlatformUser::factory()->create([
            'role' => $role,
            'password' => Hash::make('password'),
        ]);
    }

    protected function tenantWithOwner(string $plan = 'enterprise'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['plan' => $plan]);
        $organization->addMember($user, 'organization-owner');
        app(TenantContext::class)->set($organization);

        return compact('user', 'organization');
    }

    public function test_module_registry_is_configuration_driven(): void
    {
        $modules = config('modules.modules');

        $this->assertIsArray($modules);
        $this->assertArrayHasKey('crm', $modules);
        $this->assertArrayHasKey('projects', $modules);
        $this->assertArrayHasKey('hrms', $modules);
        $this->assertArrayHasKey('marketing', $modules);
        $this->assertArrayHasKey('analytics', $modules);
        $this->assertSame('crm.home', $modules['crm']['route']);
        $this->assertSame(config('modules.plan_modules'), config('dashboard.plan_modules'));
    }

    public function test_new_organization_is_automatically_provisioned_with_modules(): void
    {
        $organization = Organization::factory()->create(['plan' => 'professional']);

        $this->assertTrue(
            OrganizationModule::query()->where('organization_id', $organization->id)->exists()
        );

        $service = app(ModuleSubscriptionService::class);
        $this->assertTrue($service->moduleAllowed($organization, 'crm'));
        $this->assertTrue($service->moduleAllowed($organization, 'projects'));
        $this->assertTrue($service->moduleAllowed($organization, 'hrms'));
        $this->assertTrue($service->moduleAllowed($organization, 'marketing'));
    }

    public function test_upgrade_command_is_idempotent(): void
    {
        $organization = Organization::factory()->create(['plan' => 'starter']);
        $firstCount = OrganizationModule::query()->where('organization_id', $organization->id)->count();

        Artisan::call('organization:upgrade', [
            '--organization' => $organization->id,
        ]);
        $secondCount = OrganizationModule::query()->where('organization_id', $organization->id)->count();

        Artisan::call('organization:upgrade', [
            '--organization' => $organization->id,
        ]);
        $thirdCount = OrganizationModule::query()->where('organization_id', $organization->id)->count();

        $this->assertSame($firstCount, $secondCount);
        $this->assertSame($secondCount, $thirdCount);
        $this->assertGreaterThan(0, $firstCount);
    }

    public function test_upgrade_never_overwrites_existing_module_state(): void
    {
        $organization = Organization::factory()->create(['plan' => 'professional']);

        OrganizationModule::query()
            ->where('organization_id', $organization->id)
            ->where('module_key', 'hrms')
            ->update(['is_enabled' => false]);

        app(OrganizationUpgradeService::class)->upgrade($organization);

        $hrms = OrganizationModule::query()
            ->where('organization_id', $organization->id)
            ->where('module_key', 'hrms')
            ->first();

        $this->assertNotNull($hrms);
        $this->assertFalse($hrms->is_enabled);
        $this->assertFalse(app(ModuleSubscriptionService::class)->moduleAllowed($organization->fresh(), 'hrms'));
    }

    public function test_platform_admin_can_manage_organization_modules(): void
    {
        $platformUser = $this->createPlatformUser();
        $organization = Organization::factory()->create(['plan' => 'enterprise']);

        $this->actingAs($platformUser, 'platform')
            ->put(route('platform.organizations.modules.update', $organization), [
                'modules' => ['common', 'crm', 'notifications', 'calendar'],
            ])
            ->assertRedirect(route('platform.organizations.edit', ['organization' => $organization, 'tab' => 'modules']));

        $service = app(ModuleSubscriptionService::class);
        $organization->refresh();

        $this->assertTrue($service->moduleAllowed($organization, 'crm'));
        $this->assertFalse($service->moduleAllowed($organization, 'projects'));
        $this->assertFalse($service->moduleAllowed($organization, 'hrms'));
    }

    public function test_workspace_resolver_hides_unlicensed_modules(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->tenantWithOwner('enterprise');

        app(PlatformLicensingService::class)->assignModules(
            $organization,
            ['common', 'crm', 'notifications', 'calendar', 'tasks'],
            $this->createPlatformUser()
        );

        $organization->refresh();
        $workspaces = app(WorkspaceResolver::class)
            ->availableWorkspaces($user, $organization)
            ->pluck('id');

        $this->assertTrue($workspaces->contains('crm'));
        $this->assertFalse($workspaces->contains('projects'));
        $this->assertFalse($workspaces->contains('hr'));
        $this->assertFalse($workspaces->contains('marketing'));
        $this->assertFalse($workspaces->contains('analytics'));
    }

    public function test_module_middleware_blocks_unlicensed_workspace_routes(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->tenantWithOwner('enterprise');

        app(PlatformLicensingService::class)->assignModules(
            $organization,
            ['common', 'crm', 'notifications', 'calendar'],
            $this->createPlatformUser()
        );

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('projects.home'))
            ->assertForbidden();
    }

    public function test_module_middleware_allows_licensed_workspace_routes(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->tenantWithOwner('enterprise');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('crm.home'))
            ->assertOk();
    }

    public function test_last_workspace_is_remembered_and_used_on_login(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->tenantWithOwner('enterprise');

        UserUiPreference::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'user_id' => $user->id,
            ],
            [
                'theme' => 'light',
                'density' => 'comfortable',
                'last_workspace' => 'projects',
            ]
        );

        $landing = app(WorkspaceResolver::class)->landingUrlFor($user, $organization, 'projects');

        $this->assertStringContainsString('/projects', $landing);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('shell.workspace.switch'), ['workspace' => 'projects'])
            ->assertOk()
            ->assertJsonPath('workspace', 'projects');
    }

    public function test_organization_admin_modules_page_is_read_only_for_entitlements(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->tenantWithOwner('professional');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('administration.modules.index'))
            ->assertOk()
            ->assertSee(__('Read-only'));
    }
}
