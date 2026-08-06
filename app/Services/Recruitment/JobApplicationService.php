<?php

namespace App\Services\Recruitment;

use App\Events\ApplicationSubmitted;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JobApplicationService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected NotificationService $notificationService,
    ) {}

    public function createApplication(array $data, User $actor): JobApplication
    {
        $opening = JobOpening::query()->findOrFail($data['job_opening_id']);

        if ($opening->status !== 'published') {
            throw ValidationException::withMessages([
                'job_opening_id' => 'Applications can only be submitted for published openings.',
            ]);
        }

        $this->assertUniqueApplication((int) $data['candidate_id'], (int) $opening->id, (int) $opening->organization_id);

        return DB::transaction(function () use ($data, $opening, $actor): JobApplication {
            $application = JobApplication::query()->create(array_merge($data, [
                'organization_id' => $opening->organization_id,
                'stage' => $data['stage'] ?? 'applied',
                'status' => $data['status'] ?? 'active',
                'applied_date' => $data['applied_date'] ?? now()->toDateString(),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]));

            $this->auditLogger->log($application, 'job_application_created', [
                'candidate_id' => $application->candidate_id,
                'job_opening_id' => $application->job_opening_id,
                'stage' => $application->stage,
            ], $actor);

            event(ApplicationSubmitted::forModel($application, ['actor_id' => $actor->id]));
            $this->notifyApplicationReceived($application);

            return $application->load(['candidate', 'jobOpening', 'assignedRecruiter']);
        });
    }

    public function updateApplication(JobApplication $application, array $data, User $actor): JobApplication
    {
        return DB::transaction(function () use ($application, $data, $actor): JobApplication {
            $before = $application->only(['stage', 'status', 'assigned_recruiter_id', 'notes', 'source']);

            if (isset($data['stage'])) {
                $this->assertValidStageTransition($application->stage, $data['stage']);
            }

            $application->update(array_merge($data, ['updated_by' => $actor->id]));
            $application->refresh();

            if (in_array($application->stage, ['hired', 'rejected', 'withdrawn'], true)) {
                $application->update(['status' => 'closed', 'updated_by' => $actor->id]);
                $application->refresh();
            }

            $this->auditLogger->log($application, 'job_application_updated', [
                'before' => $before,
                'after' => $application->only(array_keys($before)),
            ], $actor);

            return $application->load(['candidate', 'jobOpening', 'assignedRecruiter']);
        });
    }

    public function deleteApplication(JobApplication $application, User $actor): void
    {
        DB::transaction(function () use ($application, $actor): void {
            $this->auditLogger->log($application, 'job_application_deleted', [
                'candidate_id' => $application->candidate_id,
                'job_opening_id' => $application->job_opening_id,
            ], $actor);
            $application->delete();
        });
    }

    protected function assertUniqueApplication(int $candidateId, int $openingId, int $organizationId): void
    {
        $exists = JobApplication::query()
            ->where('organization_id', $organizationId)
            ->where('candidate_id', $candidateId)
            ->where('job_opening_id', $openingId)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'candidate_id' => 'This candidate has already applied for the selected opening.',
            ]);
        }
    }

    protected function assertValidStageTransition(string $from, string $to): void
    {
        $terminal = ['hired', 'rejected', 'withdrawn'];

        if (in_array($from, $terminal, true) && $from !== $to) {
            throw ValidationException::withMessages([
                'stage' => 'Applications in a terminal stage cannot be moved to another stage.',
            ]);
        }

        $stages = array_keys(config('hrms.recruitment.application_stages', []));

        if (! in_array($to, $stages, true)) {
            throw ValidationException::withMessages([
                'stage' => 'Invalid application stage.',
            ]);
        }
    }

    protected function notifyApplicationReceived(JobApplication $application): void
    {
        $organizationId = (int) $application->organization_id;
        $title = 'New job application received';
        $message = sprintf(
            '%s applied for %s.',
            $application->candidate?->fullName() ?? 'A candidate',
            $application->jobOpening?->title ?? 'an opening',
        );
        $url = '/hrms/recruitment/applications/'.$application->id;

        $recipientId = $application->assigned_recruiter_id
            ?? $application->jobOpening?->creator?->id
            ?? $application->created_by;

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
                // Skip notification when recipient is not an organization member.
            }
        }
    }
}
