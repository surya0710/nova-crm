<?php

namespace Tests\Feature;

use App\Events\AppraisalCalibrated;
use App\Events\AppraisalClosed;
use App\Events\AppraisalGenerated;
use App\Events\AppraisalSessionCreated;
use App\Events\AppraisalSubmitted;
use App\Events\CompensationRecommended;
use App\Events\PromotionRecommended;
use App\Models\AppraisalSession;
use App\Models\Competency;
use App\Models\CompetencyCategory;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeAppraisal;
use App\Models\Goal;
use App\Models\Organization;
use App\Models\PerformanceConfiguration;
use App\Models\PerformanceCycle;
use App\Models\PerformanceRatingScale;
use App\Models\PerformanceRatingScaleLevel;
use App\Models\PerformanceReviewTemplate;
use App\Models\PerformanceReviewTemplateCompetency;
use App\Models\Permission;
use App\Models\User;
use App\Services\Hrms\AppraisalService;
use App\Services\Hrms\PerformanceReviewService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HrmsAppraisalPlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-21 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_appraisal_tables_exist(): void
    {
        foreach ([
            'appraisal_sessions',
            'employee_appraisals',
            'appraisal_development_plans',
            'appraisal_recommendations',
            'appraisal_calibrations',
            'talent_matrix_entries',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_appraisal_permissions_are_seeded_for_roles(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();

        foreach ([
            'performance.appraisal.view',
            'performance.appraisal.manage',
            'performance.calibration.manage',
            'performance.talent.manage',
        ] as $slug) {
            $this->assertNotNull(Permission::query()->where('slug', $slug)->first(), "Missing permission: {$slug}");
            $this->assertTrue($hr->hasPermission($slug, $organization));
        }

        $manager = User::factory()->create();
        $organization->addMember($manager, 'manager');
        $this->assertTrue($manager->hasPermission('performance.appraisal.view', $organization));
        $this->assertFalse($manager->hasPermission('performance.appraisal.manage', $organization));
        $this->assertFalse($manager->hasPermission('performance.calibration.manage', $organization));

        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');
        $this->assertTrue($employee->hasPermission('performance.appraisal.view', $organization));
        $this->assertFalse($employee->hasPermission('performance.appraisal.manage', $organization));
    }

    public function test_session_lifecycle_and_appraisal_generation(): void
    {
        Event::fake([
            AppraisalSessionCreated::class,
            AppraisalGenerated::class,
        ]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        app(TenantContext::class)->set($organization);

        $ctx = $this->seedAppraisalContext($organization);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.performance.appraisal-sessions.store'), [
            'performance_cycle_id' => $ctx['cycle']->id,
            'name' => 'FY2026 Appraisal',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'rating_weights' => [
                'goals' => 40,
                'competencies' => 30,
                'manager_review' => 20,
                'feedback_360' => 10,
            ],
        ])->assertRedirect();

        $appraisalSession = AppraisalSession::query()->firstOrFail();
        $this->assertSame('draft', $appraisalSession->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'appraisal_session_created']);
        Event::assertDispatched(AppraisalSessionCreated::class);

        app(AppraisalService::class)->activateSession($appraisalSession, $hr);
        $appraisalSession->refresh();
        $this->assertSame('active', $appraisalSession->status);

        $generated = app(AppraisalService::class)->generateAppraisals($appraisalSession, [$ctx['employee']->id], $hr);
        $this->assertCount(1, $generated);

        $appraisal = EmployeeAppraisal::query()->firstOrFail();
        $this->assertSame('generated', $appraisal->status);
        $this->assertNotNull($appraisal->manager_rating);
        $this->assertNotNull($appraisal->rating_breakdown);
        $this->assertNotNull($appraisal->developmentPlan);
        $this->assertDatabaseHas('audit_logs', ['event' => 'employee_appraisal_generated']);
        Event::assertDispatched(AppraisalGenerated::class);
    }

    public function test_rating_calculation_uses_configurable_weights(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $ctx = $this->seedAppraisalContext($organization);

        $service = app(AppraisalService::class);
        $result = $service->calculateRatingForEmployee(
            $ctx['employee']->id,
            $ctx['cycle']->id,
            ['goals' => 40, 'competencies' => 30, 'manager_review' => 20, 'feedback_360' => 10]
        );

        $this->assertNotNull($result['score']);
        $this->assertArrayHasKey('goals', $result['breakdown']);
        $this->assertArrayHasKey('competencies', $result['breakdown']);
        $this->assertSame(40.0, $result['breakdown']['goals']['weight']);
        $this->assertNotNull($result['breakdown']['goals']['score']);
    }

    public function test_invalid_rating_weights_are_rejected(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $this->expectException(ValidationException::class);

        app(AppraisalService::class)->createSession([
            'performance_cycle_id' => PerformanceCycle::factory()->create(['organization_id' => $organization->id])->id,
            'name' => 'Invalid Weights',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'rating_weights' => ['goals' => 50, 'competencies' => 30],
        ], $hr);
    }

    public function test_development_plan_and_recommendations(): void
    {
        Event::fake([PromotionRecommended::class, CompensationRecommended::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $ctx = $this->seedAppraisalContext($organization);
        $designation = Designation::factory()->create(['organization_id' => $organization->id]);

        $service = app(AppraisalService::class);
        $session = $service->createSession([
            'performance_cycle_id' => $ctx['cycle']->id,
            'name' => 'Recommendations Session',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ], $hr);
        $service->activateSession($session, $hr);
        $appraisal = $service->generateAppraisals($session, [$ctx['employee']->id], $hr)->first();

        $plan = $service->updateDevelopmentPlan($appraisal, [
            'strengths' => 'Leadership',
            'improvement_areas' => 'Delegation',
            'learning_objectives' => 'Advanced management',
            'target_completion_date' => '2026-12-31',
        ], $hr);

        $this->assertSame('Leadership', $plan->strengths);
        $this->assertDatabaseHas('audit_logs', ['event' => 'appraisal_development_plan_updated']);

        $promotion = $service->savePromotionRecommendation($appraisal, [
            'promotion_recommendation' => 'recommended',
            'target_designation_id' => $designation->id,
            'effective_date' => '2027-01-01',
            'justification' => 'Strong performance',
        ], $hr);

        $this->assertSame('promotion', $promotion->recommendation_type);
        $this->assertDatabaseHas('audit_logs', ['event' => 'promotion_recommendation_saved']);
        Event::assertDispatched(PromotionRecommended::class);

        $compensation = $service->saveCompensationRecommendation($appraisal, [
            'increment_percent' => 12.5,
            'bonus_recommendation' => 50000,
            'adjustment_notes' => 'Market alignment',
        ], $hr);

        $this->assertSame('compensation', $compensation->recommendation_type);
        $this->assertSame('12.50', $compensation->increment_percent);
        Event::assertDispatched(CompensationRecommended::class);

        $succession = $service->saveSuccessionRecommendation($appraisal, [
            'critical_role_flag' => true,
            'readiness_level' => 'ready_in_1_year',
            'succession_notes' => 'Backup for team lead',
        ], $hr);

        $this->assertSame('succession', $succession->recommendation_type);
        $this->assertTrue($succession->critical_role_flag);
    }

    public function test_calibration_preserves_manager_rating(): void
    {
        Event::fake([AppraisalCalibrated::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $ctx = $this->seedAppraisalContext($organization);
        $service = app(AppraisalService::class);

        $session = $service->createSession([
            'performance_cycle_id' => $ctx['cycle']->id,
            'name' => 'Calibration Session Test',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ], $hr);
        $service->activateSession($session, $hr);
        $appraisal = $service->generateAppraisals($session, [$ctx['employee']->id], $hr)->first();
        $originalRating = $appraisal->manager_rating;

        $calibration = $service->createCalibration($session, ['name' => 'Leadership Calibration'], $hr);
        $service->applyCalibrationAdjustments($calibration, [[
            'employee_appraisal_id' => $appraisal->id,
            'final_rating' => 4.5,
            'comments' => 'Adjusted after panel review',
        ]], $hr);

        $appraisal->refresh();
        $this->assertSame((string) $originalRating, (string) $appraisal->manager_rating);
        $this->assertSame('4.50', $appraisal->calibrated_rating);
        $this->assertSame('calibrated', $appraisal->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'employee_appraisal_calibrated']);

        $service->approveCalibration($calibration, ['session_comments' => 'Approved by HR leadership'], $hr);
        Event::assertDispatched(AppraisalCalibrated::class);
    }

    public function test_talent_matrix_classification(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $ctx = $this->seedAppraisalContext($organization);
        $service = app(AppraisalService::class);

        $session = $service->createSession([
            'performance_cycle_id' => $ctx['cycle']->id,
            'name' => 'Talent Matrix Session',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ], $hr);
        $service->activateSession($session, $hr);
        $appraisal = $service->generateAppraisals($session, [$ctx['employee']->id], $hr)->first();

        $entry = $service->classifyTalent($appraisal, [
            'performance_band' => 3,
            'potential_band' => 3,
        ], $hr);

        $this->assertSame('Future Leader', $entry->classification);
        $this->assertDatabaseHas('talent_matrix_entries', [
            'employee_id' => $ctx['employee']->id,
            'classification' => 'Future Leader',
        ]);

        $matrix = $service->buildTalentMatrix($session);
        $this->assertArrayHasKey('3-3', $matrix['cells']);
        $this->assertCount(1, $matrix['cells']['3-3']);
    }

    public function test_appraisal_submission_and_closure_workflow(): void
    {
        Event::fake([AppraisalSubmitted::class, AppraisalClosed::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $ctx = $this->seedAppraisalContext($organization);
        $service = app(AppraisalService::class);

        $session = $service->createSession([
            'performance_cycle_id' => $ctx['cycle']->id,
            'name' => 'Closure Session',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ], $hr);
        $service->activateSession($session, $hr);
        $appraisal = $service->generateAppraisals($session, [$ctx['employee']->id], $hr)->first();

        $service->submitAppraisal($appraisal, [
            'overall_summary' => 'Exceeded expectations',
            'manager_recommendation' => 'Retain and develop',
        ], $hr);

        $appraisal->refresh();
        $this->assertSame('submitted', $appraisal->status);
        Event::assertDispatched(AppraisalSubmitted::class);

        $service->hrReviewAppraisal($appraisal, ['hr_recommendation' => 'Approve recommendations'], $hr);
        $service->closeAppraisal($appraisal, $hr);

        $appraisal->refresh();
        $this->assertSame('closed', $appraisal->status);
        $this->assertNotNull($appraisal->closed_at);
        Event::assertDispatched(AppraisalClosed::class);

        $this->expectException(ValidationException::class);
        $service->updateAppraisal($appraisal, ['final_comments' => 'Should fail'], $hr);
    }

    public function test_tenant_isolation_for_appraisal_sessions(): void
    {
        [$orgA, $hrA] = $this->organizationWithHrUser();
        [$orgB] = $this->organizationWithHrUser();

        app(TenantContext::class)->set($orgA);
        $cycleA = PerformanceCycle::factory()->create(['organization_id' => $orgA->id, 'status' => 'active']);

        $sessionA = app(AppraisalService::class)->createSession([
            'performance_cycle_id' => $cycleA->id,
            'name' => 'Org A Session',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ], $hrA);

        app(TenantContext::class)->set($orgB);
        $this->assertNull(AppraisalSession::query()->find($sessionA->id));
    }

    /**
     * @return array{cycle: PerformanceCycle, employee: Employee, managerEmployee: Employee, goal: Goal}
     */
    private function seedAppraisalContext(Organization $organization): array
    {
        $scale = PerformanceRatingScale::factory()->create([
            'organization_id' => $organization->id,
            'is_default' => true,
            'is_active' => true,
        ]);

        foreach ([
            [1, 'Needs Improvement'],
            [2, 'Developing'],
            [3, 'Meets Expectations'],
            [4, 'Exceeds Expectations'],
            [5, 'Outstanding'],
        ] as $index => [$value, $label]) {
            PerformanceRatingScaleLevel::query()->create([
                'organization_id' => $organization->id,
                'rating_scale_id' => $scale->id,
                'value' => $value,
                'label' => $label,
                'sort_order' => $index + 1,
            ]);
        }

        PerformanceConfiguration::factory()->create([
            'organization_id' => $organization->id,
            'rating_scale_id' => $scale->id,
        ]);

        $cycle = PerformanceCycle::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        $managerEmployee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'active',
            'reporting_manager_id' => $managerEmployee->id,
        ]);

        $category = CompetencyCategory::factory()->create(['organization_id' => $organization->id]);
        $competency = Competency::factory()->create([
            'organization_id' => $organization->id,
            'competency_category_id' => $category->id,
        ]);

        $template = PerformanceReviewTemplate::factory()->create([
            'organization_id' => $organization->id,
            'is_active' => true,
        ]);

        PerformanceReviewTemplateCompetency::query()->create([
            'organization_id' => $organization->id,
            'review_template_id' => $template->id,
            'competency_id' => $competency->id,
            'weightage' => 100,
            'sort_order' => 1,
        ]);

        $goal = Goal::factory()->create([
            'organization_id' => $organization->id,
            'performance_cycle_id' => $cycle->id,
            'employee_id' => $employee->id,
            'assignee_type' => 'employee',
            'target_value' => 100,
            'current_value' => 80,
            'achievement_percentage' => 80,
            'weight' => 100,
            'status' => 'in_progress',
        ]);

        $hr = User::factory()->create();
        $organization->addMember($hr, 'hr');

        $reviewService = app(PerformanceReviewService::class);
        $assignment = $reviewService->createAssignment([
            'performance_cycle_id' => $cycle->id,
            'employee_id' => $employee->id,
            'review_template_id' => $template->id,
            'primary_reviewer_id' => $managerEmployee->id,
            'review_type' => 'manager',
            'status' => 'assigned',
        ], $hr);

        $review = $assignment->review;
        $evaluation = $review->competencyEvaluations->first();
        $reviewService->saveDraft($review, [
            'competency_evaluations' => [
                ['id' => $evaluation->id, 'rating' => 4, 'comments' => 'Strong'],
            ],
        ], $hr);
        $reviewService->submitReview($review, [
            'competency_evaluations' => [
                ['id' => $evaluation->id, 'rating' => 4],
            ],
        ], $hr);

        return compact('cycle', 'employee', 'managerEmployee', 'goal');
    }

    private function organizationWithHrUser(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

        return [$organization, $user];
    }
}
