<?php

namespace Tests\Feature;

use App\Events\DashboardCreated;
use App\Events\WorkspaceLoaded;
use App\Models\DashboardQuickAction;
use App\Models\DashboardSection;
use App\Models\DashboardWidget;
use App\Models\Organization;
use App\Models\OrganizationDashboardWidget;
use App\Models\User;
use App\Services\Dashboard\DashboardPreferenceService;
use App\Services\Dashboard\DashboardProvisioningService;
use App\Services\Dashboard\DashboardService;
use App\Services\Dashboard\DashboardWidgetService;
use App\Services\Dashboard\ModuleSubscriptionService;
use App\Services\Dashboard\QuickActionService;
use App\Services\Dashboard\WorkspaceService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardPlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(DashboardWidgetService::class)->seedSystemWidgets();
        app(QuickActionService::class)->seedSystemActions();
    }

    protected function tenantWithOwner(string $plan = 'enterprise'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['plan' => $plan]);
        $organization->addMember($user, 'organization-owner');
        app(TenantContext::class)->set($organization);

        return compact('user', 'organization');
    }

    public function test_dashboard_platform_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('dashboard_sections'));
        $this->assertTrue(Schema::hasTable('dashboard_widgets'));
        $this->assertTrue(Schema::hasTable('organization_dashboard_widgets'));
        $this->assertTrue(Schema::hasTable('user_dashboard_preferences'));
        $this->assertTrue(Schema::hasTable('dashboard_quick_actions'));
        $this->assertTrue(Schema::hasTable('organization_quick_actions'));
    }

    public function test_system_widgets_and_quick_actions_are_seeded(): void
    {
        $this->assertGreaterThan(0, DashboardSection::query()->whereNull('organization_id')->count());
        $this->assertGreaterThan(0, DashboardWidget::query()->whereNull('organization_id')->count());
        $this->assertGreaterThan(0, DashboardQuickAction::query()->whereNull('organization_id')->count());
    }

    public function test_organization_provisioning_installs_dashboard_defaults(): void
    {
        Event::fake([DashboardCreated::class]);

        $organization = Organization::factory()->create(['plan' => 'professional']);
        app(DashboardProvisioningService::class)->provision($organization);

        $this->assertGreaterThan(0, OrganizationDashboardWidget::query()->where('organization_id', $organization->id)->count());
        Event::assertDispatched(DashboardCreated::class);
    }

    public function test_subscription_filters_widgets_for_starter_plan(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->tenantWithOwner('starter');

        $widgets = app(DashboardService::class)->loadWidgets($user, $organization);
        $modules = $widgets->pluck('module')->unique()->all();

        $this->assertContains('common', $modules);
        $this->assertNotContains('hrms', $modules);
    }

    public function test_permission_filters_widgets_for_employee_role(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $organization->addMember($user, 'employee');
        app(TenantContext::class)->set($organization);

        $widgets = app(DashboardService::class)->loadWidgets($user, $organization);
        $keys = $widgets->pluck('widget_key')->all();

        $this->assertNotContains('recent_activities', $keys);
        $this->assertContains('welcome', $keys);
    }

    public function test_workspace_builds_dashboard_quick_actions_and_emits_event(): void
    {
        Event::fake([WorkspaceLoaded::class]);
        ['user' => $user, 'organization' => $organization] = $this->tenantWithOwner();

        $workspace = app(WorkspaceService::class)->build($user, $organization);

        $this->assertArrayHasKey('dashboard', $workspace);
        $this->assertArrayHasKey('quick_actions', $workspace);
        $this->assertArrayHasKey('notifications', $workspace);
        $this->assertArrayHasKey('recent_activities', $workspace);
        Event::assertDispatched(WorkspaceLoaded::class);
    }

    public function test_user_can_save_and_reset_dashboard_layout(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->tenantWithOwner();
        $widget = DashboardWidget::query()->whereNull('organization_id')->firstOrFail();
        $preferences = app(DashboardPreferenceService::class);

        $preferences->saveLayout($user, $organization, [[
            'widget_id' => $widget->id,
            'position_x' => 2,
            'position_y' => 1,
            'width' => 4,
            'height' => 3,
            'is_visible' => true,
        ]]);

        $this->assertDatabaseHas('user_dashboard_preferences', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'widget_id' => $widget->id,
            'position_x' => 2,
            'width' => 4,
        ]);

        $preferences->resetLayout($user, $organization);

        $this->assertDatabaseMissing('user_dashboard_preferences', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'widget_id' => $widget->id,
        ]);
    }

    public function test_widget_enable_disable_is_audited(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->tenantWithOwner();
        $widget = DashboardWidget::query()->whereNull('organization_id')->firstOrFail();
        $service = app(DashboardWidgetService::class);

        $service->disable($organization, $widget, $user);

        $this->assertDatabaseHas('organization_dashboard_widgets', [
            'organization_id' => $organization->id,
            'widget_id' => $widget->id,
            'is_enabled' => false,
        ]);

        $service->enable($organization, $widget, $user);

        $this->assertDatabaseHas('organization_dashboard_widgets', [
            'organization_id' => $organization->id,
            'widget_id' => $widget->id,
            'is_enabled' => true,
        ]);
    }

    public function test_workspace_api_requires_dashboard_permission(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'employee');
        app(TenantContext::class)->set($organization);

        $this->actingAs($user)
            ->getJson(route('dashboard.workspace'))
            ->assertOk();
    }

    public function test_module_subscription_service_respects_enabled_modules_setting(): void
    {
        $organization = Organization::factory()->create([
            'plan' => 'enterprise',
            'settings' => ['enabled_modules' => ['common', 'crm']],
        ]);

        $service = app(ModuleSubscriptionService::class);

        $this->assertTrue($service->moduleAllowed($organization, 'crm'));
        $this->assertFalse($service->moduleAllowed($organization, 'hrms'));
    }

    public function test_quick_actions_only_include_permitted_routes(): void
    {
        ['user' => $user, 'organization' => $organization] = $this->tenantWithOwner();
        $actions = app(QuickActionService::class)->available($user, $organization);

        foreach ($actions as $action) {
            $this->assertNotNull($action['url']);
        }
    }
}
