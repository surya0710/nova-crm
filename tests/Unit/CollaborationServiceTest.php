<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectCollaborationPin;
use App\Models\User;
use App\Services\CollaborationService;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollaborationServiceTest extends TestCase
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

    public function test_feed_returns_expected_structure(): void
    {
        [$user, $organization] = $this->setupOrg();

        $project = app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Collab Project',
            'owner_id' => $user->id,
            'manager_id' => $user->id,
            'priority' => 'medium',
        ], $user);

        $feed = app(CollaborationService::class)->feed($project);

        foreach ([
            'comments',
            'progress_updates',
            'mentions',
            'activity',
            'watchers',
            'pins',
            'shared_links',
            'items',
        ] as $key) {
            $this->assertArrayHasKey($key, $feed);
        }
    }

    public function test_pin_creates_collaboration_pin(): void
    {
        [$user, $organization] = $this->setupOrg();

        $project = app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Pin Project',
            'owner_id' => $user->id,
            'manager_id' => $user->id,
            'priority' => 'medium',
        ], $user);

        $pin = app(CollaborationService::class)->pin($project, [
            'source_type' => 'progress_update',
            'source_id' => 42,
            'title' => 'Important update',
        ], $user);

        $this->assertInstanceOf(ProjectCollaborationPin::class, $pin);
        $this->assertDatabaseHas('project_collaboration_pins', [
            'project_id' => $project->id,
            'source_type' => 'progress_update',
            'source_id' => 42,
            'title' => 'Important update',
            'pinned_by' => $user->id,
        ]);
    }
}
