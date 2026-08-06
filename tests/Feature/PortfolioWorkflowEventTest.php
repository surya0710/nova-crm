<?php

namespace Tests\Feature;

use App\Events\PortfolioCreated;
use App\Events\PortfolioReportGenerated;
use App\Events\ProgramCreated;
use App\Events\ProjectBaselineCreated;
use App\Events\ProjectDependencyCreated;
use App\Events\ProjectIssueCreated;
use App\Events\ProjectRiskCreated;
use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioWorkflowEventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

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
            'name' => 'Workflow Portfolio Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
            'planned_end_date' => now()->addMonth()->toDateString(),
        ], $actor);
    }

    public function test_portfolio_created_event_dispatched(): void
    {
        Event::fake([PortfolioCreated::class]);

        [$user, $organization] = $this->setupUserWithOrg();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('portfolios.store'), [
                'name' => 'Workflow Portfolio',
                'status' => 'active',
            ]);

        Event::assertDispatched(PortfolioCreated::class, function (PortfolioCreated $event) use ($organization) {
            return $event->organizationId === $organization->id
                && $event->trigger() === 'portfolio.created';
        });
    }

    public function test_program_created_event_dispatched(): void
    {
        Event::fake([ProgramCreated::class]);

        [$user, $organization] = $this->setupUserWithOrg();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('programs.store'), [
                'name' => 'Workflow Program',
                'status' => 'active',
            ]);

        Event::assertDispatched(ProgramCreated::class, function (ProgramCreated $event) {
            return $event->trigger() === 'program.created';
        });
    }

    public function test_risk_and_issue_and_dependency_events(): void
    {
        Event::fake([
            ProjectRiskCreated::class,
            ProjectIssueCreated::class,
            ProjectDependencyCreated::class,
        ]);

        [$user, $organization] = $this->setupUserWithOrg();
        $projectA = $this->createProject($organization, $user, $user);
        $projectB = app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Workflow Dep B',
            'owner_id' => $user->id,
            'manager_id' => $user->id,
            'priority' => 'medium',
        ], $user);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.risks.store', $projectA), [
                'title' => 'Workflow risk',
                'project_id' => $projectA->id,
                'probability' => 3,
                'impact' => 3,
            ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.issues.store', $projectA), [
                'title' => 'Workflow issue',
                'project_id' => $projectA->id,
                'priority' => 'medium',
            ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('project-dependencies.store'), [
                'predecessor_project_id' => $projectA->id,
                'successor_project_id' => $projectB->id,
                'dependency_type' => 'finish_to_start',
            ]);

        Event::assertDispatched(ProjectRiskCreated::class, fn (ProjectRiskCreated $e) => $e->trigger() === 'project.risk.created');
        Event::assertDispatched(ProjectIssueCreated::class, fn (ProjectIssueCreated $e) => $e->trigger() === 'project.issue.created');
        Event::assertDispatched(ProjectDependencyCreated::class, fn (ProjectDependencyCreated $e) => $e->trigger() === 'project.dependency.created');
    }

    public function test_baseline_and_report_events(): void
    {
        Notification::fake();
        Event::fake([ProjectBaselineCreated::class, PortfolioReportGenerated::class]);

        [$user, $organization] = $this->setupUserWithOrg();
        $project = $this->createProject($organization, $user, $user);
        $portfolio = Portfolio::factory()->create([
            'organization_id' => $organization->id,
            'owner_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('projects.baselines.store', $project), [
                'name' => 'Workflow Baseline',
            ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('portfolio-reports.store'), [
                'report_type' => 'portfolio',
                'format' => 'csv',
                'portfolio_id' => $portfolio->id,
            ]);

        Event::assertDispatched(ProjectBaselineCreated::class, fn (ProjectBaselineCreated $e) => $e->trigger() === 'project.baseline.created');
        Event::assertDispatched(PortfolioReportGenerated::class, fn (PortfolioReportGenerated $e) => $e->trigger() === 'portfolio.report.generated');
    }
}
