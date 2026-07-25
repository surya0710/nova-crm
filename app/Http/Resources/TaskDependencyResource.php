<?php

namespace App\Http\Resources;

use App\Models\TaskDependency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TaskDependency */
class TaskDependencyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'predecessor_task_id' => $this->predecessor_task_id,
            'successor_task_id' => $this->successor_task_id,
            'dependency_type' => $this->dependency_type,
            'dependency_type_label' => $this->dependency_type_label,
            'predecessor' => $this->whenLoaded('predecessor', fn () => [
                'id' => $this->predecessor?->id,
                'title' => $this->predecessor?->title,
                'task_number' => $this->predecessor?->task_number,
            ]),
            'successor' => $this->whenLoaded('successor', fn () => [
                'id' => $this->successor?->id,
                'title' => $this->successor?->title,
                'task_number' => $this->successor?->task_number,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
