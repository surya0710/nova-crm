<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function createProject(Organization $organization, User $owner, User $actor): Project
    {
        app(TenantContext::class)->set($organization);

        return app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'RBAC Portfolio Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_employee_cannot_create_portfolio(): void
    {
        [$employee, $organization] = $this->setupUserWithOrg('employee');

        $this->actingAs($employee)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('portfolios.store'), [
                'name' => 'Forbidden Portfolio',
                'status' => 'active',
            ])
            ->assertForbidden();
    }

    public function test_employee_cannot_create_program(): void
    {
        [$employee, $organization] = $this->setupUserWithOrg('employee');

        $this->actingAs($employee)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('programs.store'), [
                'name' => 'Forbidden Program',
                'status' => 'active',
            ])
            ->assertForbidden();
    }

    public function test_employee_cannot_create_risk(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');
        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');
        $project = $this->createProject($organization, $owner, $owner);

        $this->actingAs($employee)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.risks.store', $project), [
                'title' => 'Forbidden Risk',
                'probability' => 3,
                'impact' => 3,
            ])
            ->assertForbidden();
    }

    public function test_employee_cannot_create_dependency(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');
        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');

        $a = $this->createProject($organization, $owner, $owner);
        $b = app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'RBAC Dep B',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $owner);

        $this->actingAs($employee)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('project-dependencies.store'), [
                'predecessor_project_id' => $a->id,
                'successor_project_id' => $b->id,
                'dependency_type' => 'finish_to_start',
            ])
            ->assertForbidden();
    }
}
