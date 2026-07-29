<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanningReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'manager'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_planning_reports_index_renders(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('projects.planning.reports.index', [
                'report_type' => 'resource_allocation',
            ]))
            ->assertOk()
            ->assertSee('Resource Allocation');
    }

    public function test_planning_report_csv_export(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('projects.planning.reports.export', [
                'report_type' => 'workload',
                'format' => 'csv',
            ]))
            ->assertOk();
    }
}
