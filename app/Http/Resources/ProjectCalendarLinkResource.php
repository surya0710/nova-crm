<?php

namespace App\Http\Resources;

use App\Models\ProjectCalendarLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProjectCalendarLink */
class ProjectCalendarLinkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'project_id' => $this->project_id,
            'task_id' => $this->task_id,
            'milestone_id' => $this->milestone_id,
            'user_id' => $this->user_id,
            'provider' => $this->provider,
            'external_event_id' => $this->external_event_id,
            'event_type' => $this->event_type,
            'title' => $this->title,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'due_date' => $this->due_date?->toDateString(),
            'sync_status' => $this->sync_status,
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
