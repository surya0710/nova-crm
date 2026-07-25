<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollaborationFeedResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $feed = is_array($this->resource) ? $this->resource : [];

        return [
            'comments' => $feed['comments'] ?? [],
            'progress_updates' => $feed['progress_updates'] ?? [],
            'mentions' => isset($feed['mentions'])
                ? ProjectMentionResource::collection($feed['mentions'])
                : [],
            'activity' => $feed['activity'] ?? [],
            'watchers' => isset($feed['watchers'])
                ? ProjectWatcherResource::collection($feed['watchers'])
                : [],
            'pins' => collect($feed['pins'] ?? [])->map(fn ($pin) => [
                'id' => $pin->id ?? null,
                'source_type' => $pin->source_type ?? null,
                'source_id' => $pin->source_id ?? null,
                'title' => $pin->title ?? null,
                'body' => $pin->body ?? null,
                'sort_order' => $pin->sort_order ?? null,
                'pinned_by' => $pin->pinned_by ?? null,
                'created_at' => isset($pin->created_at) ? $pin->created_at?->toIso8601String() : null,
            ])->values(),
            'shared_links' => $feed['shared_links'] ?? [],
            'items' => collect($feed['items'] ?? [])->map(fn ($item) => [
                'type' => $item['type'] ?? null,
                'id' => $item['id'] ?? null,
                'occurred_at' => isset($item['occurred_at'])
                    ? optional($item['occurred_at'])->toIso8601String()
                    : null,
                'actor_id' => $item['actor_id'] ?? null,
            ])->values(),
        ];
    }
}
