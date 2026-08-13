<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\MetadataValueProjection;
use App\Models\Organization;
use App\Models\User;
use App\Services\MetadataProjectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadMetadataFilterBugfixTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_index_metadata_filter_works_after_web_form_create_without_manual_projection_sync(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $this->field($organization, 'destination_country', 'text', ['is_filterable' => true]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('leads.store'), [
                'name' => 'Canada Lead',
                'email' => 'canada@example.com',
                'source' => 'manual_entry',
                'status' => 'new',
                'priority' => 'medium',
                'custom_fields' => [
                    'destination_country' => 'Canada',
                ],
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('leads.store'), [
                'name' => 'Australia Lead',
                'email' => 'australia@example.com',
                'source' => 'manual_entry',
                'status' => 'new',
                'priority' => 'medium',
                'custom_fields' => [
                    'destination_country' => 'Australia',
                ],
            ])
            ->assertRedirect();

        $this->assertSame('Canada', Lead::query()->where('name', 'Canada Lead')->value('custom_fields')['destination_country'] ?? null);
        $this->assertGreaterThan(0, MetadataValueProjection::withoutGlobalScopes()->count());

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', [
                'metadata_filters' => [
                    ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
                ],
            ]));

        $response->assertOk();
        $response->assertSee('Canada Lead');
        $response->assertDontSee('Australia Lead');
    }

    public function test_lead_index_applies_multiple_metadata_filters_together(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $this->field($organization, 'destination_country', 'text', ['is_filterable' => true]);
        $this->field($organization, 'visa_type', 'select', ['is_filterable' => true]);

        $match = $this->lead($organization, $user, 'Match Lead', 'new', [
            'destination_country' => 'Canada',
            'visa_type' => 'student',
        ]);
        $this->lead($organization, $user, 'Country Only', 'new', [
            'destination_country' => 'Canada',
            'visa_type' => 'visitor',
        ]);
        $this->lead($organization, $user, 'Visa Only', 'new', [
            'destination_country' => 'Australia',
            'visa_type' => 'student',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', [
                'metadata_filters' => [
                    ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
                    ['key' => 'visa_type', 'operator' => 'equals', 'value' => 'student'],
                ],
            ]));

        $response->assertOk();
        $response->assertSee($match->name);
        $response->assertDontSee('Country Only');
        $response->assertDontSee('Visa Only');
    }

    public function test_lead_index_metadata_filter_composes_with_static_filters(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $this->field($organization, 'destination_country', 'text', ['is_filterable' => true]);

        $match = $this->lead($organization, $user, 'Qualified Canada', 'qualified', ['destination_country' => 'Canada']);
        $this->lead($organization, $user, 'New Canada', 'new', ['destination_country' => 'Canada']);
        $this->lead($organization, $user, 'Qualified Australia', 'qualified', ['destination_country' => 'Australia']);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', [
                'status' => 'qualified',
                'metadata_filters' => [
                    ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
                ],
            ]));

        $response->assertOk();
        $response->assertSee($match->name);
        $response->assertDontSee('New Canada');
        $response->assertDontSee('Qualified Australia');
    }

    public function test_lead_index_rejects_invalid_metadata_filter_fields(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $this->field($organization, 'destination_country', 'text');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', [
                'metadata_filters' => [
                    ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
                ],
            ]))
            ->assertSessionHasErrors('metadata_filters.0.key');
    }

    public function test_lead_index_ignores_inactive_metadata_filter_fields(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $this->field($organization, 'inactive_destination', 'text', ['status' => 'inactive', 'is_filterable' => true]);
        $visible = $this->lead($organization, $user, 'Visible Lead', 'new');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', [
                'metadata_filters' => [
                    ['key' => 'inactive_destination', 'operator' => 'equals', 'value' => 'Canada'],
                ],
            ]));

        $response->assertOk();
        $response->assertSee($visible->name);
    }

    public function test_lead_index_metadata_filter_preserves_tenant_isolation(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        [, $otherOrganization] = $this->setupUserWithOrg('manager');
        $this->field($organization, 'destination_country', 'text', ['is_filterable' => true]);
        $this->field($otherOrganization, 'destination_country', 'text', ['is_filterable' => true]);
        $visible = $this->lead($organization, $user, 'Tenant A Lead', 'new', ['destination_country' => 'Canada']);
        $this->lead($otherOrganization, User::factory()->create(), 'Tenant B Lead', 'new', ['destination_country' => 'Canada']);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', [
                'metadata_filters' => [
                    ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
                ],
            ]));

        $response->assertOk();
        $response->assertSee($visible->name);
        $response->assertDontSee('Tenant B Lead');
    }

    public function test_lead_index_metadata_filter_works_after_projection_backfill_for_legacy_storage(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $this->field($organization, 'destination_country', 'text', ['is_filterable' => true]);

        Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'name' => 'Legacy Canada Lead',
            'status' => 'new',
            'custom_fields' => ['destination_country' => 'Canada'],
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', [
                'metadata_filters' => [
                    ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
                ],
            ]))
            ->assertOk()
            ->assertDontSee('Legacy Canada Lead');

        app(MetadataProjectionService::class)->rebuildForOrganizationEntity($organization->id, 'lead');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', [
                'metadata_filters' => [
                    ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
                ],
            ]))
            ->assertOk()
            ->assertSee('Legacy Canada Lead');
    }

    public function test_lead_index_metadata_filter_survives_pagination_and_sorting(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $this->field($organization, 'destination_country', 'text', ['is_filterable' => true]);
        $this->field($organization, 'ielts_score', 'decimal', ['is_sortable' => true]);

        $this->lead($organization, $user, 'Target Lead', 'new', [
            'destination_country' => 'Canada',
            'ielts_score' => 8.5,
        ]);

        Lead::factory()->count(20)->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'status' => 'new',
            'custom_fields' => ['destination_country' => 'Australia'],
        ]);

        foreach (Lead::query()->where('organization_id', $organization->id)->get() as $lead) {
            app(MetadataProjectionService::class)->sync($lead);
        }

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', [
                'metadata_filters' => [
                    ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
                ],
                'metadata_sort' => ['key' => 'ielts_score', 'direction' => 'desc'],
            ]));

        $response->assertOk();
        $response->assertSee('Target Lead');
        $response->assertDontSee('Australia');
    }

    protected function setupUserWithOrg(string $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

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

    protected function lead(Organization $organization, User $user, string $name, string $status, array $customFields = []): Lead
    {
        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'name' => $name,
            'status' => $status,
            'custom_fields' => $customFields === [] ? null : $customFields,
        ]);

        app(MetadataProjectionService::class)->sync($lead);

        return $lead;
    }
}
