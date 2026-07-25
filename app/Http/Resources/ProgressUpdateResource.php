<?php

namespace App\Http\Resources;

use App\Models\ProgressUpdate;
use App\Services\MetadataApiPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProgressUpdate */
class ProgressUpdateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $metadata = is_array($this->metadata) ? $this->metadata : [];

        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'milestone_id' => $this->milestone_id,
            'progress_percentage' => $this->progress_percentage,
            'summary' => $this->summary,
            'blockers' => $this->blockers,
            'next_steps' => $this->next_steps,
            'metadata' => $metadata,
            'custom_fields' => class_exists(MetadataApiPresenter::class)
                ? app(MetadataApiPresenter::class)->customFieldsFor(
                    (int) $this->organization_id,
                    'progress_update',
                    $metadata,
                )
                : $metadata,
            'updater' => $this->whenLoaded('updater', fn () => [
                'id' => $this->updater?->id,
                'name' => $this->updater?->name,
            ]),
            'milestone' => $this->whenLoaded('milestone', fn () => $this->milestone
                ? new ProjectMilestoneResource($this->milestone)
                : null),
            'project' => $this->whenLoaded('project', fn () => new ProjectResource($this->project)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
