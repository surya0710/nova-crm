<?php

namespace Tests\Unit;

use App\Events\ProgramCreated;
use App\Events\ProgramUpdated;
use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\Program;
use App\Models\Project;
use App\Models\User;
use App\Services\PortfolioService;
use App\Services\ProgramService;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ProgramServiceTest extends TestCase
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
            'name' => 'Program Project',
            'owner_id' => $actor->id,
            'manager_id' => $actor->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_create_program_with_portfolio(): void
    {
        Event::fake([ProgramCreated::class]);

        [$user, $organization] = $this->setupOrg();
        $portfolio = app(PortfolioService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Parent Portfolio',
        ], $user);

        $program = app(ProgramService::class)->create([
            'organization_id' => $organization->id,
            'portfolio_id' => $portfolio->id,
            'name' => 'Delivery Program',
            'status' => 'active',
        ], $user);

        $this->assertInstanceOf(Program::class, $program);
        $this->assertSame($portfolio->id, $program->portfolio_id);
        $this->assertSame($organization->id, $program->organization_id);
        Event::assertDispatched(ProgramCreated::class);
    }

    public function test_attach_and_detach_project(): void
    {
        [$user, $organization] = $this->setupOrg();
        $service = app(ProgramService::class);
        $project = $this->createProject($organization, $user);

        $program = $service->create([
            'organization_id' => $organization->id,
            'name' => 'Attach Program',
        ], $user);

        Event::fake([ProgramUpdated::class]);

        $service->attachProject($program, $project, $user);
        $this->assertDatabaseHas('program_projects', [
            'program_id' => $program->id,
            'project_id' => $project->id,
        ]);

        $service->detachProject($program, $project, $user);
        $this->assertDatabaseMissing('program_projects', [
            'program_id' => $program->id,
            'project_id' => $project->id,
        ]);
        Event::assertDispatched(ProgramUpdated::class);
    }

    public function test_delete_program(): void
    {
        [$user, $organization] = $this->setupOrg();
        $service = app(ProgramService::class);

        $program = $service->create([
            'organization_id' => $organization->id,
            'name' => 'Delete Program',
        ], $user);

        Event::fake([ProgramUpdated::class]);
        $service->delete($program, $user);

        $this->assertDatabaseMissing('programs', ['id' => $program->id]);
        Event::assertDispatched(ProgramUpdated::class);
    }

    public function test_list_filters_by_portfolio(): void
    {
        [$user, $organization] = $this->setupOrg();
        $service = app(ProgramService::class);

        $portfolio = Portfolio::factory()->create([
            'organization_id' => $organization->id,
            'owner_id' => $user->id,
        ]);

        $service->create([
            'organization_id' => $organization->id,
            'portfolio_id' => $portfolio->id,
            'name' => 'In Portfolio',
        ], $user);
        $service->create([
            'organization_id' => $organization->id,
            'name' => 'Standalone',
        ], $user);

        $results = $service->list($organization, ['portfolio_id' => $portfolio->id]);
        $this->assertCount(1, $results);
        $this->assertSame('In Portfolio', $results->first()->name);
    }
}
