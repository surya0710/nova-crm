<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\MetadataValueProjection;
use App\Models\Organization;
use App\Models\User;
use App\Services\MetadataProjectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerMetadataFilterBugfixTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_index_metadata_filter_works_after_web_form_create_without_manual_projection_sync(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $this->field($organization, 'segment', 'text', ['is_filterable' => true]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('customers.store'), [
                ...$this->customerPayload(),
                'name' => 'Enterprise Customer',
                'email' => 'enterprise@example.com',
                'custom_fields' => [
                    'segment' => 'enterprise',
                ],
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('customers.store'), [
                ...$this->customerPayload(),
                'name' => 'Retail Customer',
                'email' => 'retail@example.com',
                'custom_fields' => [
                    'segment' => 'retail',
                ],
            ])
            ->assertRedirect();

        $this->assertSame('enterprise', Customer::query()->where('name', 'Enterprise Customer')->value('custom_fields')['segment'] ?? null);
        $this->assertGreaterThan(0, MetadataValueProjection::withoutGlobalScopes()->where('entity_type', 'customer')->count());

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.index', [
                'metadata_filters' => [
                    ['key' => 'segment', 'operator' => 'equals', 'value' => 'enterprise'],
                ],
            ]));

        $response->assertOk();
        $response->assertSee('Enterprise Customer');
        $response->assertDontSee('Retail Customer');
    }

    public function test_customer_index_applies_multiple_metadata_filters_together(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'segment', 'text', ['is_filterable' => true]);
        $this->field($organization, 'tier', 'select', ['is_filterable' => true]);

        $match = $this->customer($organization, $user, 'Match Customer', 'active', [
            'segment' => 'enterprise',
            'tier' => 'gold',
        ]);
        $this->customer($organization, $user, 'Segment Only', 'active', [
            'segment' => 'enterprise',
            'tier' => 'silver',
        ]);
        $this->customer($organization, $user, 'Tier Only', 'active', [
            'segment' => 'retail',
            'tier' => 'gold',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.index', [
                'metadata_filters' => [
                    ['key' => 'segment', 'operator' => 'equals', 'value' => 'enterprise'],
                    ['key' => 'tier', 'operator' => 'equals', 'value' => 'gold'],
                ],
            ]));

        $response->assertOk();
        $response->assertSee($match->name);
        $response->assertDontSee('Segment Only');
        $response->assertDontSee('Tier Only');
    }

    public function test_customer_index_metadata_filter_composes_with_static_filters(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'segment', 'text', ['is_filterable' => true]);

        $match = $this->customer($organization, $user, 'Active Enterprise', 'active', ['segment' => 'enterprise']);
        $this->customer($organization, $user, 'Inactive Enterprise', 'inactive', ['segment' => 'enterprise']);
        $this->customer($organization, $user, 'Active Retail', 'active', ['segment' => 'retail']);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.index', [
                'status' => 'active',
                'metadata_filters' => [
                    ['key' => 'segment', 'operator' => 'equals', 'value' => 'enterprise'],
                ],
            ]));

        $response->assertOk();
        $response->assertSee($match->name);
        $response->assertDontSee('Inactive Enterprise');
        $response->assertDontSee('Active Retail');
    }

    public function test_customer_index_rejects_invalid_metadata_filter_fields(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'segment', 'text');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.index', [
                'metadata_filters' => [
                    ['key' => 'segment', 'operator' => 'equals', 'value' => 'enterprise'],
                ],
            ]))
            ->assertSessionHasErrors('metadata_filters.0.key');
    }

    public function test_customer_index_ignores_inactive_metadata_filter_fields(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'inactive_segment', 'text', ['status' => 'inactive', 'is_filterable' => true]);
        $visible = $this->customer($organization, $user, 'Visible Customer', 'active');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.index', [
                'metadata_filters' => [
                    ['key' => 'inactive_segment', 'operator' => 'equals', 'value' => 'enterprise'],
                ],
            ]));

        $response->assertOk();
        $response->assertSee($visible->name);
    }

    public function test_customer_index_metadata_filter_preserves_tenant_isolation(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        [, $otherOrganization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'segment', 'text', ['is_filterable' => true]);
        $this->field($otherOrganization, 'segment', 'text', ['is_filterable' => true]);
        $visible = $this->customer($organization, $user, 'Tenant A Customer', 'active', ['segment' => 'enterprise']);
        $this->customer($otherOrganization, User::factory()->create(), 'Tenant B Customer', 'active', ['segment' => 'enterprise']);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.index', [
                'metadata_filters' => [
                    ['key' => 'segment', 'operator' => 'equals', 'value' => 'enterprise'],
                ],
            ]));

        $response->assertOk();
        $response->assertSee($visible->name);
        $response->assertDontSee('Tenant B Customer');
    }

    public function test_customer_index_metadata_filter_works_after_projection_backfill_for_legacy_storage(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'segment', 'text', ['is_filterable' => true]);

        Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'name' => 'Legacy Enterprise Customer',
            'status' => 'active',
            'custom_fields' => ['segment' => 'enterprise'],
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.index', [
                'metadata_filters' => [
                    ['key' => 'segment', 'operator' => 'equals', 'value' => 'enterprise'],
                ],
            ]))
            ->assertOk()
            ->assertDontSee('Legacy Enterprise Customer');

        app(MetadataProjectionService::class)->rebuildForOrganizationEntity($organization->id, 'customer');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.index', [
                'metadata_filters' => [
                    ['key' => 'segment', 'operator' => 'equals', 'value' => 'enterprise'],
                ],
            ]))
            ->assertOk()
            ->assertSee('Legacy Enterprise Customer');
    }

    public function test_customer_index_metadata_filter_survives_pagination_and_sorting(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'segment', 'text', ['is_filterable' => true]);
        $this->field($organization, 'annual_spend', 'currency', ['is_sortable' => true]);

        $this->customer($organization, $user, 'Target Customer', 'active', [
            'segment' => 'enterprise',
            'annual_spend' => 5000,
        ]);

        Customer::factory()->count(20)->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'status' => 'active',
            'custom_fields' => ['segment' => 'retail'],
        ]);

        foreach (Customer::query()->where('organization_id', $organization->id)->get() as $customer) {
            app(MetadataProjectionService::class)->sync($customer);
        }

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.index', [
                'metadata_filters' => [
                    ['key' => 'segment', 'operator' => 'equals', 'value' => 'enterprise'],
                ],
                'metadata_sort' => ['key' => 'annual_spend', 'direction' => 'desc'],
            ]));

        $response->assertOk();
        $response->assertSee('Target Customer');
        $response->assertDontSee('retail');
    }

    public function test_customer_index_metadata_filter_works_after_lead_conversion(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'segment', 'text', ['is_filterable' => true], 'lead');
        $this->field($organization, 'segment', 'text', ['is_filterable' => true], 'customer');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'name' => 'Converted Lead',
            'status' => 'qualified',
            'custom_fields' => ['segment' => 'enterprise'],
        ]);
        app(MetadataProjectionService::class)->sync($lead);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('leads.convert', $lead), [
                'name' => 'Converted Lead',
                'email' => $lead->email,
                'create_opportunity' => false,
            ])
            ->assertRedirect();

        $customer = Customer::query()->where('name', 'Converted Lead')->firstOrFail();
        $this->assertSame('enterprise', $customer->custom_fields['segment'] ?? null);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.index', [
                'metadata_filters' => [
                    ['key' => 'segment', 'operator' => 'equals', 'value' => 'enterprise'],
                ],
            ]));

        $response->assertOk();
        $response->assertSee('Converted Lead');
    }

    protected function setupUserWithOrg(string $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function field(
        Organization $organization,
        string $key,
        string $type,
        array $attributes = [],
        string $entityType = 'customer',
    ): MetadataFieldDefinition {
        return MetadataFieldDefinition::query()->create([
            'organization_id' => $organization->id,
            'entity_type' => $entityType,
            'key' => $key,
            'label' => str($key)->headline()->toString(),
            'type' => $type,
            'status' => 'active',
            'published_at' => now(),
            'activated_at' => now(),
            ...$attributes,
        ]);
    }

    protected function customer(
        Organization $organization,
        User $user,
        string $name,
        string $status,
        array $customFields = [],
    ): Customer {
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'name' => $name,
            'status' => $status,
            'custom_fields' => $customFields === [] ? null : $customFields,
        ]);

        app(MetadataProjectionService::class)->sync($customer);

        return $customer;
    }

    /**
     * @return array<string, mixed>
     */
    protected function customerPayload(): array
    {
        return [
            'name' => 'Jane Customer',
            'company' => 'Example Co',
            'email' => 'customer@example.com',
            'phone' => '+15550002222',
            'website' => 'https://example.com',
            'industry' => 'Education',
            'status' => 'active',
            'address_line_1' => '123 Main Street',
            'address_line_2' => null,
            'city' => 'Toronto',
            'state' => 'ON',
            'postal_code' => 'A1A 1A1',
            'country' => 'Canada',
            'tax_number' => null,
            'assigned_to' => null,
        ];
    }
}
