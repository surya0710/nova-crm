<?php

namespace App\Services\Recruitment;

use App\Events\OfferAccepted;
use App\Events\OfferApproved;
use App\Events\OfferExpired;
use App\Events\OfferGenerated;
use App\Events\OfferRejected;
use App\Events\OfferSent;
use App\Models\CandidateEvaluation;
use App\Models\JobApplication;
use App\Models\OfferLetter;
use App\Models\OfferTemplate;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfferLetterService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected NotificationService $notificationService,
    ) {}

    public function generateOffer(array $data, User $actor): OfferLetter
    {
        $application = JobApplication::query()
            ->with(['candidate', 'jobOpening.requisition.hiringManager'])
            ->findOrFail($data['job_application_id']);

        $this->assertCandidateRecommended($application);
        $this->assertNoActiveOffer($application);

        $template = isset($data['offer_template_id'])
            ? OfferTemplate::query()
                ->where('organization_id', $application->organization_id)
                ->where('id', $data['offer_template_id'])
                ->firstOrFail()
            : null;

        return DB::transaction(function () use ($data, $application, $template, $actor): OfferLetter {
            $reportingManagerId = $data['reporting_manager_id']
                ?? $application->jobOpening?->requisition?->hiring_manager_id;

            $offer = OfferLetter::query()->create([
                'organization_id' => $application->organization_id,
                'candidate_id' => $application->candidate_id,
                'job_application_id' => $application->id,
                'offer_template_id' => $template?->id,
                'reporting_manager_id' => $reportingManagerId,
                'proposed_salary' => $data['proposed_salary'],
                'variable_pay' => $data['variable_pay'] ?? null,
                'benefits' => $data['benefits'] ?? null,
                'joining_date' => $data['joining_date'],
                'expiry_date' => $data['expiry_date'],
                'status' => 'draft',
                'generated_content' => $this->renderContent($application, $template, $data),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->auditLogger->log($offer, 'offer_letter_generated', [
                'job_application_id' => $application->id,
                'proposed_salary' => $offer->proposed_salary,
            ], $actor);

            event(OfferGenerated::forModel($offer, ['actor_id' => $actor->id]));
            $this->notifyOfferGenerated($offer->load(['candidate', 'jobApplication']));

            return $offer->load(['candidate', 'jobApplication', 'offerTemplate', 'reportingManager']);
        });
    }

    public function updateOffer(OfferLetter $offer, array $data, User $actor): OfferLetter
    {
        $this->assertEditable($offer);

        return DB::transaction(function () use ($offer, $data, $actor): OfferLetter {
            $before = $offer->only([
                'proposed_salary', 'variable_pay', 'benefits', 'joining_date', 'expiry_date',
            ]);

            $offer->update(array_merge($data, ['updated_by' => $actor->id]));
            $offer->refresh();

            if ($offer->offerTemplate) {
                $offer->update([
                    'generated_content' => $this->renderContent(
                        $offer->jobApplication,
                        $offer->offerTemplate,
                        array_merge($offer->only([
                            'proposed_salary', 'variable_pay', 'benefits', 'joining_date', 'expiry_date',
                        ]), $data),
                    ),
                ]);
            }

            $this->auditLogger->log($offer, 'offer_letter_updated', [
                'before' => $before,
                'after' => $offer->only(array_keys($before)),
            ], $actor);

            return $offer->load(['candidate', 'jobApplication', 'offerTemplate']);
        });
    }

    public function deleteOffer(OfferLetter $offer, User $actor): void
    {
        if (! in_array($offer->status, ['draft', 'withdrawn'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only draft or withdrawn offers can be deleted.',
            ]);
        }

        DB::transaction(function () use ($offer, $actor): void {
            $this->auditLogger->log($offer, 'offer_letter_deleted', [
                'status' => $offer->status,
            ], $actor);
            $offer->delete();
        });
    }

    public function submitForApproval(OfferLetter $offer, array $approverIds, User $actor): OfferLetter
    {
        $this->assertStatusTransition($offer, 'draft', 'pending_approval');

        if ($approverIds === []) {
            throw ValidationException::withMessages([
                'approver_ids' => 'At least one approver is required.',
            ]);
        }

        return DB::transaction(function () use ($offer, $approverIds, $actor): OfferLetter {
            $offer->update([
                'status' => 'pending_approval',
                'updated_by' => $actor->id,
            ]);

            app(OfferApprovalService::class)->createApprovals($offer, $approverIds, $actor);

            $this->auditLogger->log($offer, 'offer_letter_submitted_for_approval', [
                'approver_ids' => $approverIds,
            ], $actor);

            $this->notifyApprovers($offer->fresh(['approvals.approver', 'candidate']));

            return $offer->load(['approvals.approver', 'candidate', 'jobApplication']);
        });
    }

    public function markApproved(OfferLetter $offer, User $actor): OfferLetter
    {
        $this->assertStatusTransition($offer, 'pending_approval', 'approved');

        return DB::transaction(function () use ($offer, $actor): OfferLetter {
            $offer->update([
                'status' => 'approved',
                'updated_by' => $actor->id,
            ]);

            $this->auditLogger->log($offer, 'offer_letter_approved', [], $actor);
            event(OfferApproved::forModel($offer, ['actor_id' => $actor->id]));

            return $offer;
        });
    }

    public function sendOffer(OfferLetter $offer, User $actor): OfferLetter
    {
        if ($offer->status !== 'approved') {
            throw ValidationException::withMessages([
                'status' => 'Offer cannot be sent before approval.',
            ]);
        }

        return DB::transaction(function () use ($offer, $actor): OfferLetter {
            $offer->update([
                'status' => 'sent',
                'sent_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $this->auditLogger->log($offer, 'offer_letter_sent', [], $actor);
            event(OfferSent::forModel($offer, ['actor_id' => $actor->id]));
            $this->notifyOfferSent($offer->load(['candidate', 'jobApplication']));

            return $offer;
        });
    }

    public function acceptOffer(OfferLetter $offer, User $actor): OfferLetter
    {
        if ($offer->status !== 'sent') {
            throw ValidationException::withMessages([
                'status' => 'Only sent offers can be accepted.',
            ]);
        }

        $this->assertNotExpired($offer);

        return DB::transaction(function () use ($offer, $actor): OfferLetter {
            $offer->update([
                'status' => 'accepted',
                'accepted_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $this->auditLogger->log($offer, 'offer_letter_accepted', [], $actor);
            event(OfferAccepted::forModel($offer, ['actor_id' => $actor->id]));
            $this->notifyOfferAccepted($offer->load(['candidate', 'jobApplication']));

            return $offer;
        });
    }

    public function rejectOffer(OfferLetter $offer, User $actor, ?string $reason = null): OfferLetter
    {
        if (! in_array($offer->status, ['sent', 'approved'], true)) {
            throw ValidationException::withMessages([
                'status' => 'This offer cannot be rejected in its current state.',
            ]);
        }

        return DB::transaction(function () use ($offer, $actor, $reason): OfferLetter {
            $offer->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $this->auditLogger->log($offer, 'offer_letter_rejected', [
                'reason' => $reason,
            ], $actor);
            event(OfferRejected::forModel($offer, ['actor_id' => $actor->id]));

            return $offer;
        });
    }

    public function withdrawOffer(OfferLetter $offer, User $actor): OfferLetter
    {
        if (! in_array($offer->status, ['draft', 'pending_approval', 'approved', 'sent'], true)) {
            throw ValidationException::withMessages([
                'status' => 'This offer cannot be withdrawn.',
            ]);
        }

        return DB::transaction(function () use ($offer, $actor): OfferLetter {
            $offer->update([
                'status' => 'withdrawn',
                'updated_by' => $actor->id,
            ]);

            $this->auditLogger->log($offer, 'offer_letter_withdrawn', [], $actor);

            return $offer;
        });
    }

    public function expireOffer(OfferLetter $offer, User $actor): OfferLetter
    {
        if (! in_array($offer->status, ['sent', 'approved'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only approved or sent offers can be expired.',
            ]);
        }

        return DB::transaction(function () use ($offer, $actor): OfferLetter {
            $offer->update([
                'status' => 'expired',
                'updated_by' => $actor->id,
            ]);

            $this->auditLogger->log($offer, 'offer_letter_expired', [], $actor);
            event(OfferExpired::forModel($offer, ['actor_id' => $actor->id]));

            return $offer;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function renderContent(
        JobApplication $application,
        ?OfferTemplate $template,
        array $data,
    ): string {
        $content = $template?->template_content ?? $this->defaultTemplateContent();
        $candidate = $application->candidate;
        $opening = $application->jobOpening;
        $manager = isset($data['reporting_manager_id'])
            ? \App\Models\Employee::query()->find($data['reporting_manager_id'])
            : $opening?->requisition?->hiringManager;

        $replacements = [
            '{{candidate_name}}' => $candidate?->fullName() ?? '',
            '{{position}}' => $opening?->title ?? '',
            '{{salary}}' => number_format((float) ($data['proposed_salary'] ?? 0), 2),
            '{{variable_pay}}' => isset($data['variable_pay']) ? number_format((float) $data['variable_pay'], 2) : '—',
            '{{joining_date}}' => isset($data['joining_date']) ? (string) $data['joining_date'] : '',
            '{{reporting_manager}}' => $manager?->full_name ?? '—',
            '{{benefits}}' => $data['benefits'] ?? '—',
            '{{expiry_date}}' => isset($data['expiry_date']) ? (string) $data['expiry_date'] : '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    protected function defaultTemplateContent(): string
    {
        return "Dear {{candidate_name}},\n\nWe are pleased to offer you the position of {{position}}.\n\nProposed Salary: {{salary}}\nVariable Pay: {{variable_pay}}\nJoining Date: {{joining_date}}\nReporting Manager: {{reporting_manager}}\nBenefits: {{benefits}}\n\nThis offer expires on {{expiry_date}}.";
    }

    protected function assertCandidateRecommended(JobApplication $application): void
    {
        $hasRecommendation = CandidateEvaluation::query()
            ->where('organization_id', $application->organization_id)
            ->whereHas('interviewRound', fn ($q) => $q->where('job_application_id', $application->id))
            ->whereIn('recommendation', ['strong_hire', 'hire'])
            ->where('status', 'submitted')
            ->exists();

        if (! $hasRecommendation) {
            throw ValidationException::withMessages([
                'job_application_id' => 'Offers can only be generated for recommended candidates.',
            ]);
        }
    }

    protected function assertNoActiveOffer(JobApplication $application): void
    {
        $exists = OfferLetter::query()
            ->where('organization_id', $application->organization_id)
            ->where('job_application_id', $application->id)
            ->whereIn('status', config('hrms.recruitment.active_offer_statuses', []))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'job_application_id' => 'An active offer already exists for this application.',
            ]);
        }
    }

    protected function assertEditable(OfferLetter $offer): void
    {
        if (! in_array($offer->status, ['draft', 'pending_approval'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only draft or pending approval offers can be edited.',
            ]);
        }
    }

    protected function assertStatusTransition(OfferLetter $offer, string $from, string $to): void
    {
        if ($offer->status !== $from) {
            throw ValidationException::withMessages([
                'status' => sprintf('Offer must be in %s status to transition to %s.', $from, $to),
            ]);
        }
    }

    protected function assertNotExpired(OfferLetter $offer): void
    {
        if ($offer->expiry_date->isPast()) {
            throw ValidationException::withMessages([
                'expiry_date' => 'Expired offers cannot be accepted.',
            ]);
        }
    }

    protected function notifyOfferGenerated(OfferLetter $offer): void
    {
        $recipientId = $offer->jobApplication?->assigned_recruiter_id ?? $offer->created_by;

        if (! $recipientId) {
            return;
        }

        try {
            $this->notificationService->send(
                (int) $offer->organization_id,
                (int) $recipientId,
                'Offer generated',
                sprintf('An offer letter was generated for %s.', $offer->candidate?->fullName() ?? 'a candidate'),
                '/hrms/recruitment/offers/'.$offer->id,
            );
        } catch (ValidationException) {
            // Skip when recipient is not an organization member.
        }
    }

    protected function notifyApprovers(OfferLetter $offer): void
    {
        foreach ($offer->approvals as $approval) {
            try {
                $this->notificationService->send(
                    (int) $offer->organization_id,
                    (int) $approval->approver_id,
                    'Offer approval required',
                    sprintf('An offer for %s requires your approval.', $offer->candidate?->fullName() ?? 'a candidate'),
                    '/hrms/recruitment/offer-approvals/'.$approval->id,
                );
            } catch (ValidationException) {
                // Skip when approver is not an organization member.
            }
        }
    }

    protected function notifyOfferSent(OfferLetter $offer): void
    {
        $recruiterId = $offer->jobApplication?->assigned_recruiter_id;

        if ($recruiterId) {
            try {
                $this->notificationService->send(
                    (int) $offer->organization_id,
                    (int) $recruiterId,
                    'Offer sent',
                    sprintf('Offer letter sent to %s.', $offer->candidate?->fullName() ?? 'candidate'),
                    '/hrms/recruitment/offers/'.$offer->id,
                );
            } catch (ValidationException) {
                // Skip when recipient is not an organization member.
            }
        }

        // Candidate delivery placeholder — no email implementation yet.
    }

    protected function notifyOfferAccepted(OfferLetter $offer): void
    {
        $recruiterId = $offer->jobApplication?->assigned_recruiter_id ?? $offer->created_by;

        if (! $recruiterId) {
            return;
        }

        try {
            $this->notificationService->send(
                (int) $offer->organization_id,
                (int) $recruiterId,
                'Offer accepted',
                sprintf('%s accepted the offer.', $offer->candidate?->fullName() ?? 'Candidate'),
                '/hrms/recruitment/offers/'.$offer->id,
            );
        } catch (ValidationException) {
            // Skip when recipient is not an organization member.
        }
    }
}
