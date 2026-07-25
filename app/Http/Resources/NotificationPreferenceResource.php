<?php

namespace App\Http\Resources;

use App\Models\NotificationPreference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NotificationPreference */
class NotificationPreferenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'user_id' => $this->user_id,
            'in_app_enabled' => $this->in_app_enabled,
            'email_enabled' => $this->email_enabled,
            'digest_enabled' => $this->digest_enabled,
            'digest_frequency' => $this->digest_frequency,
            'muted_projects' => $this->muted_projects ?? [],
            'muted_tasks' => $this->muted_tasks ?? [],
            'event_preferences' => $this->event_preferences ?? [],
            'channels' => $this->channels ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
