<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\IndustryTemplate;
use App\Models\IndustryTemplateVersion;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\OrganizationTemplateApplication;
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

    public function test_platform_admin_can_create_and_view_industry_template_draft(): void
    {
        $platformUser = $this->createPlatformUser();

        $response = $this->actingAs($platformUser, 'platform')
            ->post(route('platform.industry-templates.store'), [
                'name' => 'Immigration',
                'slug' => 'immigration',
                'industry' => 'immigration',
                'visibility' => 'internal',
                'draft_payload' => json_encode([
                    'schema_version' => 1,
                    'metadata' => ['industry_key' => 'immigration'],
                    'settings' => ['currency' => 'INR'],
                    'terminology' => ['entities' => ['lead' => ['singular' => 'Applicant']]],
                ]),
            ]);

        $template = IndustryTemplate::firstOrFail();

        $response->assertRedirect(route('platform.industry-templates.show', $template));
        $this->assertSame('draft', $template->status);
        $this->assertDatabaseHas('platform_audit_logs', ['event' => 'industry_template.created']);
    }

    public function test_publishing_industry_template_creates_immutable_version_snapshot(): void
    {
        $platformUser = $this->createPlatformUser();
        $template = IndustryTemplate::create([
            'name' => 'Healthcare',
            'slug' => 'healthcare',
            'status' => 'draft',
            'visibility' => 'internal',
            'draft_schema_version' => 1,
            'draft_payload' => [
                'schema_version' => 1,
                'metadata' => ['industry_key' => 'healthcare'],
                'settings' => ['currency' => 'USD'],
                'terminology' => ['entities' => ['lead' => ['singular' => 'Patient']]],
            ],
            'created_by_platform_user_id' => $platformUser->id,
        ]);

        $this->actingAs($platformUser, 'platform')
            ->post(route('platform.industry-templates.publish', $template), [
                'changelog' => 'Initial version',
            ])
            ->assertRedirect(route('platform.industry-templates.show', $template));

        $template->refresh();
        $version = $template->currentVersion;

        $this->assertNotNull($version);
        $this->assertSame('published', $template->status);
        $this->assertSame(1, $version->version);
        $this->assertSame('Patient', $version->payload['terminology']['entities']['lead']['singular']);
        $this->assertDatabaseHas('platform_audit_logs', ['event' => 'industry_template.published']);
    }

    public function test_publish_rejects_unknown_template_payload_sections(): void
    {
        $platformUser = $this->createPlatformUser();
        $template = IndustryTemplate::create([
            'name' => 'Travel',
            'slug' => 'travel',
            'status' => 'draft',
            'visibility' => 'internal',
            'draft_schema_version' => 1,
            'draft_payload' => [
                'schema_version' => 1,
                'metadata' => [],
                'unsupported_section' => [],
            ],
            'created_by_platform_user_id' => $platformUser->id,
        ]);

        $this->actingAs($platformUser, 'platform')
            ->post(route('platform.industry-templates.publish', $template))
            ->assertSessionHasErrors('payload');

        $this->assertDatabaseCount('industry_template_versions', 0);
    }

    public function test_platform_organization_creation_applies_template_copy(): void
    {
        $platformUser = $this->createPlatformUser();
        $template = IndustryTemplate::create([
            'name' => 'Consulting',
            'slug' => 'consulting',
            'status' => 'published',
            'visibility' => 'internal',
            'draft_schema_version' => 1,
            'draft_payload' => [],
            'created_by_platform_user_id' => $platformUser->id,
        ]);
        $version = IndustryTemplateVersion::create([
            'industry_template_id' => $template->id,
            'version' => 1,
            'schema_version' => 1,
            'payload' => [
                'schema_version' => 1,
                'metadata' => ['industry_key' => 'consulting'],
                'settings' => ['currency' => 'INR', 'timezone' => 'Asia/Kolkata'],
                'terminology' => ['entities' => ['lead' => ['singular' => 'Prospect']]],
                'business_calendar' => ['working_days' => ['monday', 'tuesday']],
                'lead_lifecycle' => [],
                'customer_configuration' => [],
                'pipelines' => [[
                    'key' => 'default_sales',
                    'name' => 'Default Sales Pipeline',
                    'is_default' => true,
                    'entity' => 'opportunity',
                    'stages' => [],
                ]],
                'dashboard' => ['layout' => [['widget_key' => 'lead_counts', 'enabled' => true, 'order' => 1, 'size' => 'small']]],
                'reports' => [['key' => 'pipeline', 'name' => 'Pipeline', 'report_type' => 'pipeline_value']],
                'notification_preferences' => [],
                'task_blueprints' => [],
                'field_blueprints' => [['entity' => 'lead', 'key' => 'budget', 'label' => 'Budget', 'type' => 'number']],
                'automation_blueprints' => [],
                'email_template_blueprints' => [],
            ],
            'payload_hash' => hash('sha256', 'consulting'),
            'status' => 'published',
            'published_by_platform_user_id' => $platformUser->id,
            'published_at' => now(),
        ]);
        $template->update(['current_version_id' => $version->id]);

        $this->actingAs($platformUser, 'platform')
            ->post(route('platform.organizations.store'), [
                'name' => 'Acme Consulting',
                'plan' => 'starter',
                'status' => 'active',
                'template_version_id' => $version->id,
                'owner_name' => 'Owner User',
                'owner_email' => 'owner@example.test',
                'owner_password' => 'password',
            ])
            ->assertRedirect();

        $organization = Organization::where('name', 'Acme Consulting')->firstOrFail();
        $application = OrganizationTemplateApplication::where('organization_id', $organization->id)->firstOrFail();

        $this->assertSame('INR', $organization->currency);
        $this->assertSame('Asia/Kolkata', $organization->timezone);
        $this->assertSame('Prospect', $organization->settings['terminology']['entities']['lead']['singular']);
        $this->assertSame($version->id, $application->industry_template_version_id);
        $this->assertDatabaseHas('platform_audit_logs', ['event' => 'industry_template.applied_to_organization']);
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
