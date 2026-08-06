<?php

namespace App\Services\Recruitment;

use App\Events\ApplicationSubmitted;
use App\Events\ApplicationWithdrawn;
use App\Events\JobApplied;
use App\Models\Candidate;
use App\Models\CandidateAccount;
use App\Models\CandidatePortalSetting;
use App\Models\CandidateResume;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublicApplicationService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected NotificationService $notificationService,
        protected CandidateNotificationService $candidateNotificationService,
        protected CandidateProfileService $candidateProfileService,
        protected ResumeService $resumeService,
    ) {}

    public function applyAsGuest(
        Organization $organization,
        JobOpening $opening,
        array $data,
        ?UploadedFile $resume = null,
        ?CandidatePortalSetting $portalSettings = null,
    ): JobApplication {
        $portalSettings ??= CandidatePortalSetting::query()->where('organization_id', $organization->id)->first();

        if ($portalSettings?->require_login_to_apply) {
            throw ValidationException::withMessages([
                'application' => 'Login is required to apply for this position.',
            ]);
        }

        if ($portalSettings && ! $portalSettings->allow_guest_apply) {
            throw ValidationException::withMessages([
                'application' => 'Guest applications are not enabled.',
            ]);
        }

        $this->assertOpeningAcceptsApplications($opening);

        return DB::transaction(function () use ($organization, $opening, $data, $resume): JobApplication {
            $candidate = $this->findOrCreateCandidate($organization, $data, 'portal_guest');
            $resumeRecord = $resume ? $this->resumeService->upload($candidate, 'Application Resume', $resume, true) : null;

            return $this->createApplication(
                $opening,
                $candidate,
                $resumeRecord,
                'portal_guest',
                false,
                $data,
            );
        });
    }

    public function applyAsCandidate(
        CandidateAccount $account,
        JobOpening $opening,
        ?CandidateResume $resume = null,
        bool $asDraft = false,
    ): JobApplication {
        $this->assertOpeningAcceptsApplications($opening);

        $candidate = $account->candidate;
        $resumeRecord = $resume ?? $candidate->defaultResume;

        if (! $asDraft && ! $resumeRecord) {
            throw ValidationException::withMessages([
                'resume' => 'A resume is required to submit your application.',
            ]);
        }

        $this->assertUniqueApplication($candidate->id, $opening->id, $opening->organization_id);

        return DB::transaction(function () use ($account, $opening, $candidate, $resumeRecord, $asDraft): JobApplication {
            return $this->createApplication(
                $opening,
                $candidate,
                $resumeRecord,
                'portal_account',
                $asDraft,
                [],
                $account,
            );
        });
    }

    public function saveDraft(CandidateAccount $account, JobOpening $opening, ?CandidateResume $resume = null): JobApplication
    {
        return $this->applyAsCandidate($account, $opening, $resume, true);
    }

    public function submitDraft(JobApplication $application, CandidateAccount $account, ?CandidateResume $resume = null): JobApplication
    {
        $this->assertApplicationOwnership($application, $account);

        if (! $application->is_draft) {
            throw ValidationException::withMessages([
                'application' => 'Only draft applications can be submitted.',
            ]);
        }

        if (! $application->canCandidateEdit()) {
            throw ValidationException::withMessages([
                'application' => 'This application cannot be modified.',
            ]);
        }

        $resumeRecord = $resume ?? $application->resume ?? $account->candidate?->defaultResume;

        if (! $resumeRecord) {
            throw ValidationException::withMessages([
                'resume' => 'A resume is required to submit your application.',
            ]);
        }

        return DB::transaction(function () use ($application, $resumeRecord, $account): JobApplication {
            $application->update([
                'is_draft' => false,
                'candidate_resume_id' => $resumeRecord->id,
                'profile_snapshot' => $this->candidateProfileService->buildProfileSnapshot($account->candidate),
                'applied_date' => now()->toDateString(),
            ]);

            $application->refresh();
            $this->finalizeSubmission($application, $account);

            return $application->load(['jobOpening', 'resume']);
        });
    }

    public function updateResume(JobApplication $application, CandidateAccount $account, CandidateResume $resume): JobApplication
    {
        $this->assertApplicationOwnership($application, $account);

        if (! $application->canCandidateEdit()) {
            throw ValidationException::withMessages([
                'application' => 'Withdrawn applications cannot be edited.',
            ]);
        }

        if ($resume->candidate_id !== $account->candidate_id) {
            abort(403);
        }

        return DB::transaction(function () use ($application, $resume): JobApplication {
            $application->update(['candidate_resume_id' => $resume->id]);
            $this->auditLogger->log($application, 'public_application_resume_updated', [
                'candidate_resume_id' => $resume->id,
            ]);

            return $application->fresh(['jobOpening', 'resume']);
        });
    }

    public function withdraw(JobApplication $application, CandidateAccount $account): JobApplication
    {
        $this->assertApplicationOwnership($application, $account);

        if ($application->stage === 'withdrawn') {
            throw ValidationException::withMessages([
                'application' => 'This application has already been withdrawn.',
            ]);
        }

        return DB::transaction(function () use ($application, $account): JobApplication {
            $application->update([
                'stage' => 'withdrawn',
                'status' => 'closed',
            ]);

            $this->auditLogger->log($application, 'public_application_withdrawn', [
                'candidate_account_id' => $account->id,
            ], null);

            event(ApplicationWithdrawn::forModel($application, ['candidate_account_id' => $account->id]));
            $this->notifyWithdrawal($application);

            return $application->fresh(['jobOpening']);
        });
    }

    protected function createApplication(
        JobOpening $opening,
        Candidate $candidate,
        ?CandidateResume $resume,
        string $submissionType,
        bool $asDraft,
        array $guestData,
        ?CandidateAccount $account = null,
    ): JobApplication {
        if (! $asDraft) {
            $this->assertUniqueApplication($candidate->id, $opening->id, $opening->organization_id);
        }

        $application = JobApplication::query()->create([
            'organization_id' => $opening->organization_id,
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'stage' => $asDraft ? 'applied' : 'applied',
            'status' => $asDraft ? 'active' : 'active',
            'is_draft' => $asDraft,
            'candidate_resume_id' => $resume?->id,
            'profile_snapshot' => $asDraft ? null : $this->candidateProfileService->buildProfileSnapshot($candidate),
            'submission_type' => $submissionType,
            'applied_date' => now()->toDateString(),
            'source' => 'careers_portal',
        ]);

        $this->auditLogger->log($application, 'public_application_submitted', [
            'submission_type' => $submissionType,
            'is_draft' => $asDraft,
            'job_opening_id' => $opening->id,
            'candidate_id' => $candidate->id,
        ]);

        if (! $asDraft) {
            $this->finalizeSubmission($application, $account, $guestData);
        }

        return $application->load(['jobOpening', 'candidate', 'resume']);
    }

    protected function finalizeSubmission(JobApplication $application, ?CandidateAccount $account = null, array $guestData = []): void
    {
        event(ApplicationSubmitted::forModel($application));
        event(JobApplied::forModel($application, [
            'candidate_account_id' => $account?->id,
            'guest' => $account === null,
        ]));

        $this->notifyRecruiterNewApplication($application);

        if ($account) {
            try {
                $this->candidateNotificationService->send(
                    (int) $application->organization_id,
                    (int) $account->id,
                    'Application submitted',
                    sprintf('Your application for %s was submitted successfully.', $application->jobOpening?->title ?? 'the position'),
                    '/careers/applications/'.$application->id,
                );
            } catch (ValidationException) {
                // Skip invalid notification payloads.
            }
        }
    }

    protected function findOrCreateCandidate(Organization $organization, array $data, string $source): Candidate
    {
        $email = strtolower($data['email']);

        $existing = Candidate::query()
            ->where('organization_id', $organization->id)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($existing) {
            return $existing;
        }

        return Candidate::query()->create([
            'organization_id' => $organization->id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'source' => $source,
        ]);
    }

    protected function assertOpeningAcceptsApplications(JobOpening $opening): void
    {
        if ($opening->status !== 'published') {
            throw ValidationException::withMessages([
                'job_opening_id' => 'This job is not accepting applications.',
            ]);
        }

        if ($opening->closing_date && $opening->closing_date->isPast()) {
            throw ValidationException::withMessages([
                'job_opening_id' => 'This job posting has closed.',
            ]);
        }

        if (in_array($opening->status, ['closed', 'filled', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'job_opening_id' => 'Closed jobs cannot receive applications.',
            ]);
        }
    }

    protected function assertUniqueApplication(int $candidateId, int $openingId, int $organizationId): void
    {
        $exists = JobApplication::query()
            ->where('organization_id', $organizationId)
            ->where('candidate_id', $candidateId)
            ->where('job_opening_id', $openingId)
            ->where('is_draft', false)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'application' => 'You have already applied for this position.',
            ]);
        }
    }

    protected function assertApplicationOwnership(JobApplication $application, CandidateAccount $account): void
    {
        if ((int) $application->candidate_id !== (int) $account->candidate_id) {
            abort(403);
        }
    }

    protected function notifyRecruiterNewApplication(JobApplication $application): void
    {
        $organizationId = (int) $application->organization_id;
        $title = 'New job application received';
        $message = sprintf(
            '%s applied for %s via the careers portal.',
            $application->candidate?->fullName() ?? 'A candidate',
            $application->jobOpening?->title ?? 'an opening',
        );
        $url = '/hrms/recruitment/applications/'.$application->id;

        $recipientId = $application->assigned_recruiter_id
            ?? $application->jobOpening?->created_by;

        if ($recipientId) {
            try {
                $this->notificationService->send(
                    $organizationId,
                    (int) $recipientId,
                    $title,
                    $message,
                    $url,
                );
            } catch (ValidationException) {
                // Skip when recipient is invalid.
            }
        }
    }

    protected function notifyWithdrawal(JobApplication $application): void
    {
        $organizationId = (int) $application->organization_id;
        $title = 'Candidate withdrew application';
        $message = sprintf(
            '%s withdrew their application for %s.',
            $application->candidate?->fullName() ?? 'A candidate',
            $application->jobOpening?->title ?? 'an opening',
        );
        $url = '/hrms/recruitment/applications/'.$application->id;

        $recipientId = $application->assigned_recruiter_id
            ?? $application->jobOpening?->created_by;

        if ($recipientId) {
            try {
                $this->notificationService->send(
                    $organizationId,
                    (int) $recipientId,
                    $title,
                    $message,
                    $url,
                );
            } catch (ValidationException) {
                // Skip when recipient is invalid.
            }
        }
    }
}
