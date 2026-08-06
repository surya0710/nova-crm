<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCollaborationRbacTest extends TestCase
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
            'name' => 'RBAC Collab Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_employee_cannot_create_label(): void
    {
        [$employee, $organization] = $this->setupUserWithOrg('employee');

        $this->actingAs($employee)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('project-labels.store'), [
                'name' => 'Forbidden Label',
                'color' => '#000000',
            ])
            ->assertForbidden();
    }

    public function test_employee_cannot_manage_collaboration_pins(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');
        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');

        $project = $this->createProject($organization, $owner, $owner);

        $this->actingAs($employee)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.collaboration.pins.store', $project), [
                'source_type' => 'comment',
                'source_id' => 1,
                'title' => 'Nope',
            ])
            ->assertForbidden();
    }

    public function test_employee_cannot_create_template(): void
    {
        [$employee, $organization] = $this->setupUserWithOrg('employee');

        $this->actingAs($employee)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('project-templates.store'), [
                'name' => 'Forbidden Template',
            ])
            ->assertForbidden();
    }
}
