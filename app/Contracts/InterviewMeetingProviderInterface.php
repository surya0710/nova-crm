<?php

namespace App\Contracts;

use App\Models\RecruitmentProvider;

/**
 * Meeting link capability for recruitment interview scheduling.
 */
interface InterviewMeetingProviderInterface
{
    public function slug(): string;

    public function displayName(): string;

    /**
     * @param  array{
     *     title: string,
     *     starts_at: string,
     *     ends_at?: string|null,
     *     timezone?: string|null,
     *     duration_minutes?: int|null,
     *     attendees?: list<array{email: string, name?: string|null}>,
     *     custom_url?: string|null,
     *     notes?: string|null,
     * }  $meeting
     * @return array{
     *     ok: bool,
     *     meeting_url?: string|null,
     *     meeting_id?: string|null,
     *     join_instructions?: string|null,
     *     provider?: string|null,
     *     message?: string|null,
     *     metadata?: array<string, mixed>
     * }
     */
    public function generateMeeting(RecruitmentProvider $provider, array $meeting): array;

    /**
     * @param  array<string, mixed>  $meeting
     * @return array{ok: bool, meeting_url?: string|null, meeting_id?: string|null, join_instructions?: string|null, message?: string|null}
     */
    public function updateMeeting(RecruitmentProvider $provider, string $meetingId, array $meeting): array;

    /**
     * @return array{ok: bool, message?: string|null}
     */
    public function cancelMeeting(RecruitmentProvider $provider, string $meetingId): array;

    /**
     * @return array{ok: bool, message?: string|null}
     */
    public function validateCredentials(RecruitmentProvider $provider): array;
}
