<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\User;
use App\Services\DocumentationService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentationServiceTest extends TestCase
{
    private string $fixtureModule = 'getting-started';

    protected function setUp(): void
    {
        parent::setUp();

        $modulePath = base_path('docs/'.$this->fixtureModule);
        if (! File::isDirectory($modulePath)) {
            File::makeDirectory($modulePath, 0755, true);
        }

        File::put($modulePath.'/phase-10-5-1-fixture.md', "# Fixture\n\n- item\n");
        File::put($modulePath.'/phase-10-5-2-search.md', "# Payroll Guide\n\n## Processing\n\nWorkflow executions automatically retry failed actions during payroll.\n");
        File::put($modulePath.'/phase-10-5-2-links.md', "# Links\n\nSee [overview](overview.md) and [CRM](../../crm/overview.md).\n");
        clearstatcache();
    }

    protected function tearDown(): void
    {
        foreach ([
            'phase-10-5-1-fixture.md',
            'phase-10-5-2-search.md',
            'phase-10-5-2-links.md',
        ] as $fixture) {
            $path = base_path('docs/'.$this->fixtureModule.'/'.$fixture);
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        parent::tearDown();
    }

    public function test_module_discovery_returns_configured_sidebar_modules(): void
    {
        $service = app(DocumentationService::class);
        $modules = $service->getSidebarModules($this->organizationOwnerUser());

        $keys = $modules->pluck('key')->all();

        $this->assertContains('getting-started', $keys);
        $this->assertContains('crm', $keys);
    }

    public function test_page_discovery_returns_module_documents(): void
    {
        $service = app(DocumentationService::class);
        $documents = $service->getModuleDocuments($this->organizationOwnerUser(), 'getting-started');

        $slugs = $documents->pluck('slug')->all();
        $this->assertContains('getting-started/overview', $slugs);
    }

    public function test_markdown_is_loaded_and_rendered_safely(): void
    {
        $service = app(DocumentationService::class);
        $document = $service->findDocument($this->organizationOwnerUser(), 'getting-started', 'phase-10-5-1-fixture');

        $this->assertNotNull($document);
        $this->assertArrayHasKey('html', $document);
        $this->assertStringContainsString('Fixture</h1>', (string) $document['html']);
    }

    public function test_breadcrumb_generation_includes_page_hierarchy(): void
    {
        $service = app(DocumentationService::class);
        $document = $service->findDocument($this->organizationOwnerUser(), 'crm', 'user-guide/leads');

        $this->assertNotNull($document);
        $titles = collect($document['breadcrumbs'])->pluck('title')->all();

        $this->assertSame('Knowledge Center', $titles[0]);
        $this->assertSame('CRM', $titles[1]);
        $this->assertContains('User Guide', $titles);
    }

    public function test_previous_next_navigation_is_generated(): void
    {
        $service = app(DocumentationService::class);
        $document = $service->findDocument($this->organizationOwnerUser(), 'crm', 'user-guide/leads');

        $this->assertNotNull($document);
        $navigation = $service->resolvePreviousNext($this->organizationOwnerUser(), $document);

        $this->assertIsArray($navigation);
        $this->assertArrayHasKey('previous', $navigation);
        $this->assertArrayHasKey('next', $navigation);
    }

    public function test_render_cache_invalidates_when_document_changes(): void
    {
        $service = app(DocumentationService::class);
        $user = $this->organizationOwnerUser();
        $modulePath = base_path('docs/'.$this->fixtureModule.'/phase-10-5-1-fixture.md');

        $first = $service->findDocument($user, 'getting-started', 'phase-10-5-1-fixture');
        $this->assertNotNull($first);
        $this->assertStringContainsString('Fixture', (string) $first['html']);

        sleep(1);
        File::put($modulePath, "# Fixture Updated\n\n- changed\n");
        clearstatcache();

        $second = $service->findDocument($user, 'getting-started', 'phase-10-5-1-fixture');
        $this->assertNotNull($second);
        $this->assertStringContainsString('Fixture Updated', (string) $second['html']);
    }

    public function test_search_returns_ranked_results_with_snippets(): void
    {
        $service = app(DocumentationService::class);
        $results = $service->search($this->organizationOwnerUser(), 'payroll');

        $this->assertTrue($results->isNotEmpty());
        $first = $results->first();
        $this->assertArrayHasKey('score', $first);
        $this->assertArrayHasKey('snippet', $first);
        $this->assertGreaterThan(0, $first['score']);
    }

    public function test_search_orders_more_relevant_title_matches_first(): void
    {
        $service = app(DocumentationService::class);
        $results = $service->search($this->organizationOwnerUser(), 'payroll guide');

        $this->assertTrue($results->isNotEmpty());
        $this->assertStringContainsString('payroll', Str::lower($results->first()['title']));
    }

    public function test_table_of_contents_is_generated_from_headings(): void
    {
        $service = app(DocumentationService::class);
        $document = $service->findDocument($this->organizationOwnerUser(), 'getting-started', 'phase-10-5-2-search');

        $this->assertNotNull($document);
        $this->assertNotEmpty($document['toc']);
        $this->assertSame('Processing', $document['toc'][1]['title']);
        $this->assertSame(2, $document['toc'][1]['level']);
        $this->assertNotEmpty($document['toc'][1]['anchor']);
        $this->assertStringContainsString('id="processing"', (string) $document['html']);
    }

    public function test_internal_links_resolve_to_knowledge_center_routes(): void
    {
        $service = app(DocumentationService::class);
        $resolved = $service->resolveInternalLinks(
            'See [CRM](../../crm/overview.md).',
            'getting-started/phase-10-5-2-links.md'
        );

        $this->assertStringContainsString(route('knowledge.page', ['module' => 'crm', 'page' => 'overview']), $resolved);
    }

    public function test_broken_internal_links_remain_unchanged(): void
    {
        $service = app(DocumentationService::class);
        $resolved = $service->resolveInternalLinks(
            'See [Missing](../missing/page.md).',
            'getting-started/phase-10-5-2-links.md'
        );

        $this->assertSame('See [Missing](../missing/page.md).', $resolved);
    }

    public function test_search_index_invalidates_when_fixture_changes(): void
    {
        $service = app(DocumentationService::class);
        $user = $this->organizationOwnerUser();
        $path = base_path('docs/'.$this->fixtureModule.'/phase-10-5-2-search.md');

        $before = $service->search($user, 'unicorn-term-1052');
        $this->assertTrue($before->isEmpty());

        sleep(1);
        File::put($path, "# Payroll Guide\n\nUnique unicorn-term-1052 appears here.\n");
        touch($path, time() + 60);
        clearstatcache();

        $after = $service->search($user, 'unicorn-term-1052');
        $this->assertTrue($after->isNotEmpty());
    }

    public function test_highlight_query_wraps_matches(): void
    {
        $service = app(DocumentationService::class);
        $highlighted = $service->highlightQuery('Workflow executions retry', 'workflow');

        $this->assertStringContainsString('<mark', $highlighted);
        $this->assertStringContainsString('Workflow', $highlighted);
    }

    private function organizationOwnerUser(): User
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'organization-owner');

        return $user;
    }
}
