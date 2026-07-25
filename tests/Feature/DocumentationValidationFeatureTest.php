<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DocumentationValidationFeatureTest extends TestCase
{
    use RefreshDatabase;

    private string $fixtureModule = 'doc-validation-feature';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->createFixtureModule();
        $this->configureValidationForFixture();
        clearstatcache();
    }

    protected function tearDown(): void
    {
        $path = base_path('docs/'.$this->fixtureModule);
        if (File::isDirectory($path)) {
            File::deleteDirectory($path);
        }

        parent::tearDown();
    }

    public function test_docs_validate_command_reports_failures_with_exit_code_one(): void
    {
        $this->artisan('docs:validate', ['--no-cache' => true])
            ->expectsOutputToContain('Documentation Validation Summary')
            ->assertExitCode(1);
    }

    public function test_docs_health_command_outputs_statistics(): void
    {
        $this->artisan('docs:health', ['--no-cache' => true])
            ->expectsOutputToContain('Documentation Health Report')
            ->expectsOutputToContain('Total modules:')
            ->assertExitCode(0);
    }

    public function test_health_page_renders_for_authorized_administrator(): void
    {
        [$organization, $user] = $this->organizationOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('knowledge.health'))
            ->assertOk()
            ->assertSee('Documentation Health')
            ->assertSee('Overall Health')
            ->assertSee('Module Coverage');
    }

    public function test_health_page_is_forbidden_without_settings_permission(): void
    {
        [$organization, $user] = $this->memberWithoutSettingsPermission();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('knowledge.health'))
            ->assertForbidden();
    }

    public function test_health_page_shows_empty_warning_state_for_complete_module(): void
    {
        $this->createCompleteFixtureModule();
        Config::set('documentation.modules', [
            $this->fixtureModule => [
                'enabled' => true,
                'name' => 'Validation Feature Fixture',
                'icon' => 'check',
                'searchable' => false,
            ],
        ]);
        Config::set('documentation.sidebar_order', [$this->fixtureModule]);
        Config::set('documentation.document_metadata', []);
        Config::set('documentation.route_mappings', []);
        Config::set('documentation.route_help_targets', []);
        Config::set('documentation.context_help', []);
        $this->isolateFixtureModule();
        clearstatcache();
        Cache::flush();

        [$organization, $user] = $this->organizationOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('knowledge.health'))
            ->assertOk()
            ->assertSee('All Checks Passed');
    }

    public function test_validation_failures_are_visible_on_health_page(): void
    {
        [$organization, $user] = $this->organizationOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('knowledge.health'))
            ->assertOk()
            ->assertSee('Validation Errors');
    }

    private function isolateFixtureModule(): void
    {
        foreach (array_keys((array) config('documentation.modules')) as $moduleKey) {
            if ($moduleKey === $this->fixtureModule) {
                continue;
            }

            Config::set("documentation.modules.{$moduleKey}.enabled", false);
        }
    }

    private function createFixtureModule(): void
    {
        $base = base_path('docs/'.$this->fixtureModule);
        File::ensureDirectoryExists($base);
        File::put($base.'/overview.md', "# Overview\n\nFeature fixture overview.\n");
    }

    private function createCompleteFixtureModule(): void
    {
        $base = base_path('docs/'.$this->fixtureModule);
        $sections = [
            'user-guide/overview.md' => "# User Guide\n\n- item\n",
            'admin-guide/overview.md' => "# Administrator Guide\n\n- item\n",
            'business-process/overview.md' => "# Business Processes\n\n- item\n",
            'architecture/overview.md' => "# Technical Architecture\n\n- item\n",
            'api/overview.md' => "# API Reference\n\n- item\n",
            'configuration/overview.md' => "# Configuration\n\n- item\n",
            'troubleshooting/overview.md' => "# Troubleshooting\n\n- item\n",
            'faq/overview.md' => "# FAQ\n\n- item\n",
            'release-notes/overview.md' => "# Release Notes\n\n## Version\n\n- Release: v1.0.0\n\n## Highlights\n\n- Initial release\n",
        ];

        foreach ($sections as $relativePath => $content) {
            $path = $base.'/'.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $content);
        }

        File::put($base.'/overview.md', "# Overview\n\nComplete feature fixture.\n");
    }

    private function configureValidationForFixture(): void
    {
        Config::set('documentation.modules', array_merge(
            (array) config('documentation.modules'),
            [
                $this->fixtureModule => [
                    'enabled' => true,
                    'name' => 'Validation Feature Fixture',
                    'icon' => 'check',
                    'searchable' => false,
                ],
            ]
        ));
        Config::set('documentation.sidebar_order', array_merge(
            (array) config('documentation.sidebar_order'),
            [$this->fixtureModule]
        ));
        Config::set('documentation.validation.module_exemptions', ['faq', 'release-notes']);
        $this->isolateFixtureModule();
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

    /**
     * @return array{0: Organization, 1: User}
     */
    private function memberWithoutSettingsPermission(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'employee');

        return [$organization, $user];
    }
}
