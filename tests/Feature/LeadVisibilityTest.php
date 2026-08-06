<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use App\Services\Bulk\BulkOperationsService;
use App\Services\Export\Adapters\LeadExportAdapter;
use App\Services\LeadVisibilityService;
use App\Services\ReportService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadVisibilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Organization}
     */
    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_sales_executive_index_only_shows_assigned_leads(): void
    {
        [$exec, $organization] = $this->setupUserWithOrg('sales-executive');
        $other = User::factory()->create();
        $organization->addMember($other, 'sales-executive');

        $mine = Lead::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'My Visible Lead',
            'assigned_to' => $exec->id,
            'created_by' => $exec->id,
        ]);
        Lead::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Other Owner Lead',
            'assigned_to' => $other->id,
            'created_by' => $other->id,
        ]);
        Lead::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Unassigned Lead',
            'assigned_to' => null,
            'created_by' => $other->id,
        ]);

        $response = $this->actingAs($exec)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index'));

        $response->assertOk();
        $response->assertSee('My Visible Lead');
        $response->assertDontSee('Other Owner Lead');
        $response->assertDontSee('Unassigned Lead');
        $response->assertDontSee('name="assigned_to"', false);
        $this->assertTrue($response->viewData('leads')->contains('id', $mine->id));
    }

    public function test_sales_executive_cannot_bypass_with_assigned_to_query_param(): void
    {
        [$exec, $organization] = $this->setupUserWithOrg('sales-executive');
        $other = User::factory()->create();
        $organization->addMember($other, 'sales-executive');

        Lead::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Mine Only',
            'assigned_to' => $exec->id,
            'created_by' => $exec->id,
        ]);
        Lead::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Other Secret Lead',
            'assigned_to' => $other->id,
            'created_by' => $other->id,
        ]);

        $response = $this->actingAs($exec)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', ['assigned_to' => $other->id]));

        $response->assertOk();
        $response->assertSee('Mine Only');
        $response->assertDontSee('Other Secret Lead');
    }

    public function test_sales_executive_cannot_view_another_users_lead(): void
    {
        [$exec, $organization] = $this->setupUserWithOrg('sales-executive');
        $other = User::factory()->create();
        $organization->addMember($other, 'sales-executive');

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'assigned_to' => $other->id,
            'created_by' => $other->id,
        ]);

        $this->actingAs($exec)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.show', $lead))
            ->assertForbidden();
    }

    public function test_manager_with_leads_manage_sees_all_and_can_filter_assignee(): void
    {
        [$manager, $organization] = $this->setupUserWithOrg('manager');
        $exec = User::factory()->create();
        $organization->addMember($exec, 'sales-executive');

        Lead::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Manager Pipeline Lead',
            'assigned_to' => $manager->id,
            'created_by' => $manager->id,
        ]);
        Lead::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Exec Pipeline Lead',
            'assigned_to' => $exec->id,
            'created_by' => $exec->id,
        ]);

        $all = $this->actingAs($manager)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index'));

        $all->assertOk();
        $all->assertSee('Manager Pipeline Lead');
        $all->assertSee('Exec Pipeline Lead');
        $all->assertSee('name="assigned_to"', false);

        $filtered = $this->actingAs($manager)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', ['assigned_to' => $exec->id]));

        $filtered->assertOk();
        $filtered->assertSee('Exec Pipeline Lead');
        $filtered->assertDontSee('Manager Pipeline Lead');
    }

    public function test_dashboard_lead_stats_are_personal_for_sales_executive(): void
    {
        [$exec, $organization] = $this->setupUserWithOrg('sales-executive');
        $other = User::factory()->create();
        $organization->addMember($other, 'sales-executive');
        $manager = User::factory()->create();
        $organization->addMember($manager, 'manager');

        Lead::factory()->count(2)->create([
            'organization_id' => $organization->id,
            'status' => 'new',
            'assigned_to' => $exec->id,
            'created_by' => $exec->id,
        ]);
        Lead::factory()->count(5)->create([
            'organization_id' => $organization->id,
            'status' => 'new',
            'assigned_to' => $other->id,
            'created_by' => $other->id,
        ]);

        $visibility = app(LeadVisibilityService::class);
        app(TenantContext::class)->set($organization);

        $this->assertFalse($visibility->canViewAll($exec, $organization));
        $this->assertTrue($visibility->canViewAll($manager, $organization));
        $this->assertSame(2, $visibility->visibleQuery($exec, $organization)->count());
        $this->assertSame(7, $visibility->visibleQuery($manager, $organization)->count());
    }

    public function test_reports_lead_totals_respect_visibility(): void
    {
        [$exec, $organization] = $this->setupUserWithOrg('sales-executive');
        $other = User::factory()->create();
        $organization->addMember($other, 'organization-owner');

        Lead::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'new',
            'assigned_to' => $exec->id,
            'created_by' => $exec->id,
        ]);
        Lead::factory()->count(3)->create([
            'organization_id' => $organization->id,
            'status' => 'new',
            'assigned_to' => $other->id,
            'created_by' => $other->id,
        ]);

        app(TenantContext::class)->set($organization);
        $reports = app(ReportService::class);

        $execData = $reports->compile($organization, null, 'state', $exec);
        $this->assertSame(1, $execData['lead_total']);

        $ownerData = $reports->compile($organization, null, 'state', $other);
        $this->assertSame(4, $ownerData['lead_total']);
    }

    public function test_bulk_filtered_actions_only_affect_visible_leads(): void
    {
        [$exec, $organization] = $this->setupUserWithOrg('sales-executive');
        $other = User::factory()->create();
        $organization->addMember($other, 'sales-executive');
        app(TenantContext::class)->set($organization);

        $mine = Lead::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'new',
            'assigned_to' => $exec->id,
            'created_by' => $exec->id,
        ]);
        $theirs = Lead::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'new',
            'assigned_to' => $other->id,
            'created_by' => $other->id,
        ]);

        $operation = app(BulkOperationsService::class)->start(
            $organization,
            $exec,
            'lead.change_status',
            ['mode' => 'ids', 'ids' => [$mine->id, $theirs->id]],
            ['status' => 'contacted'],
            true,
        );

        $this->assertSame(1, $operation->success_count);
        $this->assertSame('contacted', $mine->fresh()->status);
        $this->assertSame('new', $theirs->fresh()->status);
    }

    public function test_export_query_respects_visibility_for_sales_executive(): void
    {
        [$exec, $organization] = $this->setupUserWithOrg('sales-executive');
        $other = User::factory()->create();
        $organization->addMember($other, 'sales-executive');
        app(TenantContext::class)->set($organization);
        $this->actingAs($exec);

        Lead::factory()->create([
            'organization_id' => $organization->id,
            'assigned_to' => $exec->id,
            'created_by' => $exec->id,
        ]);
        Lead::factory()->create([
            'organization_id' => $organization->id,
            'assigned_to' => $other->id,
            'created_by' => $other->id,
        ]);

        $adapter = app(LeadExportAdapter::class);
        $count = $adapter->resolveQuery($organization, [
            'mode' => 'all',
            'filters' => [],
        ])->count();

        $this->assertSame(1, $count);
    }
}
