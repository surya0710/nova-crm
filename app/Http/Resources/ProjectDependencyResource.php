<?php

namespace App\Http\Resources;

use App\Models\ProjectDependency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProjectDependency */
class ProjectDependencyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'predecessor_project_id' => $this->predecessor_project_id,
            'successor_project_id' => $this->successor_project_id,
            'dependency_type' => $this->dependency_type,
            'lag_days' => $this->lag_days,
            'notes' => $this->notes,
            'predecessor' => $this->whenLoaded('predecessor', fn () => $this->predecessor
                ? ['id' => $this->predecessor->id, 'name' => $this->predecessor->name]
                : null),
            'successor' => $this->whenLoaded('successor', fn () => $this->successor
                ? ['id' => $this->successor->id, 'name' => $this->successor->name]
                : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
