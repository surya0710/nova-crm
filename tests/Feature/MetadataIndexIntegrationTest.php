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
use Tests\TestCase;

class MetadataIndexIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_index_filters_by_metadata_and_composes_with_static_filters(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $this->field($organization, 'lead', 'destination_country', 'text', ['is_filterable' => true]);

        $match = $this->lead($organization, $user, 'Canada Match', 'new', ['destination_country' => 'Canada']);
        $this->lead($organization, $user, 'Canada Lost', 'lost', ['destination_country' => 'Canada']);
        $this->lead($organization, $user, 'Australia Match', 'new', ['destination_country' => 'Australia']);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', [
                'status' => 'new',
                'metadata_filters' => [
                    ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
                ],
            ]));

        $response->assertOk();
        $response->assertSee($match->name);
        $response->assertDontSee('Canada Lost');
        $response->assertDontSee('Australia Match');
    }

    public function test_lead_index_metadata_sort_is_deterministic_places_nulls_last_and_keeps_pagination(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $this->field($organization, 'lead', 'ielts_score', 'decimal', ['is_sortable' => true]);

        $eightA = $this->lead($organization, $user, 'Eight A', 'new', ['ielts_score' => 8.0]);
        $eightB = $this->lead($organization, $user, 'Eight B', 'new', ['ielts_score' => 8.0]);
        $null = $this->lead($organization, $user, 'No Score', 'new');
        $seven = $this->lead($organization, $user, 'Seven', 'new', ['ielts_score' => 7.0]);

        Lead::factory()->count(12)->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'status' => 'new',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', [
                'metadata_sort' => ['key' => 'ielts_score', 'direction' => 'asc'],
            ]));

        $response->assertOk();
        $this->assertAppearsInOrder($response->getContent(), [
            $seven->name,
            $eightA->name,
            $eightB->name,
            $null->name,
        ]);
        $response->assertSee('page=2', false);
    }

    public function test_lead_index_preserves_permissions_and_tenant_isolation_for_metadata_filters(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        [, $otherOrganization] = $this->setupUserWithOrg('manager');
        $this->field($organization, 'lead', 'destination_country', 'text', ['is_filterable' => true]);
        $this->field($otherOrganization, 'lead', 'destination_country', 'text', ['is_filterable' => true]);
        $visible = $this->lead($organization, $user, 'Tenant A Lead', 'new', ['destination_country' => 'Canada']);
        $this->lead($otherOrganization, User::factory()->create(), 'Tenant B Lead', 'new', ['destination_country' => 'Canada']);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', [
                'metadata_filters' => [
                    ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
                ],
            ]));

        $response->assertOk();
        $response->assertSee($visible->name);
        $response->assertDontSee('Tenant B Lead');

        [$hrUser, $hrOrganization] = $this->setupUserWithOrg('hr');

        $this->actingAs($hrUser)
            ->withSession(['current_organization_id' => $hrOrganization->id])
            ->get(route('leads.index'))
            ->assertForbidden();
    }

    public function test_customer_index_filters_and_sorts_by_metadata(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'customer', 'segment', 'text', ['is_filterable' => true]);
        $this->field($organization, 'customer', 'annual_spend', 'currency', ['is_sortable' => true]);
        $low = $this->customer($organization, $user, 'Low Customer', 'active', ['segment' => 'enterprise', 'annual_spend' => 1000]);
        $high = $this->customer($organization, $user, 'High Customer', 'active', ['segment' => 'enterprise', 'annual_spend' => 5000]);
        $this->customer($organization, $user, 'Retail Customer', 'active', ['segment' => 'retail', 'annual_spend' => 9000]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.index', [
                'status' => 'active',
                'metadata_filters' => [
                    ['key' => 'segment', 'operator' => 'equals', 'value' => 'enterprise'],
                ],
                'metadata_sort' => ['key' => 'annual_spend', 'direction' => 'asc'],
            ]));

        $response->assertOk();
        $response->assertDontSee('Retail Customer');
        $this->assertAppearsInOrder($response->getContent(), [$low->name, $high->name]);
    }

    public function test_opportunity_index_filters_and_sorts_by_metadata(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'opportunity', 'property_type', 'text', ['is_filterable' => true]);
        $this->field($organization, 'opportunity', 'commission_rate', 'percentage', ['is_sortable' => true]);
        $first = $this->opportunity($organization, $user, 'First Deal', 'qualification', ['property_type' => 'apartment', 'commission_rate' => 1.5]);
        $second = $this->opportunity($organization, $user, 'Second Deal', 'qualification', ['property_type' => 'apartment', 'commission_rate' => 3]);
        $this->opportunity($organization, $user, 'Villa Deal', 'qualification', ['property_type' => 'villa', 'commission_rate' => 2]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('pipeline.index', [
                'stage' => 'qualification',
                'metadata_filters' => [
                    ['key' => 'property_type', 'operator' => 'equals', 'value' => 'apartment'],
                ],
                'metadata_sort' => ['key' => 'commission_rate', 'direction' => 'asc'],
            ]));

        $response->assertOk();
        $response->assertDontSee('Villa Deal');
        $this->assertAppearsInOrder($response->getContent(), [$first->title, $second->title]);
    }

    public function test_metadata_index_security_rejects_disallowed_fields_and_ignores_inactive_fields(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $this->field($organization, 'lead', 'not_filterable', 'text');
        $this->field($organization, 'lead', 'not_sortable', 'text');
        $this->field($organization, 'lead', 'sensitive_code', 'text', ['is_filterable' => true, 'is_sensitive' => true]);
        $this->field($organization, 'lead', 'inactive_destination', 'text', ['status' => 'inactive', 'is_filterable' => true]);
        $visible = $this->lead($organization, $user, 'Visible Lead', 'new');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', [
                'metadata_filters' => [
                    ['key' => 'not_filterable', 'operator' => 'equals', 'value' => 'x'],
                ],
            ]))
            ->assertSessionHasErrors('metadata_filters.0.key');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', [
                'metadata_sort' => ['key' => 'not_sortable', 'direction' => 'asc'],
            ]))
            ->assertSessionHasErrors('metadata_sort.key');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', [
                'metadata_filters' => [
                    ['key' => 'sensitive_code', 'operator' => 'equals', 'value' => 'secret'],
                ],
            ]))
            ->assertSessionHasErrors('metadata_filters.0.key');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', [
                'metadata_filters' => [
                    ['key' => 'inactive_destination', 'operator' => 'equals', 'value' => 'Canada'],
                ],
            ]));

        $response->assertOk();
        $response->assertSee($visible->name);
    }

    public function test_metadata_index_queries_use_projections_not_json_columns(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'lead', 'destination_country', 'text', ['is_filterable' => true]);
        $this->lead($organization, $user, 'Projection Lead', 'new', ['destination_country' => 'Canada']);
        $queries = [];

        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', [
                'metadata_filters' => [
                    ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
                ],
            ]))
            ->assertOk();

        $this->assertTrue(collect($queries)->contains(fn (string $sql) => str_contains($sql, 'metadata_value_projections')));
        $this->assertFalse(collect($queries)->contains(fn (string $sql) => str_contains($sql, 'custom_fields')));
    }

    protected function setupUserWithOrg(string $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

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

    protected function assertAppearsInOrder(string $content, array $needles): void
    {
        $lastPosition = -1;

        foreach ($needles as $needle) {
            $position = strpos($content, $needle);

            $this->assertNotFalse($position, "Failed asserting that [{$needle}] appears in the response.");
            $this->assertGreaterThan($lastPosition, $position, "Failed asserting that [{$needle}] appears after the previous value.");

            $lastPosition = $position;
        }
    }
}
