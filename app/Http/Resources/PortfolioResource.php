<?php

namespace App\Http\Resources;

use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Portfolio */
class PortfolioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'owner_id' => $this->owner_id,
            'status' => $this->status,
            'color' => $this->color,
            'start_date' => $this->start_date?->toDateString(),
            'target_end_date' => $this->target_end_date?->toDateString(),
            'archived_at' => $this->archived_at?->toIso8601String(),
            'metadata' => $this->metadata,
            'settings' => $this->settings,
            'projects_count' => $this->whenCounted('projects'),
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner?->id,
                'name' => $this->owner?->name,
            ]),
            'projects' => ProjectResource::collection($this->whenLoaded('projects')),
            'programs' => ProgramResource::collection($this->whenLoaded('programs')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
