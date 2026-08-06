<?php

namespace App\Services;

use App\Events\ClientApproved;
use App\Events\ClientRejected;
use App\Models\ClientApproval;
use App\Models\ClientUser;
use App\Models\Deliverable;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected PortalNotificationService $portalNotifications,
        protected DeliverableService $deliverables,
    ) {}

    public function createForDeliverable(Deliverable $deliverable, User $actor, ?string $message = null): ClientApproval
    {
        if (! in_array($deliverable->status, ['submitted', 'client_review', 'revised'], true)) {
            throw ValidationException::withMessages([
                'status' => __('Deliverable must be submitted before requesting approval.'),
            ]);
        }

        $approval = ClientApproval::query()->create([
            'organization_id' => $deliverable->organization_id,
            'project_id' => $deliverable->project_id,
            'approvable_type' => $deliverable->getMorphClass(),
            'approvable_id' => $deliverable->id,
            'status' => 'client_review',
            'request_message' => $message,
            'requested_by' => $actor->id,
            'submitted_at' => now(),
        ]);

        if ($deliverable->status !== 'client_review') {
            $this->deliverables->markClientReview($deliverable, $actor);
        }

        $this->auditLogger->log($approval, 'approval_requested', [
            'deliverable_id' => $deliverable->id,
        ], $actor);

        return $approval->fresh(['approvable']);
    }

    public function approve(ClientApproval $approval, ClientUser $client, ?string $notes = null): ClientApproval
    {
        $this->assertClientReview($approval);
        app(ClientAccessService::class)->assertCanAccessProject($client, $approval->project, 'deliverables');

        return DB::transaction(function () use ($approval, $client, $notes): ClientApproval {
            $approval->update([
                'status' => 'approved',
                'decision_notes' => $notes,
                'decided_by_client_user_id' => $client->id,
                'decided_at' => now(),
            ]);

            $approvable = $approval->approvable;
            if ($approvable instanceof Deliverable) {
                $approvable->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);
            }

            $runtime = app(WorkflowRuntimeContext::class);
            event(ClientApproved::forModel(
                $approval,
                ['client_user_id' => $client->id],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            $this->auditLogger->log($approval, 'client_approved', [], null);

            if ($approval->requester) {
                try {
                    $this->portalNotifications->notifyStaff(
                        (int) $approval->organization_id,
                        $approval->requester,
                        __('Client approved'),
                        __('A client approved: :title', ['title' => $approvable->title ?? '#'.$approval->id]),
                    );
                } catch (ValidationException) {
                    // Skip when recipient is not an organization member.
                }
            }

            return $approval->fresh(['approvable']);
        });
    }

    public function reject(ClientApproval $approval, ClientUser $client, ?string $notes = null): ClientApproval
    {
        $this->assertClientReview($approval);
        app(ClientAccessService::class)->assertCanAccessProject($client, $approval->project, 'deliverables');

        return DB::transaction(function () use ($approval, $client, $notes): ClientApproval {
            $approval->update([
                'status' => 'rejected',
                'decision_notes' => $notes,
                'decided_by_client_user_id' => $client->id,
                'decided_at' => now(),
            ]);

            $approvable = $approval->approvable;
            if ($approvable instanceof Deliverable) {
                $approvable->update([
                    'status' => 'rejected',
                ]);
            }

            $runtime = app(WorkflowRuntimeContext::class);
            event(ClientRejected::forModel(
                $approval,
                ['client_user_id' => $client->id],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            $this->auditLogger->log($approval, 'client_rejected', [], null);

            if ($approval->requester) {
                try {
                    $this->portalNotifications->notifyStaff(
                        (int) $approval->organization_id,
                        $approval->requester,
                        __('Client rejected'),
                        __('A client rejected: :title', ['title' => $approvable->title ?? '#'.$approval->id]),
                    );
                } catch (ValidationException) {
                    // Skip when recipient is not an organization member.
                }
            }

            return $approval->fresh(['approvable']);
        });
    }

    public function markRevised(Deliverable $deliverable, User $actor): Deliverable
    {
        if ($deliverable->status !== 'rejected') {
            throw ValidationException::withMessages([
                'status' => __('Only rejected deliverables can be marked revised.'),
            ]);
        }

        $deliverable->update([
            'status' => 'revised',
            'updated_by' => $actor->id,
        ]);

        return $deliverable->fresh();
    }

    protected function assertClientReview(ClientApproval $approval): void
    {
        if ($approval->status !== 'client_review') {
            throw ValidationException::withMessages([
                'status' => __('Approval is not awaiting client review.'),
            ]);
        }
    }
}
