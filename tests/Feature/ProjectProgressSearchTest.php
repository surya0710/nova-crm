<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProgressTrackingService;
use App\Services\ProjectService;
use App\Services\SearchService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectProgressSearchTest extends TestCase
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
            'name' => 'Search Progress Project',
            'owner_id' => $owner->id,
            'manager_id' => $owner->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_search_finds_progress_update_by_summary(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $project = $this->createProject($organization, $user, $user);

        app(TenantContext::class)->set($organization);
        app(ProgressTrackingService::class)->create($project, [
            'progress_percentage' => 50,
            'summary' => 'UniqueProgressSearchPhrase',
        ], $user);

        $results = app(SearchService::class)->search($user, 'UniqueProgressSearchPhrase');

        $titles = $results
            ->filter(fn (array $result) => $result['type'] === __('Progress Update'))
            ->pluck('title')
            ->all();

        $this->assertContains('UniqueProgressSearchPhrase', $titles);
    }

    public function test_search_excludes_progress_without_permission(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('manager');
        $hrUser = User::factory()->create();
        $organization->addMember($hrUser, 'hr');

        $project = $this->createProject($organization, $owner, $owner);

        app(TenantContext::class)->set($organization);
        app(ProgressTrackingService::class)->create($project, [
            'progress_percentage' => 10,
            'summary' => 'HiddenProgressSearchPhrase',
        ], $owner);

        $results = app(SearchService::class)->search($hrUser, 'HiddenProgressSearchPhrase');

        $titles = $results
            ->filter(fn (array $result) => $result['type'] === __('Progress Update'))
            ->pluck('title')
            ->all();

        $this->assertNotContains('HiddenProgressSearchPhrase', $titles);
    }
}
