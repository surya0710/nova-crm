<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;
use App\Services\WorkloadService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourcePerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_calculate_team_for_small_set_returns_structure(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        Employee::factory()->count(3)->create(['organization_id' => $organization->id]);

        $results = app(WorkloadService::class)->calculateTeam(
            $organization,
            Carbon::parse('2026-07-20'),
            Carbon::parse('2026-07-24'),
        );

        $this->assertNotEmpty($results);
        $this->assertCount(3, $results);

        foreach ($results as $row) {
            $this->assertArrayHasKey('employee_id', $row);
            $this->assertArrayHasKey('capacity', $row);
            $this->assertArrayHasKey('allocated', $row);
            $this->assertArrayHasKey('available', $row);
            $this->assertArrayHasKey('utilization', $row);
            $this->assertArrayHasKey('status', $row);
            $this->assertArrayHasKey('days', $row);
        }
    }
}
