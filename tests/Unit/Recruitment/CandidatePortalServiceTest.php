<?php

namespace Tests\Unit\Recruitment;

use App\Models\Candidate;
use App\Models\CandidateAccount;
use App\Models\CandidatePortalSetting;
use App\Models\Department;
use App\Models\Designation;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\Organization;
use App\Models\User;
use App\Services\Recruitment\JobAlertService;
use App\Services\Recruitment\JobOpeningService;
use App\Services\Recruitment\JobRequisitionService;
use App\Services\Recruitment\PublicApplicationService;
use App\Services\Recruitment\SavedJobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CandidatePortalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_closed_opening_rejects_public_application(): void
    {
        [$organization, , , , $opening] = $this->portalScenario('closed');
        CandidatePortalSetting::factory()->create(['organization_id' => $organization->id]);

        $this->expectException(ValidationException::class);
        app(PublicApplicationService::class)->applyAsGuest($organization, $opening, [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);
    }

    public function test_job_alert_requires_at_least_one_criterion(): void
    {
        $organization = Organization::factory()->create();
        $candidate = Candidate::factory()->create(['organization_id' => $organization->id]);
        $account = CandidateAccount::factory()->create([
            'organization_id' => $organization->id,
            'candidate_id' => $candidate->id,
            'email' => 'alert@example.com',
        ]);

        $this->expectException(ValidationException::class);
        app(JobAlertService::class)->subscribe($account, []);
    }

    public function test_saved_job_is_unique_per_account_and_opening(): void
    {
        [$organization, , , , $opening] = $this->portalScenario('published');
        $candidate = Candidate::factory()->create(['organization_id' => $organization->id]);
        $account = CandidateAccount::factory()->create([
            'organization_id' => $organization->id,
            'candidate_id' => $candidate->id,
        ]);

        app(SavedJobService::class)->save($account, $opening);
        app(SavedJobService::class)->save($account, $opening);

        $this->assertTrue(app(SavedJobService::class)->isSaved($account, $opening));
    }

    public function test_withdrawn_application_stage_is_terminal_for_candidates(): void
    {
        [$organization, , , , $opening] = $this->portalScenario('published');
        $candidate = Candidate::factory()->create(['organization_id' => $organization->id]);
        $application = JobApplication::query()->create([
            'organization_id' => $organization->id,
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'stage' => 'withdrawn',
            'status' => 'closed',
            'applied_date' => now()->toDateString(),
        ]);

        $this->assertFalse($application->canCandidateEdit());
    }

    /**
     * @return array{0: Organization, 1: User, 2: Department, 3: Designation, 4: JobOpening}
     */
    private function portalScenario(string $status = 'published'): array
    {
        $organization = Organization::factory()->create();
        $hr = User::factory()->create();
        $organization->addMember($hr, 'hr');
        $department = Department::factory()->create(['organization_id' => $organization->id]);
        $designation = Designation::factory()->create(['organization_id' => $organization->id]);

        $requisition = app(JobRequisitionService::class)->createRequisition([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'employment_type' => 'full_time',
            'number_of_positions' => 1,
            'business_justification' => 'Unit test opening.',
        ], $hr);
        app(JobRequisitionService::class)->submitForApproval($requisition, $hr);
        app(JobRequisitionService::class)->approveRequisition($requisition->fresh(), $hr);

        $opening = app(JobOpeningService::class)->createOpeningFromRequisition($requisition->fresh(), [
            'title' => 'Engineer',
        ], $hr);

        if ($status === 'published') {
            app(JobOpeningService::class)->publishOpening($opening, $hr);
        } else {
            $opening->update(['status' => $status]);
        }

        return [$organization, $hr, $department, $designation, $opening->fresh()];
    }
}
