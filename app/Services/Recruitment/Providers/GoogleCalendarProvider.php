<?php

namespace App\Services\Recruitment\Providers;

use App\Contracts\RecruitmentCalendarProviderInterface;
use App\Models\RecruitmentProvider;
use Illuminate\Support\Str;

class GoogleCalendarProvider extends AbstractRecruitmentProvider implements RecruitmentCalendarProviderInterface
{
    public function slug(): string
    {
        return 'google_calendar';
    }

    public function displayName(): string
    {
        return 'Google Calendar';
    }

    public function category(): string
    {
        return 'calendar';
    }

    public function capabilities(): array
    {
        return ['oauth', 'calendar_events', 'meeting_links'];
    }

    public function createEvent(RecruitmentProvider $provider, array $event): array
    {
        $externalId = 'gcal_'.Str::uuid()->toString();

        return [
            'ok' => true,
            'external_event_id' => $externalId,
            'meeting_link' => 'https://meet.google.com/'.Str::lower(Str::random(10)),
            'message' => 'Google Calendar event created (placeholder).',
            'metadata' => ['attendees' => count($event['attendees'] ?? [])],
        ];
    }

    public function updateEvent(RecruitmentProvider $provider, string $externalEventId, array $event): array
    {
        return [
            'ok' => true,
            'external_event_id' => $externalEventId,
            'meeting_link' => $event['meeting_link'] ?? null,
            'message' => 'Google Calendar event updated (placeholder).',
        ];
    }

    public function cancelEvent(RecruitmentProvider $provider, string $externalEventId): array
    {
        return [
            'ok' => true,
            'message' => 'Google Calendar event cancelled (placeholder).',
        ];
    }
}
