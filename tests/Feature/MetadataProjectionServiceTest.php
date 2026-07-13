<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\MetadataValueProjection;
use App\Models\Organization;
use App\Models\User;
use App\Services\MetadataEntityFormService;
use App\Services\MetadataProjectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetadataProjectionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_projects_typed_values_from_canonical_json(): void
    {
        [, $organization] = $this->setupOrganization();
        $this->field($organization, 'lead', 'destination_country', 'text');
        $this->field($organization, 'lead', 'ielts_score', 'decimal');
        $this->field($organization, 'lead', 'approved', 'boolean');
        $this->field($organization, 'lead', 'arrival_date', 'date');
        $this->field($organization, 'lead', 'appointment_at', 'datetime');
        $this->field($organization, 'lead', 'appointment_time', 'time');
        $this->field($organization, 'lead', 'preferred_countries', 'multi_select');
        $this->field($organization, 'lead', 'legacy_key', 'text', 'inactive');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'custom_fields' => [
                'destination_country' => 'Canada',
                'ielts_score' => 7.5,
                'approved' => false,
                'arrival_date' => '2026-09-15',
                'appointment_at' => '2026-09-15T14:30:00+00:00',
                'appointment_time' => '14:30:00',
                'preferred_countries' => ['canada', 'australia'],
                'legacy_key' => 'ignored',
                'unknown_key' => 'ignored',
            ],
        ]);

        $result = app(MetadataProjectionService::class)->sync($lead);

        $this->assertSame(8, $result['projected']);
        $this->assertSame(0, $result['deleted']);

        $text = $this->projection($lead, 'destination_country');
        $this->assertSame('Canada', $text->value_string);
        $this->assertSame('canada', $text->normalized_search_text);

        $decimal = $this->projection($lead, 'ielts_score');
        $this->assertSame('7.500000', $decimal->value_decimal);

        $boolean = $this->projection($lead, 'approved');
        $this->assertFalse($boolean->value_boolean);

        $date = $this->projection($lead, 'arrival_date');
        $this->assertSame('2026-09-15', $date->value_date->toDateString());

        $time = $this->projection($lead, 'appointment_time');
        $this->assertSame('14:30:00', $time->value_time);

        $this->assertSame(2, MetadataValueProjection::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('entity_type', 'lead')
            ->where('entity_id', $lead->id)
            ->where('field_key', 'preferred_countries')
            ->count());

        $this->assertDatabaseMissing('metadata_value_projections', [
            'organization_id' => $organization->id,
            'entity_type' => 'lead',
            'entity_id' => $lead->id,
            'field_key' => 'legacy_key',
        ]);
        $this->assertDatabaseMissing('metadata_value_projections', [
            'organization_id' => $organization->id,
            'entity_type' => 'lead',
            'entity_id' => $lead->id,
            'field_key' => 'unknown_key',
        ]);
    }

    public function test_sync_removes_projection_rows_for_cleared_values(): void
    {
        [, $organization] = $this->setupOrganization();
        $this->field($organization, 'lead', 'destination_country', 'text');
        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'custom_fields' => ['destination_country' => 'Canada'],
        ]);
        $service = app(MetadataProjectionService::class);

        $service->sync($lead);
        $this->assertDatabaseHas('metadata_value_projections', [
            'organization_id' => $organization->id,
            'entity_type' => 'lead',
            'entity_id' => $lead->id,
            'field_key' => 'destination_country',
        ]);

        $lead->forceFill(['custom_fields' => null])->save();
        $result = $service->sync($lead->refresh());

        $this->assertSame(1, $result['deleted']);
        $this->assertSame(0, $result['projected']);
        $this->assertDatabaseMissing('metadata_value_projections', [
            'organization_id' => $organization->id,
            'entity_type' => 'lead',
            'entity_id' => $lead->id,
            'field_key' => 'destination_country',
        ]);
    }

    public function test_metadata_entity_form_persistence_synchronizes_projection_after_storage(): void
    {
        [, $organization] = $this->setupOrganization();
        $this->field($organization, 'lead', 'destination_country', 'text');
        $lead = Lead::factory()->create(['organization_id' => $organization->id]);

        app(MetadataEntityFormService::class)->persistValues($lead, [
            'destination_country' => 'Canada',
        ]);

        $lead->refresh();
        $this->assertSame('Canada', $lead->custom_fields['destination_country']);
        $this->assertDatabaseHas('metadata_value_projections', [
            'organization_id' => $organization->id,
            'entity_type' => 'lead',
            'entity_id' => $lead->id,
            'field_key' => 'destination_country',
            'value_string' => 'Canada',
        ]);
    }

    public function test_detect_drift_reports_and_repairs_stale_projection_rows(): void
    {
        [, $organization] = $this->setupOrganization();
        $this->field($organization, 'lead', 'destination_country', 'text');
        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'custom_fields' => ['destination_country' => 'Canada'],
        ]);
        $service = app(MetadataProjectionService::class);

        $service->sync($lead);

        $lead->forceFill(['custom_fields' => ['destination_country' => 'Australia']])->save();
        $drift = $service->detectDrift($lead->refresh());

        $this->assertTrue($drift['drifted']);
        $this->assertSame([], $drift['missing']);
        $this->assertNotSame([], $drift['stale']);

        $service->repairDrift($lead);
        $this->assertFalse($service->detectDrift($lead->refresh())['drifted']);
        $this->assertSame('Australia', $this->projection($lead, 'destination_country')->value_string);
    }

    public function test_rebuild_for_organization_entity_is_idempotent_and_removes_stale_rows(): void
    {
        [, $organization] = $this->setupOrganization();
        $definition = $this->field($organization, 'lead', 'destination_country', 'text');
        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'custom_fields' => ['destination_country' => 'Canada'],
        ]);
        MetadataValueProjection::query()->create([
            'organization_id' => $organization->id,
            'metadata_field_definition_id' => $definition->id,
            'entity_type' => 'lead',
            'entity_id' => $lead->id,
            'field_key' => 'destination_country',
            'field_type' => 'text',
            'value_hash' => 'scalar',
            'value_string' => 'Stale',
            'definition_status' => 'active',
            'projected_at' => now(),
        ]);

        $summary = app(MetadataProjectionService::class)->rebuildForOrganizationEntity($organization->id, 'lead', 1);

        $this->assertSame(1, $summary['entities']);
        $this->assertSame(1, $summary['projected']);
        $this->assertSame(1, MetadataValueProjection::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('entity_type', 'lead')
            ->where('entity_id', $lead->id)
            ->count());
        $this->assertSame('Canada', $this->projection($lead, 'destination_country')->value_string);
    }

    public function test_rebuild_command_reprojects_one_entity(): void
    {
        [, $organization] = $this->setupOrganization();
        $this->field($organization, 'lead', 'destination_country', 'text');
        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'custom_fields' => ['destination_country' => 'Canada'],
        ]);

        $this->artisan('metadata:projections:rebuild', [
            '--organization_id' => $organization->id,
            '--entity_type' => 'lead',
            '--entity_id' => $lead->id,
        ])->assertExitCode(0);

        $this->assertSame('Canada', $this->projection($lead, 'destination_country')->value_string);
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
        string $status = 'active'
    ): MetadataFieldDefinition {
        return MetadataFieldDefinition::query()->create([
            'organization_id' => $organization->id,
            'entity_type' => $entity,
            'key' => $key,
            'label' => str($key)->headline()->toString(),
            'type' => $type,
            'status' => $status,
            'published_at' => $status === 'active' ? now() : null,
            'activated_at' => $status === 'active' ? now() : null,
        ]);
    }

    protected function projection(Lead $lead, string $fieldKey): MetadataValueProjection
    {
        return MetadataValueProjection::withoutGlobalScopes()
            ->where('organization_id', $lead->organization_id)
            ->where('entity_type', 'lead')
            ->where('entity_id', $lead->id)
            ->where('field_key', $fieldKey)
            ->firstOrFail();
    }
}
