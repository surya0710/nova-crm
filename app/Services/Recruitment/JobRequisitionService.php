<?php

namespace App\Services\Recruitment;

use App\Events\RequisitionApproved;
use App\Models\JobRequisition;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JobRequisitionService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected NotificationService $notificationService,
    ) {}

    public function createRequisition(array $data, User $actor): JobRequisition
    {
        return DB::transaction(function () use ($data, $actor): JobRequisition {
            $requisition = JobRequisition::query()->create(array_merge($data, [
                'status' => $data['status'] ?? 'draft',
                'requested_by' => $data['requested_by'] ?? $actor->id,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]));

            $this->auditLogger->log($requisition, 'job_requisition_created', [
                'department_id' => $requisition->department_id,
                'designation_id' => $requisition->designation_id,
                'status' => $requisition->status,
            ], $actor);

            return $requisition->load(['department', 'designation', 'hiringManager', 'requester']);
        });
    }

    public function updateRequisition(JobRequisition $requisition, array $data, User $actor): JobRequisition
    {
        $this->assertEditable($requisition);

        return DB::transaction(function () use ($requisition, $data, $actor): JobRequisition {
            $before = $requisition->only([
                'department_id', 'designation_id', 'employment_type', 'hiring_manager_id',
                'number_of_positions', 'business_justification', 'target_joining_date', 'budget',
            ]);

            $requisition->update(array_merge($data, ['updated_by' => $actor->id]));
            $requisition->refresh();

            $this->auditLogger->log($requisition, 'job_requisition_updated', [
                'before' => $before,
                'after' => $requisition->only(array_keys($before)),
            ], $actor);

            return $requisition->load(['department', 'designation', 'hiringManager', 'requester']);
        });
    }

    public function deleteRequisition(JobRequisition $requisition, User $actor): void
    {
        $this->assertEditable($requisition);

        DB::transaction(function () use ($requisition, $actor): void {
            $this->auditLogger->log($requisition, 'job_requisition_deleted', [
                'status' => $requisition->status,
            ], $actor);
            $requisition->delete();
        });
    }

    public function submitForApproval(JobRequisition $requisition, User $actor): JobRequisition
    {
        $this->assertStatusTransition($requisition, 'draft', 'pending_approval');

        return DB::transaction(function () use ($requisition, $actor): JobRequisition {
            $requisition->update([
                'status' => 'pending_approval',
                'updated_by' => $actor->id,
            ]);
            $requisition->refresh();

            $this->auditLogger->log($requisition, 'job_requisition_submitted', [
                'status' => $requisition->status,
            ], $actor);

            $this->notifyApprovalRequest($requisition);

            return $requisition->load(['department', 'designation', 'hiringManager', 'requester']);
        });
    }

    public function approveRequisition(JobRequisition $requisition, User $actor): JobRequisition
    {
        $this->assertStatusTransition($requisition, 'pending_approval', 'approved');

        return DB::transaction(function () use ($requisition, $actor): JobRequisition {
            $requisition->update([
                'status' => 'approved',
                'updated_by' => $actor->id,
            ]);
            $requisition->refresh();

            $this->auditLogger->log($requisition, 'job_requisition_approved', [
                'status' => $requisition->status,
            ], $actor);

            event(RequisitionApproved::forModel($requisition, ['actor_id' => $actor->id]));

            return $requisition->load(['department', 'designation', 'hiringManager', 'requester']);
        });
    }

    public function rejectRequisition(JobRequisition $requisition, User $actor, ?string $reason = null): JobRequisition
    {
        $this->assertStatusTransition($requisition, 'pending_approval', 'rejected');

        return DB::transaction(function () use ($requisition, $actor, $reason): JobRequisition {
            $requisition->update([
                'status' => 'rejected',
                'updated_by' => $actor->id,
            ]);
            $requisition->refresh();

            $this->auditLogger->log($requisition, 'job_requisition_rejected', [
                'status' => $requisition->status,
                'reason' => $reason,
            ], $actor);

            return $requisition;
        });
    }

    public function cancelRequisition(JobRequisition $requisition, User $actor): JobRequisition
    {
        if (! in_array($requisition->status, ['draft', 'pending_approval', 'approved'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only draft, pending, or approved requisitions can be cancelled.',
            ]);
        }

        return DB::transaction(function () use ($requisition, $actor): JobRequisition {
            $requisition->update([
                'status' => 'cancelled',
                'updated_by' => $actor->id,
            ]);
            $requisition->refresh();

            $this->auditLogger->log($requisition, 'job_requisition_cancelled', [
                'status' => $requisition->status,
            ], $actor);

            return $requisition;
        });
    }

    public function closeRequisition(JobRequisition $requisition, User $actor): JobRequisition
    {
        $this->assertStatusTransition($requisition, 'approved', 'closed');

        return DB::transaction(function () use ($requisition, $actor): JobRequisition {
            $requisition->update([
                'status' => 'closed',
                'updated_by' => $actor->id,
            ]);
            $requisition->refresh();

            $this->auditLogger->log($requisition, 'job_requisition_closed', [
                'status' => $requisition->status,
            ], $actor);

            return $requisition;
        });
    }

    protected function assertEditable(JobRequisition $requisition): void
    {
        if (! in_array($requisition->status, ['draft', 'pending_approval'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Requisitions can only be edited while in draft or pending approval.',
            ]);
        }
    }

    protected function assertStatusTransition(JobRequisition $requisition, string $from, string $to): void
    {
        if ($requisition->status !== $from) {
            throw ValidationException::withMessages([
                'status' => "Requisition must be in {$from} status to transition to {$to}.",
            ]);
        }
    }

    protected function notifyApprovalRequest(JobRequisition $requisition): void
    {
        $organizationId = (int) $requisition->organization_id;
        $title = 'Job requisition approval requested';
        $message = sprintf(
            'A job requisition for %s requires approval.',
            $requisition->designation?->name ?? 'a position',
        );
        $url = '/hrms/recruitment/requisitions/'.$requisition->id;

        if ($requisition->hiringManager?->user_id) {
            $this->notificationService->send(
                $organizationId,
                (int) $requisition->hiringManager->user_id,
                $title,
                $message,
                $url,
            );
        }
    }
}
