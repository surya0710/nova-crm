<?php

namespace App\Services\Recruitment;

use App\Models\OfferApproval;
use App\Models\OfferLetter;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfferApprovalService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected NotificationService $notificationService,
    ) {}

    /**
     * @param  array<int, int>  $approverIds
     */
    public function createApprovals(OfferLetter $offer, array $approverIds, User $actor): void
    {
        foreach (array_unique($approverIds) as $approverId) {
            OfferApproval::query()->create([
                'organization_id' => $offer->organization_id,
                'offer_letter_id' => $offer->id,
                'approver_id' => $approverId,
                'status' => 'pending',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        }
    }

    public function approve(OfferApproval $approval, User $actor, ?string $comments = null): OfferApproval
    {
        $this->assertApprover($approval, $actor);
        $this->assertPending($approval);

        return DB::transaction(function () use ($approval, $actor, $comments): OfferApproval {
            $approval->update([
                'status' => 'approved',
                'comments' => $comments,
                'approved_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $this->auditLogger->log($approval, 'offer_approval_approved', [
                'offer_letter_id' => $approval->offer_letter_id,
            ], $actor);

            $offer = $approval->offerLetter;
            $pendingCount = $offer->approvals()->where('status', 'pending')->count();

            if ($pendingCount === 0) {
                app(OfferLetterService::class)->markApproved($offer, $actor);
            }

            $this->notifyRecruiter($approval->fresh(['offerLetter.candidate']));

            return $approval->load(['offerLetter.candidate', 'approver']);
        });
    }

    public function reject(OfferApproval $approval, User $actor, ?string $comments = null): OfferApproval
    {
        $this->assertApprover($approval, $actor);
        $this->assertPending($approval);

        return DB::transaction(function () use ($approval, $actor, $comments): OfferApproval {
            $approval->update([
                'status' => 'rejected',
                'comments' => $comments,
                'updated_by' => $actor->id,
            ]);

            $offer = $approval->offerLetter;
            $offer->update([
                'status' => 'draft',
                'updated_by' => $actor->id,
            ]);

            $this->auditLogger->log($approval, 'offer_approval_rejected', [
                'offer_letter_id' => $approval->offer_letter_id,
                'comments' => $comments,
            ], $actor);

            return $approval->load(['offerLetter.candidate', 'approver']);
        });
    }

    public function returnForRevision(OfferApproval $approval, User $actor, ?string $comments = null): OfferApproval
    {
        $this->assertApprover($approval, $actor);
        $this->assertPending($approval);

        return DB::transaction(function () use ($approval, $actor, $comments): OfferApproval {
            $approval->update([
                'status' => 'returned',
                'comments' => $comments,
                'updated_by' => $actor->id,
            ]);

            $offer = $approval->offerLetter;
            $offer->update([
                'status' => 'draft',
                'updated_by' => $actor->id,
            ]);

            $this->auditLogger->log($approval, 'offer_approval_returned', [
                'offer_letter_id' => $approval->offer_letter_id,
                'comments' => $comments,
            ], $actor);

            $this->notifyReturned($approval->fresh(['offerLetter']));

            return $approval->load(['offerLetter.candidate', 'approver']);
        });
    }

    protected function assertApprover(OfferApproval $approval, User $actor): void
    {
        if ((int) $approval->approver_id !== (int) $actor->id
            && ! $actor->hasPermission('recruitment.offer.approve', $approval->organization)) {
            throw ValidationException::withMessages([
                'approver' => 'You are not authorized to act on this approval.',
            ]);
        }
    }

    protected function assertPending(OfferApproval $approval): void
    {
        if ($approval->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'This approval has already been processed.',
            ]);
        }
    }

    protected function notifyRecruiter(OfferApproval $approval): void
    {
        $offer = $approval->offerLetter;
        $recipientId = $offer?->created_by;

        if (! $recipientId) {
            return;
        }

        try {
            $this->notificationService->send(
                (int) $approval->organization_id,
                (int) $recipientId,
                'Offer approval updated',
                sprintf('An approver acted on the offer for %s.', $offer->candidate?->fullName() ?? 'a candidate'),
                '/hrms/recruitment/offers/'.$offer->id,
            );
        } catch (ValidationException) {
            // Skip when recipient is not an organization member.
        }
    }

    protected function notifyReturned(OfferApproval $approval): void
    {
        $offer = $approval->offerLetter;
        $recipientId = $offer?->created_by;

        if (! $recipientId) {
            return;
        }

        try {
            $this->notificationService->send(
                (int) $approval->organization_id,
                (int) $recipientId,
                'Offer returned for revision',
                'An approver returned the offer for revision.',
                '/hrms/recruitment/offers/'.$offer->id,
            );
        } catch (ValidationException) {
            // Skip when recipient is not an organization member.
        }
    }
}
