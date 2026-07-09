<?php

namespace Tests\Feature;

use App\Models\MetadataFieldDefinition;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetadataFieldDefinitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'manager'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_manager_can_create_metadata_field_draft(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('metadata-fields.store'), [
                'entity_type' => 'lead',
                'label' => 'Visa Type',
                'key' => 'visa_type',
                'type' => 'select',
                'group_label' => 'Immigration Details',
                'is_required' => '1',
                'is_searchable' => '1',
                'is_filterable' => '1',
                'is_exportable' => '1',
                'is_api_visible' => '1',
                'options_text' => "student|Student\nwork|Work",
            ]);

        $field = MetadataFieldDefinition::firstOrFail();

        $response->assertRedirect(route('metadata-fields.show', $field));

        $this->assertDatabaseHas('metadata_field_definitions', [
            'organization_id' => $organization->id,
            'entity_type' => 'lead',
            'key' => 'visa_type',
            'label' => 'Visa Type',
            'type' => 'select',
            'status' => 'draft',
            'is_required' => true,
            'is_searchable' => true,
        ]);
        $this->assertDatabaseHas('metadata_field_options', [
            'metadata_field_definition_id' => $field->id,
            'value' => 'student',
            'label' => 'Student',
        ]);
        $this->assertDatabaseHas('metadata_field_versions', [
            'metadata_field_definition_id' => $field->id,
            'version' => 1,
            'event' => 'created',
        ]);
    }

    public function test_metadata_field_lifecycle_creates_version_snapshots(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('metadata-fields.store'), [
                'entity_type' => 'customer',
                'label' => 'Patient ID',
                'key' => 'patient_id',
                'type' => 'text',
                'is_exportable' => '1',
                'is_api_visible' => '1',
            ]);

        $field = MetadataFieldDefinition::firstOrFail();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('metadata-fields.publish', $field))
            ->assertRedirect(route('metadata-fields.show', $field));

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('metadata-fields.activate', $field))
            ->assertRedirect(route('metadata-fields.show', $field));

        $field->refresh();

        $this->assertSame('active', $field->status);
        $this->assertNotNull($field->published_at);
        $this->assertNotNull($field->activated_at);
        $this->assertDatabaseHas('metadata_field_versions', [
            'metadata_field_definition_id' => $field->id,
            'version' => 2,
            'event' => 'published',
        ]);
        $this->assertDatabaseHas('metadata_field_versions', [
            'metadata_field_definition_id' => $field->id,
            'version' => 3,
            'event' => 'activated',
        ]);
    }

    public function test_published_field_identity_is_locked(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $field = MetadataFieldDefinition::query()->create([
            'organization_id' => $organization->id,
            'entity_type' => 'lead',
            'key' => 'passport_number',
            'label' => 'Passport Number',
            'type' => 'text',
            'status' => 'published',
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('metadata-fields.update', $field), [
                'entity_type' => 'customer',
                'label' => 'Passport No.',
                'key' => 'passport_no',
                'type' => 'number',
                'is_exportable' => '1',
                'is_api_visible' => '1',
            ]);

        $response->assertSessionHasErrors(['entity_type', 'type', 'key']);
    }

    public function test_metadata_fields_are_tenant_scoped(): void
    {
        [$user, $orgA] = $this->setupUserWithOrg();
        $orgB = Organization::factory()->create();

        MetadataFieldDefinition::query()->create([
            'organization_id' => $orgA->id,
            'entity_type' => 'lead',
            'key' => 'org_a_field',
            'label' => 'Org A Field',
            'type' => 'text',
            'status' => 'draft',
        ]);

        MetadataFieldDefinition::withoutGlobalScopes()->create([
            'organization_id' => $orgB->id,
            'entity_type' => 'lead',
            'key' => 'org_b_field',
            'label' => 'Org B Field',
            'type' => 'text',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $orgA->id])
            ->get(route('metadata-fields.index'));

        $response->assertOk();
        $response->assertSee('Org A Field');
        $response->assertDontSee('Org B Field');
    }

    public function test_employee_cannot_manage_metadata_fields(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('employee');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('metadata-fields.index'))
            ->assertForbidden();
    }

    public function test_owner_can_activate_copied_template_blueprints_for_existing_organization(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $organization->update([
            'settings' => [
                'field_blueprints' => [[
                    'entity' => 'lead',
                    'key' => 'visa_type',
                    'label' => 'Visa Type',
                    'type' => 'select',
                    'group' => 'Immigration Details',
                    'options' => [
                        ['value' => 'student', 'label' => 'Student Visa'],
                        ['value' => 'work', 'label' => 'Work Visa'],
                    ],
                    'required' => true,
                    'filterable' => true,
                ]],
            ],
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('metadata-fields.activate-blueprints'))
            ->assertRedirect(route('metadata-fields.index'))
            ->assertSessionHas('metadata_activation_summary');

        $field = MetadataFieldDefinition::where('organization_id', $organization->id)
            ->where('entity_type', 'lead')
            ->where('key', 'visa_type')
            ->firstOrFail();

        $this->assertSame('active', $field->status);
        $this->assertSame('industry_template', $field->source);
        $this->assertTrue($field->is_required);
        $this->assertTrue($field->is_filterable);
        $this->assertDatabaseHas('metadata_field_groups', [
            'organization_id' => $organization->id,
            'entity_type' => 'lead',
            'key' => 'immigration_details',
        ]);
        $this->assertDatabaseHas('metadata_field_options', [
            'metadata_field_definition_id' => $field->id,
            'value' => 'student',
            'label' => 'Student Visa',
        ]);
    }

    public function test_blueprint_activation_is_idempotent_and_reports_conflicts(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('organization-owner');
        $organization->update([
            'settings' => [
                'field_blueprints' => [[
                    'entity' => 'lead',
                    'key' => 'passport_number',
                    'label' => 'Passport Number',
                    'type' => 'text',
                ]],
            ],
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('metadata-fields.activate-blueprints'))
            ->assertRedirect(route('metadata-fields.index'));

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('metadata-fields.activate-blueprints'))
            ->assertRedirect(route('metadata-fields.index'));

        $this->assertSame(1, MetadataFieldDefinition::where('organization_id', $organization->id)
            ->where('entity_type', 'lead')
            ->where('key', 'passport_number')
            ->count());
    }

    public function test_employee_cannot_activate_template_blueprints(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('employee');
        $organization->update([
            'settings' => [
                'field_blueprints' => [[
                    'entity' => 'lead',
                    'key' => 'blocked',
                    'label' => 'Blocked',
                    'type' => 'text',
                ]],
            ],
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('metadata-fields.activate-blueprints'))
            ->assertForbidden();

        $this->assertDatabaseMissing('metadata_field_definitions', [
            'organization_id' => $organization->id,
            'key' => 'blocked',
        ]);
    }
}
