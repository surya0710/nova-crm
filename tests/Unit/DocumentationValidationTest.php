<?php

namespace Tests\Unit;

use App\Services\DocumentationValidationService;
use App\Services\Documentation\Validators\LinkValidator;
use App\Services\Documentation\Validators\MetadataValidator;
use App\Services\Documentation\Validators\ModuleCompletenessValidator;
use App\Services\Documentation\Validators\RelatedDocumentValidator;
use App\Services\Documentation\Validators\ReleaseNotesValidator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DocumentationValidationTest extends TestCase
{
    private string $fixtureModule = 'doc-validation-fixture';

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

    public function test_module_completeness_detects_missing_required_documents(): void
    {
        $validator = app(ModuleCompletenessValidator::class);
        $issues = collect($validator->validate());

        $this->assertTrue(
            $issues->contains(fn ($issue): bool => $issue->code === 'missing_document' && $issue->module === $this->fixtureModule)
        );
    }

    public function test_metadata_validation_flags_invalid_optional_metadata(): void
    {
        Config::set('documentation.document_metadata.'.$this->fixtureModule.'/overview', [
            'keywords' => 'not-an-array',
            'related' => 'also-not-an-array',
        ]);
        Cache::flush();

        $validator = app(MetadataValidator::class);
        $issues = collect($validator->validate())
            ->filter(fn ($issue): bool => $issue->slug === $this->fixtureModule.'/overview');

        $this->assertTrue($issues->contains(fn ($issue): bool => $issue->code === 'invalid_metadata'));
        $this->assertGreaterThanOrEqual(2, $issues->count());
    }

    public function test_link_validator_detects_broken_internal_links(): void
    {
        File::put(
            base_path('docs/'.$this->fixtureModule.'/broken-link.md'),
            "# Broken Link\n\nSee [missing](../doc-validation-fixture/missing-target.md).\n"
        );
        clearstatcache();
        Cache::flush();

        $validator = app(LinkValidator::class);
        $issues = collect($validator->validate());

        $this->assertTrue($issues->contains(fn ($issue): bool => $issue->code === 'broken_link'));
    }

    public function test_link_validator_detects_invalid_anchors(): void
    {
        File::put(
            base_path('docs/'.$this->fixtureModule.'/anchor-link.md'),
            "# Anchor Link\n\nSee [overview](overview.md#missing-anchor).\n"
        );
        File::put(
            base_path('docs/'.$this->fixtureModule.'/overview.md'),
            "# Overview\n\n## Purpose\n\nContent.\n"
        );
        clearstatcache();
        Cache::flush();

        $validator = app(LinkValidator::class);
        $issues = collect($validator->validate())
            ->filter(fn ($issue): bool => $issue->slug === $this->fixtureModule.'/anchor-link');

        $this->assertTrue($issues->contains(fn ($issue): bool => $issue->code === 'invalid_anchor'));
    }

    public function test_related_document_validator_detects_missing_and_duplicate_entries(): void
    {
        Config::set('documentation.document_metadata.'.$this->fixtureModule.'/overview', [
            'related' => [
                $this->fixtureModule.'/overview',
                'doc-validation-fixture/missing-related',
                'doc-validation-fixture/missing-related',
            ],
        ]);
        Cache::flush();

        $validator = app(RelatedDocumentValidator::class);
        $issues = collect($validator->validate())
            ->filter(fn ($issue): bool => $issue->slug === $this->fixtureModule.'/overview');

        $this->assertTrue($issues->contains(fn ($issue): bool => $issue->code === 'circular_related_document'));
        $this->assertTrue($issues->contains(fn ($issue): bool => $issue->code === 'duplicate_related_document'));
        $this->assertTrue($issues->contains(fn ($issue): bool => $issue->code === 'missing_related_document'));
    }

    public function test_release_notes_validator_checks_structure(): void
    {
        File::ensureDirectoryExists(base_path('docs/'.$this->fixtureModule.'/release-notes'));
        File::put(
            base_path('docs/'.$this->fixtureModule.'/release-notes/overview.md'),
            "# Release Notes\n\nNo structured release content.\n"
        );
        clearstatcache();
        Cache::flush();

        $validator = app(ReleaseNotesValidator::class);
        $issues = collect($validator->validate())
            ->filter(fn ($issue): bool => $issue->module === $this->fixtureModule);

        $this->assertTrue($issues->contains(fn ($issue): bool => $issue->code === 'invalid_release_notes'));
    }

    public function test_health_calculation_returns_failed_when_errors_exist(): void
    {
        $service = app(DocumentationValidationService::class);
        $report = $service->validate(useCache: false);

        $this->assertSame('failed', $report['status']);
        $this->assertGreaterThan(0, $report['statistics']['errors']);
    }

    public function test_health_calculation_returns_healthy_for_complete_fixture_module(): void
    {
        $this->createCompleteFixtureModule();
        clearstatcache();
        Cache::flush();

        Config::set('documentation.validation.module_exemptions', ['faq', 'release-notes']);
        Config::set('documentation.modules', [
            $this->fixtureModule => [
                'enabled' => true,
                'name' => 'Validation Fixture',
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

        $service = app(DocumentationValidationService::class);
        $report = $service->validate(useCache: false);

        $this->assertSame('healthy', $report['status']);
        $this->assertSame(0, $report['statistics']['errors']);
        $this->assertSame(0, $report['statistics']['warnings']);
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
        File::put($base.'/overview.md', "# Overview\n\nFixture module overview.\n");
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

        File::put($base.'/overview.md', "# Overview\n\nComplete fixture module.\n");
    }

    private function configureValidationForFixture(): void
    {
        Config::set('documentation.modules', array_merge(
            (array) config('documentation.modules'),
            [
                $this->fixtureModule => [
                    'enabled' => true,
                    'name' => 'Validation Fixture',
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
    }
}
