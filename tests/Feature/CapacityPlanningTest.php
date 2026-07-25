<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\CapacityPlanningService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CapacityPlanningTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_forecast_returns_structure_and_risks_method(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        Employee::factory()->count(2)->create(['organization_id' => $organization->id]);

        $service = app(CapacityPlanningService::class);
        $from = Carbon::parse('2026-07-20');
        $to = Carbon::parse('2026-08-03');

        $forecast = $service->forecast($organization, $from, $to);

        $this->assertSame($organization->id, $forecast['organization_id']);
        $this->assertSame('2026-07-20', $forecast['from']);
        $this->assertSame('2026-08-03', $forecast['to']);
        $this->assertArrayHasKey('employees', $forecast);
        $this->assertArrayHasKey('summary', $forecast);
        $this->assertArrayHasKey('employee_count', $forecast['summary']);
        $this->assertArrayHasKey('total_available_hours', $forecast['summary']);
        $this->assertArrayHasKey('total_allocated_hours', $forecast['summary']);
        $this->assertArrayHasKey('total_forecast_load_hours', $forecast['summary']);
        $this->assertCount(2, $forecast['employees']);

        $employeeForecast = $forecast['employees'][0];
        $this->assertArrayHasKey('utilization', $employeeForecast);
        $this->assertArrayHasKey('status', $employeeForecast);
        $this->assertArrayHasKey('available_hours', $employeeForecast);
        $this->assertArrayHasKey('allocated_hours', $employeeForecast);

        $risks = $service->upcomingRisks($organization);
        $this->assertIsArray($risks);
    }
}
