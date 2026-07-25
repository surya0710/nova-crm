<?php

namespace App\Services\Recruitment\Providers;

use App\Contracts\RecruitmentCalendarProviderInterface;
use App\Models\RecruitmentProvider;
use Illuminate\Support\Str;

class OutlookCalendarProvider extends AbstractRecruitmentProvider implements RecruitmentCalendarProviderInterface
{
    public function slug(): string
    {
        return 'outlook_calendar';
    }

    public function displayName(): string
    {
        return 'Microsoft Outlook Calendar';
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
        $externalId = 'outlook_'.Str::uuid()->toString();

        return [
            'ok' => true,
            'external_event_id' => $externalId,
            'meeting_link' => 'https://teams.microsoft.com/l/meetup-join/'.Str::random(16),
            'message' => 'Outlook Calendar event created (placeholder).',
            'metadata' => ['attendees' => count($event['attendees'] ?? [])],
        ];
    }

    public function updateEvent(RecruitmentProvider $provider, string $externalEventId, array $event): array
    {
        return [
            'ok' => true,
            'external_event_id' => $externalEventId,
            'meeting_link' => $event['meeting_link'] ?? null,
            'message' => 'Outlook Calendar event updated (placeholder).',
        ];
    }

    public function cancelEvent(RecruitmentProvider $provider, string $externalEventId): array
    {
        return [
            'ok' => true,
            'message' => 'Outlook Calendar event cancelled (placeholder).',
        ];
    }
}
