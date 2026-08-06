<?php

namespace App\Http\Resources;

use App\Models\Project;
use App\Services\MetadataApiPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Project */
class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $metadata = is_array($this->metadata) ? $this->metadata : [];

        return [
            'id' => $this->id,
            'project_number' => $this->project_number,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'objective' => $this->objective,
            'priority' => $this->priority,
            'priority_label' => $this->priority_label,
            'start_date' => $this->start_date?->toDateString(),
            'planned_end_date' => $this->planned_end_date?->toDateString(),
            'actual_end_date' => $this->actual_end_date?->toDateString(),
            'estimated_budget' => $this->estimated_budget,
            'actual_budget' => $this->actual_budget,
            'completion_percentage' => $this->completion_percentage,
            'is_archived' => $this->is_archived,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => new ProjectCategoryResource($this->category)),
            'project_type_id' => $this->project_type_id,
            'project_type' => $this->whenLoaded('projectType', fn () => new ProjectTypeResource($this->projectType)),
            'status_id' => $this->status_id,
            'status' => $this->whenLoaded('status', fn () => new ProjectStatusResource($this->status)),
            'lifecycle_stage_id' => $this->lifecycle_stage_id,
            'lifecycle_stage' => $this->whenLoaded('lifecycleStage', fn () => new ProjectLifecycleStageResource($this->lifecycleStage)),
            'client_id' => $this->client_id,
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client?->id,
                'name' => $this->client?->name,
                'company' => $this->client?->company,
            ]),
            'owner_id' => $this->owner_id,
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner?->id,
                'name' => $this->owner?->name,
            ]),
            'manager_id' => $this->manager_id,
            'manager' => $this->whenLoaded('manager', fn () => [
                'id' => $this->manager?->id,
                'name' => $this->manager?->name,
            ]),
            'department_id' => $this->department_id,
            'department' => $this->whenLoaded('department', fn () => [
                'id' => $this->department?->id,
                'name' => $this->department?->name,
            ]),
            'members' => ProjectMemberResource::collection($this->whenLoaded('members')),
            'milestones' => ProjectMilestoneResource::collection($this->whenLoaded('milestones')),
            'custom_fields' => class_exists(MetadataApiPresenter::class)
                ? app(MetadataApiPresenter::class)->customFieldsFor(
                    (int) $this->organization_id,
                    'project',
                    $metadata,
                )
                : $metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
