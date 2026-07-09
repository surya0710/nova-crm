<?php

namespace Tests\Feature;

use App\Models\MetadataFieldDefinition;
use App\Models\MetadataFieldOption;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationDynamicMetadataFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_settings_render_active_metadata_fields(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->field($organization, 'license_number', 'text');
        $this->field($organization, 'draft_only', 'text', ['status' => 'draft']);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('organization.edit'));

        $response->assertOk();
        $response->assertSee('Custom Fields');
        $response->assertSee('License Number');
        $response->assertDontSee('Draft Only');
    }

    public function test_organization_settings_persist_submitted_metadata_values(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $organizationType = $this->field($organization, 'organization_type', 'select');
        $employeeCount = $this->field($organization, 'employee_count', 'number');
        $this->option($organization, $organizationType, 'agency', 'Agency');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('organization.update'), [
                ...$this->organizationPayload($organization),
                'custom_fields' => [
                    'organization_type' => 'agency',
                    'employee_count' => '42',
                    'unknown_key' => 'ignored',
                ],
            ]);

        $response->assertRedirect(route('organization.edit'));

        $organization->refresh();

        $this->assertSame('agency', $organization->custom_fields['organization_type']);
        $this->assertSame(42, $organization->custom_fields['employee_count']);
        $this->assertArrayNotHasKey('unknown_key', $organization->custom_fields);
    }

    public function test_organization_settings_display_metadata_option_labels(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $organizationType = $this->field($organization, 'organization_type', 'select');
        $this->option($organization, $organizationType, 'agency', 'Agency');
        $organization->update([
            'custom_fields' => [
                'organization_type' => 'agency',
            ],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('organization.edit'));

        $response->assertOk();
        $response->assertSee('Organization Type');
        $response->assertSee('Agency');
    }

    public function test_organization_settings_update_preserves_omitted_metadata_values(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->field($organization, 'organization_type', 'text');
        $this->field($organization, 'license_number', 'text');
        $organization->update([
            'custom_fields' => [
                'organization_type' => 'agency',
                'license_number' => 'LIC-123',
            ],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('organization.update'), [
                ...$this->organizationPayload($organization, ['name' => 'Updated Org']),
                'custom_fields' => [
                    'organization_type' => 'consultancy',
                ],
            ]);

        $response->assertRedirect(route('organization.edit'));

        $organization->refresh();

        $this->assertSame('Updated Org', $organization->name);
        $this->assertSame('consultancy', $organization->custom_fields['organization_type']);
        $this->assertSame('LIC-123', $organization->custom_fields['license_number']);
    }

    protected function setupUserWithOrg(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create([
            'name' => 'Nova Org',
            'email' => 'org@example.com',
            'timezone' => 'UTC',
            'currency' => 'USD',
        ]);
        $organization->addMember($user, 'organization-owner');

        return [$user, $organization];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function organizationPayload(Organization $organization, array $overrides = []): array
    {
        return [
            'name' => $organization->name,
            'email' => $organization->email,
            'phone' => $organization->phone,
            'website' => $organization->website,
            'description' => $organization->description,
            'address_line_1' => $organization->address_line_1,
            'address_line_2' => $organization->address_line_2,
            'city' => $organization->city,
            'state' => $organization->state,
            'postal_code' => $organization->postal_code,
            'country' => $organization->country,
            'tax_name' => $organization->tax_name,
            'tax_number' => $organization->tax_number,
            'timezone' => $organization->timezone,
            'currency' => $organization->currency,
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
            'entity_type' => 'organization',
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
