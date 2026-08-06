<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\Hrms\EmployeeProvisioningService;
use App\Services\Identity\BulkEmployeeUserProvisioningService;
use App\Services\Identity\UserAccountService;
use App\Services\Identity\UserInvitationService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class IdentityAccessPlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_provisioning_sends_invitation_without_admin_password(): void
    {
        Mail::fake();
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
            'send_invitation' => true,
            'notify' => false,
            'entry_point' => 'hrms',
        ], $hr, $organization);

        $this->assertNotNull($employee->user_id);
        $this->assertSame(UserAccountStatus::PendingInvitation, $employee->user->account_status);
        $this->assertDatabaseHas('user_invitations', [
            'user_id' => $employee->user_id,
            'organization_id' => $organization->id,
        ]);
        $this->assertFalse(Hash::check('password', $employee->user->password));
    }

    public function test_invitation_accept_activates_account_and_allows_login(): void
    {
        Mail::fake();
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $employee = app(EmployeeProvisioningService::class)->provision([
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'employment_type' => 'full_time',
            'status' => 'active',
            'create_user' => true,
            'email' => 'grace@example.com',
            'send_invitation' => true,
            'notify' => false,
        ], $hr, $organization);

        $invitation = UserInvitation::query()->where('user_id', $employee->user_id)->latest('id')->firstOrFail();
        // Rebuild plaintext by re-inviting and capturing plain_token
        $fresh = app(UserInvitationService::class)->resend($employee->user, $organization, $hr, ['send_email' => false]);
        $token = $fresh->getAttribute('plain_token');

        $this->post(route('invitations.accept.store', ['token' => $token]), [
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertRedirect(route('login'));

        $user = $employee->user->fresh();
        $this->assertSame(UserAccountStatus::Active, $user->account_status);
        $this->assertTrue(Hash::check('SecurePass123!', $user->password));

        $this->post(route('login'), [
            'email' => 'grace@example.com',
            'password' => 'SecurePass123!',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_pending_invitation_cannot_login(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $employee = app(EmployeeProvisioningService::class)->provision([
            'first_name' => 'Pending',
            'last_name' => 'User',
            'employment_type' => 'full_time',
            'status' => 'active',
            'create_user' => true,
            'email' => 'pending@example.com',
            'send_invitation' => true,
            'notify' => false,
        ], $hr, $organization);

        $this->post(route('login'), [
            'email' => 'pending@example.com',
            'password' => 'anything',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(UserAccountStatus::PendingInvitation, $employee->user->fresh()->account_status);
    }

    public function test_locked_account_cannot_login(): void
    {
        [$organization, $owner] = $this->organizationWithOwner();
        app(TenantContext::class)->set($organization);

        $user = User::factory()->create([
            'email' => 'locked@example.com',
            'password' => Hash::make('password'),
            'account_status' => UserAccountStatus::Active,
        ]);
        $organization->addMember($user, 'employee');

        app(UserAccountService::class)->lock($user, $organization, $owner);

        $this->post(route('login'), [
            'email' => 'locked@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_existing_employee_conversion_creates_user_once(): void
    {
        Mail::fake();
        [$organization, $owner] = $this->organizationWithOwner();
        app(TenantContext::class)->set($organization);

        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'convert@example.com',
            'user_id' => null,
        ]);

        app(EmployeeProvisioningService::class)->provisionUserForEmployee($employee, [
            'name' => $employee->full_name,
            'email' => 'convert@example.com',
            'role' => 'employee',
            'send_invitation' => true,
            'portal_access' => true,
            'notify' => false,
        ], $owner);

        $employee->refresh();
        $this->assertNotNull($employee->user_id);
        $this->assertSame(UserAccountStatus::PendingInvitation, $employee->user->account_status);
        $this->assertSame(1, User::query()->where('email', 'convert@example.com')->count());

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(EmployeeProvisioningService::class)->provisionUserForEmployee($employee->fresh(), [
            'name' => $employee->full_name,
            'email' => 'convert@example.com',
            'role' => 'employee',
            'notify' => false,
        ], $owner);
    }

    public function test_team_invite_does_not_require_password(): void
    {
        Mail::fake();
        [$organization, $manager] = $this->organizationWithManager();

        $this->actingAs($manager)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('team.store'), [
                'name' => 'New Sales Rep',
                'email' => 'sales@example.com',
                'role' => 'sales-executive',
                'send_invitation' => 1,
            ])
            ->assertRedirect(route('team.index'));

        $newUser = User::query()->where('email', 'sales@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertSame(UserAccountStatus::PendingInvitation, $newUser->account_status);
        $this->assertDatabaseHas('user_invitations', ['user_id' => $newUser->id]);
    }

    public function test_bulk_provisioning_skips_existing_and_creates_missing(): void
    {
        Mail::fake();
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $withUser = app(EmployeeProvisioningService::class)->provision([
            'first_name' => 'Has',
            'last_name' => 'User',
            'employment_type' => 'full_time',
            'status' => 'active',
            'create_user' => true,
            'email' => 'has-user@example.com',
            'send_invitation' => false,
            'notify' => false,
        ], $hr, $organization);

        $without = Employee::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'needs-user@example.com',
            'user_id' => null,
        ]);

        $batch = app(BulkEmployeeUserProvisioningService::class)->start(
            $organization,
            $hr,
            [$withUser->id, $without->id],
            ['role' => 'employee', 'send_invitation' => true, 'portal_access' => true]
        );

        $batch->refresh();
        $this->assertSame('completed', $batch->status);
        $this->assertSame(1, $batch->succeeded);
        $this->assertSame(1, $batch->skipped);
        $this->assertNotNull($without->fresh()->user_id);
    }

    public function test_portal_enable_disable(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $user = User::factory()->create(['portal_access_enabled' => true]);
        $organization->addMember($user, 'employee');

        $accounts = app(UserAccountService::class);
        $accounts->disablePortal($user, $organization, $hr);
        $this->assertFalse($user->fresh()->portal_access_enabled);

        $accounts->enablePortal($user, $organization, $hr, notify: false);
        $this->assertTrue($user->fresh()->portal_access_enabled);
    }

    public function test_invitation_org_isolation(): void
    {
        Mail::fake();
        [$orgA, $hrA] = $this->organizationWithHrUser();
        [$orgB] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($orgA);

        $employee = app(EmployeeProvisioningService::class)->provision([
            'first_name' => 'Iso',
            'last_name' => 'Lation',
            'employment_type' => 'full_time',
            'status' => 'active',
            'create_user' => true,
            'email' => 'iso@example.com',
            'send_invitation' => true,
            'notify' => false,
        ], $hrA, $orgA);

        $invitation = app(UserInvitationService::class)->resend($employee->user, $orgA, $hrA, ['send_email' => false]);

        $this->assertSame($orgA->id, $invitation->organization_id);
        $this->assertNotSame($orgB->id, $invitation->organization_id);
        $this->assertDatabaseMissing('user_invitations', [
            'user_id' => $employee->user_id,
            'organization_id' => $orgB->id,
        ]);
    }

    public function test_expired_invitation_cannot_be_accepted(): void
    {
        Mail::fake();
        [$organization, $hr] = $this->organizationWithHrUser();
        $user = User::factory()->pendingInvitation()->create(['email' => 'expired@example.com']);
        $organization->addMember($user, 'employee');

        $invitation = UserInvitation::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'invited_by' => $hr->id,
            'email' => $user->email,
            'token_hash' => hash('sha256', 'expired-token'),
            'expires_at' => now()->subHour(),
            'sent_at' => now()->subDay(),
        ]);

        $this->post(route('invitations.accept.store', ['token' => 'expired-token']), [
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertSessionHasErrors();

        $this->assertNull($invitation->fresh()->accepted_at);
        $this->assertSame(UserAccountStatus::PendingInvitation, $user->fresh()->account_status);
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

    private function organizationWithManager(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'manager');

        return [$organization, $user];
    }
}
