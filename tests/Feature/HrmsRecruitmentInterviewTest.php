<?php

namespace Tests\Feature;

use App\Events\EvaluationSubmitted;
use App\Events\InterviewScheduled;
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
use App\Notifications\CrmNotification;
use App\Services\Recruitment\CandidateEvaluationService;
use App\Services\Recruitment\InterviewRoundService;
use App\Services\Recruitment\InterviewStageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HrmsRecruitmentInterviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_interview_stages_index_seeds_defaults(): void
    {
        [$organization, $hr] = $this->interviewFeatureScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)
            ->get(route('hrms.recruitment.interview-stages.index'))
            ->assertOk()
            ->assertSee('Applied')
            ->assertSee('Technical Interview');

        $this->assertDatabaseHas('interview_stages', [
            'organization_id' => $organization->id,
            'slug' => 'applied',
        ]);
    }

    public function test_interview_scheduling_emits_event_and_notification(): void
    {
        Event::fake([InterviewScheduled::class]);
        Notification::fake();

        [$organization, $hr, $application, $stage, $employee] = $this->fullInterviewScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.recruitment.interview-rounds.store'), [
            'job_application_id' => $application->id,
            'interview_stage_id' => $stage->id,
            'interview_type' => 'video',
            'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'duration_minutes' => 60,
            'location' => 'Room 1',
            'status' => 'scheduled',
            'participants' => [
                ['participant_type' => 'internal', 'employee_id' => $employee->id, 'role' => 'lead_interviewer'],
            ],
        ])->assertRedirect();

        Event::assertDispatched(InterviewScheduled::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'interview_round_created']);
    }

    public function test_evaluation_submission_emits_event(): void
    {
        Event::fake([EvaluationSubmitted::class]);

        [$organization, $hr, $application, $stage, $employee, $template, $question] = $this->fullInterviewScenario(withTemplate: true);

        $round = InterviewRound::factory()->create([
            'organization_id' => $organization->id,
            'job_application_id' => $application->id,
            'interview_stage_id' => $stage->id,
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

        app(CandidateEvaluationService::class)->submitEvaluation([
            'interview_round_id' => $round->id,
            'interview_participant_id' => $participant->id,
            'evaluation_template_id' => $template->id,
            'overall_rating' => 4.5,
            'recommendation' => 'hire',
            'strengths' => 'Strong communicator',
            'responses' => [$question->id => '4'],
        ], $hr);

        Event::assertDispatched(EvaluationSubmitted::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'candidate_evaluation_submitted']);
    }

    public function test_tenant_isolation_for_interview_rounds(): void
    {
        [$organization, $hr] = $this->interviewFeatureScenario();
        $otherOrg = Organization::factory()->create();
        app(InterviewStageService::class)->ensureDefaultStages($otherOrg);

        $otherDepartment = Department::factory()->create(['organization_id' => $otherOrg->id]);
        $otherDesignation = Designation::factory()->create(['organization_id' => $otherOrg->id]);
        $otherRequisition = JobRequisition::factory()->approved()->create([
            'organization_id' => $otherOrg->id,
            'department_id' => $otherDepartment->id,
            'designation_id' => $otherDesignation->id,
        ]);
        $otherOpening = JobOpening::factory()->published()->create([
            'organization_id' => $otherOrg->id,
            'job_requisition_id' => $otherRequisition->id,
            'department_id' => $otherDepartment->id,
            'designation_id' => $otherDesignation->id,
        ]);
        $otherCandidate = Candidate::factory()->create(['organization_id' => $otherOrg->id]);
        $otherApplication = JobApplication::factory()->create([
            'organization_id' => $otherOrg->id,
            'candidate_id' => $otherCandidate->id,
            'job_opening_id' => $otherOpening->id,
        ]);
        $otherStage = InterviewStage::query()->where('organization_id', $otherOrg->id)->firstOrFail();

        $otherRound = InterviewRound::factory()->create([
            'organization_id' => $otherOrg->id,
            'job_application_id' => $otherApplication->id,
            'interview_stage_id' => $otherStage->id,
        ]);

        $this->actingAs($hr)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.recruitment.interview-rounds.show', $otherRound))
            ->assertForbidden();
    }

    public function test_unauthorized_user_cannot_access_interviews(): void
    {
        [$organization] = $this->interviewFeatureScenario();
        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');

        $this->actingAs($employee)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.recruitment.interview-rounds.index'))
            ->assertForbidden();
    }

    public function test_candidate_timeline_shows_interview_history(): void
    {
        [$organization, $hr, $application, $stage] = $this->fullInterviewScenario();
        $candidate = $application->candidate;

        InterviewRound::factory()->create([
            'organization_id' => $organization->id,
            'job_application_id' => $application->id,
            'interview_stage_id' => $stage->id,
            'status' => 'scheduled',
        ]);

        $this->actingAs($hr)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.recruitment.candidates.show', $candidate))
            ->assertOk()
            ->assertSee('Interview History')
            ->assertSee($stage->name);
    }

    /**
     * @return array{0: Organization, 1: User}
     */
    private function interviewFeatureScenario(): array
    {
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $hr = User::factory()->create();
        $organization->addMember($hr, 'hr');

        return [$organization, $hr];
    }

    /**
     * @return array{0: Organization, 1: User, 2: JobApplication, 3: InterviewStage, 4: Employee, 5?: EvaluationTemplate, 6?: EvaluationQuestion}
     */
    private function fullInterviewScenario(bool $withTemplate = false): array
    {
        [$organization, $hr] = $this->interviewFeatureScenario();
        app(InterviewStageService::class)->ensureDefaultStages($organization, $hr);

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
        $stage = InterviewStage::query()->where('organization_id', $organization->id)->where('slug', 'screening')->firstOrFail();
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
        ]);

        $result = [$organization, $hr, $application, $stage, $employee];

        if ($withTemplate) {
            $template = EvaluationTemplate::factory()->create([
                'organization_id' => $organization->id,
                'created_by' => $hr->id,
                'updated_by' => $hr->id,
            ]);
            $section = EvaluationSection::query()->create([
                'organization_id' => $organization->id,
                'evaluation_template_id' => $template->id,
                'title' => 'Core',
                'weight' => 1,
                'sort_order' => 0,
                'created_by' => $hr->id,
                'updated_by' => $hr->id,
            ]);
            $question = EvaluationQuestion::query()->create([
                'organization_id' => $organization->id,
                'evaluation_section_id' => $section->id,
                'question' => 'Problem solving',
                'question_type' => 'rating_1_5',
                'is_required' => true,
                'weight' => 1,
                'sort_order' => 0,
                'created_by' => $hr->id,
                'updated_by' => $hr->id,
            ]);
            $result[] = $template;
            $result[] = $question;
        }

        return $result;
    }
}
