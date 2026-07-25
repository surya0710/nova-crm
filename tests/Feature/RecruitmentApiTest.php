<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Department;
use App\Models\Designation;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RecruitmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sanctum_token_and_organization_header_can_get_jobs(): void
    {
        [$user, $organization, $opening] = $this->apiScenario('organization-owner');

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/recruitment/jobs', $this->apiHeaders($organization));

        $response->assertOk();
        $response->assertJsonFragment(['id' => $opening->id, 'title' => $opening->title]);
    }

    public function test_without_permission_returns_403(): void
    {
        [$user, $organization] = $this->apiScenario('employee');

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/recruitment/jobs', $this->apiHeaders($organization))
            ->assertForbidden();
    }

    public function test_tenant_isolation_on_candidates(): void
    {
        [$userA, $organizationA] = $this->apiScenario('organization-owner');
        $organizationB = Organization::factory()->create();
        $foreignCandidate = Candidate::factory()->create([
            'organization_id' => $organizationB->id,
            'first_name' => 'Foreign',
            'last_name' => 'Candidate',
            'email' => 'foreign-candidate@example.com',
        ]);
        Candidate::factory()->create([
            'organization_id' => $organizationA->id,
            'first_name' => 'Local',
            'last_name' => 'Candidate',
            'email' => 'local-candidate@example.com',
        ]);

        Sanctum::actingAs($userA, ['*']);

        $list = $this->getJson('/api/v1/recruitment/candidates', $this->apiHeaders($organizationA));
        $list->assertOk();
        $list->assertJsonFragment(['email' => 'local-candidate@example.com']);
        $list->assertJsonMissing(['email' => 'foreign-candidate@example.com']);

        $this->getJson(
            '/api/v1/recruitment/candidates/'.$foreignCandidate->id,
            $this->apiHeaders($organizationA)
        )->assertNotFound();
    }

    /**
     * @return array{0: User, 1: Organization, 2?: JobOpening}
     */
    private function apiScenario(string $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        $department = Department::factory()->create(['organization_id' => $organization->id]);
        $designation = Designation::factory()->create(['organization_id' => $organization->id]);
        $requisition = JobRequisition::factory()->approved()->create([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
        ]);
        $opening = JobOpening::factory()->published()->create([
            'organization_id' => $organization->id,
            'job_requisition_id' => $requisition->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'title' => 'API Test Engineer',
        ]);

        return [$user, $organization, $opening];
    }

    /**
     * @return array<string, string>
     */
    private function apiHeaders(Organization $organization): array
    {
        return [
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ];
    }
}
