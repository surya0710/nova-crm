<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CrmActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'contact_id' => $this->contact_id,
            'opportunity_id' => $this->opportunity_id,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'subject' => $this->subject,
            'body' => $this->body,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'due_at' => $this->due_at?->toIso8601String(),
            'duration_minutes' => $this->duration_minutes,
            'direction' => $this->direction,
            'direction_label' => $this->direction_label,
            'outcome' => $this->outcome,
            'outcome_label' => $this->outcome_label,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'priority' => $this->priority,
            'priority_label' => $this->priority_label,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'assigned_to' => $this->assigned_to,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
