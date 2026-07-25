<?php

namespace Tests\Unit\Recruitment;

use App\Models\Candidate;
use App\Models\Department;
use App\Models\Designation;
use App\Models\HiringDecision;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\Organization;
use App\Models\User;
use App\Services\Recruitment\BackgroundVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BackgroundVerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_reject_when_hiring_decision_recommendation_is_not_hire(): void
    {
        Notification::fake();

        [$organization, $user, $decision] = $this->decisionScenario(recommendation: 'reject');

        $this->expectException(ValidationException::class);
        app(BackgroundVerificationService::class)->submit($decision, $user);
    }

    public function test_submit_when_recommendation_is_hire(): void
    {
        Notification::fake();

        [$organization, $user, $decision] = $this->decisionScenario(recommendation: 'hire');

        $verification = app(BackgroundVerificationService::class)->submit($decision, $user);

        $this->assertSame('pending', $verification->status);
        $this->assertNotNull($verification->external_verification_id);
        $this->assertStringStartsWith('bgv_', $verification->external_verification_id);
        $this->assertSame($decision->id, $verification->hiring_decision_id);
        $this->assertDatabaseHas('recruitment_background_verifications', [
            'id' => $verification->id,
            'organization_id' => $organization->id,
            'hiring_decision_id' => $decision->id,
            'status' => 'pending',
        ]);
    }

    /**
     * @return array{0: Organization, 1: User, 2: HiringDecision}
     */
    private function decisionScenario(string $recommendation): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

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
        ]);
        $candidate = Candidate::factory()->create(['organization_id' => $organization->id]);
        $application = JobApplication::factory()->create([
            'organization_id' => $organization->id,
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
        ]);

        $decision = HiringDecision::factory()->create([
            'organization_id' => $organization->id,
            'job_application_id' => $application->id,
            'recommendation' => $recommendation,
            'decision_by' => $user->id,
        ]);

        return [$organization, $user, $decision];
    }
}
