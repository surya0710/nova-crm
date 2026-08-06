<?php

namespace App\Services\Recruitment\Providers;

use App\Models\RecruitmentProvider;
use Illuminate\Validation\ValidationException;

class CustomMeetingUrlProvider extends AbstractMeetingProvider
{
    public function slug(): string
    {
        return 'custom_meeting_url';
    }

    public function displayName(): string
    {
        return 'Custom Meeting URL';
    }

    public function generateMeeting(RecruitmentProvider $provider, array $meeting): array
    {
        $url = trim((string) ($meeting['custom_url'] ?? $meeting['meeting_url'] ?? ''));

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            throw ValidationException::withMessages([
                'meeting_link' => __('A valid custom meeting URL is required.'),
            ]);
        }

        $id = $this->meetingId('custom');

        return [
            'ok' => true,
            'meeting_url' => $url,
            'meeting_id' => $id,
            'provider' => $this->slug(),
            'join_instructions' => (string) ($meeting['join_instructions'] ?? __('Use the custom meeting URL provided in the invitation.')),
            'message' => 'Custom meeting URL accepted.',
        ];
    }
}
