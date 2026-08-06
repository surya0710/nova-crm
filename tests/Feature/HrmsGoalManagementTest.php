<?php

namespace Tests\Feature;

use App\Events\GoalAssigned;
use App\Events\GoalCancelled;
use App\Events\GoalCompleted;
use App\Events\GoalCreated;
use App\Events\GoalProgressUpdated;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Goal;
use App\Models\GoalCategory;
use App\Models\GoalCheckin;
use App\Models\GoalProgressUpdate;
use App\Models\GoalTemplate;
use App\Models\HrmsTeam;
use App\Models\Kpi;
use App\Models\Organization;
use App\Models\PerformanceCycle;
use App\Models\Permission;
use App\Models\User;
use App\Services\Hrms\GoalManagementService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HrmsGoalManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-21 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_goal_management_tables_exist(): void
    {
        foreach ([
            'goal_categories',
            'goal_templates',
            'kpis',
            'goals',
            'goal_progress_updates',
            'goal_checkins',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_goal_permissions_are_seeded_for_roles(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();

        foreach (['performance.goal.view', 'performance.goal.manage', 'performance.goal.update'] as $slug) {
            $this->assertNotNull(Permission::query()->where('slug', $slug)->first(), "Missing permission: {$slug}");
            $this->assertTrue($hr->hasPermission($slug, $organization));
        }

        $manager = User::factory()->create();
        $organization->addMember($manager, 'manager');
        $this->assertTrue($manager->hasPermission('performance.goal.view', $organization));
        $this->assertTrue($manager->hasPermission('performance.goal.update', $organization));
        $this->assertFalse($manager->hasPermission('performance.goal.manage', $organization));

        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');
        $this->assertTrue($employee->hasPermission('performance.goal.view', $organization));
        $this->assertTrue($employee->hasPermission('performance.goal.update', $organization));
        $this->assertFalse($employee->hasPermission('performance.goal.manage', $organization));
    }

    public function test_goal_library_crud_and_audit(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.performance.goal-categories.store'), [
            'name' => 'Business',
            'code' => 'BIZ',
            'is_active' => true,
        ])->assertRedirect(route('hrms.performance.goal-categories.index'));

        $category = GoalCategory::query()->where('code', 'BIZ')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['event' => 'goal_category_created']);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.performance.goals.library.store'), [
            'goal_category_id' => $category->id,
            'title' => 'Increase Revenue',
            'goal_type' => 'individual',
            'default_weight' => 25,
            'measurement_type' => 'percentage',
            'is_active' => true,
        ])->assertRedirect(route('hrms.performance.goals.library.index'));

        $template = GoalTemplate::query()->where('title', 'Increase Revenue')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['event' => 'goal_template_created']);
        $this->assertSame($category->id, $template->goal_category_id);

        $this->actingAs($hr)->withSession($session)->delete(route('hrms.performance.goals.library.destroy', $template))
            ->assertRedirect(route('hrms.performance.goals.library.index'));

        $this->assertSoftDeleted('goal_templates', ['id' => $template->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'goal_template_deleted']);
    }

    public function test_kpi_crud_and_audit(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.performance.kpis.store'), [
            'name' => 'Sales Closed',
            'code' => 'SALES',
            'unit' => 'deals',
            'measurement_type' => 'numeric',
            'default_target' => 50,
            'is_active' => true,
        ])->assertRedirect(route('hrms.performance.kpis.index'));

        $kpi = Kpi::query()->where('code', 'SALES')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['event' => 'kpi_created']);

        $this->actingAs($hr)->withSession($session)->put(route('hrms.performance.kpis.update', $kpi), [
            'name' => 'Sales Closed Deals',
            'code' => 'SALES',
            'unit' => 'deals',
            'measurement_type' => 'numeric',
            'default_target' => 60,
            'is_active' => true,
        ])->assertRedirect(route('hrms.performance.kpis.index'));

        $this->assertDatabaseHas('audit_logs', ['event' => 'kpi_updated']);
        $this->assertDatabaseHas('kpis', ['id' => $kpi->id, 'name' => 'Sales Closed Deals']);

        $this->actingAs($hr)->withSession($session)->delete(route('hrms.performance.kpis.destroy', $kpi))
            ->assertRedirect(route('hrms.performance.kpis.index'));

        $this->assertSoftDeleted('kpis', ['id' => $kpi->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'kpi_deleted']);
    }

    public function test_goal_assignment_workflow_and_audit(): void
    {
        Event::fake([GoalCreated::class, GoalAssigned::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        app(TenantContext::class)->set($organization);

        $cycle = PerformanceCycle::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $template = GoalTemplate::factory()->create([
            'organization_id' => $organization->id,
            'title' => 'Close Deals',
            'default_weight' => 100,
            'measurement_type' => 'numeric',
        ]);
        $kpi = Kpi::factory()->create([
            'organization_id' => $organization->id,
            'default_target' => 40,
            'measurement_type' => 'numeric',
        ]);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.performance.goals.store'), [
            'performance_cycle_id' => $cycle->id,
            'goal_template_id' => $template->id,
            'kpi_id' => $kpi->id,
            'assignee_type' => 'employee',
            'employee_id' => $employee->id,
            'weight' => 100,
            'target_value' => 40,
            'status' => 'assigned',
        ])->assertRedirect(route('hrms.performance.goals.index'));

        $goal = Goal::query()->where('title', 'Close Deals')->firstOrFail();
        $this->assertSame('assigned', $goal->status);
        $this->assertSame($employee->id, $goal->employee_id);
        $this->assertDatabaseHas('audit_logs', ['event' => 'goal_created']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'goal_assigned']);
        Event::assertDispatched(GoalCreated::class);
        Event::assertDispatched(GoalAssigned::class);
    }

    public function test_team_and_department_goals(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        app(TenantContext::class)->set($organization);

        $cycle = PerformanceCycle::factory()->create(['organization_id' => $organization->id]);
        $team = HrmsTeam::factory()->create(['organization_id' => $organization->id]);
        $department = Department::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.performance.goals.store'), [
            'performance_cycle_id' => $cycle->id,
            'title' => 'Team Delivery',
            'assignee_type' => 'team',
            'team_id' => $team->id,
            'goal_type' => 'team',
            'measurement_type' => 'percentage',
            'target_value' => 100,
            'weight' => 50,
        ])->assertRedirect(route('hrms.performance.goals.index'));

        $this->actingAs($hr)->withSession($session)->post(route('hrms.performance.goals.store'), [
            'performance_cycle_id' => $cycle->id,
            'title' => 'Dept NPS',
            'assignee_type' => 'department',
            'department_id' => $department->id,
            'goal_type' => 'department',
            'measurement_type' => 'numeric',
            'target_value' => 80,
            'weight' => 50,
        ])->assertRedirect(route('hrms.performance.goals.index'));

        $this->assertDatabaseHas('goals', [
            'organization_id' => $organization->id,
            'title' => 'Team Delivery',
            'assignee_type' => 'team',
            'team_id' => $team->id,
        ]);
        $this->assertDatabaseHas('goals', [
            'organization_id' => $organization->id,
            'title' => 'Dept NPS',
            'assignee_type' => 'department',
            'department_id' => $department->id,
        ]);
    }

    public function test_progress_updates_achievement_and_history_immutable(): void
    {
        Event::fake([GoalProgressUpdated::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        app(TenantContext::class)->set($organization);

        $cycle = PerformanceCycle::factory()->create(['organization_id' => $organization->id]);
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);

        $goal = app(GoalManagementService::class)->assignGoal([
            'performance_cycle_id' => $cycle->id,
            'title' => 'Tickets Closed',
            'assignee_type' => 'employee',
            'employee_id' => $employee->id,
            'measurement_type' => 'numeric',
            'target_value' => 100,
            'weight' => 100,
            'status' => 'assigned',
        ], $hr);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.performance.goals.progress', $goal), [
                'progress_value' => 40,
                'notes' => 'First update',
            ])
            ->assertRedirect(route('hrms.performance.goals.show', $goal));

        $goal->refresh();
        $this->assertSame('in_progress', $goal->status);
        $this->assertEquals(40.0, (float) $goal->current_value);
        $this->assertEquals(40.0, (float) $goal->achievement_percentage);
        $this->assertDatabaseHas('audit_logs', ['event' => 'goal_progress_updated']);
        Event::assertDispatched(GoalProgressUpdated::class);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.performance.goals.progress', $goal), [
                'progress_value' => 75,
                'notes' => 'Second update',
            ])
            ->assertRedirect(route('hrms.performance.goals.show', $goal));

        $this->assertSame(2, GoalProgressUpdate::query()->where('goal_id', $goal->id)->count());
        $this->assertDatabaseHas('goal_progress_updates', [
            'goal_id' => $goal->id,
            'progress_value' => 40,
            'notes' => 'First update',
        ]);
        $this->assertDatabaseHas('goal_progress_updates', [
            'goal_id' => $goal->id,
            'progress_value' => 75,
            'notes' => 'Second update',
        ]);
    }

    public function test_achievement_calculation_variants(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);
        $service = app(GoalManagementService::class);

        $this->assertEquals(50.0, $service->calculateAchievement('numeric', 50, 100));
        $this->assertEquals(100.0, $service->calculateAchievement('percentage', 100, null));
        $this->assertEquals(0.0, $service->calculateAchievement('boolean', 0, 1));
        $this->assertEquals(100.0, $service->calculateAchievement('boolean', 1, 1));
        $this->assertEquals(80.0, $service->calculateAchievement('currency', 8000, 10000));
        $this->assertEquals(25.0, $service->calculateAchievement('milestone', 1, 4));
    }

    public function test_weight_validation_rejects_over_100(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $cycle = PerformanceCycle::factory()->create(['organization_id' => $organization->id]);
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $service = app(GoalManagementService::class);

        $service->assignGoal([
            'performance_cycle_id' => $cycle->id,
            'title' => 'Goal A',
            'assignee_type' => 'employee',
            'employee_id' => $employee->id,
            'measurement_type' => 'percentage',
            'weight' => 60,
            'target_value' => 100,
        ], $hr);

        $this->expectException(ValidationException::class);

        $service->assignGoal([
            'performance_cycle_id' => $cycle->id,
            'title' => 'Goal B',
            'assignee_type' => 'employee',
            'employee_id' => $employee->id,
            'measurement_type' => 'percentage',
            'weight' => 50,
            'target_value' => 100,
        ], $hr);
    }

    public function test_checkins_are_append_only(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        app(TenantContext::class)->set($organization);

        $cycle = PerformanceCycle::factory()->create(['organization_id' => $organization->id]);
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);

        $goal = app(GoalManagementService::class)->assignGoal([
            'performance_cycle_id' => $cycle->id,
            'title' => 'Learning Goal',
            'assignee_type' => 'employee',
            'employee_id' => $employee->id,
            'measurement_type' => 'percentage',
            'weight' => 100,
            'target_value' => 100,
        ], $hr);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.performance.checkins.store', $goal), [
                'summary' => 'On track',
                'progress' => '50%',
                'risks' => 'None',
                'next_steps' => 'Continue',
            ])
            ->assertRedirect(route('hrms.performance.checkins.index'));

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.performance.checkins.store', $goal), [
                'summary' => 'Still on track',
                'progress' => '70%',
            ])
            ->assertRedirect(route('hrms.performance.checkins.index'));

        $this->assertSame(2, GoalCheckin::query()->where('goal_id', $goal->id)->count());
        $this->assertDatabaseHas('audit_logs', ['event' => 'goal_checkin_recorded']);
        $this->assertDatabaseHas('goal_checkins', ['summary' => 'On track']);
        $this->assertDatabaseHas('goal_checkins', ['summary' => 'Still on track']);
    }

    public function test_complete_and_cancel_emit_workflow_events(): void
    {
        Event::fake([GoalCompleted::class, GoalCancelled::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        app(TenantContext::class)->set($organization);

        $cycle = PerformanceCycle::factory()->create(['organization_id' => $organization->id]);
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $service = app(GoalManagementService::class);

        $goal = $service->assignGoal([
            'performance_cycle_id' => $cycle->id,
            'title' => 'Complete Me',
            'assignee_type' => 'employee',
            'employee_id' => $employee->id,
            'measurement_type' => 'percentage',
            'weight' => 50,
            'target_value' => 100,
        ], $hr);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.performance.goals.complete', $goal))
            ->assertRedirect(route('hrms.performance.goals.show', $goal));

        $this->assertDatabaseHas('goals', ['id' => $goal->id, 'status' => 'completed']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'goal_completed']);
        Event::assertDispatched(GoalCompleted::class);

        $goal2 = $service->assignGoal([
            'performance_cycle_id' => $cycle->id,
            'title' => 'Cancel Me',
            'assignee_type' => 'employee',
            'employee_id' => $employee->id,
            'measurement_type' => 'percentage',
            'weight' => 50,
            'target_value' => 100,
        ], $hr);

        $this->actingAs($hr)->withSession($session)
            ->delete(route('hrms.performance.goals.destroy', $goal2))
            ->assertRedirect(route('hrms.performance.goals.index'));

        $this->assertDatabaseHas('goals', ['id' => $goal2->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'goal_cancelled']);
        Event::assertDispatched(GoalCancelled::class);
    }

    public function test_tenant_isolation_blocks_cross_organization_access(): void
    {
        [$orgA, $hrA] = $this->organizationWithHrUser();
        [$orgB] = $this->organizationWithHrUser();

        GoalCategory::factory()->create([
            'organization_id' => $orgA->id,
            'name' => 'Org A Business',
            'code' => 'ORG-A-BIZ',
        ]);
        GoalCategory::factory()->create([
            'organization_id' => $orgB->id,
            'name' => 'Org B Business',
            'code' => 'ORG-B-BIZ',
        ]);

        $cycleB = PerformanceCycle::factory()->create(['organization_id' => $orgB->id]);
        $employeeB = Employee::factory()->create(['organization_id' => $orgB->id]);

        app(TenantContext::class)->set($orgB);
        $hrB = User::factory()->create();
        $orgB->addMember($hrB, 'hr');
        $goalB = app(GoalManagementService::class)->assignGoal([
            'performance_cycle_id' => $cycleB->id,
            'title' => 'Org B Goal',
            'assignee_type' => 'employee',
            'employee_id' => $employeeB->id,
            'measurement_type' => 'percentage',
            'weight' => 100,
            'target_value' => 100,
        ], $hrB);

        app(TenantContext::class)->set($orgA);
        $this->assertSame(1, GoalCategory::query()->count());

        $this->actingAs($hrA)->withSession(['current_organization_id' => $orgA->id])
            ->get(route('hrms.performance.goal-categories.index'))
            ->assertOk()
            ->assertSee('Org A Business')
            ->assertDontSee('Org B Business');

        $this->actingAs($hrA)->withSession(['current_organization_id' => $orgA->id])
            ->get(route('hrms.performance.goals.show', $goalB))
            ->assertNotFound();
    }

    public function test_rbac_blocks_employee_from_managing_library(): void
    {
        [$organization] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];

        $manager = User::factory()->create();
        $organization->addMember($manager, 'manager');
        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');

        $this->actingAs($manager)->withSession($session)
            ->get(route('hrms.performance.goals.index'))
            ->assertOk();

        $this->actingAs($manager)->withSession($session)
            ->post(route('hrms.performance.kpis.store'), [
                'name' => 'Blocked KPI',
                'code' => 'BLK',
                'measurement_type' => 'numeric',
            ])
            ->assertForbidden();

        $this->actingAs($employee)->withSession($session)
            ->post(route('hrms.performance.goals.library.store'), [
                'title' => 'Blocked Template',
                'goal_type' => 'individual',
                'measurement_type' => 'percentage',
            ])
            ->assertForbidden();

        $this->actingAs($employee)->withSession($session)
            ->get(route('hrms.performance.goals.index'))
            ->assertOk();
    }

    public function test_employee_can_update_progress_on_assigned_goal_only(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $employeeUser = User::factory()->create();
        $organization->addMember($employeeUser, 'employee');
        $otherUser = User::factory()->create();
        $organization->addMember($otherUser, 'employee');

        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $employeeUser->id,
        ]);
        $otherEmployee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $otherUser->id,
        ]);

        $cycle = PerformanceCycle::factory()->create(['organization_id' => $organization->id]);
        $service = app(GoalManagementService::class);

        $ownGoal = $service->assignGoal([
            'performance_cycle_id' => $cycle->id,
            'title' => 'My Goal',
            'assignee_type' => 'employee',
            'employee_id' => $employee->id,
            'measurement_type' => 'percentage',
            'weight' => 100,
            'target_value' => 100,
        ], $hr);

        $otherGoal = $service->assignGoal([
            'performance_cycle_id' => $cycle->id,
            'title' => 'Other Goal',
            'assignee_type' => 'employee',
            'employee_id' => $otherEmployee->id,
            'measurement_type' => 'percentage',
            'weight' => 100,
            'target_value' => 100,
        ], $hr);

        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($employeeUser)->withSession($session)
            ->post(route('hrms.performance.goals.progress', $ownGoal), [
                'progress_value' => 55,
            ])
            ->assertRedirect(route('hrms.performance.goals.show', $ownGoal));

        $this->actingAs($employeeUser)->withSession($session)
            ->post(route('hrms.performance.goals.progress', $otherGoal), [
                'progress_value' => 10,
            ])
            ->assertForbidden();
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
