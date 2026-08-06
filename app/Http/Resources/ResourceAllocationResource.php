<?php

namespace App\Http\Resources;

use App\Models\ResourceAllocation;
use App\Services\MetadataApiPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ResourceAllocation */
class ResourceAllocationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $metadata = is_array($this->metadata) ? $this->metadata : [];

        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'employee_id' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee?->id,
                'name' => $this->employee?->full_name,
            ]),
            'project_id' => $this->project_id,
            'project' => $this->whenLoaded('project', fn () => [
                'id' => $this->project?->id,
                'name' => $this->project?->name,
                'project_number' => $this->project?->project_number,
            ]),
            'task_id' => $this->task_id,
            'task' => $this->whenLoaded('task', fn () => [
                'id' => $this->task?->id,
                'title' => $this->task?->title,
                'task_number' => $this->task?->task_number,
            ]),
            'allocation_type' => $this->allocation_type,
            'allocation_type_label' => $this->allocation_type_label,
            'allocation_percentage' => $this->allocation_percentage,
            'planned_hours' => $this->planned_hours,
            'planned_start_date' => $this->planned_start_date?->toDateString(),
            'planned_end_date' => $this->planned_end_date?->toDateString(),
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ]),
            'custom_fields' => class_exists(MetadataApiPresenter::class)
                ? app(MetadataApiPresenter::class)->customFieldsFor(
                    (int) $this->organization_id,
                    'resource_allocation',
                    $metadata,
                )
                : $metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
