<?php

namespace App\Contracts;

use App\Models\RecruitmentProvider;

/**
 * Calendar event capability for recruitment providers.
 */
interface RecruitmentCalendarProviderInterface
{
    /**
     * @param  array{
     *     title: string,
     *     starts_at: string,
     *     ends_at?: string|null,
     *     timezone?: string|null,
     *     location?: string|null,
     *     description?: string|null,
     *     attendees?: list<array{email: string, name?: string|null}>,
     *     notes?: string|null,
     *     meeting_provider?: string|null,
     * }  $event
     * @return array{ok: bool, external_event_id?: string|null, meeting_link?: string|null, message?: string|null, metadata?: array<string, mixed>}
     */
    public function createEvent(RecruitmentProvider $provider, array $event): array;

    /**
     * @param  array<string, mixed>  $event
     * @return array{ok: bool, external_event_id?: string|null, meeting_link?: string|null, message?: string|null, metadata?: array<string, mixed>}
     */
    public function updateEvent(RecruitmentProvider $provider, string $externalEventId, array $event): array;

    /**
     * @return array{ok: bool, message?: string|null}
     */
    public function cancelEvent(RecruitmentProvider $provider, string $externalEventId): array;
}
