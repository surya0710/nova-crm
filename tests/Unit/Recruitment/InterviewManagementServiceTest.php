<?php

namespace Tests\Unit\Recruitment;

use App\Models\Candidate;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationSection;
use App\Models\EvaluationTemplate;
use App\Models\InterviewParticipant;
use App\Models\InterviewRound;
use App\Models\InterviewStage;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\Organization;
use App\Models\User;
use App\Services\Recruitment\CandidateEvaluationService;
use App\Services\Recruitment\EvaluationTemplateService;
use App\Services\Recruitment\InterviewRoundService;
use App\Services\Recruitment\InterviewStageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InterviewManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_stage_progression_enforces_sequence(): void
    {
        [$organization, $hr, $stages, $application] = $this->interviewScenario(withApplication: true);

        InterviewRound::factory()->create([
            'organization_id' => $organization->id,
            'job_application_id' => $application->id,
            'interview_stage_id' => $stages['screening']->id,
            'status' => 'completed',
        ]);

        $this->expectException(ValidationException::class);

        app(InterviewRoundService::class)->createRound([
            'job_application_id' => $application->id,
            'interview_stage_id' => $stages['applied']->id,
            'interview_type' => 'phone',
            'status' => 'draft',
        ], $hr);
    }

    public function test_rejected_application_cannot_receive_interview_round(): void
    {
        [$organization, $hr, $stages, $application] = $this->interviewScenario(withApplication: true);
        $application->update(['stage' => 'rejected']);

        $this->expectException(ValidationException::class);

        app(InterviewRoundService::class)->createRound([
            'job_application_id' => $application->id,
            'interview_stage_id' => $stages['screening']->id,
            'interview_type' => 'phone',
            'status' => 'draft',
        ], $hr);
    }

    public function test_evaluation_template_cannot_be_deleted_while_in_use(): void
    {
        [$organization, $hr, $stages, $application, $template] = $this->interviewScenario(withApplication: true, withTemplate: true);

        InterviewRound::factory()->create([
            'organization_id' => $organization->id,
            'job_application_id' => $application->id,
            'interview_stage_id' => $stages['screening']->id,
            'evaluation_template_id' => $template->id,
        ]);

        $this->expectException(ValidationException::class);
        app(EvaluationTemplateService::class)->deleteTemplate($template, $hr);
    }

    public function test_one_evaluation_per_interviewer_per_round(): void
    {
        [$organization, $hr, $stages, $application, $template, $employee] = $this->interviewScenario(
            withApplication: true,
            withTemplate: true,
            withEmployee: true,
        );

        $round = InterviewRound::factory()->create([
            'organization_id' => $organization->id,
            'job_application_id' => $application->id,
            'interview_stage_id' => $stages['technical_interview']->id,
            'evaluation_template_id' => $template->id,
        ]);

        $participant = InterviewParticipant::query()->create([
            'organization_id' => $organization->id,
            'interview_round_id' => $round->id,
            'participant_type' => 'internal',
            'employee_id' => $employee->id,
            'role' => 'lead_interviewer',
            'created_by' => $hr->id,
            'updated_by' => $hr->id,
        ]);

        $question = EvaluationQuestion::query()->where('organization_id', $organization->id)->firstOrFail();

        app(CandidateEvaluationService::class)->submitEvaluation([
            'interview_round_id' => $round->id,
            'interview_participant_id' => $participant->id,
            'evaluation_template_id' => $template->id,
            'overall_rating' => 4,
            'recommendation' => 'hire',
            'responses' => [$question->id => '4'],
        ], $hr);

        $this->expectException(ValidationException::class);

        app(CandidateEvaluationService::class)->submitEvaluation([
            'interview_round_id' => $round->id,
            'interview_participant_id' => $participant->id,
            'evaluation_template_id' => $template->id,
            'overall_rating' => 5,
            'recommendation' => 'strong_hire',
            'responses' => [$question->id => '5'],
        ], $hr);
    }

    public function test_interview_cannot_complete_without_evaluations(): void
    {
        [$organization, $hr, $stages, $application, $employee] = $this->interviewScenario(
            withApplication: true,
            withEmployee: true,
        );

        $round = InterviewRound::factory()->create([
            'organization_id' => $organization->id,
            'job_application_id' => $application->id,
            'interview_stage_id' => $stages['technical_interview']->id,
        ]);

        InterviewParticipant::query()->create([
            'organization_id' => $organization->id,
            'interview_round_id' => $round->id,
            'participant_type' => 'internal',
            'employee_id' => $employee->id,
            'role' => 'lead_interviewer',
            'created_by' => $hr->id,
            'updated_by' => $hr->id,
        ]);

        $this->expectException(ValidationException::class);
        app(InterviewRoundService::class)->completeRound($round, $hr);
    }

    /**
     * @return array{0: Organization, 1: User, 2: array<string, InterviewStage>, 3?: JobApplication, 4?: EvaluationTemplate, 5?: Employee}
     */
    private function interviewScenario(
        bool $withApplication = false,
        bool $withTemplate = false,
        bool $withEmployee = false,
    ): array {
        $organization = Organization::factory()->create();
        $hr = User::factory()->create();
        $organization->addMember($hr, 'hr');

        app(InterviewStageService::class)->ensureDefaultStages($organization, $hr);

        $stages = InterviewStage::query()
            ->where('organization_id', $organization->id)
            ->get()
            ->keyBy('slug');

        $result = [$organization, $hr, $stages];

        if ($withApplication) {
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
            $result[] = JobApplication::factory()->create([
                'organization_id' => $organization->id,
                'candidate_id' => $candidate->id,
                'job_opening_id' => $opening->id,
            ]);
        }

        if ($withTemplate) {
            $template = EvaluationTemplate::factory()->create([
                'organization_id' => $organization->id,
                'created_by' => $hr->id,
                'updated_by' => $hr->id,
            ]);
            $section = EvaluationSection::query()->create([
                'organization_id' => $organization->id,
                'evaluation_template_id' => $template->id,
                'title' => 'Skills',
                'weight' => 1,
                'sort_order' => 0,
                'created_by' => $hr->id,
                'updated_by' => $hr->id,
            ]);
            EvaluationQuestion::query()->create([
                'organization_id' => $organization->id,
                'evaluation_section_id' => $section->id,
                'question' => 'Technical skills',
                'question_type' => 'rating_1_5',
                'is_required' => true,
                'weight' => 1,
                'sort_order' => 0,
                'created_by' => $hr->id,
                'updated_by' => $hr->id,
            ]);
            $result[] = $template;
        }

        if ($withEmployee) {
            $department = Department::factory()->create(['organization_id' => $organization->id]);
            $designation = Designation::factory()->create(['organization_id' => $organization->id]);
            $result[] = Employee::factory()->create([
                'organization_id' => $organization->id,
                'department_id' => $department->id,
                'designation_id' => $designation->id,
            ]);
        }

        return $result;
    }
}
