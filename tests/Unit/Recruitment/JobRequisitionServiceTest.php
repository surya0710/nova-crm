<?php

namespace Tests\Unit\Recruitment;

use App\Models\Department;
use App\Models\Designation;
use App\Models\JobRequisition;
use App\Models\Organization;
use App\Models\User;
use App\Services\Recruitment\JobRequisitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class JobRequisitionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_and_approve_transitions(): void
    {
        [$organization, $user, $requisition] = $this->makeRequisition('draft');

        app(JobRequisitionService::class)->submitForApproval($requisition, $user);
        $this->assertSame('pending_approval', $requisition->fresh()->status);

        app(JobRequisitionService::class)->approveRequisition($requisition->fresh(), $user);
        $this->assertSame('approved', $requisition->fresh()->status);
    }

    public function test_cannot_approve_draft_requisition(): void
    {
        [, $user, $requisition] = $this->makeRequisition('draft');

        $this->expectException(ValidationException::class);
        app(JobRequisitionService::class)->approveRequisition($requisition, $user);
    }

    /**
     * @return array{0: Organization, 1: User, 2: JobRequisition}
     */
    private function makeRequisition(string $status): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $department = Department::factory()->create(['organization_id' => $organization->id]);
        $designation = Designation::factory()->create(['organization_id' => $organization->id]);

        $requisition = JobRequisition::factory()->create([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'status' => $status,
        ]);

        return [$organization, $user, $requisition];
    }
}
