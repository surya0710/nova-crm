<?php

namespace Tests\Unit;

use App\Events\ProjectRiskCreated;
use App\Events\ProjectRiskEscalated;
use App\Events\ProjectRiskUpdated;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectRisk;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\RiskManagementService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RiskManagementServiceTest extends TestCase
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

    protected function createProject(Organization $organization, User $actor): Project
    {
        return app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Risk Project',
            'owner_id' => $actor->id,
            'manager_id' => $actor->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_create_computes_severity(): void
    {
        Event::fake([ProjectRiskCreated::class]);

        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);

        $risk = app(RiskManagementService::class)->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Vendor delay',
            'probability' => 4,
            'impact' => 5,
            'category' => 'schedule',
        ], $user);

        $this->assertInstanceOf(ProjectRisk::class, $risk);
        $this->assertSame(20, $risk->severity);
        $this->assertSame('open', $risk->status);
        Event::assertDispatched(ProjectRiskCreated::class);
    }

    public function test_update_recalculates_severity_and_history(): void
    {
        Event::fake([ProjectRiskUpdated::class]);

        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);
        $service = app(RiskManagementService::class);

        $risk = $service->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Budget risk',
            'probability' => 2,
            'impact' => 2,
        ], $user);

        Event::fake([ProjectRiskUpdated::class]);

        $updated = $service->update($risk, [
            'probability' => 5,
            'impact' => 3,
            'status' => 'mitigating',
        ], $user);

        $this->assertSame(15, $updated->severity);
        $this->assertSame('mitigating', $updated->status);
        $this->assertIsArray($updated->history);
        $this->assertNotEmpty($updated->history);
        Event::assertDispatched(ProjectRiskUpdated::class);
    }

    public function test_escalate_sets_status_and_fires_event(): void
    {
        Notification::fake();
        Event::fake([ProjectRiskEscalated::class]);

        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);
        $service = app(RiskManagementService::class);

        $risk = $service->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Escalate me',
            'probability' => 5,
            'impact' => 5,
        ], $user);

        Event::fake([ProjectRiskEscalated::class]);

        $escalated = $service->escalate($risk, $user, 'Need leadership');
        $this->assertSame('escalated', $escalated->status);
        $this->assertNotNull($escalated->escalated_at);
        Event::assertDispatched(ProjectRiskEscalated::class);
    }

    public function test_matrix_returns_heatmap_structure(): void
    {
        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);
        $service = app(RiskManagementService::class);

        $service->create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Matrix risk',
            'probability' => 3,
            'impact' => 4,
        ], $user);

        $matrix = $service->matrix($organization, $project->id);
        $this->assertArrayHasKey('matrix', $matrix);
        $this->assertArrayHasKey('cells', $matrix);
    }
}
