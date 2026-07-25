<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\ProjectTemplateService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setupOrg(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');
        app(TenantContext::class)->set($organization);

        return [$user, $organization];
    }

    public function test_create_template(): void
    {
        [$user, $organization] = $this->setupOrg();

        $template = app(ProjectTemplateService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Onboarding Template',
            'description' => 'Standard onboarding',
            'category' => 'hr',
        ], $user);

        $this->assertInstanceOf(ProjectTemplate::class, $template);
        $this->assertSame('Onboarding Template', $template->name);
        $this->assertSame($organization->id, $template->organization_id);
        $this->assertNotEmpty($template->slug);
    }

    public function test_save_from_project_creates_template_with_source(): void
    {
        [$user, $organization] = $this->setupOrg();

        $project = app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Source Project',
            'owner_id' => $user->id,
            'manager_id' => $user->id,
            'priority' => 'medium',
        ], $user);

        $template = app(ProjectTemplateService::class)->saveFromProject($project, [
            'name' => 'Source Project Template',
        ], $user);

        $this->assertInstanceOf(ProjectTemplate::class, $template);
        $this->assertSame($project->id, $template->source_project_id);
        $this->assertSame('Source Project Template', $template->name);
    }
}
