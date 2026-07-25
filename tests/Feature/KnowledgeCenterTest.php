<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class KnowledgeCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $modulePath = base_path('docs/getting-started');
        if (! File::isDirectory($modulePath)) {
            File::makeDirectory($modulePath, 0755, true);
        }

        File::put($modulePath.'/phase-10-5-2-feature.md', "# Feature Search\n\nWorkflow executions automatically retry failed actions.\n");
        clearstatcache();
    }

    protected function tearDown(): void
    {
        $fixture = base_path('docs/getting-started/phase-10-5-2-feature.md');
        if (File::exists($fixture)) {
            File::delete($fixture);
        }

        parent::tearDown();
    }

    public function test_user_can_open_knowledge_center_home(): void
    {
        [$organization, $user] = $this->organizationOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('knowledge.index'))
            ->assertOk()
            ->assertSee('Knowledge Center')
            ->assertSee('Getting Started')
            ->assertSee('CRM')
            ->assertSee('HRMS')
            ->assertSee('Ctrl+K', false);
    }

    public function test_module_landing_page_renders(): void
    {
        [$organization, $user] = $this->organizationOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('knowledge.module', ['module' => 'crm']))
            ->assertOk()
            ->assertSee('Knowledge Center');
    }

    public function test_documentation_page_renders(): void
    {
        [$organization, $user] = $this->organizationOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('knowledge.page', ['module' => 'crm', 'page' => 'user-guide/leads']))
            ->assertOk()
            ->assertSee('Previous')
            ->assertSee('Next')
            ->assertSee('Knowledge Center');
    }

    public function test_invalid_module_returns_404(): void
    {
        [$organization, $user] = $this->organizationOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('knowledge.module', ['module' => 'missing-module']))
            ->assertNotFound();
    }

    public function test_invalid_page_returns_404(): void
    {
        [$organization, $user] = $this->organizationOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('knowledge.page', ['module' => 'crm', 'page' => 'missing-page']))
            ->assertNotFound();
    }

    public function test_breadcrumbs_are_displayed_on_page(): void
    {
        [$organization, $user] = $this->organizationOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('knowledge.page', ['module' => 'crm', 'page' => 'user-guide/leads']))
            ->assertOk()
            ->assertSee('Knowledge Center')
            ->assertSee('CRM')
            ->assertSee('User Guide');
    }

    public function test_search_endpoint_renders_results(): void
    {
        [$organization, $user] = $this->organizationOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('knowledge.search', ['q' => 'workflow']))
            ->assertOk()
            ->assertSee('Search Documentation')
            ->assertSee('<mark', false);
    }

    public function test_empty_search_shows_guidance(): void
    {
        [$organization, $user] = $this->organizationOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('knowledge.search'))
            ->assertOk()
            ->assertSee('Enter a search term');
    }

    public function test_search_with_no_results_shows_message(): void
    {
        [$organization, $user] = $this->organizationOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('knowledge.search', ['q' => 'zzzz-no-docs-match-1052']))
            ->assertOk()
            ->assertSee('No documentation found');
    }

    public function test_table_of_contents_renders_on_document_page(): void
    {
        [$organization, $user] = $this->organizationOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('knowledge.page', ['module' => 'getting-started', 'page' => 'phase-10-5-2-feature']))
            ->assertOk()
            ->assertSee('On this page');
    }

    public function test_sidebar_marks_active_module_and_page(): void
    {
        [$organization, $user] = $this->organizationOwner();

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('knowledge.page', ['module' => 'crm', 'page' => 'user-guide/leads']));

        $response->assertOk();
        $response->assertSee('aria-current="page"', false);
        $response->assertSee('bg-indigo-50', false);
    }

    public function test_keyboard_shortcut_markup_is_available(): void
    {
        [$organization, $user] = $this->organizationOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('knowledge.search'))
            ->assertOk()
            ->assertSee('knowledge-search-input', false)
            ->assertSee('handleShortcut', false)
            ->assertSee('Escape', false);
    }

    public function test_responsive_navigation_uses_sticky_sidebar_markup(): void
    {
        [$organization, $user] = $this->organizationOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('knowledge.index'))
            ->assertOk()
            ->assertSee('lg:sticky', false)
            ->assertSee('Toggle module pages');
    }

    public function test_help_dropdown_renders_on_mapped_crm_screen(): void
    {
        [$organization, $user] = $this->organizationOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index'))
            ->assertOk()
            ->assertSee('Help')
            ->assertSee(route('knowledge.page', ['module' => 'crm', 'page' => 'user-guide/leads']), false);
    }

    public function test_help_is_hidden_on_unmapped_screen(): void
    {
        [$organization, $user] = $this->organizationOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('aria-label="'.__('Open documentation for this page'), false);
    }

    public function test_related_documentation_displays_on_knowledge_page(): void
    {
        [$organization, $user] = $this->organizationOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('knowledge.page', ['module' => 'crm', 'page' => 'user-guide/leads']))
            ->assertOk()
            ->assertSee('Related Documentation')
            ->assertSee('Customers');
    }

    public function test_help_dropdown_exposes_accessibility_attributes(): void
    {
        [$organization, $user] = $this->organizationOwner();

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index'));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertTrue(
            str_contains($content, 'aria-haspopup="true"') || str_contains($content, 'aria-label="'.__('Open documentation for this page')),
            'Expected help control accessibility attributes.'
        );
        $this->assertStringContainsString('User Guide', $content);
    }

    public function test_recently_viewed_is_tracked_in_session(): void
    {
        [$organization, $user] = $this->organizationOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('knowledge.page', ['module' => 'crm', 'page' => 'overview']))
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('knowledge.page', ['module' => 'crm', 'page' => 'user-guide/leads']))
            ->assertOk()
            ->assertSee('Recently Viewed')
            ->assertSee('CRM Overview');
    }

    /**
     * @return array{0: Organization, 1: User}
     */
    private function organizationOwner(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'organization-owner');

        return [$organization, $user];
    }
}
