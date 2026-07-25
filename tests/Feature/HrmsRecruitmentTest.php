<?php

namespace Tests\Feature;

use App\Events\ApplicationSubmitted;
use App\Events\CandidateCreated;
use App\Events\JobOpeningPublished;
use App\Events\RequisitionApproved;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Services\Recruitment\CandidateService;
use App\Services\Recruitment\JobApplicationService;
use App\Services\Recruitment\JobOpeningService;
use App\Services\Recruitment\JobRequisitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HrmsRecruitmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_requisition_crud_audit_and_approval_flow(): void
    {
        Event::fake([RequisitionApproved::class]);
        Notification::fake();

        [$organization, $hr, $department, $designation] = $this->recruitmentScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.recruitment.requisitions.store'), [
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'employment_type' => 'full_time',
            'number_of_positions' => 2,
            'business_justification' => 'Team expansion required for Q3 delivery.',
        ])->assertRedirect(route('hrms.recruitment.requisitions.index'));

        $requisition = JobRequisition::query()->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['event' => 'job_requisition_created']);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.recruitment.requisitions.submit', $requisition))
            ->assertRedirect(route('hrms.recruitment.requisitions.show', $requisition));
        $this->assertSame('pending_approval', $requisition->fresh()->status);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.recruitment.requisitions.approve', $requisition))
            ->assertRedirect(route('hrms.recruitment.requisitions.show', $requisition));

        $this->assertSame('approved', $requisition->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'job_requisition_approved']);
        Event::assertDispatched(RequisitionApproved::class);
    }

    public function test_opening_can_only_be_created_from_approved_requisition(): void
    {
        [$organization, $hr, $department, $designation] = $this->recruitmentScenario();
        $session = ['current_organization_id' => $organization->id];

        $draft = JobRequisition::factory()->create([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'status' => 'draft',
        ]);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.recruitment.openings.store'), [
            'job_requisition_id' => $draft->id,
            'title' => 'Software Engineer',
        ])->assertSessionHasErrors('job_requisition_id');

        $draft->update(['status' => 'approved']);
        $this->actingAs($hr)->withSession($session)->post(route('hrms.recruitment.openings.store'), [
            'job_requisition_id' => $draft->id,
            'title' => 'Software Engineer',
        ])->assertRedirect();

        $this->assertDatabaseHas('job_openings', [
            'organization_id' => $organization->id,
            'title' => 'Software Engineer',
        ]);
    }

    public function test_publish_opening_emits_event_and_notification(): void
    {
        Event::fake([JobOpeningPublished::class]);
        Notification::fake();

        [$organization, $hr, $department, $designation] = $this->recruitmentScenario();
        $session = ['current_organization_id' => $organization->id];

        $requisition = JobRequisition::factory()->approved()->create([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'requested_by' => $hr->id,
        ]);

        $opening = app(JobOpeningService::class)->createOpeningFromRequisition($requisition, [
            'title' => 'Backend Developer',
        ], $hr);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.recruitment.openings.publish', $opening))
            ->assertRedirect(route('hrms.recruitment.openings.show', $opening));

        Event::assertDispatched(JobOpeningPublished::class);
        Notification::assertSentTo($hr, CrmNotification::class);
    }

    public function test_candidate_uniqueness_and_creation_event(): void
    {
        Event::fake([CandidateCreated::class]);
        Storage::fake(config('hrms.recruitment.documents.disk', 'local'));

        [$organization, $hr] = $this->recruitmentScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.recruitment.candidates.store'), [
            'first_name' => 'Jane',
            'last_name' => 'Candidate',
            'email' => 'jane@example.com',
            'source' => 'referral',
        ])->assertRedirect();

        Event::assertDispatched(CandidateCreated::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'candidate_created']);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.recruitment.candidates.store'), [
            'first_name' => 'Jane',
            'last_name' => 'Duplicate',
            'email' => 'jane@example.com',
        ])->assertSessionHasErrors('email');

        $this->expectException(ValidationException::class);
        app(CandidateService::class)->createCandidate([
            'organization_id' => $organization->id,
            'first_name' => 'Jane',
            'last_name' => 'Again',
            'email' => 'jane@example.com',
        ], $hr);
    }

    public function test_application_creation_rules_and_event(): void
    {
        Event::fake([ApplicationSubmitted::class]);
        Notification::fake();

        [$organization, $hr, $department, $designation] = $this->recruitmentScenario();
        $session = ['current_organization_id' => $organization->id];

        $requisition = JobRequisition::factory()->approved()->create([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
        ]);

        $opening = app(JobOpeningService::class)->createOpeningFromRequisition($requisition, [
            'title' => 'QA Engineer',
        ], $hr);

        $candidate = Candidate::factory()->create(['organization_id' => $organization->id]);

        $this->expectException(ValidationException::class);
        app(JobApplicationService::class)->createApplication([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
        ], $hr);

        app(JobOpeningService::class)->publishOpening($opening, $hr);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.recruitment.applications.store'), [
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'source' => 'direct',
        ])->assertRedirect();

        Event::assertDispatched(ApplicationSubmitted::class);
        $this->assertDatabaseHas('job_applications', [
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
        ]);

        $this->expectException(ValidationException::class);
        app(JobApplicationService::class)->createApplication([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
        ], $hr);
    }

    public function test_tenant_isolation_for_requisitions(): void
    {
        [$organization, $hr, $department, $designation] = $this->recruitmentScenario();
        $otherOrg = Organization::factory()->create();
        $otherRequisition = JobRequisition::factory()->create([
            'organization_id' => $otherOrg->id,
            'department_id' => Department::factory()->create(['organization_id' => $otherOrg->id])->id,
            'designation_id' => Designation::factory()->create(['organization_id' => $otherOrg->id])->id,
        ]);

        $this->actingAs($hr)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.recruitment.requisitions.show', $otherRequisition))
            ->assertForbidden();
    }

    public function test_unauthorized_user_cannot_access_recruitment(): void
    {
        [$organization] = $this->recruitmentScenario();
        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');

        $this->actingAs($employee)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.recruitment.dashboard'))
            ->assertForbidden();
    }

    public function test_service_status_transitions(): void
    {
        [$organization, $hr, $department, $designation] = $this->recruitmentScenario();

        $requisition = app(JobRequisitionService::class)->createRequisition([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'employment_type' => 'full_time',
            'number_of_positions' => 1,
            'business_justification' => 'Replacement hire.',
        ], $hr);

        app(JobRequisitionService::class)->submitForApproval($requisition, $hr);
        app(JobRequisitionService::class)->approveRequisition($requisition->fresh(), $hr);

        $opening = app(JobOpeningService::class)->createOpeningFromRequisition($requisition->fresh(), [
            'title' => 'Analyst',
        ], $hr);

        app(JobOpeningService::class)->publishOpening($opening, $hr);
        $this->assertSame('published', $opening->fresh()->status);
    }

    /**
     * @return array{0: Organization, 1: User, 2: Department, 3: Designation}
     */
    private function recruitmentScenario(): array
    {
        $organization = Organization::factory()->create();
        $hr = User::factory()->create();
        $organization->addMember($hr, 'hr');

        $department = Department::factory()->create(['organization_id' => $organization->id]);
        $designation = Designation::factory()->create(['organization_id' => $organization->id]);

        return [$organization, $hr, $department, $designation];
    }
}
