<?php

namespace App\Services\Recruitment;

use App\Models\OfferLetter;
use App\Models\OfferNegotiation;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfferNegotiationService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected NotificationService $notificationService,
    ) {}

    public function recordNegotiation(OfferLetter $offer, array $data, User $actor): OfferNegotiation
    {
        if ($offer->isNegotiationLocked()) {
            throw ValidationException::withMessages([
                'offer' => 'Accepted offers lock further negotiations.',
            ]);
        }

        if (! in_array($offer->status, ['sent', 'approved', 'pending_approval', 'draft'], true)) {
            throw ValidationException::withMessages([
                'offer' => 'Negotiations cannot be recorded for this offer status.',
            ]);
        }

        return DB::transaction(function () use ($offer, $data, $actor): OfferNegotiation {
            $negotiation = OfferNegotiation::query()->create([
                'organization_id' => $offer->organization_id,
                'offer_letter_id' => $offer->id,
                'requested_salary' => $data['requested_salary'] ?? null,
                'requested_joining_date' => $data['requested_joining_date'] ?? null,
                'candidate_comments' => $data['candidate_comments'] ?? null,
                'recruiter_notes' => $data['recruiter_notes'] ?? null,
                'outcome' => $data['outcome'] ?? 'pending',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->auditLogger->log($negotiation, 'offer_negotiation_recorded', [
                'offer_letter_id' => $offer->id,
                'outcome' => $negotiation->outcome,
            ], $actor);

            $this->notifyNegotiationRecorded($negotiation->load(['offerLetter.candidate', 'offerLetter.jobApplication']));

            return $negotiation->load('offerLetter.candidate');
        });
    }

    public function updateNegotiation(OfferNegotiation $negotiation, array $data, User $actor): OfferNegotiation
    {
        $offer = $negotiation->offerLetter;

        if ($offer?->isNegotiationLocked()) {
            throw ValidationException::withMessages([
                'offer' => 'Accepted offers lock further negotiations.',
            ]);
        }

        return DB::transaction(function () use ($negotiation, $data, $actor): OfferNegotiation {
            $before = $negotiation->only([
                'requested_salary', 'requested_joining_date', 'candidate_comments', 'recruiter_notes', 'outcome',
            ]);

            $negotiation->update(array_merge($data, ['updated_by' => $actor->id]));
            $negotiation->refresh();

            $this->auditLogger->log($negotiation, 'offer_negotiation_updated', [
                'before' => $before,
                'after' => $negotiation->only(array_keys($before)),
            ], $actor);

            return $negotiation->load('offerLetter.candidate');
        });
    }

    protected function notifyNegotiationRecorded(OfferNegotiation $negotiation): void
    {
        $offer = $negotiation->offerLetter;
        $recipientId = $offer?->jobApplication?->assigned_recruiter_id ?? $offer?->created_by;

        if (! $recipientId) {
            return;
        }

        try {
            $this->notificationService->send(
                (int) $negotiation->organization_id,
                (int) $recipientId,
                'Offer negotiation recorded',
                sprintf('A negotiation entry was added for %s.', $offer->candidate?->fullName() ?? 'a candidate'),
                '/hrms/recruitment/negotiations/'.$negotiation->id,
            );
        } catch (ValidationException) {
            // Skip when recipient is not an organization member.
        }
    }
}
