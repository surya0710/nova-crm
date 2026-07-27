<?php

namespace Tests\Feature\Smoke;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Production-readiness smoke checks for workspace homes (Phase 14.9).
 *
 * @group smoke
 */
#[Group('smoke')]
class WorkspaceHomesSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function ownerSession(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $organization->addMember($user, 'organization-owner');

        return [
            'user' => $user,
            'organization' => $organization,
            'session' => ['current_organization_id' => $organization->id],
        ];
    }

    public function test_tenant_workspace_homes_respond_ok(): void
    {
        ['user' => $user, 'session' => $session] = $this->ownerSession();

        $homes = [
            'crm.home',
            'projects.home',
            'hrms.home',
            'marketing.home',
            'analytics.home',
            'operations.home',
            'administration.home',
        ];

        foreach ($homes as $routeName) {
            if (! Route::has($routeName)) {
                $this->markTestSkipped("Route [{$routeName}] is not registered.");
            }

            $this->actingAs($user)
                ->withSession($session)
                ->get(route($routeName))
                ->assertOk();
        }
    }

    public function test_guest_cannot_access_workspace_homes(): void
    {
        if (! Route::has('crm.home')) {
            $this->markTestSkipped('crm.home is not registered.');
        }

        $this->get(route('crm.home'))->assertRedirect();
    }

    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_employee_cannot_access_administration_home(): void
    {
        if (! Route::has('administration.home')) {
            $this->markTestSkipped('administration.home is not registered.');
        }

        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'employee');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('administration.home'))
            ->assertForbidden();
    }
}
