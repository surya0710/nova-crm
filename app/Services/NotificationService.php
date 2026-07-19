<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use App\Notifications\CrmNotification;
use Illuminate\Validation\ValidationException;

class NotificationService
{
    public function send(int $organizationId, int $userId, string $title, string $message, ?string $url = null): void
    {
        if (trim($title) === '' || trim($message) === '') {
            throw ValidationException::withMessages(['notification' => 'Notification title and message are required.']);
        }
        if (mb_strlen($title) > 255 || mb_strlen($message) > 5000) {
            throw ValidationException::withMessages(['notification' => 'Notification title or message exceeds the allowed length.']);
        }
        self::validateActionUrl($url);

        $organization = Organization::query()->findOrFail($organizationId);
        $user = $organization->users()->whereKey($userId)->first();

        if (! $user instanceof User) {
            throw ValidationException::withMessages(['user_id' => 'The recipient is not an organization member.']);
        }

        $user->notify(new CrmNotification($title, $message, $url, $organizationId));
    }

    public static function validateActionUrl(?string $url, string $field = 'action_url'): void
    {
        if ($url === null || $url === '') {
            return;
        }

        if (strlen($url) > 2048 || preg_match('/^\/(?!\/)(?!.*\\\\)[^\r\n]*$/', $url) !== 1) {
            throw ValidationException::withMessages([
                $field => 'The action URL must be a safe application-relative path beginning with one slash.',
            ]);
        }
    }
}
