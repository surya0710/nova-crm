<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProjectBudgetTest extends TestCase
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
            'name' => 'Budget Feature Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_create_budget_via_update_route(): void
    {
        Notification::fake();

        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->put(route('projects.budgets.update', $project), [
                'name' => 'Primary Budget',
                'currency' => 'USD',
                'status' => 'draft',
                'items' => [
                    [
                        'name' => 'Labor',
                        'category_slug' => 'labor',
                        'planned' => 2000,
                        'actual' => 500,
                        'forecast' => 2100,
                    ],
                ],
            ])
            ->assertRedirect(route('projects.budgets.show', $project));

        $budget = ProjectBudget::query()->where('project_id', $project->id)->firstOrFail();
        $this->assertEquals(2000.0, (float) $budget->planned_total);
        $this->assertDatabaseHas('budget_items', [
            'project_budget_id' => $budget->id,
            'name' => 'Labor',
        ]);
    }

    public function test_show_budget_page(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user, $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('projects.budgets.show', $project))
            ->assertOk();
    }
}
