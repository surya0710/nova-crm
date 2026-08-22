<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationModule;
use App\Models\User;
use App\Services\Configuration\ConfigurationRegistry;
use App\Services\Search\AdminSettingsSearchProvider;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfigurationHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_defines_module_aware_catalog(): void
    {
        $modules = config('organization_settings.modules');

        $this->assertIsArray($modules);
        foreach (['organization', 'crm', 'commercial', 'hrms', 'projects', 'marketing', 'security', 'platform'] as $key) {
            $this->assertArrayHasKey($key, $modules);
            $this->assertArrayHasKey('name', $modules[$key]);
            $this->assertArrayHasKey('description', $modules[$key]);
            $this->assertArrayHasKey('icon', $modules[$key]);
            $this->assertArrayHasKey('license', $modules[$key]);
            $this->assertArrayHasKey('order', $modules[$key]);
            $this->assertNotEmpty($modules[$key]['sections']);
        }

        $this->assertSame('crm', $modules['crm']['license']);
        $this->assertSame('crm', $modules['commercial']['license']);
        $this->assertSame('hrms', $modules['hrms']['license']);
        $this->assertSame('recruitment', $modules['hrms']['sections']['recruitment']['license']);
        $this->assertSame('workflow', $modules['platform']['sections']['workflows']['license']);
        $this->assertNull($modules['organization']['license']);
        $this->assertArrayNotHasKey('dashboard', $modules['platform']['sections']);
        $this->assertArrayNotHasKey('business_hours', config('organization_settings.sections'));
    }

    public function test_hub_is_reachable_for_organization_owner(): void
    {
        [$organization, $owner] = $this->starterOwner();

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('organization.settings.hub'))
            ->assertOk()
            ->assertSee('Configuration Hub')
            ->assertSee('CRM')
            ->assertSee('Commercial')
            ->assertSee('Lead Settings')
            ->assertSee('Tax / GST')
            ->assertDontSee('Working Days')
            ->assertDontSee('Leave Policies');
    }

    public function test_starter_plan_hides_hrms_and_projects_from_registry(): void
    {
        [$organization, $owner] = $this->starterOwner();

        $keys = $this->visibleModuleKeys($owner, $organization);

        $this->assertContains('organization', $keys);
        $this->assertContains('crm', $keys);
        $this->assertContains('commercial', $keys);
        $this->assertNotContains('hrms', $keys);
        $this->assertNotContains('projects', $keys);
        $this->assertNotContains('marketing', $keys);
    }

    public function test_professional_plan_shows_hrms_when_enabled(): void
    {
        [$organization, $owner] = $this->ownerOnPlan('professional');

        $keys = $this->visibleModuleKeys($owner, $organization);

        $this->assertContains('hrms', $keys);
        $this->assertContains('projects', $keys);
    }

    public function test_hub_renders_attendance_section_when_hrms_is_enabled(): void
    {
        [$organization, $owner] = $this->ownerOnPlan('professional');

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('organization.settings.hub'))
            ->assertOk()
            ->assertSee('Attendance')
            ->assertDontSee('attendance.label');
    }

    public function test_disabled_hrms_module_is_hidden_from_hub(): void
    {
        [$organization, $owner] = $this->ownerOnPlan('professional');

        OrganizationModule::query()
            ->where('organization_id', $organization->id)
            ->where('module_key', 'hrms')
            ->update(['is_enabled' => false]);

        $organization->refresh();

        $this->assertNotContains('hrms', $this->visibleModuleKeys($owner, $organization));

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('organization.settings.hub'))
            ->assertOk()
            ->assertDontSee('Working Days')
            ->assertSee('Lead Settings');
    }

    public function test_recruitment_sections_require_recruitment_license(): void
    {
        [$organization, $owner] = $this->ownerOnPlan('professional');

        OrganizationModule::query()
            ->where('organization_id', $organization->id)
            ->where('module_key', 'recruitment')
            ->update(['is_enabled' => false]);

        $organization->refresh();
        $sections = collect($this->visibleModules($owner, $organization))
            ->firstWhere('key', 'hrms')['sections'] ?? [];
        $sectionKeys = collect($sections)->pluck('key')->all();

        $this->assertContains('employees', $sectionKeys);
        $this->assertNotContains('recruitment', $sectionKeys);
        $this->assertNotContains('recruitment_portal', $sectionKeys);
    }

    public function test_settings_search_omits_unlicensed_modules(): void
    {
        [$organization, $owner] = $this->starterOwner();

        $results = app(AdminSettingsSearchProvider::class)
            ->search($owner, $organization, 'leave', 10);

        $this->assertTrue($results->isEmpty());

        $crmResults = app(AdminSettingsSearchProvider::class)
            ->search($owner, $organization, 'lead', 10);

        $this->assertTrue(
            $crmResults->contains(fn (array $row) => $row['title'] === 'Lead Settings')
        );
    }

    public function test_settings_search_matches_keywords_and_descriptions(): void
    {
        [$organization, $owner] = $this->starterOwner();

        $results = app(AdminSettingsSearchProvider::class)
            ->search($owner, $organization, 'gst', 10);

        $this->assertTrue(
            $results->contains(fn (array $row) => $row['title'] === 'Tax / GST')
        );
    }

    public function test_sales_user_can_search_visible_crm_settings(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['plan' => 'starter']);
        $organization->addMember($user, 'sales-executive');
        app(TenantContext::class)->set($organization);

        $results = app(AdminSettingsSearchProvider::class)
            ->search($user, $organization->fresh(), 'lead', 10);

        $this->assertTrue(
            $results->contains(fn (array $row) => $row['title'] === 'Lead Settings')
        );
    }

    public function test_hub_records_recently_used_visible_settings(): void
    {
        [$organization, $owner] = $this->starterOwner();

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('organization.settings.commercial-automation.edit'))
            ->assertOk()
            ->assertSee('Configuration Hub');

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('organization.settings.hub'))
            ->assertOk()
            ->assertSee('Recently used')
            ->assertSee('Automation');
    }

    public function test_starter_plan_cannot_open_hrms_settings_directly(): void
    {
        [$organization, $owner] = $this->starterOwner();

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('organization.settings.working-days.edit'))
            ->assertForbidden();
    }

    public function test_disabled_hrms_hides_working_days_from_recent_and_direct_route(): void
    {
        [$organization, $owner] = $this->ownerOnPlan('professional');

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('organization.settings.working-days.edit'))
            ->assertOk();

        OrganizationModule::query()
            ->where('organization_id', $organization->id)
            ->where('module_key', 'hrms')
            ->update(['is_enabled' => false]);

        $organization->refresh();

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('organization.settings.working-days.edit'))
            ->assertForbidden();

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('organization.settings.hub'))
            ->assertOk()
            ->assertDontSee('Working Days');
    }

    /**
     * @return array{0: Organization, 1: User}
     */
    private function starterOwner(): array
    {
        return $this->ownerOnPlan('starter');
    }

    /**
     * @return array{0: Organization, 1: User}
     */
    private function ownerOnPlan(string $plan): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['plan' => $plan]);
        $organization->addMember($user, 'organization-owner');
        app(TenantContext::class)->set($organization);

        return [$organization->fresh(), $user];
    }

    /**
     * @return list<string>
     */
    private function visibleModuleKeys(User $user, Organization $organization): array
    {
        return collect($this->visibleModules($user, $organization))->pluck('key')->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function visibleModules(User $user, Organization $organization): array
    {
        return app(ConfigurationRegistry::class)->visibleModules($user, $organization);
    }
}
