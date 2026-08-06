<?php

namespace App\Services\Recruitment\Providers;

use App\Models\RecruitmentProvider;
use Illuminate\Support\Str;

class JitsiMeetProvider extends AbstractMeetingProvider
{
    public function slug(): string
    {
        return 'jitsi_meet';
    }

    public function displayName(): string
    {
        return 'Jitsi Meet';
    }

    public function generateMeeting(RecruitmentProvider $provider, array $meeting): array
    {
        $room = config('branding.filename_prefix', 'KonnectNex').'-'.Str::upper(Str::random(8));
        $id = $this->meetingId('jitsi');

        return [
            'ok' => true,
            'meeting_url' => 'https://meet.jit.si/'.$room,
            'meeting_id' => $id,
            'provider' => $this->slug(),
            'join_instructions' => __('Open the Jitsi Meet link in your browser. No account required.'),
            'message' => 'Jitsi Meet room generated.',
            'metadata' => ['room' => $room],
        ];
    }
}
