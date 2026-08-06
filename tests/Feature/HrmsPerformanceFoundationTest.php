<?php

namespace Tests\Feature;

use App\Events\PerformanceConfigurationUpdated;
use App\Events\PerformanceCycleActivated;
use App\Events\PerformanceCycleCreated;
use App\Events\PerformanceTemplateCreated;
use App\Models\Competency;
use App\Models\CompetencyCategory;
use App\Models\Organization;
use App\Models\PerformanceConfiguration;
use App\Models\PerformanceCycle;
use App\Models\PerformanceRatingScale;
use App\Models\PerformanceReviewTemplate;
use App\Models\Permission;
use App\Models\User;
use App\Services\Hrms\PerformanceService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HrmsPerformanceFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-20 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_performance_tables_exist(): void
    {
        foreach ([
            'performance_configurations',
            'performance_rating_scales',
            'performance_rating_scale_levels',
            'competency_categories',
            'competencies',
            'performance_cycles',
            'performance_review_templates',
            'performance_review_template_sections',
            'performance_review_template_competencies',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_performance_permissions_are_seeded_for_hr_and_manager(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();

        foreach (['performance.view', 'performance.manage', 'performance.configuration'] as $slug) {
            $this->assertNotNull(Permission::query()->where('slug', $slug)->first(), "Missing permission: {$slug}");
            $this->assertTrue($hr->hasPermission($slug, $organization));
        }

        $manager = User::factory()->create();
        $organization->addMember($manager, 'manager');
        $this->assertTrue($manager->hasPermission('performance.view', $organization));
        $this->assertFalse($manager->hasPermission('performance.manage', $organization));
        $this->assertFalse($manager->hasPermission('performance.configuration', $organization));

        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');
        $this->assertFalse($employee->hasPermission('performance.view', $organization));
        $this->assertFalse($employee->hasPermission('performance.manage', $organization));
    }

    public function test_configuration_update_emits_workflow_and_audit(): void
    {
        Event::fake([PerformanceConfigurationUpdated::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        app(TenantContext::class)->set($organization);

        $scale = PerformanceRatingScale::factory()->create([
            'organization_id' => $organization->id,
            'is_active' => true,
        ]);

        $this->actingAs($hr)->withSession($session)->put(route('hrms.performance.configuration.update'), [
            'default_review_frequency' => 'quarterly',
            'rating_scale_id' => $scale->id,
            'goal_weighting' => 40,
            'competency_weighting' => 60,
            'review_visibility' => 'hr_and_manager',
            'calibration_enabled' => true,
        ])->assertRedirect(route('hrms.performance.configuration.edit'));

        $this->assertDatabaseHas('performance_configurations', [
            'organization_id' => $organization->id,
            'default_review_frequency' => 'quarterly',
            'rating_scale_id' => $scale->id,
            'calibration_enabled' => true,
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'performance_configuration_updated']);
        Event::assertDispatched(PerformanceConfigurationUpdated::class);
    }

    public function test_rating_scale_crud_and_audit(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.performance.rating-scales.store'), [
            'name' => 'Standard 5',
            'code' => 'STD5',
            'is_default' => true,
            'is_active' => true,
            'levels' => [
                ['value' => 1, 'label' => 'Needs Improvement'],
                ['value' => 2, 'label' => 'Developing'],
                ['value' => 3, 'label' => 'Meets Expectations'],
                ['value' => 4, 'label' => 'Exceeds Expectations'],
                ['value' => 5, 'label' => 'Outstanding'],
            ],
        ])->assertRedirect(route('hrms.performance.rating-scales.index'));

        $scale = PerformanceRatingScale::query()->where('code', 'STD5')->firstOrFail();
        $this->assertCount(5, $scale->levels);
        $this->assertDatabaseHas('audit_logs', ['event' => 'performance_rating_scale_created']);

        $this->actingAs($hr)->withSession($session)->put(route('hrms.performance.rating-scales.update', $scale), [
            'name' => 'Standard Five',
            'code' => 'STD5',
            'is_default' => true,
            'is_active' => true,
        ])->assertRedirect(route('hrms.performance.rating-scales.index'));

        $this->assertDatabaseHas('audit_logs', ['event' => 'performance_rating_scale_updated']);
        $this->assertDatabaseHas('performance_rating_scales', ['id' => $scale->id, 'name' => 'Standard Five']);

        $this->actingAs($hr)->withSession($session)->delete(route('hrms.performance.rating-scales.destroy', $scale))
            ->assertRedirect(route('hrms.performance.rating-scales.index'));

        $this->assertSoftDeleted('performance_rating_scales', ['id' => $scale->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'performance_rating_scale_deleted']);
    }

    public function test_competency_category_and_competency_crud(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.performance.categories.store'), [
            'name' => 'Leadership',
            'code' => 'LEAD',
            'is_active' => true,
        ])->assertRedirect(route('hrms.performance.categories.index'));

        $category = CompetencyCategory::query()->where('code', 'LEAD')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['event' => 'competency_category_created']);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.performance.competencies.store'), [
            'competency_category_id' => $category->id,
            'name' => 'Decision Making',
            'code' => 'DECIDE',
            'is_active' => true,
        ])->assertRedirect(route('hrms.performance.competencies.index'));

        $competency = Competency::query()->where('code', 'DECIDE')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['event' => 'competency_created']);
        $this->assertSame($category->id, $competency->competency_category_id);

        $this->actingAs($hr)->withSession($session)->put(route('hrms.performance.competencies.update', $competency), [
            'competency_category_id' => $category->id,
            'name' => 'Decision Making Skills',
            'code' => 'DECIDE',
            'is_active' => true,
        ])->assertRedirect(route('hrms.performance.competencies.index'));

        $this->assertDatabaseHas('audit_logs', ['event' => 'competency_updated']);

        $this->actingAs($hr)->withSession($session)->delete(route('hrms.performance.competencies.destroy', $competency))
            ->assertRedirect(route('hrms.performance.competencies.index'));
        $this->assertSoftDeleted('competencies', ['id' => $competency->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'competency_deleted']);

        $this->actingAs($hr)->withSession($session)->delete(route('hrms.performance.categories.destroy', $category))
            ->assertRedirect(route('hrms.performance.categories.index'));
        $this->assertSoftDeleted('competency_categories', ['id' => $category->id]);
    }

    public function test_cycle_lifecycle_workflow_and_active_resolution(): void
    {
        Event::fake([PerformanceCycleCreated::class, PerformanceCycleActivated::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        app(TenantContext::class)->set($organization);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.performance.cycles.store'), [
            'name' => 'FY 2026 Annual',
            'cycle_type' => 'annual',
            'status' => 'draft',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ])->assertRedirect(route('hrms.performance.cycles.index'));

        $cycle = PerformanceCycle::query()->where('name', 'FY 2026 Annual')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['event' => 'performance_cycle_created']);
        Event::assertDispatched(PerformanceCycleCreated::class);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.performance.cycles.activate', $cycle))
            ->assertRedirect(route('hrms.performance.cycles.index'));

        $this->assertDatabaseHas('performance_cycles', ['id' => $cycle->id, 'status' => 'active']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'performance_cycle_activated']);
        Event::assertDispatched(PerformanceCycleActivated::class);

        $active = app(PerformanceService::class)->resolveActiveCycle();
        $this->assertNotNull($active);
        $this->assertSame($cycle->id, $active->id);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.performance.cycles.close', $cycle))
            ->assertRedirect(route('hrms.performance.cycles.index'));
        $this->assertDatabaseHas('performance_cycles', ['id' => $cycle->id, 'status' => 'closed']);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.performance.cycles.archive', $cycle))
            ->assertRedirect(route('hrms.performance.cycles.index'));
        $this->assertDatabaseHas('performance_cycles', ['id' => $cycle->id, 'status' => 'archived']);
    }

    public function test_template_crud_emits_workflow_event(): void
    {
        Event::fake([PerformanceTemplateCreated::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        $category = CompetencyCategory::factory()->create(['organization_id' => $organization->id]);
        $competency = Competency::factory()->create([
            'organization_id' => $organization->id,
            'competency_category_id' => $category->id,
        ]);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.performance.templates.store'), [
            'name' => 'Annual Review Template',
            'code' => 'ART',
            'instructions' => 'Rate each competency.',
            'is_active' => true,
            'sections' => [
                ['key' => 'core', 'name' => 'Core', 'weightage' => 100],
            ],
            'competencies' => [
                ['competency_id' => $competency->id, 'section_key' => 'core', 'weightage' => 100],
            ],
        ])->assertRedirect(route('hrms.performance.templates.index'));

        $template = PerformanceReviewTemplate::query()->where('code', 'ART')->firstOrFail();
        $this->assertDatabaseHas('performance_review_template_sections', [
            'review_template_id' => $template->id,
            'name' => 'Core',
        ]);
        $this->assertDatabaseHas('performance_review_template_competencies', [
            'review_template_id' => $template->id,
            'competency_id' => $competency->id,
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'performance_template_created']);
        Event::assertDispatched(PerformanceTemplateCreated::class);

        $this->actingAs($hr)->withSession($session)->delete(route('hrms.performance.templates.destroy', $template))
            ->assertRedirect(route('hrms.performance.templates.index'));
        $this->assertSoftDeleted('performance_review_templates', ['id' => $template->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'performance_template_deleted']);
    }

    public function test_tenant_isolation_blocks_cross_organization_access(): void
    {
        [$orgA, $hrA] = $this->organizationWithHrUser();
        [$orgB, $hrB] = $this->organizationWithHrUser();

        CompetencyCategory::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'Org A Leadership',
            'code' => 'ORG-A-LEAD',
        ]);
        CompetencyCategory::factory()->create([
            'organization_id' => $orgB->id,
            'name' => 'Org B Leadership',
            'code' => 'ORG-B-LEAD',
        ]);

        $cycleB = PerformanceCycle::factory()->create(['organization_id' => $orgB->id]);

        app(TenantContext::class)->set($orgA);
        $this->assertSame(1, CompetencyCategory::query()->count());

        $this->actingAs($hrA)->withSession(['current_organization_id' => $orgA->id])
            ->get(route('hrms.performance.categories.index'))
            ->assertOk()
            ->assertSee('Org A Leadership')
            ->assertDontSee('Org B Leadership');

        $this->actingAs($hrA)->withSession(['current_organization_id' => $orgA->id])
            ->post(route('hrms.performance.cycles.activate', $cycleB))
            ->assertNotFound();

        $this->actingAs($hrB)->withSession(['current_organization_id' => $orgB->id])
            ->get(route('hrms.performance.categories.index'))
            ->assertOk()
            ->assertSee('Org B Leadership')
            ->assertDontSee('Org A Leadership');
    }

    public function test_rbac_blocks_employee_and_limits_manager(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];

        $manager = User::factory()->create();
        $organization->addMember($manager, 'manager');
        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');

        $this->actingAs($manager)->withSession($session)
            ->get(route('hrms.performance.index'))
            ->assertOk();

        $this->actingAs($manager)->withSession($session)
            ->get(route('hrms.performance.configuration.edit'))
            ->assertForbidden();

        $this->actingAs($manager)->withSession($session)
            ->post(route('hrms.performance.categories.store'), [
                'name' => 'Technical',
                'code' => 'TECH',
            ])
            ->assertForbidden();

        $this->actingAs($employee)->withSession($session)
            ->get(route('hrms.performance.index'))
            ->assertForbidden();

        $this->actingAs($hr)->withSession($session)
            ->get(route('hrms.performance.configuration.edit'))
            ->assertOk();
    }

    public function test_one_configuration_per_organization(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $first = app(PerformanceService::class)->getOrCreateConfiguration();
        $second = app(PerformanceService::class)->getOrCreateConfiguration();

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, PerformanceConfiguration::query()->where('organization_id', $organization->id)->count());
        $this->assertTrue($hr->hasPermission('performance.configuration', $organization));
    }

    /** @return array{0: Organization, 1: User} */
    private function organizationWithHrUser(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

        return [$organization, $user];
    }
}
