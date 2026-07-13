<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\User;
use App\Services\MetadataValueStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetadataValueStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setupOrganization(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');

        return [$user, $organization];
    }

    public function test_service_merges_active_metadata_values_into_lead_storage(): void
    {
        [, $organization] = $this->setupOrganization();
        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'custom_fields' => [
                'legacy_key' => 'preserved',
                'visa_type' => 'visitor',
            ],
        ]);
        $this->field($organization, 'lead', 'visa_type', 'select');
        $this->field($organization, 'lead', 'ielts_score', 'decimal');

        $result = app(MetadataValueStorageService::class)->mergeValues($lead, [
            'visa_type' => 'student',
            'ielts_score' => '7.5',
        ]);

        $lead->refresh();

        $this->assertTrue($result['changed']);
        $this->assertSame('preserved', $lead->custom_fields['legacy_key']);
        $this->assertSame('student', $lead->custom_fields['visa_type']);
        $this->assertSame(7.5, $lead->custom_fields['ielts_score']);
        $this->assertSame('visitor', $result['changes']['visa_type']['old']);
        $this->assertSame('student', $result['changes']['visa_type']['new']);
    }

    public function test_unknown_values_are_ignored_unless_legacy_storage_is_allowed(): void
    {
        [, $organization] = $this->setupOrganization();
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'custom_fields' => ['existing' => 'value'],
        ]);
        $this->field($organization, 'customer', 'patient_id', 'text');

        $service = app(MetadataValueStorageService::class);
        $result = $service->mergeValues($customer, [
            'patient_id' => 'P-100',
            'unknown_key' => 'ignored',
        ]);

        $customer->refresh();
        $this->assertSame(['unknown_key'], $result['ignored']);
        $this->assertArrayNotHasKey('unknown_key', $customer->custom_fields);

        $service->mergeValues($customer, [
            'unknown_key' => 'legacy',
        ], allowUnknown: true);

        $customer->refresh();
        $this->assertSame('legacy', $customer->custom_fields['unknown_key']);
    }

    public function test_null_value_clears_existing_metadata_value_without_removing_omitted_values(): void
    {
        [, $organization] = $this->setupOrganization();
        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'custom_fields' => [
                'visa_type' => 'student',
                'destination_country' => 'Canada',
            ],
        ]);
        $this->field($organization, 'lead', 'visa_type', 'select');
        $this->field($organization, 'lead', 'destination_country', 'text');

        $result = app(MetadataValueStorageService::class)->mergeValues($lead, [
            'visa_type' => null,
        ]);

        $lead->refresh();

        $this->assertTrue($result['changed']);
        $this->assertArrayNotHasKey('visa_type', $lead->custom_fields);
        $this->assertSame('Canada', $lead->custom_fields['destination_country']);
    }

    public function test_inactive_metadata_fields_are_not_written_by_default(): void
    {
        [, $organization] = $this->setupOrganization();
        $lead = Lead::factory()->create(['organization_id' => $organization->id]);
        $this->field($organization, 'lead', 'draft_only', 'text', 'draft');

        $result = app(MetadataValueStorageService::class)->mergeValues($lead, [
            'draft_only' => 'ignored',
        ]);

        $lead->refresh();

        $this->assertFalse($result['changed']);
        $this->assertSame(['draft_only'], $result['ignored']);
        $this->assertNull($lead->custom_fields);
    }

    public function test_opportunity_and_organization_support_metadata_value_storage(): void
    {
        [, $organization] = $this->setupOrganization();
        $opportunity = Opportunity::factory()->create(['organization_id' => $organization->id]);
        $this->field($organization, 'opportunity', 'property_type', 'text');
        $this->field($organization, 'organization', 'license_number', 'text');

        $service = app(MetadataValueStorageService::class);

        $service->mergeValues($opportunity, [
            'property_type' => 'Apartment',
        ]);

        $service->mergeValues($organization, [
            'license_number' => 'ORG-123',
        ]);

        $opportunity->refresh();
        $organization->refresh();

        $this->assertSame('Apartment', $opportunity->custom_fields['property_type']);
        $this->assertSame('ORG-123', $organization->custom_fields['license_number']);
    }

    public function test_empty_string_clears_existing_metadata_value(): void
    {
        [, $organization] = $this->setupOrganization();
        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'custom_fields' => ['destination_country' => 'Canada'],
        ]);
        $this->field($organization, 'lead', 'destination_country', 'text');

        app(MetadataValueStorageService::class)->mergeValues($lead, [
            'destination_country' => '',
        ]);

        $lead->refresh();

        $this->assertNull($lead->custom_fields);
    }

    public function test_empty_multi_select_clears_existing_metadata_value(): void
    {
        [, $organization] = $this->setupOrganization();
        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'custom_fields' => [
                'preferred_countries' => ['canada', 'australia'],
                'destination_country' => 'Canada',
            ],
        ]);
        $this->field($organization, 'lead', 'preferred_countries', 'multi_select');
        $this->field($organization, 'lead', 'destination_country', 'text');

        app(MetadataValueStorageService::class)->mergeValues($lead, [
            'preferred_countries' => [],
        ]);

        $lead->refresh();

        $this->assertArrayNotHasKey('preferred_countries', $lead->custom_fields);
        $this->assertSame('Canada', $lead->custom_fields['destination_country']);
    }

    public function test_boolean_false_is_stored_as_false_not_cleared(): void
    {
        [, $organization] = $this->setupOrganization();
        $lead = Lead::factory()->create(['organization_id' => $organization->id]);
        $this->field($organization, 'lead', 'approved', 'boolean');

        app(MetadataValueStorageService::class)->mergeValues($lead, [
            'approved' => '0',
        ]);

        $lead->refresh();

        $this->assertArrayHasKey('approved', $lead->custom_fields);
        $this->assertFalse($lead->custom_fields['approved']);
    }

    public function test_date_and_datetime_values_are_normalized_for_storage(): void
    {
        [, $organization] = $this->setupOrganization();
        $lead = Lead::factory()->create(['organization_id' => $organization->id]);
        $this->field($organization, 'lead', 'arrival_date', 'date');
        $this->field($organization, 'lead', 'appointment_at', 'datetime');
        $this->field($organization, 'lead', 'appointment_time', 'time');

        app(MetadataValueStorageService::class)->mergeValues($lead, [
            'arrival_date' => '2026-09-15',
            'appointment_at' => '2026-09-15T14:30',
            'appointment_time' => '14:30',
        ]);

        $lead->refresh();

        $this->assertSame('2026-09-15', $lead->custom_fields['arrival_date']);
        $this->assertStringStartsWith('2026-09-15T14:30:00', $lead->custom_fields['appointment_at']);
        $this->assertSame('14:30:00', $lead->custom_fields['appointment_time']);
    }

    protected function field(Organization $organization, string $entity, string $key, string $type, string $status = 'active'): MetadataFieldDefinition
    {
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
}
