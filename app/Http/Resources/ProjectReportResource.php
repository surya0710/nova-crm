<?php

namespace App\Http\Resources;

use App\Models\ProjectReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProjectReport */
class ProjectReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'report_type' => $this->report_type,
            'report_type_label' => $this->report_type_label,
            'filters' => $this->filters,
            'storage_path' => $this->storage_path,
            'generated_at' => $this->generated_at?->toIso8601String(),
            'generator' => $this->whenLoaded('generator', fn () => [
                'id' => $this->generator?->id,
                'name' => $this->generator?->name,
            ]),
            'project' => $this->whenLoaded('project', fn () => $this->project
                ? new ProjectResource($this->project)
                : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
