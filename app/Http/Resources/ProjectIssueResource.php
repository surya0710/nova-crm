<?php

namespace App\Http\Resources;

use App\Models\ProjectIssue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProjectIssue */
class ProjectIssueResource extends JsonResource
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
            'priority' => $this->priority,
            'severity' => $this->severity,
            'owner_id' => $this->owner_id,
            'status' => $this->status,
            'resolution' => $this->resolution,
            'root_cause' => $this->root_cause,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'due_date' => $this->due_date?->toDateString(),
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
