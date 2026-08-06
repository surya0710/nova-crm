<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectRbacTest extends TestCase
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
            'name' => 'RBAC Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_employee_cannot_create_project(): void
    {
        [$employee, $organization] = $this->setupUserWithOrg('employee');

        $response = $this->actingAs($employee)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.store'), [
                'name' => 'Blocked',
                'owner_id' => $employee->id,
                'manager_id' => $employee->id,
                'priority' => 'medium',
            ]);

        $response->assertForbidden();
    }

    public function test_employee_cannot_edit_project(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');
        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');

        $project = $this->createProject($organization, $owner, $owner);

        $response = $this->actingAs($employee)
            ->withSession(['current_organization_id' => $organization->id])
            ->put(route('projects.update', $project), [
                'name' => 'Changed',
                'owner_id' => $owner->id,
                'manager_id' => $owner->id,
                'priority' => 'medium',
            ]);

        $response->assertForbidden();
    }

    public function test_sales_executive_cannot_delete_project(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');
        $sales = User::factory()->create();
        $organization->addMember($sales, 'sales-executive');

        $project = $this->createProject($organization, $owner, $owner);

        $response = $this->actingAs($sales)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('projects.destroy', $project));

        $response->assertForbidden();
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_sales_executive_cannot_archive_project(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');
        $sales = User::factory()->create();
        $organization->addMember($sales, 'sales-executive');

        $project = $this->createProject($organization, $owner, $owner);

        $response = $this->actingAs($sales)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.archive', $project));

        $response->assertForbidden();
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'is_archived' => false,
        ]);
    }

    public function test_organization_owner_can_create_edit_delete_and_archive(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.store'), [
                'name' => 'Owner Project',
                'owner_id' => $owner->id,
                'manager_id' => $owner->id,
                'priority' => 'medium',
            ])
            ->assertRedirect();

        $project = Project::query()->where('organization_id', $organization->id)->firstOrFail();

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->put(route('projects.update', $project), [
                'name' => 'Owner Project Updated',
                'owner_id' => $owner->id,
                'manager_id' => $owner->id,
                'priority' => 'high',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Owner Project Updated',
            'priority' => 'high',
        ]);

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.archive', $project))
            ->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'is_archived' => true,
        ]);

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.restore', $project))
            ->assertRedirect();

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'));

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_employee_cannot_create_project_label(): void
    {
        [$employee, $organization] = $this->setupUserWithOrg('employee');

        $this->actingAs($employee)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('project-labels.store'), [
                'name' => 'Blocked Label',
                'color' => '#000000',
            ])
            ->assertForbidden();
    }
}
