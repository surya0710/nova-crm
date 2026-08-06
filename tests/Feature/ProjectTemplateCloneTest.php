<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\TemplateMilestone;
use App\Models\TemplateTask;
use App\Models\User;
use App\Services\ProjectTemplateService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTemplateCloneTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_create_project_from_template(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $template = app(ProjectTemplateService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Clone Source Template',
            'category' => 'ops',
        ], $user);

        TemplateMilestone::query()->create([
            'project_template_id' => $template->id,
            'name' => 'Kickoff',
            'sequence' => 1,
            'offset_days' => 0,
            'duration_days' => 3,
        ]);

        TemplateTask::query()->create([
            'project_template_id' => $template->id,
            'title' => 'Prepare kickoff deck',
            'priority' => 'medium',
            'offset_days' => 1,
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('project-templates.create-project', $template), [
                'name' => 'Cloned Project',
                'owner_id' => $user->id,
                'manager_id' => $user->id,
                'start_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $project = Project::query()
            ->where('organization_id', $organization->id)
            ->where('name', 'Cloned Project')
            ->firstOrFail();

        $this->assertDatabaseHas('project_milestones', [
            'project_id' => $project->id,
            'name' => 'Kickoff',
        ]);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'title' => 'Prepare kickoff deck',
        ]);

        $this->assertSame(1, $template->fresh()->usage_count);
    }
}
