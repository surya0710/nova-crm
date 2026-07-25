<?php

namespace App\Services\Recruitment\Providers;

use App\Models\RecruitmentProvider;

class ZoomMeetingProvider extends AbstractMeetingProvider
{
    public function slug(): string
    {
        return 'zoom';
    }

    public function displayName(): string
    {
        return 'Zoom';
    }

    public function generateMeeting(RecruitmentProvider $provider, array $meeting): array
    {
        $id = $this->meetingId('zoom');
        $numeric = (string) random_int(10000000000, 99999999999);

        return [
            'ok' => true,
            'meeting_url' => 'https://zoom.us/j/'.$numeric,
            'meeting_id' => $id,
            'provider' => $this->slug(),
            'join_instructions' => __('Join Zoom Meeting ID :id. Waiting room may be enabled.', ['id' => $numeric]),
            'message' => 'Zoom meeting generated (placeholder).',
            'metadata' => ['zoom_meeting_number' => $numeric],
        ];
    }
}
