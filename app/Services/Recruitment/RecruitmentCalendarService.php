<?php

namespace App\Services\Recruitment;

use App\Contracts\RecruitmentCalendarProviderInterface;
use App\Models\InterviewRound;
use App\Models\Organization;
use App\Models\RecruitmentCalendarEvent;
use App\Models\RecruitmentProvider;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Services\Recruitment\Providers\RecruitmentProviderRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class RecruitmentCalendarService
{
    public function __construct(
        protected RecruitmentProviderRegistry $registry,
        protected RecruitmentProviderService $providers,
        protected AuditLogger $auditLogger,
        protected NotificationService $notificationService,
    ) {}

    /**
     * Create or update a calendar event for a scheduled interview. Failures never throw to callers
     * of recruitment workflows — they are logged and optionally notified.
     */
    public function syncInterviewEvent(
        InterviewRound $round,
        RecruitmentProvider $provider,
        ?User $actor = null,
    ): ?RecruitmentCalendarEvent {
        if ($round->scheduled_at === null) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Calendar events require a scheduled interview.',
            ]);
        }

        if (! $provider->isConnected()) {
            throw ValidationException::withMessages([
                'provider' => 'Calendar provider must be connected.',
            ]);
        }

        $adapter = $this->registry->resolve($provider->slug);

        if (! $adapter instanceof RecruitmentCalendarProviderInterface) {
            throw ValidationException::withMessages([
                'provider' => 'Provider does not support calendar events.',
            ]);
        }

        $round->loadMissing(['jobApplication.candidate', 'participants.user']);

        $payload = [
            'title' => 'Interview: '.($round->jobApplication?->candidate?->fullName() ?? 'Candidate'),
            'starts_at' => $round->scheduled_at->toIso8601String(),
            'ends_at' => $round->scheduled_at->copy()->addMinutes((int) ($round->duration_minutes ?: 60))->toIso8601String(),
            'location' => $round->location,
            'description' => $round->notes,
            'notes' => $round->notes,
            'attendees' => $round->participants
                ->map(fn ($p) => [
                    'email' => $p->user?->email,
                    'name' => $p->user?->name,
                ])
                ->filter(fn ($a) => filled($a['email']))
                ->values()
                ->all(),
        ];

        return DB::transaction(function () use ($round, $provider, $adapter, $payload, $actor) {
            $event = RecruitmentCalendarEvent::query()->firstOrNew([
                'interview_round_id' => $round->id,
                'recruitment_provider_id' => $provider->id,
            ]);
            $event->organization_id = $round->organization_id;
            $event->payload = $payload;
            $event->attempt_count = (int) $event->attempt_count + 1;

            try {
                if ($event->external_event_id) {
                    $result = $adapter->updateEvent($provider, $event->external_event_id, $payload);
                } else {
                    $result = $adapter->createEvent($provider, $payload);
                }

                if (! ($result['ok'] ?? false)) {
                    throw new \RuntimeException($result['message'] ?? 'Calendar sync failed.');
                }

                $event->external_event_id = $result['external_event_id'] ?? $event->external_event_id;
                $event->meeting_link = $result['meeting_link'] ?? $event->meeting_link;
                $event->meeting_provider = $provider->slug;
                $event->status = $event->wasRecentlyCreated || ! $event->exists ? 'synced' : 'updated';
                if ($event->external_event_id && $event->getOriginal('external_event_id')) {
                    $event->status = 'updated';
                } else {
                    $event->status = 'synced';
                }
                $event->last_error = null;
                $event->synced_at = now();
                $event->metadata = $result['metadata'] ?? null;
                $event->save();

                $round->update([
                    'meeting_link' => $event->meeting_link ?? $round->meeting_link,
                    'meeting_provider' => $event->meeting_provider ?? $round->meeting_provider,
                ]);

                $this->auditLogger->log($event, 'recruitment_calendar_synced', [
                    'interview_round_id' => $round->id,
                    'provider' => $provider->slug,
                    'external_event_id' => $event->external_event_id,
                ], $actor);

                $provider->last_synced_at = now();
                $provider->save();

                return $event;
            } catch (Throwable $e) {
                $event->status = 'failed';
                $event->last_error = $e->getMessage();
                $event->save();

                $this->notifySyncFailure($round->organization_id, $provider, $e->getMessage(), $actor);

                return $event;
            }
        });
    }

    public function cancelInterviewEvent(InterviewRound $round, ?User $actor = null): void
    {
        $events = RecruitmentCalendarEvent::query()
            ->where('interview_round_id', $round->id)
            ->whereNotNull('external_event_id')
            ->with('provider')
            ->get();

        foreach ($events as $event) {
            try {
                $provider = $event->provider;
                if (! $provider || ! $this->registry->has($provider->slug)) {
                    continue;
                }

                $adapter = $this->registry->resolve($provider->slug);
                if ($adapter instanceof RecruitmentCalendarProviderInterface) {
                    $adapter->cancelEvent($provider, (string) $event->external_event_id);
                }

                $event->update(['status' => 'cancelled', 'last_error' => null]);
                $this->auditLogger->log($event, 'recruitment_calendar_cancelled', [
                    'interview_round_id' => $round->id,
                    'external_event_id' => $event->external_event_id,
                ], $actor);
            } catch (Throwable $e) {
                $event->update(['status' => 'failed', 'last_error' => $e->getMessage()]);
                $this->notifySyncFailure($round->organization_id, $event->provider, $e->getMessage(), $actor);
            }
        }
    }

    /**
     * Safe wrapper for workflow listeners — never throws.
     */
    public function trySyncScheduledInterview(InterviewRound $round, Organization $organization, ?User $actor = null): void
    {
        try {
            if ($round->scheduled_at === null) {
                return;
            }

            $calendarProviders = $this->providers->listProviders($organization, 'calendar')
                ->filter(fn (RecruitmentProvider $p) => $p->isConnected());

            foreach ($calendarProviders as $provider) {
                $this->syncInterviewEvent($round, $provider, $actor);
            }
        } catch (Throwable) {
            // Provider failures must not interrupt recruitment workflows.
        }
    }

    protected function notifySyncFailure(int $organizationId, ?RecruitmentProvider $provider, string $message, ?User $actor): void
    {
        if (! $actor) {
            return;
        }

        try {
            $this->notificationService->send(
                $organizationId,
                $actor->id,
                'Calendar sync failed',
                ($provider?->display_name ?? 'Calendar provider').': '.$message,
                '/hrms/recruitment/integrations/calendar',
            );
        } catch (Throwable) {
            // Notifications are best-effort.
        }
    }
}
