<?php

namespace App\Services\Recruitment;

use App\Models\CandidateNotification;
use Illuminate\Validation\ValidationException;

class CandidateNotificationService
{
    public function send(
        int $organizationId,
        int $candidateAccountId,
        string $title,
        string $message,
        ?string $url = null,
    ): CandidateNotification {
        if (strlen($title) > 120) {
            throw ValidationException::withMessages(['title' => 'Notification title is too long.']);
        }

        if (strlen($message) > 1000) {
            throw ValidationException::withMessages(['message' => 'Notification message is too long.']);
        }

        if ($url !== null && ! preg_match('/^\/(?!\/)(?!.*\\\\)[^\r\n]*$/', $url)) {
            throw ValidationException::withMessages(['url' => 'Invalid notification action URL.']);
        }

        return CandidateNotification::query()->create([
            'organization_id' => $organizationId,
            'candidate_account_id' => $candidateAccountId,
            'title' => $title,
            'message' => $message,
            'action_url' => $url,
        ]);
    }
}
