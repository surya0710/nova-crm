<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\Organization;
use App\Models\SavedFilter;
use App\Models\User;
use App\Services\MetadataProjectionService;
use App\Services\SavedFilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MetadataSavedFilterIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_update_and_delete_saved_filter(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'lead', 'destination_country', 'text', ['is_filterable' => true]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('saved-filters.store'), [
                'entity_type' => 'lead',
                'name' => 'Canada Leads',
                'description' => 'New leads in Canada',
                'visibility' => 'private',
                'redirect_route' => 'leads.index',
                'status' => 'new',
                'metadata_filters' => [
                    ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'saved-filter-created');

        $filter = SavedFilter::query()->first();
        $this->assertNotNull($filter);
        $this->assertSame('Canada Leads', $filter->name);
        $this->assertSame('private', $filter->visibility);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('saved-filters.update', $filter), [
                'name' => 'Canada Leads Renamed',
                'description' => 'Updated description',
                'visibility' => 'shared',
                'redirect_route' => 'leads.index',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'saved-filter-updated');

        $filter->refresh();
        $this->assertSame('Canada Leads Renamed', $filter->name);
        $this->assertSame('shared', $filter->visibility);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('saved-filters.destroy', $filter))
            ->assertRedirect(route('leads.index'))
            ->assertSessionHas('status', 'saved-filter-deleted');

        $this->assertDatabaseMissing('saved_filters', ['id' => $filter->id]);
    }

    public function test_private_saved_filters_are_not_visible_to_other_users(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('sales-executive');
        [$other, ] = $this->setupUserWithOrg('sales-executive');
        $organization->addMember($other, 'sales-executive');

        $filter = app(SavedFilterService::class)->create($organization->id, $owner, 'lead', [
            'name' => 'Private Filter',
            'visibility' => 'private',
            'filter_definition' => [
                'static_filters' => ['status' => 'new'],
            ],
        ]);

        $this->actingAs($other)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', ['saved_filter' => $filter->id]))
            ->assertSessionHasErrors('saved_filter');

        $available = app(SavedFilterService::class)->availableFor($other, $organization->id, 'lead');
        $this->assertFalse($available->contains('id', $filter->id));
    }

    public function test_shared_saved_filters_can_be_executed_by_organization_members(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('sales-executive');
        [$member, ] = $this->setupUserWithOrg('sales-executive');
        $organization->addMember($member, 'sales-executive');
        $this->field($organization, 'lead', 'destination_country', 'text', ['is_filterable' => true]);
        $match = $this->lead($organization, $owner, 'Shared Filter Match', 'new', ['destination_country' => 'Canada']);
        $this->lead($organization, $owner, 'Other Lead', 'new', ['destination_country' => 'Australia']);

        $filter = app(SavedFilterService::class)->create($organization->id, $owner, 'lead', [
            'name' => 'Shared Canada',
            'visibility' => 'shared',
            'filter_definition' => [
                'static_filters' => ['status' => 'new'],
                'metadata_filters' => [
                    ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
                ],
            ],
        ]);

        $this->actingAs($member)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', ['saved_filter' => $filter->id]))
            ->assertOk()
            ->assertSee($match->name)
            ->assertDontSee('Other Lead');
    }

    public function test_saved_filter_execution_uses_projection_queries(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'lead', 'destination_country', 'text', ['is_filterable' => true]);
        $this->lead($organization, $user, 'Projection Lead', 'new', ['destination_country' => 'Canada']);
        $filter = app(SavedFilterService::class)->create($organization->id, $user, 'lead', [
            'name' => 'Projection Filter',
            'visibility' => 'private',
            'filter_definition' => [
                'metadata_filters' => [
                    ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
                ],
            ],
        ]);
        $queries = [];

        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', ['saved_filter' => $filter->id]))
            ->assertOk();

        $this->assertTrue(collect($queries)->contains(fn (string $sql) => str_contains($sql, 'metadata_value_projections')));
        $this->assertFalse(collect($queries)->contains(fn (string $sql) => str_contains($sql, 'custom_fields')));
    }

    public function test_saved_filter_with_inactive_metadata_is_marked_partial_and_still_applies_valid_criteria(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $field = $this->field($organization, 'lead', 'destination_country', 'text', ['is_filterable' => true]);
        $match = $this->lead($organization, $user, 'Status Match', 'new', ['destination_country' => 'Canada']);
        $this->lead($organization, $user, 'Status Lost', 'lost', ['destination_country' => 'Canada']);

        $filter = app(SavedFilterService::class)->create($organization->id, $user, 'lead', [
            'name' => 'Partial Filter',
            'visibility' => 'private',
            'filter_definition' => [
                'static_filters' => ['status' => 'new'],
                'metadata_filters' => [
                    ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
                ],
            ],
        ]);

        $field->update(['status' => 'inactive']);

        $filter = app(SavedFilterService::class)->refreshValidation($filter->fresh());
        $this->assertSame('partial', $filter->validation_status);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', ['saved_filter' => $filter->id]))
            ->assertOk()
            ->assertSee($match->name)
            ->assertDontSee('Status Lost');
    }

    public function test_saved_filter_rejects_unsupported_operators_at_validation_time(): void
    {
        [, $organization] = $this->setupOrganization();
        $this->field($organization, 'lead', 'destination_country', 'text', ['is_filterable' => true]);

        $validation = app(SavedFilterService::class)->validateDefinition($organization->id, 'lead', [
            'static_filters' => ['status' => 'new'],
            'metadata_filters' => [
                ['key' => 'destination_country', 'operator' => 'fuzzy_match', 'value' => 'Canada'],
            ],
        ]);

        $this->assertSame('partial', $validation['status']);
        $this->assertNotEmpty($validation['errors']);
    }

    public function test_saved_filters_are_tenant_isolated(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $otherOrganization = Organization::factory()->create();
        $otherOrganization->addMember($user, 'sales-executive');

        $filter = app(SavedFilterService::class)->create($organization->id, $user, 'lead', [
            'name' => 'Tenant Filter',
            'visibility' => 'shared',
            'filter_definition' => [
                'static_filters' => ['status' => 'new'],
            ],
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $otherOrganization->id])
            ->from(route('leads.index'))
            ->get(route('leads.index', ['saved_filter' => $filter->id]))
            ->assertRedirect(route('leads.index'))
            ->assertSessionHasErrors('saved_filter');
    }

    public function test_saved_filter_respects_entity_permissions(): void
    {
        [$hrUser, $organization] = $this->setupUserWithOrg('hr');
        [$owner, ] = $this->setupUserWithOrg('sales-executive');
        $organization->addMember($owner, 'sales-executive');

        $filter = app(SavedFilterService::class)->create($organization->id, $owner, 'lead', [
            'name' => 'Shared Leads Filter',
            'visibility' => 'shared',
            'filter_definition' => [
                'static_filters' => ['status' => 'new'],
            ],
        ]);

        $this->actingAs($hrUser)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', ['saved_filter' => $filter->id]))
            ->assertForbidden();
    }

    public function test_saved_filter_supports_metadata_sorting(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'lead', 'ielts_score', 'decimal', ['is_sortable' => true]);
        $seven = $this->lead($organization, $user, 'Seven Lead', 'new', ['ielts_score' => 7.0]);
        $nine = $this->lead($organization, $user, 'Nine Lead', 'new', ['ielts_score' => 9.0]);

        $filter = app(SavedFilterService::class)->create($organization->id, $user, 'lead', [
            'name' => 'Sorted Filter',
            'visibility' => 'private',
            'filter_definition' => [
                'metadata_sort' => ['key' => 'ielts_score', 'direction' => 'asc'],
            ],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', ['saved_filter' => $filter->id]));

        $response->assertOk();
        $this->assertLessThan(
            strpos($response->getContent(), $nine->name),
            strpos($response->getContent(), $seven->name),
        );
    }

    public function test_user_can_duplicate_saved_filter(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $filter = app(SavedFilterService::class)->create($organization->id, $user, 'lead', [
            'name' => 'Original Filter',
            'visibility' => 'shared',
            'filter_definition' => [
                'static_filters' => ['status' => 'new'],
            ],
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('saved-filters.duplicate', $filter))
            ->assertRedirect()
            ->assertSessionHas('status', 'saved-filter-duplicated');

        $this->assertDatabaseHas('saved_filters', [
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'visibility' => 'private',
        ]);
        $this->assertSame(2, SavedFilter::query()->count());
    }

    public function test_user_can_overwrite_saved_filter_criteria_from_index_filters(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'lead', 'destination_country', 'text', ['is_filterable' => true]);

        $filter = app(SavedFilterService::class)->create($organization->id, $user, 'lead', [
            'name' => 'Original Criteria',
            'visibility' => 'private',
            'filter_definition' => [
                'static_filters' => ['status' => 'new'],
                'metadata_filters' => [
                    ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
                ],
            ],
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('saved-filters.update', $filter), [
                'name' => 'Original Criteria',
                'description' => null,
                'visibility' => 'private',
                'redirect_route' => 'leads.index',
                'update_filter_definition' => true,
                'status' => 'qualified',
                'metadata_filters' => [
                    ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Australia'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'saved-filter-updated');

        $filter->refresh();
        $this->assertSame('qualified', $filter->filter_definition['static_filters']['status'] ?? null);
        $this->assertSame('Australia', $filter->filter_definition['metadata_filters'][0]['value'] ?? null);
    }

    public function test_loading_saved_filter_restores_complete_filter_state_in_ui(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'lead', 'destination_country', 'text', ['is_filterable' => true]);
        $this->field($organization, 'lead', 'ielts_score', 'decimal', ['is_sortable' => true]);

        $filter = app(SavedFilterService::class)->create($organization->id, $user, 'lead', [
            'name' => 'Complete State Filter',
            'visibility' => 'private',
            'filter_definition' => [
                'static_filters' => [
                    'search' => 'Acme',
                    'status' => 'new',
                    'source' => 'website',
                    'priority' => 'high',
                ],
                'metadata_filters' => [
                    ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
                ],
                'metadata_sort' => ['key' => 'ielts_score', 'direction' => 'desc'],
            ],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', ['saved_filter' => $filter->id]));

        $response->assertOk();
        $response->assertSee('value="Acme"', false);
        $response->assertSee('Active saved filter: Complete State Filter', false);
        $response->assertSee('value="destination_country"', false);
        $response->assertSee('value="Canada"', false);
        $response->assertSee('value="ielts_score"', false);
        $response->assertSee(__('Clear saved filter'), false);
    }

    public function test_save_filter_captures_unsubmitted_index_form_values(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'lead', 'destination_country', 'text', ['is_filterable' => true]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('saved-filters.store'), [
                'entity_type' => 'lead',
                'name' => 'Unsubmitted Form Filter',
                'description' => 'Captured directly from form',
                'visibility' => 'private',
                'redirect_route' => 'leads.index',
                'status' => 'qualified',
                'metadata_filters' => [
                    ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'New Zealand'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'saved-filter-created');

        $filter = SavedFilter::query()->where('name', 'Unsubmitted Form Filter')->first();
        $this->assertNotNull($filter);
        $this->assertSame('qualified', $filter->filter_definition['static_filters']['status'] ?? null);
        $this->assertSame('New Zealand', $filter->filter_definition['metadata_filters'][0]['value'] ?? null);
    }

    public function test_saved_filter_load_ignores_conflicting_query_parameters(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $match = $this->lead($organization, $user, 'Saved Status Match', 'new');
        $this->lead($organization, $user, 'Conflicting Status', 'lost');

        $filter = app(SavedFilterService::class)->create($organization->id, $user, 'lead', [
            'name' => 'Status New Only',
            'visibility' => 'private',
            'filter_definition' => [
                'static_filters' => ['status' => 'new'],
            ],
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', [
                'saved_filter' => $filter->id,
                'status' => 'lost',
            ]))
            ->assertOk()
            ->assertSee($match->name)
            ->assertDontSee('Conflicting Status');
    }

    public function test_saved_filter_pagination_preserves_saved_filter_parameter(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        for ($index = 1; $index <= 16; $index++) {
            $this->lead($organization, $user, "Lead {$index}", 'new');
        }

        $filter = app(SavedFilterService::class)->create($organization->id, $user, 'lead', [
            'name' => 'Paginated Filter',
            'visibility' => 'private',
            'filter_definition' => [
                'static_filters' => ['status' => 'new'],
            ],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', ['saved_filter' => $filter->id, 'page' => 2]));

        $response->assertOk();
        $response->assertSee('saved_filter='.$filter->id, false);
    }

    public function test_deleted_saved_filter_is_rejected(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $filter = app(SavedFilterService::class)->create($organization->id, $user, 'lead', [
            'name' => 'Deleted Filter',
            'visibility' => 'private',
            'filter_definition' => [
                'static_filters' => ['status' => 'new'],
            ],
        ]);

        $filterId = $filter->id;
        $filter->delete();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->from(route('leads.index'))
            ->get(route('leads.index', ['saved_filter' => $filterId]))
            ->assertRedirect(route('leads.index'))
            ->assertSessionHasErrors('saved_filter');
    }

    public function test_invalid_saved_filter_is_rejected(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $filter = app(SavedFilterService::class)->create($organization->id, $user, 'lead', [
            'name' => 'Invalid Filter',
            'visibility' => 'private',
            'filter_definition' => [
                'static_filters' => ['status' => 'new'],
            ],
        ]);

        $filter->update([
            'filter_definition' => [
                'version' => 1,
                'static_filters' => [],
                'metadata_filters' => [],
                'metadata_sort' => null,
            ],
            'validation_status' => 'invalid',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->from(route('leads.index'))
            ->get(route('leads.index', ['saved_filter' => $filter->id]))
            ->assertRedirect(route('leads.index'))
            ->assertSessionHasErrors('saved_filter');
    }

    public function test_unauthorized_user_cannot_update_saved_filter(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('sales-executive');
        [$other, ] = $this->setupUserWithOrg('sales-executive');
        $organization->addMember($other, 'sales-executive');

        $filter = app(SavedFilterService::class)->create($organization->id, $owner, 'lead', [
            'name' => 'Owner Filter',
            'visibility' => 'private',
            'filter_definition' => [
                'static_filters' => ['status' => 'new'],
            ],
        ]);

        $this->actingAs($other)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('saved-filters.update', $filter), [
                'name' => 'Hijacked',
                'visibility' => 'private',
                'redirect_route' => 'leads.index',
            ])
            ->assertForbidden();
    }

    public function test_mixed_static_and_metadata_filters_execute_together(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $this->field($organization, 'lead', 'destination_country', 'text', ['is_filterable' => true]);
        $match = $this->lead($organization, $user, 'Mixed Match', 'new', ['destination_country' => 'Canada']);
        $this->lead($organization, $user, 'Wrong Status', 'lost', ['destination_country' => 'Canada']);
        $this->lead($organization, $user, 'Wrong Country', 'new', ['destination_country' => 'Australia']);

        $filter = app(SavedFilterService::class)->create($organization->id, $user, 'lead', [
            'name' => 'Mixed Filter',
            'visibility' => 'private',
            'filter_definition' => [
                'static_filters' => ['status' => 'new'],
                'metadata_filters' => [
                    ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
                ],
            ],
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', ['saved_filter' => $filter->id]))
            ->assertOk()
            ->assertSee($match->name)
            ->assertDontSee('Wrong Status')
            ->assertDontSee('Wrong Country');
    }

    public function test_archived_metadata_field_marks_filter_partial_without_exception(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');
        $field = $this->field($organization, 'lead', 'destination_country', 'text', ['is_filterable' => true]);
        $match = $this->lead($organization, $user, 'Archived Field Match', 'new');
        $this->lead($organization, $user, 'Archived Field Lost', 'lost');

        $filter = app(SavedFilterService::class)->create($organization->id, $user, 'lead', [
            'name' => 'Archived Metadata Filter',
            'visibility' => 'private',
            'filter_definition' => [
                'static_filters' => ['status' => 'new'],
                'metadata_filters' => [
                    ['key' => 'destination_country', 'operator' => 'equals', 'value' => 'Canada'],
                ],
            ],
        ]);

        $field->update(['status' => 'archived']);

        $filter = app(SavedFilterService::class)->refreshValidation($filter->fresh());
        $this->assertSame('partial', $filter->validation_status);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', ['saved_filter' => $filter->id]))
            ->assertOk()
            ->assertSee($match->name)
            ->assertDontSee('Archived Field Lost');
    }

    protected function setupUserWithOrg(string $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    protected function setupOrganization(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');

        return [$user, $organization];
    }

    protected function field(Organization $organization, string $entity, string $key, string $type, array $attributes = []): MetadataFieldDefinition
    {
        return MetadataFieldDefinition::query()->create([
            'organization_id' => $organization->id,
            'entity_type' => $entity,
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
