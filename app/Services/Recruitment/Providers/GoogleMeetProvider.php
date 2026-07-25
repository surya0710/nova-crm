<?php

namespace App\Services\Recruitment\Providers;

use App\Models\RecruitmentProvider;

class GoogleMeetProvider extends AbstractMeetingProvider
{
    public function slug(): string
    {
        return 'google_meet';
    }

    public function displayName(): string
    {
        return 'Google Meet';
    }

    public function generateMeeting(RecruitmentProvider $provider, array $meeting): array
    {
        $id = $this->meetingId('gmeet');
        $code = strtolower(substr(str_replace('_', '', $id), -10));

        return [
            'ok' => true,
            'meeting_url' => 'https://meet.google.com/'.$code,
            'meeting_id' => $id,
            'provider' => $this->slug(),
            'join_instructions' => __('Join with Google Meet using the link below. No PIN required.'),
            'message' => 'Google Meet link generated (placeholder).',
            'metadata' => ['title' => $meeting['title'] ?? null],
        ];
    }
}
