<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\Program;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramManagementTest extends TestCase
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
            'name' => 'Program Mgmt Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_web_crud_for_programs(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $portfolio = Portfolio::factory()->create([
            'organization_id' => $organization->id,
            'owner_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('programs.store'), [
                'name' => 'Delivery Program',
                'portfolio_id' => $portfolio->id,
                'status' => 'active',
            ])
            ->assertRedirect();

        $program = Program::query()
            ->where('organization_id', $organization->id)
            ->where('name', 'Delivery Program')
            ->firstOrFail();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('programs.show', $program))
            ->assertOk()
            ->assertSee('Delivery Program');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->put(route('programs.update', $program), [
                'name' => 'Delivery Program Updated',
                'portfolio_id' => $portfolio->id,
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('programs', [
            'id' => $program->id,
            'name' => 'Delivery Program Updated',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('programs.destroy', $program))
            ->assertRedirect(route('programs.index'));

        $this->assertDatabaseMissing('programs', ['id' => $program->id]);
    }

    public function test_attach_project_to_program(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user, $user);

        $program = Program::factory()->create([
            'organization_id' => $organization->id,
            'manager_id' => $user->id,
            'portfolio_id' => null,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('programs.projects.attach', $program), [
                'project_id' => $project->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('program_projects', [
            'program_id' => $program->id,
            'project_id' => $project->id,
        ]);
    }
}
