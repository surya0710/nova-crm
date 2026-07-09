<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MetadataFieldDefinition;
use App\Models\MetadataFieldOption;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDynamicMetadataFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_create_form_renders_active_metadata_fields(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->field($organization, 'patient_id', 'text');
        $this->field($organization, 'draft_only', 'text', ['status' => 'draft']);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.create'));

        $response->assertOk();
        $response->assertSee('Custom Fields');
        $response->assertSee('Patient Id');
        $response->assertDontSee('Draft Only');
    }

    public function test_customer_create_persists_submitted_metadata_values(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $customerType = $this->field($organization, 'customer_type', 'select');
        $annualSpend = $this->field($organization, 'annual_spend', 'currency');
        $this->option($organization, $customerType, 'enterprise', 'Enterprise');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('customers.store'), [
                ...$this->customerPayload(),
                'custom_fields' => [
                    'customer_type' => 'enterprise',
                    'annual_spend' => '12500.50',
                    'unknown_key' => 'ignored',
                ],
            ]);

        $customer = Customer::query()->firstOrFail();

        $response->assertRedirect(route('customers.show', $customer));
        $this->assertSame('enterprise', $customer->custom_fields['customer_type']);
        $this->assertSame(12500.5, $customer->custom_fields['annual_spend']);
        $this->assertArrayNotHasKey('unknown_key', $customer->custom_fields);
    }

    public function test_customer_show_displays_metadata_option_labels(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $customerType = $this->field($organization, 'customer_type', 'select');
        $this->option($organization, $customerType, 'enterprise', 'Enterprise');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'custom_fields' => [
                'customer_type' => 'enterprise',
            ],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('Custom Fields');
        $response->assertSee('Customer Type');
        $response->assertSee('Enterprise');
    }

    public function test_customer_update_preserves_omitted_metadata_values(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->field($organization, 'customer_type', 'text');
        $this->field($organization, 'preferred_language', 'text');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'custom_fields' => [
                'customer_type' => 'prospect',
                'preferred_language' => 'English',
            ],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('customers.update', $customer), [
                ...$this->customerPayload(['name' => 'Updated Customer']),
                'custom_fields' => [
                    'customer_type' => 'vip',
                ],
            ]);

        $response->assertRedirect(route('customers.show', $customer));

        $customer->refresh();

        $this->assertSame('vip', $customer->custom_fields['customer_type']);
        $this->assertSame('English', $customer->custom_fields['preferred_language']);
    }

    protected function setupUserWithOrg(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'manager');

        return [$user, $organization];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function customerPayload(array $overrides = []): array
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
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function field(Organization $organization, string $key, string $type, array $attributes = []): MetadataFieldDefinition
    {
        return MetadataFieldDefinition::query()->create([
            'organization_id' => $organization->id,
            'entity_type' => 'customer',
            'key' => $key,
            'label' => str($key)->headline()->toString(),
            'type' => $type,
            'status' => 'active',
            'published_at' => now(),
            'activated_at' => now(),
            ...$attributes,
        ]);
    }

    protected function option(Organization $organization, MetadataFieldDefinition $field, string $value, string $label): MetadataFieldOption
    {
        return MetadataFieldOption::query()->create([
            'organization_id' => $organization->id,
            'metadata_field_definition_id' => $field->id,
            'value' => $value,
            'label' => $label,
            'is_active' => true,
        ]);
    }
}
