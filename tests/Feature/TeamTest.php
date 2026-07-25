<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_manager_can_view_team_page(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('team.index'));

        $response->assertOk();
        $response->assertSee('Users');
        $response->assertSee('Invite User');
    }

    public function test_employee_cannot_view_team_page(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('employee');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('team.index'));

        $response->assertForbidden();
    }

    public function test_manager_can_add_new_user_to_organization(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('team.store'), [
                'name' => 'New Sales Rep',
                'email' => 'sales@example.com',
                'role' => 'sales-executive',
                'send_invitation' => 1,
            ]);

        $response->assertRedirect(route('team.index'));

        $newUser = User::query()->where('email', 'sales@example.com')->first();

        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->belongsToOrganization($organization));
        $this->assertEquals('Sales Executive', $newUser->getRoleNameInOrganization($organization));
        $this->assertSame(\App\Enums\UserAccountStatus::PendingInvitation, $newUser->account_status);
    }

    public function test_manager_can_add_existing_user_to_organization(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $existing = User::factory()->create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('team.store'), [
                'name' => 'Existing User',
                'email' => 'existing@example.com',
                'role' => 'employee',
            ]);

        $response->assertRedirect(route('team.index'));

        $this->assertTrue($existing->fresh()->belongsToOrganization($organization));
        $this->assertEquals('Employee', $existing->getRoleNameInOrganization($organization));
    }

    public function test_cannot_add_user_already_in_organization(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $member = User::factory()->create(['email' => 'member@example.com']);
        $organization->addMember($member, 'employee');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('team.store'), [
                'name' => 'Member',
                'email' => 'member@example.com',
                'role' => 'employee',
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_manager_can_update_member_role(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $member = User::factory()->create();
        $organization->addMember($member, 'employee');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('team.update', $member), [
                'role' => 'sales-executive',
            ]);

        $response->assertRedirect(route('team.index'));
        $this->assertEquals('Sales Executive', $member->fresh()->getRoleNameInOrganization($organization));
    }

    public function test_manager_can_remove_member(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $member = User::factory()->create();
        $organization->addMember($member, 'employee');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('team.destroy', $member));

        $response->assertRedirect(route('team.index'));
        $this->assertFalse($member->fresh()->belongsToOrganization($organization));
    }

    public function test_cannot_remove_only_organization_owner(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');
        $manager = User::factory()->create();
        $organization->addMember($manager, 'manager');

        $response = $this->actingAs($manager)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('team.destroy', $owner));

        $response->assertSessionHasErrors('member');
        $this->assertTrue($owner->fresh()->belongsToOrganization($organization));
    }

    public function test_user_cannot_remove_themselves(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('team.destroy', $user));

        $response->assertForbidden();
    }

    public function test_new_member_appears_in_lead_assignee_list(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('team.store'), [
                'name' => 'Assignee Test',
                'email' => 'assignee@example.com',
                'role' => 'sales-executive',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.create'));

        $response->assertOk();
        $response->assertSee('Assignee Test');
    }
}
