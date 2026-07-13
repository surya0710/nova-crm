<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\User;
use App\Services\MetadataProjectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MetadataApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_leads_api_filters_by_metadata_and_composes_with_static_filters(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');
        $this->field($organization, 'lead', 'destination_country', 'text', [
            'is_filterable' => true,
            'is_api_visible' => true,
        ]);
        $match = $this->lead($organization, $user, 'Canada Match', 'new', ['destination_country' => 'Canada']);
        $this->lead($organization, $user, 'Canada Lost', 'lost', ['destination_country' => 'Canada']);
        $this->lead($organization, $user, 'Australia Match', 'new', ['destination_country' => 'Australia']);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/leads?'.http_build_query([
            'status' => 'new',
            'metadata_filters' => [
                ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
            ],
        ]), $this->apiHeaders($organization));

        $response->assertOk();
        $response->assertJsonPath('data.0.name', $match->name);
        $response->assertJsonCount(1, 'data');
    }

    public function test_leads_api_sorts_by_metadata_and_keeps_pagination(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');
        $this->field($organization, 'lead', 'ielts_score', 'decimal', [
            'is_sortable' => true,
            'is_api_visible' => true,
        ]);
        $seven = $this->lead($organization, $user, 'Seven Lead', 'new', ['ielts_score' => 7.0]);
        $eight = $this->lead($organization, $user, 'Eight Lead', 'new', ['ielts_score' => 8.0]);
        Lead::factory()->count(14)->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'status' => 'new',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/leads?'.http_build_query([
            'metadata_sort' => ['key' => 'ielts_score', 'direction' => 'asc'],
            'per_page' => 15,
        ]), $this->apiHeaders($organization));

        $response->assertOk();
        $response->assertJsonPath('data.0.name', $seven->name);
        $response->assertJsonPath('data.1.name', $eight->name);
        $response->assertJsonPath('meta.per_page', 15);
        $response->assertJsonPath('meta.last_page', 2);
    }

    public function test_leads_api_exposes_only_api_visible_custom_fields(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');
        $this->field($organization, 'lead', 'destination_country', 'text', [
            'is_api_visible' => true,
        ]);
        $this->field($organization, 'lead', 'internal_note', 'text', [
            'is_api_visible' => false,
        ]);
        $lead = $this->lead($organization, $user, 'Visible Lead', 'new', [
            'destination_country' => 'Canada',
            'internal_note' => 'Hidden note',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson("/api/v1/leads/{$lead->id}", $this->apiHeaders($organization));

        $response->assertOk();
        $response->assertJsonPath('data.custom_fields.destination_country', 'Canada');
        $response->assertJsonMissingPath('data.custom_fields.internal_note');
    }

    public function test_customers_api_filters_sorts_and_exposes_metadata(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');
        $this->field($organization, 'customer', 'segment', 'text', [
            'is_filterable' => true,
            'is_api_visible' => true,
        ]);
        $this->field($organization, 'customer', 'annual_spend', 'currency', [
            'is_sortable' => true,
            'is_api_visible' => true,
        ]);
        $low = $this->customer($organization, $user, 'Low Customer', 'active', [
            'segment' => 'enterprise',
            'annual_spend' => 1000,
        ]);
        $high = $this->customer($organization, $user, 'High Customer', 'active', [
            'segment' => 'enterprise',
            'annual_spend' => 5000,
        ]);
        $this->customer($organization, $user, 'Retail Customer', 'active', [
            'segment' => 'retail',
            'annual_spend' => 9000,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/customers?'.http_build_query([
            'metadata_filters' => [
                ['key' => 'segment', 'operator' => 'equals', 'value' => 'enterprise'],
            ],
            'metadata_sort' => ['key' => 'annual_spend', 'direction' => 'desc'],
        ]), $this->apiHeaders($organization));

        $response->assertOk();
        $response->assertJsonPath('data.0.name', $high->name);
        $response->assertJsonPath('data.1.name', $low->name);
        $response->assertJsonPath('data.0.custom_fields.segment', 'enterprise');
    }

    public function test_opportunities_api_filters_sorts_and_exposes_metadata(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');
        $this->field($organization, 'opportunity', 'region', 'text', [
            'is_filterable' => true,
            'is_api_visible' => true,
        ]);
        $this->field($organization, 'opportunity', 'deal_score', 'number', [
            'is_sortable' => true,
            'is_api_visible' => true,
        ]);
        $west = $this->opportunity($organization, $user, 'West Deal', 'prospect', [
            'region' => 'west',
            'deal_score' => 80,
        ]);
        $this->opportunity($organization, $user, 'East Deal', 'prospect', [
            'region' => 'east',
            'deal_score' => 90,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/opportunities?'.http_build_query([
            'metadata_filters' => [
                ['key' => 'region', 'operator' => 'equals', 'value' => 'west'],
            ],
            'metadata_sort' => ['key' => 'deal_score', 'direction' => 'desc'],
        ]), $this->apiHeaders($organization));

        $response->assertOk();
        $response->assertJsonPath('data.0.title', $west->title);
        $response->assertJsonPath('data.0.custom_fields.region', 'west');
        $response->assertJsonCount(1, 'data');
    }

    public function test_api_hides_sensitive_inactive_and_non_api_visible_metadata(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');
        $this->field($organization, 'lead', 'destination_country', 'text', [
            'is_api_visible' => true,
        ]);
        $this->field($organization, 'lead', 'passport_number', 'text', [
            'is_api_visible' => true,
            'is_sensitive' => true,
        ]);
        $this->field($organization, 'lead', 'old_destination', 'text', [
            'is_api_visible' => true,
            'status' => 'inactive',
        ]);
        $this->field($organization, 'lead', 'internal_code', 'text', [
            'is_api_visible' => false,
        ]);
        $lead = $this->lead($organization, $user, 'Security Lead', 'new', [
            'destination_country' => 'Canada',
            'passport_number' => 'P-123',
            'old_destination' => 'France',
            'internal_code' => 'SECRET',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson("/api/v1/leads/{$lead->id}", $this->apiHeaders($organization));

        $response->assertOk();
        $response->assertJsonPath('data.custom_fields.destination_country', 'Canada');
        $response->assertJsonMissingPath('data.custom_fields.passport_number');
        $response->assertJsonMissingPath('data.custom_fields.old_destination');
        $response->assertJsonMissingPath('data.custom_fields.internal_code');
    }

    public function test_api_metadata_queries_are_tenant_isolated(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');
        [, $otherOrganization] = $this->setupApiUser('sales-executive');
        $this->field($organization, 'lead', 'destination_country', 'text', [
            'is_filterable' => true,
            'is_api_visible' => true,
        ]);
        $this->field($otherOrganization, 'lead', 'destination_country', 'text', [
            'is_filterable' => true,
            'is_api_visible' => true,
        ]);
        $visible = $this->lead($organization, $user, 'Tenant A Lead', 'new', ['destination_country' => 'Canada']);
        $this->lead($otherOrganization, User::factory()->create(), 'Tenant B Lead', 'new', ['destination_country' => 'Canada']);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/leads?'.http_build_query([
            'metadata_filters' => [
                ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
            ],
        ]), $this->apiHeaders($organization));

        $response->assertOk();
        $response->assertJsonPath('data.0.name', $visible->name);
        $response->assertJsonCount(1, 'data');
    }

    public function test_api_metadata_endpoints_respect_rbac_permissions(): void
    {
        [$user, $organization] = $this->setupApiUser('hr');
        $this->field($organization, 'lead', 'destination_country', 'text', [
            'is_filterable' => true,
            'is_api_visible' => true,
        ]);
        $this->lead($organization, $user, 'RBAC Lead', 'new', ['destination_country' => 'Canada']);

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/leads', $this->apiHeaders($organization))
            ->assertForbidden();

        $this->getJson('/api/v1/customers', $this->apiHeaders($organization))
            ->assertForbidden();

        $this->getJson('/api/v1/opportunities', $this->apiHeaders($organization))
            ->assertForbidden();
    }

    public function test_api_rejects_unknown_metadata_fields_and_invalid_operators(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');
        $this->field($organization, 'lead', 'destination_country', 'text', [
            'is_filterable' => true,
            'is_api_visible' => true,
        ]);

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/leads?'.http_build_query([
            'metadata_filters' => [
                ['key' => 'missing_field', 'operator' => 'equals', 'value' => 'Canada'],
            ],
        ]), $this->apiHeaders($organization))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['metadata_filters.0.key']);

        $this->getJson('/api/v1/leads?'.http_build_query([
            'metadata_filters' => [
                ['key' => 'destination_country', 'operator' => 'fuzzy_match', 'value' => 'Canada'],
            ],
        ]), $this->apiHeaders($organization))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['metadata_filters']);
    }

    public function test_api_rejects_non_filterable_non_sortable_and_non_api_visible_metadata(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');
        $this->field($organization, 'lead', 'destination_country', 'text', [
            'is_filterable' => false,
            'is_api_visible' => true,
        ]);
        $this->field($organization, 'lead', 'ielts_score', 'decimal', [
            'is_sortable' => false,
            'is_api_visible' => true,
        ]);
        $this->field($organization, 'lead', 'internal_code', 'text', [
            'is_filterable' => true,
            'is_api_visible' => false,
        ]);

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/leads?'.http_build_query([
            'metadata_filters' => [
                ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
            ],
        ]), $this->apiHeaders($organization))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['metadata_filters.0.key']);

        $this->getJson('/api/v1/leads?'.http_build_query([
            'metadata_sort' => ['key' => 'ielts_score', 'direction' => 'asc'],
        ]), $this->apiHeaders($organization))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['metadata_sort.key']);

        $this->getJson('/api/v1/leads?'.http_build_query([
            'metadata_filters' => [
                ['key' => 'internal_code', 'operator' => 'equals', 'value' => 'ABC'],
            ],
        ]), $this->apiHeaders($organization))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['metadata_filters.0.key']);
    }

    public function test_api_rejects_malformed_metadata_filter_requests(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/leads?'.http_build_query([
            'metadata_filters' => [
                ['key' => 'destination_country'],
            ],
        ]), $this->apiHeaders($organization))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['metadata_filters.0.operator']);
    }

    public function test_api_metadata_queries_use_projections_not_json_columns(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');
        $this->field($organization, 'lead', 'destination_country', 'text', [
            'is_filterable' => true,
            'is_api_visible' => true,
        ]);
        $this->lead($organization, $user, 'Projection Lead', 'new', ['destination_country' => 'Canada']);
        $queries = [];

        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/leads?'.http_build_query([
            'metadata_filters' => [
                ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
            ],
        ]), $this->apiHeaders($organization))
            ->assertOk();

        $this->assertTrue(collect($queries)->contains(fn (string $sql) => str_contains($sql, 'metadata_value_projections')));
        $this->assertFalse(collect($queries)->contains(fn (string $sql) => str_contains($sql, 'custom_fields')));
    }

    public function test_api_metadata_listing_does_not_trigger_n_plus_one_projection_queries(): void
    {
        [$user, $organization] = $this->setupApiUser('manager');
        $this->field($organization, 'lead', 'destination_country', 'text', [
            'is_filterable' => true,
            'is_api_visible' => true,
        ]);

        for ($index = 0; $index < 3; $index++) {
            $this->lead($organization, $user, "Bulk Lead {$index}", 'new', [
                'destination_country' => "BulkTerm{$index}",
            ]);
        }

        $queries = [];

        DB::listen(function ($query) use (&$queries): void {
            if (str_contains($query->sql, 'metadata_value_projections')) {
                $queries[] = $query->sql;
            }
        });

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/leads?'.http_build_query([
            'metadata_filters' => [
                ['key' => 'destination_country', 'operator' => 'contains', 'value' => 'BulkTerm'],
            ],
        ]), $this->apiHeaders($organization))
            ->assertOk();

        $this->assertLessThanOrEqual(2, count($queries));
    }

    public function test_existing_leads_api_parameters_remain_backward_compatible(): void
    {
        [$user, $organization] = $this->setupApiUser('organization-owner');
        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Legacy API Lead',
            'status' => 'new',
            'created_by' => $user->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/leads?status=new', $this->apiHeaders($organization));

        $response->assertOk();
        $response->assertJsonPath('data.0.name', $lead->name);
        $response->assertJsonStructure([
            'data' => [
                ['id', 'name', 'status', 'created_at', 'updated_at'],
            ],
            'links',
            'meta',
        ]);
    }

    protected function setupApiUser(string $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    /**
     * @return array<string, string>
     */
    protected function apiHeaders(Organization $organization): array
    {
        return [
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ];
    }

    protected function field(Organization $organization, string $entity, string $key, string $type, array $attributes = []): MetadataFieldDefinition
    {
        return MetadataFieldDefinition::query()->create([
            'organization_id' => $organization->id,
            'entity_type' => $entity,
            'key' => $key,
            'label' => str($key)->headline()->toString(),
            'type' => $type,
            'status' => 'active',
            'published_at' => now(),
            'activated_at' => now(),
            ...$attributes,
        ]);
    }

    protected function lead(Organization $organization, User $user, string $name, string $status, array $customFields = []): Lead
    {
        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'name' => $name,
            'status' => $status,
            'custom_fields' => $customFields === [] ? null : $customFields,
        ]);

        app(MetadataProjectionService::class)->sync($lead);

        return $lead;
    }

    protected function customer(Organization $organization, User $user, string $name, string $status, array $customFields = []): Customer
    {
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'name' => $name,
            'company' => $name,
            'status' => $status,
            'custom_fields' => $customFields === [] ? null : $customFields,
        ]);

        app(MetadataProjectionService::class)->sync($customer);

        return $customer;
    }

    protected function opportunity(Organization $organization, User $user, string $title, string $stage, array $customFields = []): Opportunity
    {
        $opportunity = Opportunity::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'title' => $title,
            'stage' => $stage,
            'custom_fields' => $customFields === [] ? null : $customFields,
        ]);

        app(MetadataProjectionService::class)->sync($opportunity);

        return $opportunity;
    }
}
