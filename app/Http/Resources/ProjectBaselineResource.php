<?php

namespace App\Http\Resources;

use App\Models\ProjectBaseline;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProjectBaseline */
class ProjectBaselineResource extends JsonResource
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
            'version' => $this->version,
            'name' => $this->name,
            'scope_snapshot' => $this->scope_snapshot,
            'schedule_snapshot' => $this->schedule_snapshot,
            'budget_snapshot' => $this->budget_snapshot,
            'progress_snapshot' => $this->progress_snapshot,
            'created_by' => $this->created_by,
            'notes' => $this->notes,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
