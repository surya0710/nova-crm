<?php

namespace App\Http\Resources;

use App\Models\TaskRecurrence;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TaskRecurrence */
class TaskRecurrenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'task_id' => $this->task_id,
            'recurrence_type' => $this->recurrence_type,
            'interval' => $this->interval,
            'days_of_week' => $this->days_of_week,
            'end_type' => $this->end_type,
            'end_date' => $this->end_date?->toDateString(),
            'occurrences' => $this->occurrences,
            'generated_count' => $this->generated_count,
            'skip_holidays' => $this->skip_holidays,
            'copy_attachments' => $this->copy_attachments,
            'is_active' => $this->is_active,
            'last_generated_at' => $this->last_generated_at?->toIso8601String(),
            'next_run_at' => $this->next_run_at?->toIso8601String(),
            'settings' => $this->settings,
            'task' => $this->whenLoaded('task', fn () => [
                'id' => $this->task?->id,
                'title' => $this->task?->title,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
