<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\MetadataFieldOption;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadDynamicMetadataFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_create_form_renders_active_metadata_fields(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->field($organization, 'visa_type', 'select');
        $this->field($organization, 'draft_only', 'text', ['status' => 'draft']);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.create'));

        $response->assertOk();
        $response->assertSee('Custom Fields');
        $response->assertSee('Visa Type');
        $response->assertDontSee('Draft Only');
    }

    public function test_lead_create_persists_submitted_metadata_values(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $visaType = $this->field($organization, 'visa_type', 'select');
        $score = $this->field($organization, 'ielts_score', 'decimal');
        $this->option($organization, $visaType, 'student', 'Student Visa');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('leads.store'), [
                ...$this->leadPayload(),
                'custom_fields' => [
                    'visa_type' => 'student',
                    'ielts_score' => '7.5',
                    'unknown_key' => 'ignored',
                ],
            ]);

        $lead = Lead::query()->firstOrFail();

        $response->assertRedirect(route('leads.show', $lead));
        $this->assertSame('student', $lead->custom_fields['visa_type']);
        $this->assertSame(7.5, $lead->custom_fields['ielts_score']);
        $this->assertArrayNotHasKey('unknown_key', $lead->custom_fields);
    }

    public function test_lead_create_rejects_missing_required_metadata_before_creating_record(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->field($organization, 'visa_type', 'text', ['is_required' => true]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('leads.store'), $this->leadPayload());

        $response->assertSessionHasErrors('custom_fields.visa_type');
        $this->assertDatabaseMissing('leads', [
            'email' => 'jane@example.com',
        ]);
    }

    public function test_lead_create_rejects_invalid_metadata_option(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $visaType = $this->field($organization, 'visa_type', 'select');
        $this->option($organization, $visaType, 'student', 'Student Visa');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('leads.store'), [
                ...$this->leadPayload(),
                'custom_fields' => [
                    'visa_type' => 'visitor',
                ],
            ]);

        $response->assertSessionHasErrors('custom_fields.visa_type');
        $this->assertDatabaseMissing('leads', [
            'email' => 'jane@example.com',
        ]);
    }

    public function test_optional_metadata_clear_remains_valid(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->field($organization, 'visa_type', 'text');
        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'custom_fields' => [
                'visa_type' => 'student',
            ],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('leads.update', $lead), [
                ...$this->leadPayload(),
                'custom_fields' => [
                    'visa_type' => '',
                ],
            ]);

        $response->assertRedirect(route('leads.show', $lead));

        $lead->refresh();
        $this->assertNull($lead->custom_fields);
    }

    public function test_lead_show_displays_metadata_option_labels(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $visaType = $this->field($organization, 'visa_type', 'select');
        $this->option($organization, $visaType, 'student', 'Student Visa');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'custom_fields' => [
                'visa_type' => 'student',
            ],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.show', $lead));

        $response->assertOk();
        $response->assertSee('Custom Fields');
        $response->assertSee('Visa Type');
        $response->assertSee('Student Visa');
    }

    public function test_lead_update_preserves_omitted_metadata_values(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->field($organization, 'visa_type', 'text');
        $this->field($organization, 'destination_country', 'text');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'custom_fields' => [
                'visa_type' => 'student',
                'destination_country' => 'Canada',
            ],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('leads.update', $lead), [
                ...$this->leadPayload(['name' => 'Updated Lead']),
                'custom_fields' => [
                    'visa_type' => 'work',
                ],
            ]);

        $response->assertRedirect(route('leads.show', $lead));

        $lead->refresh();

        $this->assertSame('work', $lead->custom_fields['visa_type']);
        $this->assertSame('Canada', $lead->custom_fields['destination_country']);
    }

    public function test_lead_show_masks_sensitive_metadata_values(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->field($organization, 'passport_number', 'text', ['is_sensitive' => true]);

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'custom_fields' => [
                'passport_number' => 'P1234567',
            ],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.show', $lead));

        $response->assertOk();
        $response->assertSee('Passport Number');
        $response->assertSee('********');
        $response->assertDontSee('P1234567');
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
    protected function leadPayload(array $overrides = []): array
    {
        return [
            'name' => 'Jane Applicant',
            'company' => 'Example Co',
            'email' => 'jane@example.com',
            'phone' => '+15550001111',
            'source' => 'manual_entry',
            'industry' => 'Education',
            'budget' => '2500',
            'priority' => 'medium',
            'assigned_to' => null,
            'status' => 'new',
            'next_follow_up_at' => null,
            'next_follow_up_note' => null,
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
            'entity_type' => 'lead',
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
