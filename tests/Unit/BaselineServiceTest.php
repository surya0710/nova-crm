<?php

namespace Tests\Unit;

use App\Events\ProjectBaselineCreated;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectBaseline;
use App\Models\User;
use App\Services\BaselineService;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BaselineServiceTest extends TestCase
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
            'name' => 'Baseline Project',
            'owner_id' => $actor->id,
            'manager_id' => $actor->id,
            'priority' => 'medium',
            'planned_end_date' => now()->addMonth()->toDateString(),
            'estimated_budget' => 10000,
        ], $actor);
    }

    public function test_capture_creates_versioned_baseline(): void
    {
        Notification::fake();
        Event::fake([ProjectBaselineCreated::class]);

        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);
        $service = app(BaselineService::class);

        $first = $service->capture($project, $user, 'Initial', 'Kickoff baseline');
        $this->assertInstanceOf(ProjectBaseline::class, $first);
        $this->assertSame(1, $first->version);
        $this->assertSame('Kickoff baseline', $first->name);
        $this->assertIsArray($first->scope_snapshot);
        $this->assertIsArray($first->schedule_snapshot);
        Event::assertDispatched(ProjectBaselineCreated::class);

        $second = $service->capture($project, $user, 'Second');
        $this->assertSame(2, $second->version);
    }

    public function test_compare_returns_structure(): void
    {
        Notification::fake();

        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);
        $service = app(BaselineService::class);

        $baseline = $service->capture($project, $user);
        $comparison = $service->compare($baseline, $project);

        $this->assertArrayHasKey('scope', $comparison);
        $this->assertArrayHasKey('schedule', $comparison);
        $this->assertArrayHasKey('budget', $comparison);
        $this->assertArrayHasKey('progress', $comparison);
    }
}
