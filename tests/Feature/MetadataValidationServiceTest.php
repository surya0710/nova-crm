<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\MetadataFieldOption;
use App\Models\Organization;
use App\Services\MetadataFormResolver;
use App\Services\MetadataValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MetadataValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_metadata_field_fails_when_missing(): void
    {
        $organization = Organization::factory()->create();
        $this->field($organization, 'lead', 'visa_type', 'text', ['is_required' => true]);

        $this->expectValidationError('custom_fields.visa_type', function () use ($organization) {
            $this->validate($organization, 'lead', []);
        });
    }

    public function test_required_boolean_false_is_valid(): void
    {
        $organization = Organization::factory()->create();
        $this->field($organization, 'lead', 'approved', 'boolean', ['is_required' => true]);

        $values = $this->validate($organization, 'lead', ['approved' => '0']);

        $this->assertSame(['approved' => '0'], $values);
    }

    public function test_option_backed_fields_reject_unknown_values(): void
    {
        $organization = Organization::factory()->create();
        $field = $this->field($organization, 'lead', 'visa_type', 'select');
        $this->option($organization, $field, 'student', 'Student');

        $this->expectValidationError('custom_fields.visa_type', function () use ($organization) {
            $this->validate($organization, 'lead', ['visa_type' => 'visitor']);
        });
    }

    public function test_multi_select_rejects_unknown_option_values(): void
    {
        $organization = Organization::factory()->create();
        $field = $this->field($organization, 'lead', 'countries', 'multi_select');
        $this->option($organization, $field, 'canada', 'Canada');

        $this->expectValidationError('custom_fields.countries', function () use ($organization) {
            $this->validate($organization, 'lead', ['countries' => ['canada', 'unknown']]);
        });
    }

    public function test_type_validation_rejects_invalid_numeric_values(): void
    {
        $organization = Organization::factory()->create();
        $this->field($organization, 'lead', 'ielts_score', 'decimal');

        $this->expectValidationError('custom_fields.ielts_score', function () use ($organization) {
            $this->validate($organization, 'lead', ['ielts_score' => 'not-a-number']);
        });
    }

    public function test_unique_metadata_field_is_scoped_to_organization_and_entity(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $this->field($organization, 'lead', 'passport_number', 'text', ['is_unique' => true]);
        $this->field($otherOrganization, 'lead', 'passport_number', 'text', ['is_unique' => true]);
        Lead::factory()->create([
            'organization_id' => $organization->id,
            'custom_fields' => ['passport_number' => 'P-100'],
        ]);
        Lead::factory()->create([
            'organization_id' => $otherOrganization->id,
            'custom_fields' => ['passport_number' => 'P-100'],
        ]);

        $this->expectValidationError('custom_fields.passport_number', function () use ($organization) {
            $this->validate($organization, 'lead', ['passport_number' => 'P-100']);
        });

        $values = $this->validate($organization, 'lead', ['passport_number' => 'P-200']);
        $this->assertSame(['passport_number' => 'P-200'], $values);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function validate(Organization $organization, string $entityType, array $payload): array
    {
        return app(MetadataValidationService::class)->validate(
            null,
            $organization,
            $entityType,
            app(MetadataFormResolver::class)->fieldsFor($organization, $entityType, 'create'),
            $payload,
        );
    }

    protected function expectValidationError(string $key, callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($key, $exception->errors());
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function field(Organization $organization, string $entityType, string $key, string $type, array $attributes = []): MetadataFieldDefinition
    {
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
