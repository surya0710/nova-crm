<?php

namespace Tests\Feature;

use App\Models\MetadataFieldDefinition;
use App\Models\MetadataFieldOption;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityDynamicMetadataFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_opportunity_create_form_renders_active_metadata_fields(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->field($organization, 'property_type', 'text');
        $this->field($organization, 'draft_only', 'text', ['status' => 'draft']);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('pipeline.create'));

        $response->assertOk();
        $response->assertSee('Custom Fields');
        $response->assertSee('Property Type');
        $response->assertDontSee('Draft Only');
    }

    public function test_opportunity_create_persists_submitted_metadata_values(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $propertyType = $this->field($organization, 'property_type', 'select');
        $commissionRate = $this->field($organization, 'commission_rate', 'percentage');
        $this->option($organization, $propertyType, 'apartment', 'Apartment');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('pipeline.store'), [
                ...$this->opportunityPayload(),
                'custom_fields' => [
                    'property_type' => 'apartment',
                    'commission_rate' => '2.5',
                    'unknown_key' => 'ignored',
                ],
            ]);

        $opportunity = Opportunity::query()->firstOrFail();

        $response->assertRedirect(route('pipeline.show', $opportunity));
        $this->assertSame('apartment', $opportunity->custom_fields['property_type']);
        $this->assertSame(2.5, $opportunity->custom_fields['commission_rate']);
        $this->assertArrayNotHasKey('unknown_key', $opportunity->custom_fields);
    }

    public function test_opportunity_show_displays_metadata_option_labels(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $propertyType = $this->field($organization, 'property_type', 'select');
        $this->option($organization, $propertyType, 'apartment', 'Apartment');

        $opportunity = Opportunity::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'custom_fields' => [
                'property_type' => 'apartment',
            ],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('pipeline.show', $opportunity));

        $response->assertOk();
        $response->assertSee('Custom Fields');
        $response->assertSee('Property Type');
        $response->assertSee('Apartment');
    }

    public function test_opportunity_update_preserves_omitted_metadata_values(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->field($organization, 'property_type', 'text');
        $this->field($organization, 'region', 'text');

        $opportunity = Opportunity::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'stage' => 'qualification',
            'custom_fields' => [
                'property_type' => 'Apartment',
                'region' => 'North',
            ],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('pipeline.update', $opportunity), [
                ...$this->opportunityPayload(['title' => 'Updated Deal']),
                'custom_fields' => [
                    'property_type' => 'Villa',
                ],
            ]);

        $response->assertRedirect(route('pipeline.show', $opportunity));

        $opportunity->refresh();

        $this->assertSame('Villa', $opportunity->custom_fields['property_type']);
        $this->assertSame('North', $opportunity->custom_fields['region']);
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
    protected function opportunityPayload(array $overrides = []): array
    {
        return [
            'title' => 'Enterprise Deal',
            'customer_id' => null,
            'lead_id' => null,
            'stage' => 'qualification',
            'amount' => '50000',
            'currency' => 'USD',
            'probability' => 50,
            'expected_close_date' => now()->addMonth()->format('Y-m-d'),
            'description' => 'Large implementation opportunity.',
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
            'entity_type' => 'opportunity',
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
