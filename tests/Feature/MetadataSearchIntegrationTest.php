<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\User;
use App\Services\MetadataProjectionService;
use App\Services\MetadataSearchService;
use App\Services\SearchService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MetadataSearchIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_search_finds_lead_by_searchable_metadata_field(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'lead', 'destination_country', 'text', ['is_searchable' => true]);
        $lead = $this->lead($organization, $user, 'Plain Lead', 'new', ['destination_country' => 'Canada']);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('search.index', ['q' => 'Canada']));

        $response->assertOk();
        $response->assertSee($lead->name);
    }

    public function test_global_search_ignores_non_searchable_metadata_field(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'lead', 'internal_code', 'text');
        $this->lead($organization, $user, 'Hidden Metadata Lead', 'new', ['internal_code' => 'UniqueHiddenCode']);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('search.index', ['q' => 'UniqueHiddenCode']));

        $response->assertOk();
        $response->assertDontSee('Hidden Metadata Lead');
    }

    public function test_global_search_ignores_inactive_metadata_field(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'lead', 'old_destination', 'text', [
            'is_searchable' => true,
            'status' => 'inactive',
        ]);
        $this->lead($organization, $user, 'Inactive Field Lead', 'new', ['old_destination' => 'UniqueInactiveValue']);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('search.index', ['q' => 'UniqueInactiveValue']));

        $response->assertOk();
        $response->assertDontSee('Inactive Field Lead');
    }

    public function test_global_search_ignores_sensitive_metadata_field(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'lead', 'passport_number', 'text', [
            'is_searchable' => true,
            'is_sensitive' => true,
        ]);
        $this->lead($organization, $user, 'Sensitive Lead', 'new', ['passport_number' => 'UniqueSensitiveValue']);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('search.index', ['q' => 'UniqueSensitiveValue']));

        $response->assertOk();
        $response->assertDontSee('Sensitive Lead');
    }

    public function test_global_search_matches_any_of_multiple_searchable_metadata_fields(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'lead', 'destination_country', 'text', ['is_searchable' => true]);
        $this->field($organization, 'lead', 'visa_type', 'text', ['is_searchable' => true]);
        $countryLead = $this->lead($organization, $user, 'Country Lead', 'new', ['destination_country' => 'Australia']);
        $visaLead = $this->lead($organization, $user, 'Visa Lead', 'new', ['visa_type' => 'Student']);

        $countryResponse = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('search.index', ['q' => 'Australia']));

        $countryResponse->assertOk();
        $countryResponse->assertSee($countryLead->name);
        $countryResponse->assertDontSee($visaLead->name);

        $visaResponse = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('search.index', ['q' => 'Student']));

        $visaResponse->assertOk();
        $visaResponse->assertSee($visaLead->name);
    }

    public function test_global_search_composes_static_and_metadata_matches_without_duplicates(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'lead', 'destination_country', 'text', ['is_searchable' => true]);
        $staticMatch = $this->lead($organization, $user, 'Canada Static Lead', 'new');
        $metadataMatch = $this->lead($organization, $user, 'Other Lead', 'new', ['destination_country' => 'Canada']);
        $bothMatch = $this->lead($organization, $user, 'Canada Both Lead', 'new', ['destination_country' => 'Canada']);

        $service = app(SearchService::class);
        app(TenantContext::class)->set($organization);

        $results = $service->search($user, 'Canada');

        $leadTitles = $results
            ->filter(fn (array $result) => $result['label'] === crm_term('leads'))
            ->pluck('title')
            ->all();

        $this->assertSame([
            $staticMatch->name,
            $metadataMatch->name,
            $bothMatch->name,
        ], $leadTitles);
        $this->assertCount(3, $leadTitles);
    }

    public function test_global_search_static_fields_remain_searchable(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Unique Static Search Lead',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('search.index', ['q' => 'Unique Static Search']));

        $response->assertOk();
        $response->assertSee($lead->name);
    }

    public function test_metadata_search_is_tenant_isolated(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        [, $otherOrganization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'lead', 'destination_country', 'text', ['is_searchable' => true]);
        $this->field($otherOrganization, 'lead', 'destination_country', 'text', ['is_searchable' => true]);
        $visible = $this->lead($organization, $user, 'Tenant A Metadata Lead', 'new', ['destination_country' => 'SharedSearchTerm']);
        $this->lead($otherOrganization, User::factory()->create(), 'Tenant B Metadata Lead', 'new', ['destination_country' => 'SharedSearchTerm']);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('search.index', ['q' => 'SharedSearchTerm']));

        $response->assertOk();
        $response->assertSee($visible->name);
        $response->assertDontSee('Tenant B Metadata Lead');
    }

    public function test_metadata_search_respects_rbac_module_permissions(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');
        $this->field($organization, 'lead', 'destination_country', 'text', ['is_searchable' => true]);
        $this->lead($organization, $user, 'RBAC Metadata Lead', 'new', ['destination_country' => 'RBACSearchTerm']);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('search.index', ['q' => 'RBACSearchTerm']));

        $response->assertOk();
        $response->assertDontSee('RBAC Metadata Lead');
    }

    public function test_customer_and_opportunity_global_search_use_metadata(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'customer', 'segment', 'text', ['is_searchable' => true]);
        $this->field($organization, 'opportunity', 'region', 'text', ['is_searchable' => true]);
        $customer = $this->customer($organization, $user, 'Segment Customer', 'active', ['segment' => 'EnterprisePlus']);
        $opportunity = $this->opportunity($organization, $user, 'Region Deal', 'prospect', ['region' => 'PacificNorth']);

        $customerResponse = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('search.index', ['q' => 'EnterprisePlus']));

        $customerResponse->assertOk();
        $customerResponse->assertSee($customer->display_name);

        $opportunityResponse = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('search.index', ['q' => 'PacificNorth']));

        $opportunityResponse->assertOk();
        $opportunityResponse->assertSee($opportunity->title);
    }

    public function test_metadata_search_supports_exact_and_starts_with_modes(): void
    {
        [, $organization] = $this->setupOrganization();
        $this->field($organization, 'lead', 'destination_country', 'text', ['is_searchable' => true]);
        $canada = $this->lead($organization, User::factory()->create(), 'Canada Lead', 'new', ['destination_country' => 'Canada']);
        $this->lead($organization, User::factory()->create(), 'Mexico Lead', 'new', ['destination_country' => 'Mexico']);

        $service = app(MetadataSearchService::class);

        $this->assertSame(
            [$canada->id],
            $service->matchingEntityIds('lead', 'Canada', $organization->id, 'exact')
        );

        $this->assertSame(
            [$canada->id],
            $service->matchingEntityIds('lead', 'Can', $organization->id, 'starts_with')
        );
    }

    public function test_metadata_global_search_queries_projections_not_json_columns(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'lead', 'destination_country', 'text', ['is_searchable' => true]);
        $this->lead($organization, $user, 'Projection Search Lead', 'new', ['destination_country' => 'ProjectionTerm']);
        $queries = [];

        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('search.index', ['q' => 'ProjectionTerm']))
            ->assertOk();

        $this->assertTrue(collect($queries)->contains(fn (string $sql) => str_contains($sql, 'metadata_value_projections')));
        $this->assertFalse(collect($queries)->contains(fn (string $sql) => str_contains($sql, 'custom_fields')));
    }

    public function test_metadata_global_search_does_not_trigger_n_plus_one_projection_queries(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'lead', 'destination_country', 'text', ['is_searchable' => true]);
        $this->field($organization, 'lead', 'visa_type', 'text', ['is_searchable' => true]);

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

        app(TenantContext::class)->set($organization);
        app(SearchService::class)->search($user, 'BulkTerm');

        $this->assertCount(1, $queries);
    }

    protected function setupUserWithOrg(string $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function setupOrganization(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');

        return [$user, $organization];
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
