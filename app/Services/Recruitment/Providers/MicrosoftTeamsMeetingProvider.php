<?php

namespace App\Services\Recruitment\Providers;

use App\Models\RecruitmentProvider;

class MicrosoftTeamsMeetingProvider extends AbstractMeetingProvider
{
    public function slug(): string
    {
        return 'microsoft_teams';
    }

    public function displayName(): string
    {
        return 'Microsoft Teams';
    }

    public function generateMeeting(RecruitmentProvider $provider, array $meeting): array
    {
        $id = $this->meetingId('teams');

        return [
            'ok' => true,
            'meeting_url' => 'https://teams.microsoft.com/l/meetup-join/'.$id,
            'meeting_id' => $id,
            'provider' => $this->slug(),
            'join_instructions' => __('Join with Microsoft Teams. Sign in with your work account if prompted.'),
            'message' => 'Microsoft Teams meeting generated (placeholder).',
        ];
    }
}
