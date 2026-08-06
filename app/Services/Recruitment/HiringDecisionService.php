<?php

namespace App\Services\Recruitment;

use App\Events\HiringApproved;
use App\Models\HiringDecision;
use App\Models\JobApplication;
use App\Models\OfferLetter;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HiringDecisionService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected NotificationService $notificationService,
    ) {}

    public function recordDecision(array $data, User $actor): HiringDecision
    {
        $application = JobApplication::query()->findOrFail($data['job_application_id']);
        $recommendation = $data['recommendation'];

        if ($recommendation === 'hire') {
            $this->assertAcceptedOfferExists($application);
        }

        return DB::transaction(function () use ($data, $application, $recommendation, $actor): HiringDecision {
            $onboardingRecommended = false;
            $onboardingRecommendedAt = null;

            if ($recommendation === 'hire') {
                $acceptedOffer = OfferLetter::query()
                    ->where('organization_id', $application->organization_id)
                    ->where('job_application_id', $application->id)
                    ->where('status', 'accepted')
                    ->first();

                if ($acceptedOffer) {
                    $onboardingRecommended = true;
                    $onboardingRecommendedAt = now();
                }
            }

            $decision = HiringDecision::query()->create([
                'organization_id' => $application->organization_id,
                'job_application_id' => $application->id,
                'recommendation' => $recommendation,
                'decision_date' => $data['decision_date'] ?? now()->toDateString(),
                'decision_by' => $actor->id,
                'final_notes' => $data['final_notes'] ?? null,
                'onboarding_recommended' => $onboardingRecommended,
                'onboarding_recommended_at' => $onboardingRecommendedAt,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->auditLogger->log($decision, 'hiring_decision_recorded', [
                'job_application_id' => $application->id,
                'recommendation' => $recommendation,
                'onboarding_recommended' => $onboardingRecommended,
            ], $actor);

            if ($recommendation === 'hire') {
                event(HiringApproved::forModel($decision, [
                    'actor_id' => $actor->id,
                    'onboarding_recommended' => $onboardingRecommended,
                ]));
            }

            $this->notifyDecisionRecorded($decision->load(['jobApplication.candidate']));

            return $decision->load(['jobApplication.candidate', 'decisionMaker']);
        });
    }

    public function updateDecision(HiringDecision $decision, array $data, User $actor): HiringDecision
    {
        return DB::transaction(function () use ($decision, $data, $actor): HiringDecision {
            $before = $decision->only(['recommendation', 'decision_date', 'final_notes']);

            $decision->update(array_merge($data, ['updated_by' => $actor->id]));
            $decision->refresh();

            $this->auditLogger->log($decision, 'hiring_decision_updated', [
                'before' => $before,
                'after' => $decision->only(array_keys($before)),
            ], $actor);

            return $decision->load(['jobApplication.candidate', 'decisionMaker']);
        });
    }

    protected function assertAcceptedOfferExists(JobApplication $application): void
    {
        $hasAcceptedOffer = OfferLetter::query()
            ->where('organization_id', $application->organization_id)
            ->where('job_application_id', $application->id)
            ->where('status', 'accepted')
            ->exists();

        if (! $hasAcceptedOffer) {
            throw ValidationException::withMessages([
                'job_application_id' => 'A hire decision requires an accepted offer.',
            ]);
        }
    }

    protected function notifyDecisionRecorded(HiringDecision $decision): void
    {
        $recipientId = $decision->jobApplication?->assigned_recruiter_id ?? $decision->decision_by;

        if (! $recipientId) {
            return;
        }

        try {
            $this->notificationService->send(
                (int) $decision->organization_id,
                (int) $recipientId,
                'Hiring decision recorded',
                sprintf(
                    'A %s decision was recorded for %s.',
                    $decision->recommendationLabel(),
                    $decision->jobApplication?->candidate?->fullName() ?? 'a candidate',
                ),
                '/hrms/recruitment/hiring-decisions/'.$decision->id,
            );
        } catch (ValidationException) {
            // Skip when recipient is not an organization member.
        }
    }
}
