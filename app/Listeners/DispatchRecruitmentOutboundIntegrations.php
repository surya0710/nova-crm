<?php

namespace App\Listeners;

use App\Events\ApplicationSubmitted;
use App\Events\HiringApproved;
use App\Events\InterviewCancelled;
use App\Events\InterviewCompleted;
use App\Events\InterviewScheduled;
use App\Events\OfferAccepted;
use App\Events\OfferSent;
use App\Events\WorkflowDomainEvent;
use App\Models\InterviewRound;
use App\Models\Organization;
use App\Models\User;
use App\Services\Recruitment\RecruitmentCalendarService;
use App\Services\Recruitment\RecruitmentWebhookService;
use Throwable;

/**
 * Outbound recruitment integrations hooked to workflow domain events.
 * Failures are swallowed so recruitment workflows are never interrupted.
 */
class DispatchRecruitmentOutboundIntegrations
{
    public function __construct(
        protected RecruitmentWebhookService $webhooks,
        protected RecruitmentCalendarService $calendar,
    ) {}

    public function handle(WorkflowDomainEvent $event): void
    {
        try {
            $organization = Organization::query()->find($event->organizationId);
            if (! $organization) {
                return;
            }

            $this->webhooks->dispatchFromWorkflowTrigger(
                $organization,
                $event->trigger(),
                [
                    'subject_type' => $event->subjectType,
                    'subject_id' => $event->subjectId,
                    'subject' => $event->subjectSnapshot,
                    'payload' => $event->payload,
                    'event_id' => $event->eventId,
                ],
            );

            if ($event instanceof InterviewScheduled) {
                $round = InterviewRound::query()->find($event->subjectId);
                if ($round) {
                    $actor = isset($event->payload['actor_id'])
                        ? User::query()->find($event->payload['actor_id'])
                        : null;
                    $this->calendar->trySyncScheduledInterview($round, $organization, $actor);
                }
            }

            if ($event instanceof InterviewCancelled) {
                $round = InterviewRound::query()->find($event->subjectId);
                if ($round) {
                    $actor = isset($event->payload['actor_id'])
                        ? User::query()->find($event->payload['actor_id'])
                        : null;
                    try {
                        $this->calendar->cancelInterviewEvent($round, $actor);
                    } catch (Throwable) {
                    }
                }
            }
        } catch (Throwable) {
            // Never interrupt recruitment workflows.
        }
    }

    /**
     * @return list<class-string<WorkflowDomainEvent>>
     */
    public static function subscribedEvents(): array
    {
        return [
            ApplicationSubmitted::class,
            InterviewScheduled::class,
            InterviewCancelled::class,
            InterviewCompleted::class,
            OfferSent::class,
            OfferAccepted::class,
            HiringApproved::class,
        ];
    }
}
