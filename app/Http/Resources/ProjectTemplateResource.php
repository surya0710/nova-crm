<?php

namespace App\Http\Resources;

use App\Models\ProjectTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProjectTemplate */
class ProjectTemplateResource extends JsonResource
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
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->category,
            'industry' => $this->industry,
            'department_id' => $this->department_id,
            'source_project_id' => $this->source_project_id,
            'created_by' => $this->created_by,
            'is_system' => $this->is_system,
            'is_favorite' => $this->is_favorite,
            'version' => $this->version,
            'usage_count' => $this->usage_count,
            'defaults' => $this->defaults,
            'metadata' => $this->metadata,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ]),
            'department' => $this->whenLoaded('department', fn () => [
                'id' => $this->department?->id,
                'name' => $this->department?->name,
            ]),
            'template_milestones_count' => $this->whenCounted('templateMilestones'),
            'template_tasks_count' => $this->whenCounted('templateTasks'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
