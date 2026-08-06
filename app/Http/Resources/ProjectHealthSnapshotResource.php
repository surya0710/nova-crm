<?php

namespace App\Http\Resources;

use App\Models\ProjectHealthSnapshot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProjectHealthSnapshot */
class ProjectHealthSnapshotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'health_status' => $this->health_status,
            'health_status_label' => $this->health_status_label,
            'completion_percentage' => $this->completion_percentage,
            'schedule_variance' => $this->schedule_variance,
            'budget_variance' => $this->budget_variance,
            'estimated_completion_date' => $this->estimated_completion_date?->toDateString(),
            'calculated_at' => $this->calculated_at?->toIso8601String(),
            'metadata' => $this->metadata,
            'project' => $this->whenLoaded('project', fn () => new ProjectResource($this->project)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
