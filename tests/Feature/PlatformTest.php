<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\PlatformAuditLog;
use App\Models\PlatformUser;
use App\Models\User;
use App\Services\Platform\PlatformImpersonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function createPlatformUser(string $role = 'platform-administrator'): PlatformUser
    {
        return PlatformUser::factory()->create(['role' => $role]);
    }

    protected function createTenantWithOwner(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');

        return compact('user', 'organization');
    }

    public function test_platform_login_succeeds_with_valid_credentials(): void
    {
        $platformUser = $this->createPlatformUser();

        $response = $this->post(route('platform.login.store'), [
            'email' => $platformUser->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('platform.dashboard'));
        $this->assertAuthenticatedAs($platformUser, 'platform');
        $this->assertDatabaseHas('platform_audit_logs', ['event' => 'platform.login']);
    }

    public function test_platform_middleware_blocks_guests(): void
    {
        $this->get(route('platform.dashboard'))
            ->assertRedirect(route('platform.login'));
    }

    public function test_tenant_user_cannot_access_platform(): void
    {
        ['user' => $user] = $this->createTenantWithOwner();

        $this->actingAs($user)
            ->get(route('platform.dashboard'))
            ->assertRedirect(route('platform.login'));
    }

    public function test_platform_user_cannot_access_tenant_without_impersonation(): void
    {
        $platformUser = $this->createPlatformUser();

        $this->actingAs($platformUser, 'platform')
            ->get(route('dashboard'))
            ->assertRedirect(route('platform.dashboard'));
    }

    public function test_organization_list_is_accessible_to_platform_admin(): void
    {
        $platformUser = $this->createPlatformUser();
        Organization::factory()->count(3)->create();

        $this->actingAs($platformUser, 'platform')
            ->get(route('platform.organizations.index'))
            ->assertOk()
            ->assertSee('Organizations');
    }

    public function test_suspend_organization(): void
    {
        $platformUser = $this->createPlatformUser();
        $organization = Organization::factory()->create();

        $this->actingAs($platformUser, 'platform')
            ->post(route('platform.organizations.suspend', $organization))
            ->assertRedirect();

        $organization->refresh();
        $this->assertTrue($organization->isSuspended());
        $this->assertDatabaseHas('platform_audit_logs', [
            'event' => 'organization.suspended',
            'organization_id' => $organization->id,
        ]);
    }

    public function test_activate_organization(): void
    {
        $platformUser = $this->createPlatformUser();
        $organization = Organization::factory()->suspended()->create();

        $this->actingAs($platformUser, 'platform')
            ->post(route('platform.organizations.activate', $organization))
            ->assertRedirect();

        $organization->refresh();
        $this->assertTrue($organization->isActive());
        $this->assertDatabaseHas('platform_audit_logs', [
            'event' => 'organization.activated',
            'organization_id' => $organization->id,
        ]);
    }

    public function test_suspended_organization_blocks_tenant_login(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->createTenantWithOwner();
        $organization->update(['status' => 'suspended', 'is_active' => false]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_impersonation_flow(): void
    {
        $platformUser = $this->createPlatformUser();
        ['user' => $owner, 'organization' => $organization] = $this->createTenantWithOwner();

        $token = app(PlatformImpersonationService::class)
            ->createToken($platformUser, $organization);

        $this->get(route('impersonation.accept', ['token' => $token]))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($owner);
        $this->assertEquals($organization->id, session('current_organization_id'));
        $this->assertTrue(session()->has(PlatformImpersonationService::SESSION_KEY));
        $this->assertDatabaseHas('platform_audit_logs', ['event' => 'impersonation.started']);
    }

    public function test_support_cannot_impersonate_platform_owner_org(): void
    {
        $platformOwner = PlatformUser::factory()->owner()->create();
        $support = PlatformUser::factory()->support()->create();

        ['organization' => $organization] = $this->createTenantWithOwner();
        $owner = $organization->primaryOwner();
        $owner->update(['email' => $platformOwner->email]);

        $this->assertFalse(
            app(PlatformImpersonationService::class)->canImpersonate($support, $organization->fresh())
        );
    }

    public function test_unauthorized_platform_support_cannot_manage_organizations(): void
    {
        $support = PlatformUser::factory()->support()->create();
        $organization = Organization::factory()->create();

        $this->actingAs($support, 'platform')
            ->post(route('platform.organizations.suspend', $organization))
            ->assertForbidden();
    }

    public function test_dashboard_metrics_aggregate_across_tenants(): void
    {
        $platformUser = $this->createPlatformUser();
        ['organization' => $orgA] = $this->createTenantWithOwner();
        ['organization' => $orgB] = $this->createTenantWithOwner();

        Lead::factory()->create(['organization_id' => $orgA->id]);
        Lead::factory()->create(['organization_id' => $orgB->id]);
        Customer::factory()->create(['organization_id' => $orgA->id]);

        Cache::forget('platform.dashboard.metrics');

        $response = $this->actingAs($platformUser, 'platform')
            ->get(route('platform.dashboard'));

        $response->assertOk();
        $response->assertSee('Platform Dashboard');
    }

    public function test_platform_reports_page(): void
    {
        $platformUser = $this->createPlatformUser();

        $this->actingAs($platformUser, 'platform')
            ->get(route('platform.reports.index'))
            ->assertOk()
            ->assertSee('Platform Reports');
    }

    public function test_platform_reports_csv_export(): void
    {
        $platformUser = $this->createPlatformUser();

        $this->actingAs($platformUser, 'platform')
            ->get(route('platform.reports.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_platform_audit_log_records_events(): void
    {
        $platformUser = $this->createPlatformUser();

        PlatformAuditLog::create([
            'platform_user_id' => $platformUser->id,
            'event' => 'platform.login',
            'subject' => 'Test login',
        ]);

        $this->actingAs($platformUser, 'platform')
            ->get(route('platform.audit.index'))
            ->assertOk()
            ->assertSee('platform.login');
    }

    public function test_archive_organization(): void
    {
        $platformUser = $this->createPlatformUser();
        $organization = Organization::factory()->create();

        $this->actingAs($platformUser, 'platform')
            ->post(route('platform.organizations.archive', $organization))
            ->assertRedirect();

        $organization->refresh();
        $this->assertTrue($organization->isArchived());
    }

    public function test_read_only_role_cannot_manage_users(): void
    {
        $readOnly = PlatformUser::factory()->create(['role' => 'platform-read-only']);

        $this->actingAs($readOnly, 'platform')
            ->get(route('platform.users.index'))
            ->assertForbidden();
    }
}
