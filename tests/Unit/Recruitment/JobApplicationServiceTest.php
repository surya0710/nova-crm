<?php

namespace Tests\Unit\Recruitment;

use App\Models\Candidate;
use App\Models\Department;
use App\Models\Designation;
use App\Models\JobRequisition;
use App\Models\Organization;
use App\Models\User;
use App\Services\Recruitment\JobApplicationService;
use App\Services\Recruitment\JobOpeningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class JobApplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_requires_published_opening(): void
    {
        [$organization, $user, $opening, $candidate] = $this->publishedScenario(false);

        $this->expectException(ValidationException::class);
        app(JobApplicationService::class)->createApplication([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
        ], $user);
    }

    public function test_application_creation_succeeds_for_published_opening(): void
    {
        [, $user, $opening, $candidate] = $this->publishedScenario(true);

        $application = app(JobApplicationService::class)->createApplication([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
        ], $user);

        $this->assertSame('applied', $application->stage);
        $this->assertSame('active', $application->status);
    }

    /**
     * @return array{0: Organization, 1: User, 2: \App\Models\JobOpening, 3: Candidate}
     */
    private function publishedScenario(bool $publish): array
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
            'title' => 'Support Engineer',
        ], $user);

        if ($publish) {
            app(JobOpeningService::class)->publishOpening($opening, $user);
            $opening = $opening->fresh();
        }

        $candidate = Candidate::factory()->create(['organization_id' => $organization->id]);

        return [$organization, $user, $opening, $candidate];
    }
}
