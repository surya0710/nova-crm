<?php

namespace Tests\Feature;

use App\Data\MetadataQueryFilter;
use App\Data\MetadataQueryRequest;
use App\Data\MetadataQuerySort;
use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\Organization;
use App\Models\User;
use App\Services\MetadataProjectionService;
use App\Services\MetadataQueryDefinitionService;
use App\Services\MetadataQueryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class MetadataQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_definition_service_resolves_active_definitions_by_capability_and_caches_lookups(): void
    {
        [, $organization] = $this->setupOrganization();
        $filterable = $this->field($organization, 'lead', 'destination_country', 'text', flags: ['is_filterable' => true]);
        $this->field($organization, 'lead', 'internal_note', 'text');
        $this->field($organization, 'lead', 'inactive_filter', 'text', status: 'inactive', flags: ['is_filterable' => true]);
        $service = app(MetadataQueryDefinitionService::class);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $first = $service->definitionsFor($organization->id, 'lead', 'filter');
        $second = $service->definitionsFor($organization->id, 'lead', 'filter');

        $this->assertTrue($first->has('destination_country'));
        $this->assertTrue($second->has('destination_country'));
        $this->assertSame($filterable->id, $first->get('destination_country')->id);
        $this->assertFalse($first->has('internal_note'));
        $this->assertFalse($first->has('inactive_filter'));
        $this->assertCount(1, DB::getQueryLog());
    }

    public function test_text_operators_compile_against_projection_rows(): void
    {
        [, $organization] = $this->setupOrganization();
        $this->field($organization, 'lead', 'destination_country', 'text', flags: ['is_filterable' => true]);
        $canada = $this->lead($organization, 'Canada');
        $australia = $this->lead($organization, 'Australia');
        $this->lead($organization, null);

        $this->assertSame([$canada->id], $this->ids($organization, [
            new MetadataQueryFilter('destination_country', 'equals', 'Canada'),
        ]));
        $this->assertSame([$canada->id], $this->ids($organization, [
            new MetadataQueryFilter('destination_country', 'contains', 'nad'),
        ]));
        $this->assertSame([$canada->id], $this->ids($organization, [
            new MetadataQueryFilter('destination_country', 'starts_with', 'Can'),
        ]));
        $this->assertSame([$australia->id], $this->ids($organization, [
            new MetadataQueryFilter('destination_country', 'ends_with', 'lia'),
        ]));
    }

    public function test_numeric_operators_compile_against_projection_rows(): void
    {
        [, $organization] = $this->setupOrganization();
        $this->field($organization, 'lead', 'ielts_score', 'decimal', flags: ['is_filterable' => true]);
        $low = $this->lead($organization, null, ['ielts_score' => 6.5]);
        $mid = $this->lead($organization, null, ['ielts_score' => 7.5]);
        $high = $this->lead($organization, null, ['ielts_score' => 8.5]);

        $this->assertSame([$mid->id], $this->ids($organization, [
            new MetadataQueryFilter('ielts_score', 'equals', 7.5),
        ]));
        $this->assertSame([$high->id], $this->ids($organization, [
            new MetadataQueryFilter('ielts_score', 'greater_than', 8),
        ]));
        $this->assertSame([$mid->id, $high->id], $this->ids($organization, [
            new MetadataQueryFilter('ielts_score', 'greater_than_or_equal', 7.5),
        ]));
        $this->assertSame([$low->id], $this->ids($organization, [
            new MetadataQueryFilter('ielts_score', 'less_than', 7),
        ]));
        $this->assertSame([$low->id, $mid->id], $this->ids($organization, [
            new MetadataQueryFilter('ielts_score', 'between', [6, 8]),
        ]));
    }

    public function test_date_and_boolean_operators_compile_against_projection_rows(): void
    {
        [, $organization] = $this->setupOrganization();
        $this->field($organization, 'lead', 'arrival_date', 'date', flags: ['is_filterable' => true]);
        $this->field($organization, 'lead', 'approved', 'boolean', flags: ['is_filterable' => true]);
        $early = $this->lead($organization, null, ['arrival_date' => '2026-01-01', 'approved' => true]);
        $late = $this->lead($organization, null, ['arrival_date' => '2026-12-01', 'approved' => false]);

        $this->assertSame([$early->id], $this->ids($organization, [
            new MetadataQueryFilter('arrival_date', 'before', '2026-06-01'),
        ]));
        $this->assertSame([$late->id], $this->ids($organization, [
            new MetadataQueryFilter('arrival_date', 'after', '2026-06-01'),
        ]));
        $this->assertSame([$early->id], $this->ids($organization, [
            new MetadataQueryFilter('arrival_date', 'between', ['2025-12-01', '2026-02-01']),
        ]));
        $this->assertSame([$early->id], $this->ids($organization, [
            new MetadataQueryFilter('approved', 'true'),
        ]));
        $this->assertSame([$late->id], $this->ids($organization, [
            new MetadataQueryFilter('approved', 'false'),
        ]));
    }

    public function test_select_and_multi_select_operators_compile_against_projection_rows(): void
    {
        [, $organization] = $this->setupOrganization();
        $this->field($organization, 'lead', 'visa_type', 'select', flags: ['is_filterable' => true]);
        $this->field($organization, 'lead', 'preferred_countries', 'multi_select', flags: ['is_filterable' => true]);
        $studentCanada = $this->lead($organization, null, [
            'visa_type' => 'student',
            'preferred_countries' => ['canada', 'australia'],
        ]);
        $visitorUk = $this->lead($organization, null, [
            'visa_type' => 'visitor',
            'preferred_countries' => ['uk'],
        ]);
        $workerCanadaUk = $this->lead($organization, null, [
            'visa_type' => 'worker',
            'preferred_countries' => ['canada', 'uk'],
        ]);

        $this->assertSame([$studentCanada->id], $this->ids($organization, [
            new MetadataQueryFilter('visa_type', 'equals', 'student'),
        ]));
        $this->assertSame([$studentCanada->id, $visitorUk->id], $this->ids($organization, [
            new MetadataQueryFilter('visa_type', 'in', ['student', 'visitor']),
        ]));
        $this->assertSame([$workerCanadaUk->id], $this->ids($organization, [
            new MetadataQueryFilter('visa_type', 'not_in', ['student', 'visitor']),
        ]));
        $this->assertSame([$studentCanada->id, $workerCanadaUk->id], $this->ids($organization, [
            new MetadataQueryFilter('preferred_countries', 'contains_any', ['canada']),
        ]));
        $this->assertSame([$workerCanadaUk->id], $this->ids($organization, [
            new MetadataQueryFilter('preferred_countries', 'contains_all', ['canada', 'uk']),
        ]));
        $this->assertSame([$visitorUk->id], $this->ids($organization, [
            new MetadataQueryFilter('preferred_countries', 'contains_none', ['canada']),
        ]));
    }

    public function test_empty_and_not_empty_operators_respect_falsey_values(): void
    {
        [, $organization] = $this->setupOrganization();
        $this->field($organization, 'lead', 'approved', 'boolean', flags: ['is_filterable' => true]);
        $falseLead = $this->lead($organization, null, ['approved' => false]);
        $emptyLead = $this->lead($organization);

        $this->assertSame([$emptyLead->id], $this->ids($organization, [
            new MetadataQueryFilter('approved', 'empty'),
        ]));
        $this->assertSame([$falseLead->id], $this->ids($organization, [
            new MetadataQueryFilter('approved', 'not_empty'),
        ]));
    }

    public function test_validation_rejects_unsupported_unknown_inactive_and_non_capable_fields(): void
    {
        [, $organization] = $this->setupOrganization();
        $this->field($organization, 'lead', 'destination_country', 'text', flags: ['is_filterable' => true]);
        $this->field($organization, 'lead', 'inactive_field', 'text', status: 'inactive', flags: ['is_filterable' => true]);
        $this->field($organization, 'lead', 'not_filterable', 'text');

        try {
            $this->ids($organization, [new MetadataQueryFilter('destination_country', 'greater_than', 'A')]);
            $this->fail('Unsupported operator was not rejected.');
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);
        }

        try {
            $this->ids($organization, [new MetadataQueryFilter('missing_key', 'equals', 'x')]);
            $this->fail('Missing key was not rejected.');
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);
        }

        try {
            $this->ids($organization, [new MetadataQueryFilter('inactive_field', 'equals', 'x')]);
            $this->fail('Inactive field was not rejected.');
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);
        }

        try {
            $this->ids($organization, [new MetadataQueryFilter('not_filterable', 'equals', 'x')]);
            $this->fail('Non-filterable field was not rejected.');
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);
        }
    }

    public function test_context_capabilities_are_validated_without_consumer_integrations(): void
    {
        [, $organization] = $this->setupOrganization();
        $this->field($organization, 'lead', 'destination_country', 'text', flags: [
            'is_filterable' => true,
            'is_searchable' => false,
            'is_reportable' => false,
            'is_api_visible' => false,
        ]);

        $this->expectException(InvalidArgumentException::class);
        app(MetadataQueryService::class)->apply(Lead::query(), new MetadataQueryRequest(
            entityType: 'lead',
            filters: [new MetadataQueryFilter('destination_country', 'equals', 'Canada')],
            context: 'api',
            organizationId: $organization->id,
        ));
    }

    public function test_search_capability_is_validated_without_applying_search_constraints(): void
    {
        [, $organization] = $this->setupOrganization();
        $this->field($organization, 'lead', 'destination_country', 'text', flags: ['is_searchable' => false]);

        $this->expectException(InvalidArgumentException::class);
        app(MetadataQueryService::class)->apply(Lead::query(), new MetadataQueryRequest(
            entityType: 'lead',
            search: ['term' => 'canada', 'keys' => ['destination_country']],
            organizationId: $organization->id,
        ));
    }

    public function test_projection_constraints_are_tenant_isolated(): void
    {
        [, $tenantA] = $this->setupOrganization();
        [, $tenantB] = $this->setupOrganization();
        $this->field($tenantA, 'lead', 'destination_country', 'text', flags: ['is_filterable' => true]);
        $this->field($tenantB, 'lead', 'destination_country', 'text', flags: ['is_filterable' => true]);
        $tenantALead = $this->lead($tenantA, 'Australia');
        $this->lead($tenantB, 'Canada');

        $this->assertSame([], $this->ids($tenantA, [
            new MetadataQueryFilter('destination_country', 'equals', 'Canada'),
        ]));
        $this->assertSame([$tenantALead->id], $this->ids($tenantA, [
            new MetadataQueryFilter('destination_country', 'equals', 'Australia'),
        ]));
    }

    public function test_metadata_sorting_is_deterministic_and_places_nulls_last(): void
    {
        [, $organization] = $this->setupOrganization();
        $this->field($organization, 'lead', 'ielts_score', 'decimal', flags: ['is_sortable' => true]);
        $eightA = $this->lead($organization, null, ['ielts_score' => 8.0]);
        $eightB = $this->lead($organization, null, ['ielts_score' => 8.0]);
        $null = $this->lead($organization);
        $seven = $this->lead($organization, null, ['ielts_score' => 7.0]);

        $builder = app(MetadataQueryService::class)->apply(Lead::query(), new MetadataQueryRequest(
            entityType: 'lead',
            sort: new MetadataQuerySort('ielts_score', 'asc'),
            organizationId: $organization->id,
        ));

        $this->assertSame([$seven->id, $eightA->id, $eightB->id, $null->id], $builder->pluck('leads.id')->all());
    }

    public function test_compiler_returns_reusable_eloquent_builder_and_uses_cached_definition_lookup(): void
    {
        [, $organization] = $this->setupOrganization();
        $this->field($organization, 'lead', 'destination_country', 'text', flags: ['is_filterable' => true]);
        $this->field($organization, 'lead', 'visa_type', 'select', flags: ['is_filterable' => true]);
        $this->lead($organization, 'Canada', ['visa_type' => 'student']);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $builder = app(MetadataQueryService::class)->apply(Lead::query(), new MetadataQueryRequest(
            entityType: 'lead',
            filters: [
                new MetadataQueryFilter('destination_country', 'equals', 'Canada'),
                new MetadataQueryFilter('visa_type', 'equals', 'student'),
            ],
            organizationId: $organization->id,
        ));

        $this->assertInstanceOf(Builder::class, $builder);
        $this->assertSame(1, $builder->count());
        $definitionQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains($query['query'], 'metadata_field_definitions'))
            ->count();
        $this->assertSame(1, $definitionQueries);
    }

    protected function ids(Organization $organization, array $filters): array
    {
        return app(MetadataQueryService::class)
            ->apply(Lead::query(), new MetadataQueryRequest(
                entityType: 'lead',
                filters: $filters,
                organizationId: $organization->id,
            ))
            ->orderBy('leads.id')
            ->pluck('leads.id')
            ->all();
    }

    protected function lead(Organization $organization, ?string $destinationCountry = null, array $customFields = []): Lead
    {
        if ($destinationCountry !== null) {
            $customFields['destination_country'] = $destinationCountry;
        }

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'custom_fields' => $customFields === [] ? null : $customFields,
        ]);

        app(MetadataProjectionService::class)->sync($lead);

        return $lead;
    }

    protected function setupOrganization(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');

        return [$user, $organization];
    }

    protected function field(
        Organization $organization,
        string $entity,
        string $key,
        string $type,
        string $status = 'active',
        array $flags = [],
    ): MetadataFieldDefinition {
        return MetadataFieldDefinition::query()->create(array_merge([
            'organization_id' => $organization->id,
            'entity_type' => $entity,
            'key' => $key,
            'label' => str($key)->headline()->toString(),
            'type' => $type,
            'status' => $status,
            'published_at' => $status === 'active' ? now() : null,
            'activated_at' => $status === 'active' ? now() : null,
        ], $flags));
    }
}
