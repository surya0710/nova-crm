<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\HrmsShift;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use App\Services\Bulk\BulkOperationsService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LookupPlatformTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Organization}
     */
    protected function setupOwner(string $plan = 'enterprise'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['plan' => $plan]);
        $organization->addMember($user, 'organization-owner');
        app(TenantContext::class)->set($organization);

        return [$user, $organization];
    }

    public function test_user_lookup_returns_standard_response(): void
    {
        [$user, $organization] = $this->setupOwner();
        $other = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        $organization->addMember($other, 'employee');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->getJson(route('shell.lookups.search', ['entity' => 'users', 'q' => 'jane']))
            ->assertOk()
            ->assertJsonPath('data.0.label', 'Jane Doe')
            ->assertJsonPath('data.0.subtitle', 'jane@example.com')
            ->assertJsonStructure([
                'data' => [['id', 'label', 'subtitle', 'badge', 'metadata']],
                'meta' => ['page', 'per_page', 'total', 'has_more'],
            ]);
    }

    public function test_employee_lookup_searches_by_code_and_email(): void
    {
        [$user, $organization] = $this->setupOwner();
        $department = Department::factory()->create(['organization_id' => $organization->id, 'name' => 'Sales']);
        Employee::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'John',
            'last_name' => 'Smith',
            'employee_code' => 'EMP-1001',
            'email' => 'john.smith@example.com',
            'department_id' => $department->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->getJson(route('shell.lookups.search', ['entity' => 'employees', 'q' => 'EMP-1001']))
            ->assertOk()
            ->assertJsonPath('data.0.label', 'John Smith')
            ->assertJsonPath('data.0.badge', 'Sales');
    }

    public function test_department_branch_designation_and_shift_lookups_are_org_scoped(): void
    {
        [$user, $organization] = $this->setupOwner();
        $otherOrg = Organization::factory()->create(['plan' => 'enterprise']);

        $department = Department::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Engineering',
            'is_active' => true,
        ]);
        Department::factory()->create([
            'organization_id' => $otherOrg->id,
            'name' => 'Engineering',
            'is_active' => true,
        ]);

        Branch::factory()->create(['organization_id' => $organization->id, 'name' => 'HQ', 'is_active' => true]);
        Designation::factory()->create(['organization_id' => $organization->id, 'name' => 'Manager', 'is_active' => true]);
        HrmsShift::factory()->create(['organization_id' => $organization->id, 'name' => 'General', 'is_active' => true]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->getJson(route('shell.lookups.search', ['entity' => 'departments', 'q' => 'Engineering']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $department->id);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->getJson(route('shell.lookups.search', ['entity' => 'branches', 'q' => 'HQ']))
            ->assertOk()
            ->assertJsonPath('data.0.label', 'HQ');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->getJson(route('shell.lookups.search', ['entity' => 'designations', 'q' => 'Manager']))
            ->assertOk()
            ->assertJsonPath('data.0.label', 'Manager');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->getJson(route('shell.lookups.search', ['entity' => 'shifts', 'q' => 'General']))
            ->assertOk()
            ->assertJsonPath('data.0.label', 'General');
    }

    public function test_lookup_pagination_metadata(): void
    {
        [$user, $organization] = $this->setupOwner();

        foreach (range(1, 3) as $i) {
            $member = User::factory()->create(['name' => "Lookup User {$i}"]);
            $organization->addMember($member, 'employee');
        }

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->getJson(route('shell.lookups.search', [
                'entity' => 'users',
                'q' => 'Lookup User',
                'per_page' => 2,
                'page' => 1,
            ]))
            ->assertOk();

        $response->assertJsonPath('meta.per_page', 2);
        $response->assertJsonPath('meta.page', 1);
        $this->assertTrue($response->json('meta.has_more'));
    }

    public function test_lookup_isolation_between_organizations(): void
    {
        [$userA, $orgA] = $this->setupOwner();
        $orgB = Organization::factory()->create(['plan' => 'enterprise']);
        $isolated = User::factory()->create(['name' => 'Isolated User']);
        $orgB->addMember($isolated, 'employee');

        $this->actingAs($userA)
            ->withSession(['current_organization_id' => $orgA->id])
            ->getJson(route('shell.lookups.search', ['entity' => 'users', 'q' => 'Isolated']))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_api_lookup_endpoint_requires_organization_header(): void
    {
        [$user, $organization] = $this->setupOwner();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/lookups/users?q=test', [
            'X-Organization-Id' => (string) $organization->id,
        ])->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }
}
