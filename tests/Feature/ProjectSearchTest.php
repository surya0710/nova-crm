<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\ProjectLabel;
use App\Models\ProjectTemplate;
use App\Models\User;
use App\Services\SearchService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_search_finds_project_labels_and_templates(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        ProjectLabel::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'UniqueLabelSearchPhrase',
            'description' => 'Label for search',
        ]);

        ProjectTemplate::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'UniqueTemplateSearchPhrase',
            'created_by' => $user->id,
            'category' => 'search-cat',
        ]);

        $results = app(SearchService::class)->search($user, 'UniqueLabelSearchPhrase');
        $labelTitles = $results
            ->filter(fn (array $result) => $result['type'] === __('Label'))
            ->pluck('title')
            ->all();
        $this->assertContains('UniqueLabelSearchPhrase', $labelTitles);

        $templateResults = app(SearchService::class)->search($user, 'UniqueTemplateSearchPhrase');
        $templateTitles = $templateResults
            ->filter(fn (array $result) => $result['type'] === __('Template'))
            ->pluck('title')
            ->all();
        $this->assertContains('UniqueTemplateSearchPhrase', $templateTitles);
    }
}
