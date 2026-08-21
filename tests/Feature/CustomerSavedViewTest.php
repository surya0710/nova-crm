<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\SavedFilter;
use App\Models\User;
use App\Services\SavedFilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerSavedViewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Organization}
     */
    protected function setupUserWithOrg(string $role = 'sales-executive'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_index_filters_by_lifecycle_segment_tags_and_created_date(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $match = Customer::factory()->create([
            'organization_id' => $organization->id,
            'company' => 'Match Co',
            'lifecycle_stage' => 'opportunity',
            'segment' => 'enterprise',
            'source' => 'referral',
            'tags' => ['vip'],
            'created_by' => $user->id,
            'created_at' => now()->subDays(2),
        ]);

        Customer::factory()->create([
            'organization_id' => $organization->id,
            'company' => 'Other Co',
            'lifecycle_stage' => 'subscriber',
            'segment' => 'smb',
            'source' => 'website',
            'tags' => ['pilot'],
            'created_by' => $user->id,
            'created_at' => now()->subDays(40),
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.index', [
                'lifecycle_stage' => 'opportunity',
                'segment' => 'enterprise',
                'source' => 'referral',
                'tags' => 'vip',
                'created_from' => now()->subDays(7)->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Match Co')
            ->assertDontSee('Other Co');

        $this->assertSame($match->company, 'Match Co');
    }

    public function test_index_filters_by_last_activity_and_customer_value(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $valuable = Customer::factory()->create([
            'organization_id' => $organization->id,
            'company' => 'Valuable Co',
            'last_activity_at' => now()->subDay(),
            'created_by' => $user->id,
        ]);

        Customer::factory()->create([
            'organization_id' => $organization->id,
            'company' => 'Quiet Co',
            'last_activity_at' => now()->subDays(40),
            'created_by' => $user->id,
        ]);

        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $valuable->id,
            'status' => 'issued',
            'total' => 5000,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.index', [
                'last_activity_from' => now()->subDays(7)->toDateString(),
                'value_min' => 1000,
            ]))
            ->assertOk()
            ->assertSee('Valuable Co')
            ->assertDontSee('Quiet Co');
    }

    public function test_saved_view_persists_customer_static_filters_and_can_be_default(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        Customer::factory()->create([
            'organization_id' => $organization->id,
            'company' => 'Enterprise Match',
            'lifecycle_stage' => 'customer',
            'segment' => 'enterprise',
            'created_by' => $user->id,
        ]);
        Customer::factory()->create([
            'organization_id' => $organization->id,
            'company' => 'SMB Skip',
            'lifecycle_stage' => 'customer',
            'segment' => 'smb',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('saved-filters.store'), [
                'entity_type' => 'customer',
                'name' => 'Enterprise customers',
                'visibility' => 'private',
                'redirect_route' => 'customers.index',
                'lifecycle_stage' => 'customer',
                'segment' => 'enterprise',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'saved-filter-created');

        $filter = SavedFilter::query()->first();
        $this->assertSame('enterprise', $filter->filter_definition['static_filters']['segment'] ?? null);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('saved-filters.default', $filter))
            ->assertRedirect(route('customers.index', ['saved_filter' => $filter->id]))
            ->assertSessionHas('status', 'saved-filter-default-set');

        $this->assertTrue(app(SavedFilterService::class)->isDefaultFor($user, $organization->id, $filter));

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.index'))
            ->assertRedirect(route('customers.index', ['saved_filter' => $filter->id]));

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.index', ['saved_filter' => $filter->id]))
            ->assertOk()
            ->assertSee('Enterprise Match')
            ->assertDontSee('SMB Skip')
            ->assertSee('Clear filters');
    }

    public function test_private_customer_saved_view_is_tenant_and_owner_scoped(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg();
        [$other] = $this->setupUserWithOrg();
        $organization->addMember($other, 'sales-executive');

        $filter = app(SavedFilterService::class)->create($organization->id, $owner, 'customer', [
            'name' => 'Private customers',
            'visibility' => 'private',
            'filter_definition' => [
                'static_filters' => ['status' => 'active'],
            ],
        ]);

        $this->actingAs($other)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.index', ['saved_filter' => $filter->id]))
            ->assertSessionHasErrors('saved_filter');
    }

    public function test_index_sorts_by_name(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        Customer::factory()->create([
            'organization_id' => $organization->id,
            'company' => 'Zulu Ltd',
            'created_by' => $user->id,
        ]);
        Customer::factory()->create([
            'organization_id' => $organization->id,
            'company' => 'Alpha Ltd',
            'created_by' => $user->id,
        ]);

        $html = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.index', ['sort' => 'name', 'sort_direction' => 'asc']))
            ->assertOk()
            ->getContent();

        $this->assertTrue(strpos($html, 'Alpha Ltd') < strpos($html, 'Zulu Ltd'));
    }
}
