<?php

namespace Tests\Unit\Recruitment;

use App\Events\OfferGenerated;
use App\Models\Candidate;
use App\Models\CandidateEvaluation;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\InterviewParticipant;
use App\Models\InterviewRound;
use App\Models\InterviewStage;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\OfferLetter;
use App\Models\OfferTemplate;
use App\Models\Organization;
use App\Models\User;
use App\Services\Recruitment\HiringDecisionService;
use App\Services\Recruitment\OfferLetterService;
use App\Services\Recruitment\OfferTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OfferManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_offer_template_service_creates_template(): void
    {
        [$organization, $hr, $department] = $this->baseScenario();

        $template = app(OfferTemplateService::class)->createTemplate([
            'organization_id' => $organization->id,
            'name' => 'Engineering Offer',
            'department_id' => $department->id,
            'employment_type' => 'full_time',
            'template_content' => 'Hello {{candidate_name}}',
        ], $hr);

        $this->assertSame('Engineering Offer', $template->name);
        $this->assertDatabaseHas('offer_templates', ['name' => 'Engineering Offer']);
    }

    public function test_offer_letter_service_enforces_recommendation_rule(): void
    {
        [$organization, $hr, , , $application] = $this->baseScenario(withRecommendation: false);

        $this->expectException(ValidationException::class);

        app(OfferLetterService::class)->generateOffer([
            'job_application_id' => $application->id,
            'proposed_salary' => 75000,
            'joining_date' => now()->addWeeks(4)->toDateString(),
            'expiry_date' => now()->addMonths(2)->toDateString(),
        ], $hr);
    }

    public function test_offer_generation_renders_placeholders(): void
    {
        Event::fake([OfferGenerated::class]);

        [$organization, $hr, , , $application, $template] = $this->baseScenario();

        $offer = app(OfferLetterService::class)->generateOffer([
            'job_application_id' => $application->id,
            'offer_template_id' => $template->id,
            'proposed_salary' => 120000,
            'benefits' => 'PTO',
            'joining_date' => now()->addMonth()->toDateString(),
            'expiry_date' => now()->addMonths(2)->toDateString(),
        ], $hr);

        $this->assertStringContainsString($application->candidate->fullName(), $offer->generated_content);
        $this->assertStringContainsString($application->jobOpening->title, $offer->generated_content);
        Event::assertDispatched(OfferGenerated::class);
    }

    public function test_hiring_decision_does_not_create_employee(): void
    {
        [$organization, $hr, , , $application] = $this->baseScenario();

        $employeeCountBefore = \App\Models\Employee::query()->where('organization_id', $organization->id)->count();

        OfferLetter::factory()->create([
            'organization_id' => $organization->id,
            'candidate_id' => $application->candidate_id,
            'job_application_id' => $application->id,
            'status' => 'accepted',
            'joining_date' => now()->addMonth()->toDateString(),
            'expiry_date' => now()->addMonths(2)->toDateString(),
        ]);

        app(HiringDecisionService::class)->recordDecision([
            'job_application_id' => $application->id,
            'recommendation' => 'hire',
        ], $hr);

        $employeeCountAfter = \App\Models\Employee::query()->where('organization_id', $organization->id)->count();
        $this->assertSame($employeeCountBefore, $employeeCountAfter);
    }

    /**
     * @return array{0: Organization, 1: User, 2: Department, 3: Designation, 4: JobApplication, 5: OfferTemplate}
     */
    private function baseScenario(bool $withRecommendation = true): array
    {
        $organization = Organization::factory()->create();
        $hr = User::factory()->create();
        $organization->addMember($hr, 'hr');

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

        $template = OfferTemplate::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $hr->id,
            'updated_by' => $hr->id,
        ]);

        if ($withRecommendation) {
            $stage = InterviewStage::factory()->create(['organization_id' => $organization->id]);
            $round = InterviewRound::factory()->create([
                'organization_id' => $organization->id,
                'job_application_id' => $application->id,
                'interview_stage_id' => $stage->id,
            ]);
            $employee = Employee::factory()->create(['organization_id' => $organization->id]);
            $participant = InterviewParticipant::query()->create([
                'organization_id' => $organization->id,
                'interview_round_id' => $round->id,
                'participant_type' => 'internal',
                'employee_id' => $employee->id,
                'role' => 'lead_interviewer',
                'created_by' => $hr->id,
                'updated_by' => $hr->id,
            ]);
            CandidateEvaluation::query()->create([
                'organization_id' => $organization->id,
                'interview_round_id' => $round->id,
                'interview_participant_id' => $participant->id,
                'recommendation' => 'hire',
                'status' => 'submitted',
                'created_by' => $hr->id,
                'updated_by' => $hr->id,
            ]);
        }

        return [$organization, $hr, $department, $designation, $application, $template];
    }
}
