<?php

namespace App\Services\Recruitment;

use App\Events\JobOpeningPublished;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JobOpeningService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected NotificationService $notificationService,
        protected RecruitmentJobBoardService $jobBoardService,
    ) {}

    public function createOpeningFromRequisition(JobRequisition $requisition, array $data, User $actor): JobOpening
    {
        if ($requisition->status !== 'approved') {
            throw ValidationException::withMessages([
                'job_requisition_id' => 'Job openings can only be created from approved requisitions.',
            ]);
        }

        return DB::transaction(function () use ($requisition, $data, $actor): JobOpening {
            $opening = JobOpening::query()->create(array_merge([
                'organization_id' => $requisition->organization_id,
                'job_requisition_id' => $requisition->id,
                'department_id' => $data['department_id'] ?? $requisition->department_id,
                'designation_id' => $data['designation_id'] ?? $requisition->designation_id,
                'employment_type' => $data['employment_type'] ?? $requisition->employment_type,
                'status' => 'draft',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ], $data));

            $this->auditLogger->log($opening, 'job_opening_created', [
                'job_requisition_id' => $opening->job_requisition_id,
                'title' => $opening->title,
                'status' => $opening->status,
            ], $actor);

            return $opening->load(['requisition', 'department', 'designation']);
        });
    }

    public function updateOpening(JobOpening $opening, array $data, User $actor): JobOpening
    {
        if (in_array($opening->status, ['filled', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Filled or cancelled openings cannot be edited.',
            ]);
        }

        return DB::transaction(function () use ($opening, $data, $actor): JobOpening {
            $before = $opening->only([
                'title', 'location', 'description', 'responsibilities', 'requirements',
                'skills', 'salary_range_min', 'salary_range_max', 'experience', 'education',
                'publish_date', 'closing_date',
            ]);

            $opening->update(array_merge($data, ['updated_by' => $actor->id]));
            $opening->refresh();

            $this->auditLogger->log($opening, 'job_opening_updated', [
                'before' => $before,
                'after' => $opening->only(array_keys($before)),
            ], $actor);

            return $opening->load(['requisition', 'department', 'designation']);
        });
    }

    public function deleteOpening(JobOpening $opening, User $actor): void
    {
        if ($opening->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Only draft openings can be deleted.',
            ]);
        }

        DB::transaction(function () use ($opening, $actor): void {
            $this->auditLogger->log($opening, 'job_opening_deleted', [
                'title' => $opening->title,
            ], $actor);
            $opening->delete();
        });
    }

    public function publishOpening(JobOpening $opening, User $actor): JobOpening
    {
        $this->assertStatusTransition($opening, 'draft', 'published');

        return DB::transaction(function () use ($opening, $actor): JobOpening {
            $opening->update([
                'status' => 'published',
                'publish_date' => $opening->publish_date ?? now()->toDateString(),
                'updated_by' => $actor->id,
            ]);
            $opening->refresh();

            $this->auditLogger->log($opening, 'job_opening_published', [
                'title' => $opening->title,
                'publish_date' => $opening->publish_date?->toDateString(),
            ], $actor);

            event(JobOpeningPublished::forModel($opening, ['actor_id' => $actor->id]));
            $this->notifyOpeningPublished($opening);

            return $opening->load(['requisition', 'department', 'designation']);
        });
    }

    public function pauseOpening(JobOpening $opening, User $actor): JobOpening
    {
        $this->assertStatusTransition($opening, 'published', 'paused');

        return $this->transitionOpening($opening, 'paused', 'job_opening_paused', $actor);
    }

    public function closeOpening(JobOpening $opening, User $actor): JobOpening
    {
        if (! in_array($opening->status, ['published', 'paused'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only published or paused openings can be closed.',
            ]);
        }

        $opening = $this->transitionOpening($opening, 'closed', 'job_opening_closed', $actor);
        $this->jobBoardService->tryCloseExternalListings($opening, $actor);

        return $opening;
    }

    public function markFilled(JobOpening $opening, User $actor): JobOpening
    {
        if (! in_array($opening->status, ['published', 'paused'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only published or paused openings can be marked filled.',
            ]);
        }

        $opening = $this->transitionOpening($opening, 'filled', 'job_opening_filled', $actor);
        $this->jobBoardService->tryCloseExternalListings($opening, $actor);

        return $opening;
    }

    public function cancelOpening(JobOpening $opening, User $actor): JobOpening
    {
        if (in_array($opening->status, ['filled', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => 'This opening cannot be cancelled.',
            ]);
        }

        $opening = $this->transitionOpening($opening, 'cancelled', 'job_opening_cancelled', $actor);
        $this->jobBoardService->tryCloseExternalListings($opening, $actor);

        return $opening;
    }

    protected function transitionOpening(JobOpening $opening, string $status, string $auditEvent, User $actor): JobOpening
    {
        return DB::transaction(function () use ($opening, $status, $auditEvent, $actor): JobOpening {
            $opening->update([
                'status' => $status,
                'updated_by' => $actor->id,
            ]);
            $opening->refresh();

            $this->auditLogger->log($opening, $auditEvent, [
                'status' => $opening->status,
            ], $actor);

            return $opening;
        });
    }

    protected function assertStatusTransition(JobOpening $opening, string $from, string $to): void
    {
        if ($opening->status !== $from) {
            throw ValidationException::withMessages([
                'status' => "Opening must be in {$from} status to transition to {$to}.",
            ]);
        }
    }

    protected function notifyOpeningPublished(JobOpening $opening): void
    {
        $organizationId = (int) $opening->organization_id;
        $title = 'Job opening published';
        $message = sprintf('The opening "%s" is now published.', $opening->title);
        $url = '/hrms/recruitment/openings/'.$opening->id;

        if ($opening->requisition?->requester) {
            $this->notificationService->send(
                $organizationId,
                (int) $opening->requisition->requested_by,
                $title,
                $message,
                $url,
            );
        }
    }
}
