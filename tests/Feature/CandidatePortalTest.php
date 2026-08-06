<?php

namespace Tests\Feature;

use App\Events\ApplicationWithdrawn;
use App\Events\CandidateRegistered;
use App\Events\JobApplied;
use App\Events\ResumeUploaded;
use App\Models\CandidateAccount;
use App\Models\CareerSiteSetting;
use App\Models\CandidatePortalSetting;
use App\Models\CandidateResume;
use App\Models\Department;
use App\Models\Designation;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\Organization;
use App\Models\User;
use App\Services\Recruitment\CandidateAccountService;
use App\Services\Recruitment\JobAlertService;
use App\Services\Recruitment\JobOpeningService;
use App\Services\Recruitment\JobRequisitionService;
use App\Services\Recruitment\PublicApplicationService;
use App\Services\Recruitment\ResumeService;
use App\Services\Recruitment\SavedJobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CandidatePortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_can_register_and_login(): void
    {
        Event::fake([CandidateRegistered::class]);

        [$organization] = $this->portalScenario();

        $this->post(route('careers.register', $organization), [
            'first_name' => 'Jane',
            'last_name' => 'Applicant',
            'email' => 'jane@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ])->assertRedirect(route('careers.dashboard', $organization));

        $this->assertDatabaseHas('candidate_accounts', [
            'organization_id' => $organization->id,
            'email' => 'jane@example.com',
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'candidate_account_created']);
        Event::assertDispatched(CandidateRegistered::class);

        auth('candidate')->logout();

        $this->post(route('careers.login', $organization), [
            'email' => 'jane@example.com',
            'password' => 'Password1!',
        ])->assertRedirect(route('careers.dashboard', $organization));
    }

    public function test_public_careers_home_lists_published_openings(): void
    {
        [$organization, , $department, $designation, $opening] = $this->portalScenario(withOpening: true);

        $this->get(route('careers.home', $organization))
            ->assertOk()
            ->assertSee($opening->title);
    }

    public function test_guest_can_apply_to_published_opening(): void
    {
        Event::fake([JobApplied::class]);
        Storage::fake('local');

        [$organization, , , , $opening] = $this->portalScenario(withOpening: true);

        $this->post(route('careers.jobs.apply.guest', [$organization, $opening]), [
            'first_name' => 'Guest',
            'last_name' => 'Applicant',
            'email' => 'guest@example.com',
            'phone' => '1234567890',
            'resume' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('careers.jobs.show', [$organization, $opening]));

        $this->assertDatabaseHas('job_applications', [
            'organization_id' => $organization->id,
            'job_opening_id' => $opening->id,
            'submission_type' => 'portal_guest',
            'is_draft' => false,
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'public_application_submitted']);
        Event::assertDispatched(JobApplied::class);
    }

    public function test_candidate_can_save_job_and_create_alert(): void
    {
        [$organization, , , , $opening] = $this->portalScenario(withOpening: true);
        $account = $this->createCandidateAccount($organization);

        $this->actingAs($account, 'candidate')
            ->post(route('careers.saved-jobs.store', [$organization, $opening]))
            ->assertRedirect();

        $this->assertDatabaseHas('candidate_saved_jobs', [
            'candidate_account_id' => $account->id,
            'job_opening_id' => $opening->id,
        ]);

        $this->actingAs($account, 'candidate')
            ->post(route('careers.job-alerts.store', $organization), [
                'location' => 'Remote',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('candidate_job_alerts', [
            'candidate_account_id' => $account->id,
            'location' => 'Remote',
        ]);
    }

    public function test_resume_management_enforces_single_default(): void
    {
        Event::fake([ResumeUploaded::class]);
        Storage::fake('local');

        [$organization] = $this->portalScenario();
        $account = $this->createCandidateAccount($organization);
        $service = app(ResumeService::class);

        $first = $service->upload($account->candidate, 'Resume A', UploadedFile::fake()->create('a.pdf', 50, 'application/pdf'), true);
        $second = $service->upload($account->candidate, 'Resume B', UploadedFile::fake()->create('b.pdf', 50, 'application/pdf'), true);

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
    }

    public function test_withdrawn_application_cannot_be_edited(): void
    {
        Event::fake([ApplicationWithdrawn::class]);
        Storage::fake('local');

        [$organization, , , , $opening] = $this->portalScenario(withOpening: true);
        $account = $this->createCandidateAccount($organization);

        app(ResumeService::class)->upload(
            $account->candidate,
            'Resume',
            UploadedFile::fake()->create('resume.pdf', 50, 'application/pdf'),
            true,
        );

        $application = app(PublicApplicationService::class)->applyAsCandidate($account, $opening);
        app(PublicApplicationService::class)->withdraw($application->fresh(), $account);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(PublicApplicationService::class)->updateResume(
            $application->fresh(),
            $account,
            CandidateResume::query()->where('candidate_id', $account->candidate_id)->firstOrFail(),
        );
    }

    public function test_tenant_isolation_prevents_cross_organization_account_access(): void
    {
        [$organizationA] = $this->portalScenario();
        [$organizationB] = $this->portalScenario();

        $accountA = $this->createCandidateAccount($organizationA, 'a@example.com');

        $this->post(route('careers.login', $organizationB), [
            'email' => 'a@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_password_reset_updates_candidate_password(): void
    {
        [$organization] = $this->portalScenario();
        $account = $this->createCandidateAccount($organization, 'reset@example.com');

        app(CandidateAccountService::class)->sendPasswordResetLink($organization, 'reset@example.com');
        $token = session('password_reset_token');
        $this->assertNotEmpty($token);

        app(CandidateAccountService::class)->resetPassword($organization, [
            'email' => 'reset@example.com',
            'token' => $token,
            'password' => 'NewPassword1!',
        ]);

        $this->assertTrue(Hash::check('NewPassword1!', $account->fresh()->password));
    }

    /**
     * @return array{0: Organization, 1: User, 2: Department, 3: Designation, 4?: JobOpening}
     */
    private function portalScenario(bool $withOpening = false): array
    {
        $organization = Organization::factory()->create();
        CareerSiteSetting::factory()->create([
            'organization_id' => $organization->id,
            'is_published' => true,
        ]);
        CandidatePortalSetting::factory()->create(['organization_id' => $organization->id]);

        $hr = User::factory()->create();
        $organization->addMember($hr, 'hr');
        $department = Department::factory()->create(['organization_id' => $organization->id]);
        $designation = Designation::factory()->create(['organization_id' => $organization->id]);

        if (! $withOpening) {
            return [$organization, $hr, $department, $designation];
        }

        $requisition = app(JobRequisitionService::class)->createRequisition([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'employment_type' => 'full_time',
            'number_of_positions' => 1,
            'business_justification' => 'Portal test opening.',
        ], $hr);
        app(JobRequisitionService::class)->submitForApproval($requisition, $hr);
        app(JobRequisitionService::class)->approveRequisition($requisition->fresh(), $hr);

        $opening = app(JobOpeningService::class)->createOpeningFromRequisition($requisition->fresh(), [
            'title' => 'Portal Engineer',
        ], $hr);
        app(JobOpeningService::class)->publishOpening($opening, $hr);

        return [$organization, $hr, $department, $designation, $opening->fresh()];
    }

    private function createCandidateAccount(Organization $organization, string $email = 'candidate@example.com'): CandidateAccount
    {
        return app(CandidateAccountService::class)->register($organization, [
            'first_name' => 'Portal',
            'last_name' => 'Candidate',
            'email' => $email,
            'password' => 'password',
        ]);
    }
}
