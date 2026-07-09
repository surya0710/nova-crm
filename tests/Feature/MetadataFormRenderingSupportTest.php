<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\MetadataFieldGroup;
use App\Models\MetadataFieldLayout;
use App\Models\MetadataFieldLayoutField;
use App\Models\MetadataFieldOption;
use App\Models\Organization;
use App\Services\MetadataFormResolver;
use App\Services\MetadataFormValuePresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetadataFormRenderingSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_returns_active_organization_fields_in_group_order(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();

        $admissions = $this->group($organization, 'lead', 'admissions', 'Admissions', 1);
        $profile = $this->group($organization, 'lead', 'profile', 'Profile', 2);

        $this->field($organization, 'lead', 'passport_number', 'text', [
            'metadata_field_group_id' => $profile->id,
            'sort_order' => 1,
        ]);
        $this->field($organization, 'lead', 'visa_type', 'select', [
            'metadata_field_group_id' => $admissions->id,
            'sort_order' => 20,
        ]);
        $this->field($organization, 'lead', 'draft_only', 'text', ['status' => 'draft']);
        $this->field($organization, 'customer', 'customer_only', 'text');
        $this->field($otherOrganization, 'lead', 'other_tenant', 'text');

        $fields = app(MetadataFormResolver::class)->fieldsFor($organization, 'lead', 'edit');

        $this->assertSame(['visa_type', 'passport_number'], $fields->pluck('field.key')->all());
        $this->assertSame(['admissions', 'profile'], $fields->pluck('group.key')->all());
        $this->assertSame(['full', 'full'], $fields->pluck('width')->all());
    }

    public function test_default_layout_controls_context_order_and_placement_metadata(): void
    {
        $organization = Organization::factory()->create();
        $first = $this->field($organization, 'lead', 'first_choice', 'text', ['sort_order' => 1]);
        $second = $this->field($organization, 'lead', 'second_choice', 'text', ['sort_order' => 2]);

        $layout = MetadataFieldLayout::query()->create([
            'organization_id' => $organization->id,
            'entity_type' => 'lead',
            'context' => 'create',
            'name' => 'Lead Create',
            'is_default' => true,
        ]);

        $this->placement($organization, $layout, $second, 1, [
            'section_key' => 'custom_section',
            'width' => 'half',
        ]);
        $this->placement($organization, $layout, $first, 2, [
            'section_key' => 'custom_section',
            'width' => 'full',
        ]);

        $fields = app(MetadataFormResolver::class)->fieldsFor($organization, 'lead', 'create');

        $this->assertSame(['second_choice', 'first_choice'], $fields->pluck('field.key')->all());
        $this->assertSame(['half', 'full'], $fields->pluck('width')->all());
        $this->assertSame(['custom_section', 'custom_section'], $fields->pluck('section_key')->all());
    }

    public function test_presenter_resolves_form_values_and_display_labels(): void
    {
        $organization = Organization::factory()->create();
        $visaType = $this->field($organization, 'lead', 'visa_type', 'select');
        $countries = $this->field($organization, 'lead', 'preferred_countries', 'multi_select', [
            'default_value' => ['canada'],
        ]);
        $approved = $this->field($organization, 'lead', 'approved', 'boolean');

        $this->option($organization, $visaType, 'student', 'Student Visa', 1);
        $this->option($organization, $visaType, 'work', 'Work Visa', 2);
        $this->option($organization, $countries, 'canada', 'Canada', 1);
        $this->option($organization, $countries, 'australia', 'Australia', 2);

        $visaType->load('options');
        $countries->load('options');
        $approved->load('options');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'custom_fields' => [
                'visa_type' => 'work',
                'approved' => false,
            ],
        ]);

        $presenter = app(MetadataFormValuePresenter::class);

        $this->assertSame('student', $presenter->formValue($visaType, $lead, ['visa_type' => 'student']));
        $this->assertSame('work', $presenter->formValue($visaType, $lead));
        $this->assertSame(['canada'], $presenter->formValue($countries));
        $this->assertSame('Work Visa', $presenter->displayValue($visaType, 'work'));
        $this->assertSame('Canada, Australia', $presenter->displayValue($countries, ['canada', 'australia']));
        $this->assertSame('No', $presenter->displayValue($approved, false));
        $this->assertSame('custom_fields[visa_type]', $presenter->inputName($visaType));
        $this->assertSame('custom_fields.visa_type', $presenter->errorKey($visaType));
    }

    public function test_presenter_extracts_only_rendered_submitted_values(): void
    {
        $organization = Organization::factory()->create();
        $notes = $this->field($organization, 'lead', 'notes', 'textarea');
        $countries = $this->field($organization, 'lead', 'preferred_countries', 'multi_select');

        $resolvedFields = collect([
            ['field' => $notes],
            ['field' => $countries],
        ]);

        $values = app(MetadataFormValuePresenter::class)->extractSubmittedValues($resolvedFields, [
            'notes' => '',
            'preferred_countries' => ['canada', '', null, 'australia'],
            'unrendered_key' => 'must-not-write',
        ]);

        $this->assertSame([
            'notes' => null,
            'preferred_countries' => ['canada', 'australia'],
        ], $values);
    }

    protected function group(Organization $organization, string $entityType, string $key, string $label, int $sortOrder): MetadataFieldGroup
    {
        return MetadataFieldGroup::query()->create([
            'organization_id' => $organization->id,
            'entity_type' => $entityType,
            'key' => $key,
            'label' => $label,
            'sort_order' => $sortOrder,
        ]);
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

    protected function option(Organization $organization, MetadataFieldDefinition $field, string $value, string $label, int $sortOrder): MetadataFieldOption
    {
        return MetadataFieldOption::query()->create([
            'organization_id' => $organization->id,
            'metadata_field_definition_id' => $field->id,
            'value' => $value,
            'label' => $label,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function placement(
        Organization $organization,
        MetadataFieldLayout $layout,
        MetadataFieldDefinition $field,
        int $sortOrder,
        array $attributes = []
    ): MetadataFieldLayoutField {
        return MetadataFieldLayoutField::query()->create([
            'organization_id' => $organization->id,
            'metadata_field_layout_id' => $layout->id,
            'metadata_field_definition_id' => $field->id,
            'sort_order' => $sortOrder,
            ...$attributes,
        ]);
    }
}
