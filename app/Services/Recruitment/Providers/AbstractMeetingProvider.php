<?php

namespace App\Services\Recruitment\Providers;

use App\Contracts\InterviewMeetingProviderInterface;
use App\Models\RecruitmentProvider;
use Illuminate\Support\Str;

abstract class AbstractMeetingProvider extends AbstractRecruitmentProvider implements InterviewMeetingProviderInterface
{
    public function category(): string
    {
        return 'meeting';
    }

    public function capabilities(): array
    {
        return ['meeting_links'];
    }

    public function updateMeeting(RecruitmentProvider $provider, string $meetingId, array $meeting): array
    {
        $generated = $this->generateMeeting($provider, $meeting);

        return [
            'ok' => (bool) ($generated['ok'] ?? false),
            'meeting_url' => $generated['meeting_url'] ?? null,
            'meeting_id' => $meetingId,
            'join_instructions' => $generated['join_instructions'] ?? null,
            'message' => $this->displayName().' meeting updated (placeholder).',
        ];
    }

    public function cancelMeeting(RecruitmentProvider $provider, string $meetingId): array
    {
        return [
            'ok' => true,
            'message' => $this->displayName().' meeting cancelled (placeholder).',
        ];
    }

    public function validateCredentials(RecruitmentProvider $provider): array
    {
        return [
            'ok' => $provider->isConnected() || $this->slug() === 'custom_meeting_url' || $this->slug() === 'jitsi_meet',
            'message' => $provider->isConnected() || in_array($this->slug(), ['custom_meeting_url', 'jitsi_meet'], true)
                ? $this->displayName().' credentials are valid.'
                : $this->displayName().' is not connected.',
        ];
    }

    protected function meetingId(string $prefix): string
    {
        return $prefix.'_'.Str::lower(Str::random(12));
    }
}
