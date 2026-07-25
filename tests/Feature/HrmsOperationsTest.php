<?php

namespace Tests\Feature;

use App\Events\AssetAssigned;
use App\Events\AssetReturned;
use App\Events\EmployeeExitCompleted;
use App\Events\EmployeeExitStarted;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAsset;
use App\Models\EmployeeAssetAssignment;
use App\Models\EmployeeExitProcess;
use App\Models\Holiday;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
use App\Services\Hrms\EmployeeDirectoryService;
use App\Services\Hrms\EmployeeTimelineService;
use App\Services\Hrms\OrganizationCalendarService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HrmsOperationsTest extends TestCase
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

    /** @return array{0: Organization, 1: User} */
    private function organizationWithHrUser(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

        return [$organization, $user];
    }

    public function test_operations_tables_exist(): void
    {
        foreach (['employee_assets', 'employee_asset_assignments', 'employee_exit_processes'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_operations_permissions_are_seeded(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

        foreach (['assets.view', 'assets.manage', 'employee.exit.manage', 'employee.directory', 'organization.calendar'] as $slug) {
            $this->assertNotNull(Permission::query()->where('slug', $slug)->first(), "Missing permission: {$slug}");
        }

        $this->assertTrue($user->hasPermission('assets.view', $organization));
        $this->assertTrue($user->hasPermission('assets.manage', $organization));
        $this->assertTrue($user->hasPermission('employee.exit.manage', $organization));
    }

    public function test_asset_lifecycle_with_audit_and_events(): void
    {
        Event::fake([AssetAssigned::class, AssetReturned::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.assets.store'), [
            'name' => 'MacBook Pro',
            'category' => 'laptop',
            'serial_number' => 'SN-12345',
        ])->assertRedirect(route('hrms.assets.index'));

        $asset = EmployeeAsset::query()->where('name', 'MacBook Pro')->first();
        $this->assertNotNull($asset);
        $this->assertSame('available', $asset->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'asset_created']);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.assets.assign', $asset), [
            'employee_id' => $employee->id,
        ])->assertRedirect(route('hrms.assets.show', $asset));

        $asset->refresh();
        $this->assertSame('assigned', $asset->status);
        $this->assertSame($employee->id, $asset->employee_id);
        $this->assertDatabaseHas('employee_asset_assignments', [
            'employee_asset_id' => $asset->id,
            'employee_id' => $employee->id,
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'asset_assigned']);
        Event::assertDispatched(AssetAssigned::class);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.assets.return', $asset))
            ->assertRedirect(route('hrms.assets.show', $asset));

        $asset->refresh();
        $this->assertSame('returned', $asset->status);
        $this->assertNull($asset->employee_id);
        Event::assertDispatched(AssetReturned::class);
    }

    public function test_exit_workflow_with_checklist(): void
    {
        Event::fake([EmployeeExitStarted::class, EmployeeExitCompleted::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create(['organization_id' => $organization->id, 'status' => 'active']);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.exit-processes.store'), [
            'employee_id' => $employee->id,
            'exit_type' => 'resignation',
            'last_working_day' => '2026-08-15',
            'reason' => 'Personal reasons',
        ])->assertRedirect();

        $exitProcess = EmployeeExitProcess::query()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($exitProcess);
        $this->assertSame('in_progress', $exitProcess->status);
        $employee->refresh();
        $this->assertSame('notice_period', $employee->status);
        Event::assertDispatched(EmployeeExitStarted::class);

        $this->actingAs($hr)->withSession($session)->put(route('hrms.exit-processes.update', $exitProcess), [
            'checklist_assets_returned' => true,
            'checklist_documents_completed' => true,
            'checklist_knowledge_transfer' => true,
            'checklist_manager_approval' => true,
            'checklist_hr_approval' => true,
        ])->assertRedirect();

        $this->actingAs($hr)->withSession($session)->post(route('hrms.exit-processes.complete', $exitProcess))
            ->assertRedirect();

        $exitProcess->refresh();
        $this->assertSame('completed', $exitProcess->status);
        $employee->refresh();
        $this->assertSame('resigned', $employee->status);
        Event::assertDispatched(EmployeeExitCompleted::class);
    }

    public function test_timeline_aggregates_events(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'joining_date' => '2024-01-15',
        ]);

        $asset = EmployeeAsset::factory()->create(['organization_id' => $organization->id]);

        EmployeeAssetAssignment::factory()->create([
            'organization_id' => $organization->id,
            'employee_asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'assigned_date' => now()->toDateString(),
        ]);

        app(TenantContext::class)->set($organization);
        $timeline = app(EmployeeTimelineService::class)->timelineForEmployee($employee);

        $types = $timeline->pluck('type')->all();
        $this->assertContains('joined', $types);
        $this->assertContains('asset_assigned', $types);
    }

    public function test_organization_calendar_includes_holidays_and_birthdays(): void
    {
        [$organization] = $this->organizationWithHrUser();

        Holiday::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Independence Day',
            'holiday_date' => '2026-07-26',
        ]);

        Employee::factory()->create([
            'organization_id' => $organization->id,
            'date_of_birth' => '1990-07-22',
            'status' => 'active',
        ]);

        app(TenantContext::class)->set($organization);
        $events = app(OrganizationCalendarService::class)->eventsForMonth(2026, 7);

        $types = $events->pluck('type')->all();
        $this->assertContains('holiday', $types);
        $this->assertContains('birthday', $types);
    }

    public function test_employee_directory_search(): void
    {
        [$organization] = $this->organizationWithHrUser();
        $department = Department::factory()->create(['organization_id' => $organization->id, 'name' => 'Engineering']);

        Employee::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'department_id' => $department->id,
            'status' => 'active',
        ]);
        Employee::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Bob',
            'last_name' => 'Jones',
            'status' => 'active',
        ]);

        app(TenantContext::class)->set($organization);
        $results = app(EmployeeDirectoryService::class)->search(['q' => 'Alice']);

        $this->assertCount(1, $results->items());
        $this->assertSame('Alice', $results->items()[0]->first_name);
    }

    public function test_tenant_isolation_for_assets(): void
    {
        [$orgA, $hrA] = $this->organizationWithHrUser();
        [$orgB] = $this->organizationWithHrUser();

        $asset = EmployeeAsset::factory()->create(['organization_id' => $orgA->id]);
        EmployeeAsset::factory()->create(['organization_id' => $orgB->id]);

        $session = ['current_organization_id' => $orgA->id];

        $this->actingAs($hrA)->withSession($session)->get(route('hrms.assets.index'))
            ->assertOk()
            ->assertSee($asset->asset_code);
    }

    public function test_rbac_denies_unauthorized_asset_access(): void
    {
        $organization = Organization::factory()->create();
        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');

        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($employee)->withSession($session)->get(route('hrms.assets.index'))
            ->assertForbidden();
    }

    public function test_asset_policy_gates(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');

        $asset = EmployeeAsset::factory()->create(['organization_id' => $organization->id]);

        $this->assertTrue(Gate::forUser($hr)->allows('view', $asset));
        $this->assertFalse(Gate::forUser($employee)->allows('view', $asset));
    }

    public function test_hr_dashboard_includes_operations_widgets(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'date_of_birth' => '1990-07-25',
            'status' => 'active',
        ]);

        EmployeeAsset::factory()->assigned($employee)->create(['organization_id' => $organization->id]);
        EmployeeExitProcess::factory()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'status' => 'in_progress',
        ]);

        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->get(route('hrms.dashboard'))
            ->assertOk()
            ->assertSee('Assets Assigned')
            ->assertSee('Active Exits');
    }

    public function test_directory_and_calendar_routes(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create(['organization_id' => $organization->id, 'status' => 'active']);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->get(route('hrms.directory.index'))
            ->assertOk()
            ->assertSee($employee->first_name);

        $this->actingAs($hr)->withSession($session)->get(route('hrms.calendar'))
            ->assertOk()
            ->assertSee('Organization Calendar');
    }

    public function test_employee_timeline_route(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->get(route('hrms.employees.timeline', $employee))
            ->assertOk()
            ->assertSee('Employee Timeline');
    }
}
