<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectBaseline;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProjectBaselineTest extends TestCase
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
            'name' => 'Baseline Feature Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
            'planned_end_date' => now()->addMonth()->toDateString(),
        ], $actor);
    }

    public function test_capture_baseline(): void
    {
        Notification::fake();

        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.baselines.store', $project), [
                'name' => 'Kickoff Baseline',
                'notes' => 'Initial plan',
            ])
            ->assertRedirect(route('projects.baselines.index', $project));

        $this->assertDatabaseHas('project_baselines', [
            'project_id' => $project->id,
            'name' => 'Kickoff Baseline',
            'version' => 1,
            'created_by' => $user->id,
        ]);
    }

    public function test_show_baseline_comparison(): void
    {
        Notification::fake();

        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user, $user);

        $baseline = ProjectBaseline::factory()->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'created_by' => $user->id,
            'version' => 1,
            'name' => 'Compare Baseline',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('projects.baselines.show', [$project, $baseline]))
            ->assertOk()
            ->assertSee('Compare Baseline');
    }
}
