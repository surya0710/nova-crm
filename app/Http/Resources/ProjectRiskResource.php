<?php

namespace App\Http\Resources;

use App\Models\ProjectRisk;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProjectRisk */
class ProjectRiskResource extends JsonResource
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
            'portfolio_id' => $this->portfolio_id,
            'program_id' => $this->program_id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'probability' => $this->probability,
            'impact' => $this->impact,
            'severity' => $this->severity,
            'mitigation_plan' => $this->mitigation_plan,
            'contingency_plan' => $this->contingency_plan,
            'owner_id' => $this->owner_id,
            'due_date' => $this->due_date?->toDateString(),
            'status' => $this->status,
            'escalated_at' => $this->escalated_at?->toIso8601String(),
            'history' => $this->history,
            'metadata' => $this->metadata,
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner?->id,
                'name' => $this->owner?->name,
            ]),
            'project' => $this->whenLoaded('project', fn () => $this->project
                ? ['id' => $this->project->id, 'name' => $this->project->name]
                : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
