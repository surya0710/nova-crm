<?php

namespace Tests\Unit\Recruitment;

use App\Models\Department;
use App\Models\Designation;
use App\Models\JobRequisition;
use App\Models\Organization;
use App\Models\User;
use App\Services\Recruitment\JobOpeningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class JobOpeningServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_requires_approved_requisition(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $department = Department::factory()->create(['organization_id' => $organization->id]);
        $designation = Designation::factory()->create(['organization_id' => $organization->id]);

        $requisition = JobRequisition::factory()->create([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'status' => 'draft',
        ]);

        $this->expectException(ValidationException::class);
        app(JobOpeningService::class)->createOpeningFromRequisition($requisition, [
            'title' => 'Blocked Opening',
        ], $user);
    }

    public function test_publish_transition_from_draft(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $department = Department::factory()->create(['organization_id' => $organization->id]);
        $designation = Designation::factory()->create(['organization_id' => $organization->id]);

        $requisition = JobRequisition::factory()->approved()->create([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
        ]);

        $opening = app(JobOpeningService::class)->createOpeningFromRequisition($requisition, [
            'title' => 'Published Role',
        ], $user);

        app(JobOpeningService::class)->publishOpening($opening, $user);
        $this->assertSame('published', $opening->fresh()->status);
    }
}
