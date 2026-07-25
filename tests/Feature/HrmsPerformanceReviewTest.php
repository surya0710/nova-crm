<?php

namespace Tests\Feature;

use App\Events\PerformanceReviewAssigned;
use App\Events\PerformanceReviewClosed;
use App\Events\PerformanceReviewReviewed;
use App\Events\PerformanceReviewStarted;
use App\Events\PerformanceReviewSubmitted;
use App\Models\Competency;
use App\Models\CompetencyCategory;
use App\Models\Employee;
use App\Models\Goal;
use App\Models\Kpi;
use App\Models\Organization;
use App\Models\PerformanceConfiguration;
use App\Models\PerformanceCycle;
use App\Models\PerformanceRatingScale;
use App\Models\PerformanceRatingScaleLevel;
use App\Models\PerformanceReview;
use App\Models\PerformanceReviewAssignment;
use App\Models\PerformanceReviewTemplate;
use App\Models\PerformanceReviewTemplateCompetency;
use App\Models\Permission;
use App\Models\User;
use App\Services\Hrms\PerformanceReviewService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HrmsPerformanceReviewTest extends TestCase
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

    public function test_performance_review_tables_exist(): void
    {
        foreach ([
            'performance_review_assignments',
            'performance_reviews',
            'performance_review_competency_evaluations',
            'performance_review_goal_evaluations',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_review_permissions_are_seeded_for_roles(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();

        foreach (['performance.review.view', 'performance.review.manage', 'performance.review.submit'] as $slug) {
            $this->assertNotNull(Permission::query()->where('slug', $slug)->first(), "Missing permission: {$slug}");
            $this->assertTrue($hr->hasPermission($slug, $organization));
        }

        $manager = User::factory()->create();
        $organization->addMember($manager, 'manager');
        $this->assertTrue($manager->hasPermission('performance.review.view', $organization));
        $this->assertTrue($manager->hasPermission('performance.review.submit', $organization));
        $this->assertFalse($manager->hasPermission('performance.review.manage', $organization));

        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');
        $this->assertTrue($employee->hasPermission('performance.review.view', $organization));
        $this->assertTrue($employee->hasPermission('performance.review.submit', $organization));
        $this->assertFalse($employee->hasPermission('performance.review.manage', $organization));
    }

    public function test_assignment_generation_creates_review_snapshot_and_evaluations(): void
    {
        Event::fake([PerformanceReviewAssigned::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        app(TenantContext::class)->set($organization);

        $ctx = $this->seedReviewContext($organization);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.performance.review-assignments.store'), [
            'performance_cycle_id' => $ctx['cycle']->id,
            'employee_id' => $ctx['employee']->id,
            'review_template_id' => $ctx['template']->id,
            'primary_reviewer_id' => $ctx['managerEmployee']->id,
            'review_type' => 'manager',
            'due_date' => '2026-08-15',
            'status' => 'assigned',
        ])->assertRedirect(route('hrms.performance.review-assignments.index'));

        $assignment = PerformanceReviewAssignment::query()->firstOrFail();
        $this->assertSame('assigned', $assignment->status);
        $this->assertSame($ctx['managerEmployee']->id, $assignment->primary_reviewer_id);

        $review = $assignment->review;
        $this->assertNotNull($review);
        $this->assertSame('draft', $review->status);
        $this->assertNotNull($review->snapshot);
        $this->assertNotNull($review->snapshot_hash);
        $this->assertSame('Annual Review Template', $review->snapshot['template']['name']);
        $this->assertCount(1, $review->competencyEvaluations);
        $this->assertCount(1, $review->goalEvaluations);
        $this->assertSame('Close Deals', $review->goalEvaluations->first()->goal_title);
        $this->assertSame(40.0, (float) $review->goalEvaluations->first()->target_value);
        $this->assertDatabaseHas('audit_logs', ['event' => 'performance_review_assignment_created']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'performance_review_assigned']);
        Event::assertDispatched(PerformanceReviewAssigned::class);
    }

    public function test_self_review_draft_and_submission_workflow(): void
    {
        Event::fake([
            PerformanceReviewAssigned::class,
            PerformanceReviewSubmitted::class,
        ]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        app(TenantContext::class)->set($organization);

        $ctx = $this->seedReviewContext($organization);
        $employeeUser = User::factory()->create();
        $organization->addMember($employeeUser, 'employee');
        $ctx['employee']->update(['user_id' => $employeeUser->id]);

        $service = app(PerformanceReviewService::class);
        $assignment = $service->createAssignment([
            'performance_cycle_id' => $ctx['cycle']->id,
            'employee_id' => $ctx['employee']->id,
            'review_template_id' => $ctx['template']->id,
            'review_type' => 'self',
            'due_date' => '2026-08-20',
            'status' => 'assigned',
        ], $hr);

        $review = $assignment->review;
        $this->assertSame('self', $review->review_type);
        $this->assertSame($ctx['employee']->id, $review->reviewer_id);

        $empSession = ['current_organization_id' => $organization->id];
        $evaluation = $review->competencyEvaluations->first();

        $this->actingAs($employeeUser)->withSession($empSession)->post(route('hrms.performance.reviews.draft', $review), [
            'overall_comments' => 'Solid quarter',
            'strengths' => 'Ownership',
            'improvement_areas' => 'Documentation',
            'competency_evaluations' => [
                ['id' => $evaluation->id, 'rating' => 4, 'comments' => 'Strong', 'reviewer_notes' => 'Keep going'],
            ],
            'goal_evaluations' => [
                ['id' => $review->goalEvaluations->first()->id, 'comments' => 'On track'],
            ],
        ])->assertRedirect(route('hrms.performance.reviews.show', $review));

        $review->refresh();
        $this->assertSame('draft', $review->status);
        $this->assertSame('Solid quarter', $review->overall_comments);
        $this->assertSame(4.0, (float) $review->competencyEvaluations()->first()->rating);
        $this->assertDatabaseHas('audit_logs', ['event' => 'performance_review_draft_saved']);

        $this->actingAs($employeeUser)->withSession($empSession)->post(route('hrms.performance.reviews.submit', $review), [
            'overall_comments' => 'Solid quarter final',
            'competency_evaluations' => [
                ['id' => $evaluation->id, 'rating' => 4, 'comments' => 'Strong'],
            ],
        ])->assertRedirect(route('hrms.performance.reviews.show', $review));

        $review->refresh();
        $this->assertSame('submitted', $review->status);
        $this->assertNotNull($review->submitted_at);
        $this->assertSame('submitted', $review->assignment->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'performance_review_submitted']);
        Event::assertDispatched(PerformanceReviewSubmitted::class);

        $this->actingAs($employeeUser)->withSession($empSession)->post(route('hrms.performance.reviews.draft', $review), [
            'overall_comments' => 'Should fail',
        ])->assertSessionHasErrors();
    }

    public function test_manager_review_lifecycle_to_closed(): void
    {
        Event::fake([
            PerformanceReviewAssigned::class,
            PerformanceReviewStarted::class,
            PerformanceReviewSubmitted::class,
            PerformanceReviewReviewed::class,
            PerformanceReviewClosed::class,
        ]);

        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);
        $session = ['current_organization_id' => $organization->id];

        $ctx = $this->seedReviewContext($organization);
        $managerUser = User::factory()->create();
        $organization->addMember($managerUser, 'manager');
        $ctx['managerEmployee']->update(['user_id' => $managerUser->id]);

        $service = app(PerformanceReviewService::class);
        $assignment = $service->createAssignment([
            'performance_cycle_id' => $ctx['cycle']->id,
            'employee_id' => $ctx['employee']->id,
            'review_template_id' => $ctx['template']->id,
            'primary_reviewer_id' => $ctx['managerEmployee']->id,
            'review_type' => 'manager',
            'status' => 'assigned',
        ], $hr);

        $review = $assignment->review;
        $evaluation = $review->competencyEvaluations->first();

        $this->actingAs($managerUser)->withSession($session)->post(route('hrms.performance.reviews.start', $review))
            ->assertRedirect(route('hrms.performance.reviews.show', $review));

        $review->refresh();
        $this->assertSame('in_progress', $review->status);
        $this->assertSame('in_progress', $review->assignment->status);
        Event::assertDispatched(PerformanceReviewStarted::class);

        $this->actingAs($managerUser)->withSession($session)->post(route('hrms.performance.reviews.submit', $review), [
            'overall_comments' => 'Ready for close',
            'strengths' => 'Delivery',
            'improvement_areas' => 'Communication',
            'competency_evaluations' => [
                ['id' => $evaluation->id, 'rating' => 5, 'comments' => 'Excellent', 'reviewer_notes' => 'Promo later'],
            ],
        ])->assertRedirect(route('hrms.performance.reviews.show', $review));

        $review->refresh();
        $this->assertSame('submitted', $review->status);

        $this->actingAs($managerUser)->withSession($session)->post(route('hrms.performance.reviews.reviewed', $review))
            ->assertRedirect(route('hrms.performance.reviews.show', $review));

        $review->refresh();
        $this->assertSame('reviewed', $review->status);
        Event::assertDispatched(PerformanceReviewReviewed::class);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.performance.reviews.close', $review))
            ->assertRedirect(route('hrms.performance.reviews.show', $review));

        $review->refresh();
        $this->assertSame('closed', $review->status);
        $this->assertSame('closed', $review->assignment->status);
        $this->assertFalse($review->isEditable());
        $this->assertTrue($review->assignment->isImmutable());
        Event::assertDispatched(PerformanceReviewClosed::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'performance_review_closed']);
    }

    public function test_goal_snapshot_is_immutable_after_live_goal_changes(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $ctx = $this->seedReviewContext($organization);
        $service = app(PerformanceReviewService::class);

        $assignment = $service->createAssignment([
            'performance_cycle_id' => $ctx['cycle']->id,
            'employee_id' => $ctx['employee']->id,
            'review_template_id' => $ctx['template']->id,
            'primary_reviewer_id' => $ctx['managerEmployee']->id,
            'review_type' => 'manager',
            'status' => 'assigned',
        ], $hr);

        $review = $assignment->review;
        $snapshottedAchievement = (float) $review->goalEvaluations->first()->achievement_percentage;
        $this->assertSame(50.0, $snapshottedAchievement);

        $ctx['goal']->update([
            'title' => 'Changed Live Title',
            'current_value' => 40,
            'achievement_percentage' => 100,
        ]);

        $review->refresh();
        $evaluation = $review->goalEvaluations()->first();
        $this->assertSame('Close Deals', $evaluation->goal_title);
        $this->assertSame(50.0, (float) $evaluation->achievement_percentage);
        $this->assertSame(20.0, (float) $evaluation->current_value);
        $this->assertSame('Close Deals', $review->snapshot['goals'][0]['title']);
    }

    public function test_submission_requires_competency_ratings(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $ctx = $this->seedReviewContext($organization);
        $service = app(PerformanceReviewService::class);
        $assignment = $service->createAssignment([
            'performance_cycle_id' => $ctx['cycle']->id,
            'employee_id' => $ctx['employee']->id,
            'review_template_id' => $ctx['template']->id,
            'primary_reviewer_id' => $ctx['managerEmployee']->id,
            'review_type' => 'manager',
            'status' => 'assigned',
        ], $hr);

        $this->expectException(ValidationException::class);
        $service->submitReview($assignment->review, [
            'overall_comments' => 'Incomplete',
        ], $hr);
    }

    public function test_rbac_blocks_employee_from_managing_assignments(): void
    {
        [$organization] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        app(TenantContext::class)->set($organization);

        $ctx = $this->seedReviewContext($organization);
        $employeeUser = User::factory()->create();
        $organization->addMember($employeeUser, 'employee');

        $this->actingAs($employeeUser)->withSession($session)->post(route('hrms.performance.review-assignments.store'), [
            'performance_cycle_id' => $ctx['cycle']->id,
            'employee_id' => $ctx['employee']->id,
            'review_template_id' => $ctx['template']->id,
            'review_type' => 'self',
            'status' => 'assigned',
        ])->assertForbidden();
    }

    public function test_tenant_isolation_for_reviews(): void
    {
        [$orgA, $hrA] = $this->organizationWithHrUser();
        [$orgB, $hrB] = $this->organizationWithHrUser();

        app(TenantContext::class)->set($orgA);
        $ctxA = $this->seedReviewContext($orgA);
        $assignmentA = app(PerformanceReviewService::class)->createAssignment([
            'performance_cycle_id' => $ctxA['cycle']->id,
            'employee_id' => $ctxA['employee']->id,
            'review_template_id' => $ctxA['template']->id,
            'primary_reviewer_id' => $ctxA['managerEmployee']->id,
            'review_type' => 'manager',
            'status' => 'assigned',
        ], $hrA);

        app(TenantContext::class)->set($orgB);
        $ctxB = $this->seedReviewContext($orgB);
        $assignmentB = app(PerformanceReviewService::class)->createAssignment([
            'performance_cycle_id' => $ctxB['cycle']->id,
            'employee_id' => $ctxB['employee']->id,
            'review_template_id' => $ctxB['template']->id,
            'primary_reviewer_id' => $ctxB['managerEmployee']->id,
            'review_type' => 'manager',
            'status' => 'assigned',
        ], $hrB);

        app(TenantContext::class)->set($orgA);
        $this->assertSame(1, PerformanceReview::query()->count());

        $this->actingAs($hrA)->withSession(['current_organization_id' => $orgA->id])
            ->get(route('hrms.performance.reviews.show', $assignmentB->review))
            ->assertNotFound();

        $this->actingAs($hrA)->withSession(['current_organization_id' => $orgA->id])
            ->get(route('hrms.performance.review-assignments.show', $assignmentB))
            ->assertNotFound();

        $this->actingAs($hrA)->withSession(['current_organization_id' => $orgA->id])
            ->get(route('hrms.performance.reviews.index'))
            ->assertOk()
            ->assertSee($ctxA['employee']->first_name)
            ->assertDontSee($ctxB['employee']->first_name);
    }

    public function test_my_reviews_and_team_reviews_pages(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);
        $session = ['current_organization_id' => $organization->id];

        $ctx = $this->seedReviewContext($organization);
        $employeeUser = User::factory()->create();
        $organization->addMember($employeeUser, 'employee');
        $ctx['employee']->update(['user_id' => $employeeUser->id]);

        $managerUser = User::factory()->create();
        $organization->addMember($managerUser, 'manager');
        $ctx['managerEmployee']->update(['user_id' => $managerUser->id]);

        $service = app(PerformanceReviewService::class);
        $service->createAssignment([
            'performance_cycle_id' => $ctx['cycle']->id,
            'employee_id' => $ctx['employee']->id,
            'review_template_id' => $ctx['template']->id,
            'review_type' => 'self',
            'status' => 'assigned',
        ], $hr);
        $service->createAssignment([
            'performance_cycle_id' => $ctx['cycle']->id,
            'employee_id' => $ctx['employee']->id,
            'review_template_id' => $ctx['template']->id,
            'primary_reviewer_id' => $ctx['managerEmployee']->id,
            'review_type' => 'manager',
            'status' => 'assigned',
        ], $hr);

        $this->actingAs($employeeUser)->withSession($session)
            ->get(route('hrms.performance.my-reviews'))
            ->assertOk()
            ->assertSee('Annual Review Template');

        $this->actingAs($managerUser)->withSession($session)
            ->get(route('hrms.performance.team-reviews'))
            ->assertOk()
            ->assertSee($ctx['employee']->first_name);
    }

    /**
     * @return array{
     *     cycle: PerformanceCycle,
     *     employee: Employee,
     *     managerEmployee: Employee,
     *     template: PerformanceReviewTemplate,
     *     goal: Goal,
     *     scale: PerformanceRatingScale
     * }
     */
    private function seedReviewContext(Organization $organization): array
    {
        $cycle = PerformanceCycle::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'active',
            'name' => 'FY 2026',
        ]);

        $scale = PerformanceRatingScale::factory()->create([
            'organization_id' => $organization->id,
            'is_default' => true,
            'is_active' => true,
            'name' => 'Standard 1-5',
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

        $managerEmployee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Manager'.$organization->id,
            'last_name' => 'One',
        ]);

        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Alex'.$organization->id,
            'last_name' => 'Employee',
            'reporting_manager_id' => $managerEmployee->id,
        ]);

        $category = CompetencyCategory::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $competency = Competency::factory()->create([
            'organization_id' => $organization->id,
            'competency_category_id' => $category->id,
            'name' => 'Communication',
            'code' => 'COMM',
        ]);

        $template = PerformanceReviewTemplate::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Annual Review Template',
            'code' => 'ART',
            'is_active' => true,
        ]);

        PerformanceReviewTemplateCompetency::query()->create([
            'organization_id' => $organization->id,
            'review_template_id' => $template->id,
            'competency_id' => $competency->id,
            'weightage' => 100,
            'sort_order' => 1,
        ]);

        $kpi = Kpi::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Deals Closed',
            'code' => 'DEALS',
            'measurement_type' => 'numeric',
            'default_target' => 40,
        ]);

        $goal = Goal::factory()->create([
            'organization_id' => $organization->id,
            'performance_cycle_id' => $cycle->id,
            'employee_id' => $employee->id,
            'assignee_type' => 'employee',
            'kpi_id' => $kpi->id,
            'title' => 'Close Deals',
            'target_value' => 40,
            'current_value' => 20,
            'achievement_percentage' => 50,
            'weight' => 100,
            'status' => 'in_progress',
        ]);

        return compact('cycle', 'employee', 'managerEmployee', 'template', 'goal', 'scale');
    }

    private function organizationWithHrUser(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

        return [$organization, $user];
    }
}
