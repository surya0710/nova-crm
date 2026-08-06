<?php

namespace App\Http\Resources;

use App\Models\ProjectMention;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProjectMention */
class ProjectMentionResource extends JsonResource
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
            'task_id' => $this->task_id,
            'mentioned_user_id' => $this->mentioned_user_id,
            'mentioned_by' => $this->mentioned_by,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'excerpt' => $this->excerpt,
            'read_at' => $this->read_at?->toIso8601String(),
            'mentioned_user' => $this->whenLoaded('mentionedUser', fn () => [
                'id' => $this->mentionedUser?->id,
                'name' => $this->mentionedUser?->name,
            ]),
            'mentioned_by_user' => $this->whenLoaded('mentionedBy', fn () => [
                'id' => $this->mentionedBy?->id,
                'name' => $this->mentionedBy?->name,
            ]),
            'project' => $this->whenLoaded('project', fn () => [
                'id' => $this->project?->id,
                'name' => $this->project?->name,
            ]),
            'task' => $this->whenLoaded('task', fn () => [
                'id' => $this->task?->id,
                'title' => $this->task?->title,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
