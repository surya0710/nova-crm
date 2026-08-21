<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\Hrms\EmployeeProvisioningService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_provisioning_creates_user_employee_membership_and_role(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $employee = app(EmployeeProvisioningService::class)->provision([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'employment_type' => 'full_time',
            'status' => 'active',
            'create_user' => true,
            'email' => 'ada@example.com',
            'role' => 'employee',
            'entry_point' => 'hrms',
            'notify' => false,
        ], $hr, $organization);

        $this->assertNotNull($employee->user_id);
        $this->assertTrue($employee->user->belongsToOrganization($organization));
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'organization_id' => $organization->id,
            'first_name' => 'Ada',
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'employee_provisioned']);
    }

    public function test_hrms_create_with_user_flag_uses_provisioning(): void
    {
        [$organization, $owner] = $this->organizationWithOwner();

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('hrms.employees.store'), [
                'first_name' => 'Grace',
                'last_name' => 'Hopper',
                'employment_type' => 'full_time',
                'status' => 'active',
                'create_user' => 1,
                'user_email' => 'grace@example.com',
                'email' => 'grace@example.com',
                'role' => 'employee',
                'send_invitation' => 1,
                'portal_access' => 1,
            ])
            ->assertRedirect();

        $employee = Employee::query()->where('email', 'grace@example.com')->firstOrFail();
        $this->assertNotNull($employee->user_id);
        $this->assertSame('grace@example.com', $employee->user->email);
        $this->assertSame(\App\Enums\UserAccountStatus::PendingInvitation, $employee->user->account_status);
    }

    public function test_missing_employee_record_renders_empty_state_not_forbidden(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'manager');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.manager.dashboard'));

        $response->assertOk();
        $response->assertSee('No employee record is linked to your account.');
    }

    public function test_organization_settings_hub_is_reachable(): void
    {
        [$organization, $owner] = $this->organizationWithOwner();

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('organization.settings.hub'))
            ->assertOk()
            ->assertSee('Configuration Hub');
    }

    public function test_working_days_settings_persist_on_organization(): void
    {
        $organization = Organization::factory()->create(['plan' => 'professional']);
        $owner = User::factory()->create();
        $organization->addMember($owner, 'organization-owner');

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->put(route('organization.settings.working-days.update'), [
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            ])
            ->assertRedirect(route('organization.settings.working-days.edit'));

        $organization->refresh();
        $this->assertContains('saturday', $organization->settings['working_days']);
        $this->assertContains('sunday', $organization->settings['weekend_days']);
    }

    public function test_assets_module_marked_future_in_config(): void
    {
        $future = config('organization_settings.future_modules.assets');
        $this->assertIsArray($future);
        $this->assertTrue($future['hidden_from_navigation']);
        $this->assertSame('future', $future['status']);
    }

    private function organizationWithHrUser(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

        return [$organization, $user];
    }

    private function organizationWithOwner(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'organization-owner');

        return [$organization, $user];
    }
}
