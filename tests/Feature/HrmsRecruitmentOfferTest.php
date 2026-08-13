<?php

namespace Tests\Feature;

use App\Events\HiringApproved;
use App\Events\OfferAccepted;
use App\Events\OfferApproved;
use App\Events\OfferGenerated;
use App\Events\OfferSent;
use App\Models\Candidate;
use App\Models\CandidateEvaluation;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationSection;
use App\Models\EvaluationTemplate;
use App\Models\HiringDecision;
use App\Models\InterviewParticipant;
use App\Models\InterviewRound;
use App\Models\InterviewStage;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\OfferApproval;
use App\Models\OfferLetter;
use App\Models\OfferTemplate;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Services\Recruitment\HiringDecisionService;
use App\Services\Recruitment\OfferApprovalService;
use App\Services\Recruitment\OfferLetterService;
use App\Services\Recruitment\OfferNegotiationService;
use App\Services\Recruitment\OfferTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HrmsRecruitmentOfferTest extends TestCase
{
    use RefreshDatabase;

    public function test_offer_management_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('offer_templates'));
        $this->assertTrue(Schema::hasTable('offer_letters'));
        $this->assertTrue(Schema::hasTable('offer_approvals'));
        $this->assertTrue(Schema::hasTable('offer_negotiations'));
        $this->assertTrue(Schema::hasTable('hiring_decisions'));
    }

    public function test_offer_template_crud_and_audit(): void
    {
        [$organization, $hr, $department] = $this->offerScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.recruitment.offer-templates.store'), [
            'name' => 'Standard Offer',
            'department_id' => $department->id,
            'employment_type' => 'full_time',
            'template_content' => 'Dear {{candidate_name}}, welcome to {{position}}.',
        ])->assertRedirect();

        $template = OfferTemplate::query()->where('name', 'Standard Offer')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['event' => 'offer_template_created']);

        $this->actingAs($hr)->withSession($session)
            ->get(route('hrms.recruitment.offer-templates.show', $template))
            ->assertOk()
            ->assertSee('Standard Offer');
    }

    public function test_offer_generation_requires_recommended_candidate(): void
    {
        [$organization, $hr, , , $application] = $this->offerScenario(withRecommendation: false);

        $this->expectException(ValidationException::class);

        app(OfferLetterService::class)->generateOffer([
            'job_application_id' => $application->id,
            'proposed_salary' => 80000,
            'joining_date' => now()->addMonth()->toDateString(),
            'expiry_date' => now()->addMonths(2)->toDateString(),
        ], $hr);
    }

    public function test_offer_generation_emits_event_and_notification(): void
    {
        Event::fake([OfferGenerated::class]);
        Notification::fake();

        [$organization, $hr, , , $application, $template] = $this->offerScenario();

        $offer = app(OfferLetterService::class)->generateOffer([
            'job_application_id' => $application->id,
            'offer_template_id' => $template->id,
            'proposed_salary' => 95000,
            'joining_date' => now()->addMonth()->toDateString(),
            'expiry_date' => now()->addMonths(2)->toDateString(),
            'benefits' => 'Health insurance',
        ], $hr);

        $this->assertSame('draft', $offer->status);
        $this->assertStringContainsString($application->candidate->fullName(), $offer->generated_content);
        $this->assertDatabaseHas('audit_logs', ['event' => 'offer_letter_generated']);
        Event::assertDispatched(OfferGenerated::class);
        Notification::assertSentTo($hr, CrmNotification::class);
    }

    public function test_one_active_offer_per_application(): void
    {
        [$organization, $hr, , , $application, $template] = $this->offerScenario();

        app(OfferLetterService::class)->generateOffer([
            'job_application_id' => $application->id,
            'offer_template_id' => $template->id,
            'proposed_salary' => 90000,
            'joining_date' => now()->addMonth()->toDateString(),
            'expiry_date' => now()->addMonths(2)->toDateString(),
        ], $hr);

        $this->expectException(ValidationException::class);

        app(OfferLetterService::class)->generateOffer([
            'job_application_id' => $application->id,
            'proposed_salary' => 91000,
            'joining_date' => now()->addMonth()->toDateString(),
            'expiry_date' => now()->addMonths(2)->toDateString(),
        ], $hr);
    }

    public function test_approval_workflow_and_send_rules(): void
    {
        Event::fake([OfferApproved::class, OfferSent::class]);

        [$organization, $hr, , , $application, $template] = $this->offerScenario();
        $approver = User::factory()->create();
        $organization->addMember($approver, 'hr');

        $offer = app(OfferLetterService::class)->generateOffer([
            'job_application_id' => $application->id,
            'offer_template_id' => $template->id,
            'proposed_salary' => 88000,
            'joining_date' => now()->addMonth()->toDateString(),
            'expiry_date' => now()->addMonths(2)->toDateString(),
        ], $hr);

        $this->expectException(ValidationException::class);
        app(OfferLetterService::class)->sendOffer($offer, $hr);

        $offer = app(OfferLetterService::class)->submitForApproval($offer->fresh(), [$approver->id], $hr);
        $this->assertSame('pending_approval', $offer->status);

        $approval = OfferApproval::query()->where('offer_letter_id', $offer->id)->firstOrFail();
        app(OfferApprovalService::class)->approve($approval, $approver);

        $this->assertSame('approved', $offer->fresh()->status);
        Event::assertDispatched(OfferApproved::class);

        app(OfferLetterService::class)->sendOffer($offer->fresh(), $hr);
        $this->assertSame('sent', $offer->fresh()->status);
        Event::assertDispatched(OfferSent::class);
    }

    public function test_expired_offer_cannot_be_accepted(): void
    {
        [$organization, $hr, , , $application, $template] = $this->offerScenario();

        $offer = OfferLetter::factory()->create([
            'organization_id' => $organization->id,
            'candidate_id' => $application->candidate_id,
            'job_application_id' => $application->id,
            'offer_template_id' => $template->id,
            'status' => 'sent',
            'expiry_date' => now()->subDay()->toDateString(),
            'joining_date' => now()->addMonth()->toDateString(),
        ]);

        $this->expectException(ValidationException::class);
        app(OfferLetterService::class)->acceptOffer($offer, $hr);
    }

    public function test_accepted_offer_locks_negotiations(): void
    {
        [$organization, $hr, , , $application, $template] = $this->offerScenario();

        $offer = OfferLetter::factory()->create([
            'organization_id' => $organization->id,
            'candidate_id' => $application->candidate_id,
            'job_application_id' => $application->id,
            'offer_template_id' => $template->id,
            'status' => 'accepted',
            'expiry_date' => now()->addMonth()->toDateString(),
            'joining_date' => now()->addMonth()->toDateString(),
        ]);

        $this->expectException(ValidationException::class);

        app(OfferNegotiationService::class)->recordNegotiation($offer, [
            'requested_salary' => 100000,
        ], $hr);
    }

    public function test_hiring_decision_generates_onboarding_recommendation_without_employee(): void
    {
        Event::fake([HiringApproved::class]);

        [$organization, $hr, , , $application] = $this->offerScenario();

        $employeeCountBefore = Employee::query()->where('organization_id', $organization->id)->count();

        OfferLetter::factory()->create([
            'organization_id' => $organization->id,
            'candidate_id' => $application->candidate_id,
            'job_application_id' => $application->id,
            'status' => 'accepted',
            'expiry_date' => now()->addMonth()->toDateString(),
            'joining_date' => now()->addMonth()->toDateString(),
        ]);

        $decision = app(HiringDecisionService::class)->recordDecision([
            'job_application_id' => $application->id,
            'recommendation' => 'hire',
            'final_notes' => 'Proceed to HR onboarding.',
        ], $hr);

        $this->assertTrue($decision->onboarding_recommended);
        $this->assertNotNull($decision->onboarding_recommended_at);
        $this->assertSame($employeeCountBefore, Employee::query()->where('organization_id', $organization->id)->count());
        $this->assertDatabaseHas('audit_logs', ['event' => 'hiring_decision_recorded']);
        Event::assertDispatched(HiringApproved::class);
    }

    public function test_hire_decision_requires_accepted_offer(): void
    {
        [$organization, $hr, , , $application] = $this->offerScenario();

        $this->expectException(ValidationException::class);

        app(HiringDecisionService::class)->recordDecision([
            'job_application_id' => $application->id,
            'recommendation' => 'hire',
        ], $hr);
    }

    public function test_tenant_isolation_for_offers(): void
    {
        [$organization, $hr, , , $application] = $this->offerScenario();
        $otherOrg = Organization::factory()->create();
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
        $otherOffer = OfferLetter::factory()->create([
            'organization_id' => $otherOrg->id,
            'candidate_id' => $otherCandidate->id,
            'job_application_id' => $otherApplication->id,
            'offer_template_id' => null,
        ]);

        $this->actingAs($hr)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.recruitment.offers.show', $otherOffer))
            ->assertForbidden();
    }

    public function test_unauthorized_user_cannot_access_offers(): void
    {
        [$organization] = $this->offerScenario();
        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');

        $this->actingAs($employee)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.recruitment.offers.index'))
            ->assertForbidden();
    }

    public function test_candidate_show_displays_offer_and_hiring_sections(): void
    {
        [$organization, $hr, , , $application] = $this->offerScenario();
        $candidate = $application->candidate;

        OfferLetter::factory()->create([
            'organization_id' => $organization->id,
            'candidate_id' => $candidate->id,
            'job_application_id' => $application->id,
            'status' => 'sent',
        ]);

        HiringDecision::factory()->create([
            'organization_id' => $organization->id,
            'job_application_id' => $application->id,
            'decision_by' => $hr->id,
            'recommendation' => 'hold',
        ]);

        $this->actingAs($hr)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.recruitment.candidates.show', $candidate))
            ->assertOk()
            ->assertSee('Offer History')
            ->assertSee('Hiring Decisions');
    }

    /**
     * @return array{0: Organization, 1: User, 2: Department, 3: Designation, 4: JobApplication, 5?: OfferTemplate}
     */
    private function offerScenario(bool $withRecommendation = true): array
    {
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
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
            'department_id' => $department->id,
            'created_by' => $hr->id,
            'updated_by' => $hr->id,
        ]);

        if ($withRecommendation) {
            $this->seedHireRecommendation($organization, $application, $hr);
        }

        return [$organization, $hr, $department, $designation, $application, $template];
    }

    private function seedHireRecommendation(Organization $organization, JobApplication $application, User $hr): void
    {
        $stage = InterviewStage::factory()->create([
            'organization_id' => $organization->id,
            'slug' => 'final_review',
            'name' => 'Final Review',
        ]);

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
}
