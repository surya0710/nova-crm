<?php

namespace App\Http\Resources;

use App\Models\Task;
use App\Services\MetadataApiPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Task */
class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $metadata = is_array($this->metadata) ? $this->metadata : [];

        return [
            'id' => $this->id,
            'task_number' => $this->task_number,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'priority' => $this->priority,
            'priority_label' => $this->priority_label,
            'status_id' => $this->status_id,
            'task_status' => $this->whenLoaded('taskStatus', fn () => new TaskStatusResource($this->taskStatus)),
            'priority_id' => $this->priority_id,
            'task_priority' => $this->whenLoaded('taskPriority', fn () => new TaskPriorityResource($this->taskPriority)),
            'project_id' => $this->project_id,
            'project' => $this->whenLoaded('project', fn () => [
                'id' => $this->project?->id,
                'name' => $this->project?->name,
                'project_number' => $this->project?->project_number,
            ]),
            'parent_task_id' => $this->parent_task_id,
            'milestone_id' => $this->milestone_id,
            'due_at' => $this->due_at?->toIso8601String(),
            'start_date' => $this->start_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'estimated_hours' => $this->estimated_hours,
            'actual_hours' => $this->actual_hours,
            'completion_percentage' => $this->completion_percentage,
            'sort_order' => $this->sort_order,
            'is_archived' => $this->is_archived,
            'assigned_to' => $this->assigned_to,
            'assignee' => $this->whenLoaded('assignee', fn () => [
                'id' => $this->assignee?->id,
                'name' => $this->assignee?->name,
            ]),
            'assigned_by' => $this->assigned_by,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ]),
            'taskable_type' => $this->taskable_type,
            'taskable_id' => $this->taskable_id,
            'checklists' => TaskChecklistResource::collection($this->whenLoaded('checklists')),
            'comments' => TaskCommentResource::collection($this->whenLoaded('comments')),
            'attachments' => TaskAttachmentResource::collection($this->whenLoaded('attachments')),
            'time_logs' => TaskTimeLogResource::collection($this->whenLoaded('timeLogs')),
            'custom_fields' => class_exists(MetadataApiPresenter::class)
                ? app(MetadataApiPresenter::class)->customFieldsFor(
                    (int) $this->organization_id,
                    'task',
                    $metadata,
                )
                : $metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
