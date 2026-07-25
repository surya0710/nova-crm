<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\User;
use App\Services\DocumentationService;
use Tests\TestCase;

class DocumentationHelpTest extends TestCase
{
    public function test_context_resolution_returns_documentation_for_mapped_route(): void
    {
        $service = app(DocumentationService::class);
        $context = $service->resolveContextForRoute($this->user(), 'leads.index');

        $this->assertTrue($context['available']);
        $this->assertSame('available', $context['status']);
        $this->assertStringContainsString('knowledge/crm/user-guide/leads', $context['url']);
        $this->assertSame('crm/user-guide/leads', $context['slug']);
        $this->assertContains('CRM', $context['breadcrumb']);
    }

    public function test_help_availability_is_false_for_unmapped_route(): void
    {
        $service = app(DocumentationService::class);

        $this->assertFalse($service->isHelpAvailable($this->user(), 'dashboard'));
    }

    public function test_metadata_is_loaded_for_document_slug(): void
    {
        $service = app(DocumentationService::class);
        $metadata = $service->getDocumentMetadata('crm/user-guide/leads');

        $this->assertSame('crm', $metadata['module']);
        $this->assertSame('user-guide/leads', $metadata['page']);
        $this->assertContains('leads', $metadata['keywords']);
        $this->assertNotEmpty($metadata['related']);
    }

    public function test_related_documents_are_generated_without_duplicates(): void
    {
        $service = app(DocumentationService::class);
        $document = $service->findDocument($this->user(), 'crm', 'user-guide/leads');

        $this->assertNotNull($document);
        $related = $service->getRelatedDocuments($this->user(), $document);
        $titles = $related->pluck('title')->all();

        $this->assertNotEmpty($related);
        $this->assertSame($titles, array_values(array_unique($titles)));
        $this->assertFalse($related->pluck('slug')->contains('crm/user-guide/leads'));
    }

    public function test_route_mapping_resolves_hrms_payroll_documentation(): void
    {
        $service = app(DocumentationService::class);
        $user = User::factory()->create(['is_super_admin' => true]);
        $context = $service->resolveContextForRoute($user, 'hrms.payroll.index');

        $this->assertTrue($context['available']);
        $this->assertSame('hrms/user-guide/payroll', $context['slug']);
    }

    public function test_deep_link_appends_valid_anchor(): void
    {
        $service = app(DocumentationService::class);
        $document = $service->findDocument($this->user(), 'getting-started', 'overview');

        $this->assertNotNull($document);
        $anchor = $document['toc'][0]['anchor'] ?? 'purpose';
        $this->assertTrue($service->validateAnchor('getting-started/overview', $anchor));

        $url = $service->buildDeepLink($this->user(), 'getting-started/overview', $anchor);
        $this->assertNotNull($url);
        $this->assertStringEndsWith('#'.$anchor, $url);
    }

    public function test_deep_link_omits_invalid_anchor(): void
    {
        $service = app(DocumentationService::class);
        $url = $service->buildDeepLink($this->user(), 'crm/user-guide/leads', 'missing-anchor');

        $this->assertNotNull($url);
        $this->assertStringNotContainsString('#missing-anchor', $url);
    }

    public function test_help_targets_include_multiple_categories_for_crm_route(): void
    {
        $service = app(DocumentationService::class);
        $targets = $service->resolveHelpTargets($this->user(), 'leads.index');

        $this->assertGreaterThan(1, $targets->count());
        $this->assertTrue($targets->pluck('label')->contains('User Guide'));
        $this->assertTrue($targets->pluck('label')->contains('API Reference'));
    }

    private function user(): User
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'organization-owner');

        return $user;
    }
}
