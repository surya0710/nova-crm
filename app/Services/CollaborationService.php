<?php

namespace App\Services;

use App\Events\ProjectCollaborationUpdated;
use App\Models\AuditLog;
use App\Models\ProgressUpdate;
use App\Models\Project;
use App\Models\ProjectCollaborationPin;
use App\Models\ProjectMention;
use App\Models\ProjectWatcher;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CollaborationService
{
    /**
     * Aggregate a collaboration feed for a project.
     *
     * @param  array<string, mixed>  $options
     * @return array{
     *     comments: Collection,
     *     progress_updates: Collection,
     *     mentions: Collection,
     *     activity: Collection,
     *     watchers: Collection,
     *     pins: Collection,
     *     shared_links: Collection,
     *     items: Collection
     * }
     */
    public function feed(Project $project, array $options = []): array
    {
        $limit = (int) ($options['limit'] ?? 50);
        $taskIds = Task::query()
            ->where('organization_id', $project->organization_id)
            ->where(function ($query) use ($project) {
                $query->where('project_id', $project->id)
                    ->orWhere(function ($morph) use ($project) {
                        $morph->where('taskable_type', $project->getMorphClass())
                            ->where('taskable_id', $project->id);
                    });
            })
            ->pluck('id');

        $comments = TaskComment::query()
            ->whereIn('task_id', $taskIds)
            ->with(['user', 'task'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (TaskComment $comment) => [
                'type' => 'comment',
                'id' => $comment->id,
                'occurred_at' => $comment->created_at,
                'actor_id' => $comment->user_id,
                'payload' => $comment,
            ]);

        $progressUpdates = ProgressUpdate::query()
            ->where('project_id', $project->id)
            ->with('updater')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (ProgressUpdate $update) => [
                'type' => 'progress_update',
                'id' => $update->id,
                'occurred_at' => $update->created_at,
                'actor_id' => $update->updated_by,
                'payload' => $update,
            ]);

        $mentions = ProjectMention::query()
            ->where('project_id', $project->id)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (ProjectMention $mention) => [
                'type' => 'mention',
                'id' => $mention->id,
                'occurred_at' => $mention->created_at,
                'actor_id' => $mention->mentioned_by,
                'payload' => $mention,
            ]);

        $activity = AuditLog::query()
            ->where('organization_id', $project->organization_id)
            ->where(function ($query) use ($project, $taskIds) {
                $query->where(function ($projectLogs) use ($project) {
                    $projectLogs->where('auditable_type', $project->getMorphClass())
                        ->where('auditable_id', $project->id);
                })->orWhere(function ($taskLogs) use ($taskIds) {
                    $taskLogs->where('auditable_type', (new Task)->getMorphClass())
                        ->whereIn('auditable_id', $taskIds);
                });
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (AuditLog $log) => [
                'type' => 'activity',
                'id' => $log->id,
                'occurred_at' => $log->created_at,
                'actor_id' => $log->user_id,
                'payload' => $log,
            ]);

        $watchers = ProjectWatcher::query()
            ->where('project_id', $project->id)
            ->latest()
            ->get()
            ->map(fn (ProjectWatcher $watcher) => [
                'type' => 'watcher',
                'id' => $watcher->id,
                'occurred_at' => $watcher->created_at,
                'actor_id' => $watcher->user_id,
                'payload' => $watcher,
            ]);

        $pins = ProjectCollaborationPin::query()
            ->where('project_id', $project->id)
            ->orderBy('sort_order')
            ->latest()
            ->get()
            ->map(fn (ProjectCollaborationPin $pin) => [
                'type' => 'pin',
                'id' => $pin->id,
                'occurred_at' => $pin->created_at,
                'actor_id' => $pin->pinned_by,
                'payload' => $pin,
            ]);

        $sharedLinks = $this->sharedLinks($project, $limit);

        $items = collect()
            ->merge($comments)
            ->merge($progressUpdates)
            ->merge($mentions)
            ->merge($activity)
            ->merge($watchers)
            ->merge($pins)
            ->merge($sharedLinks)
            ->sortByDesc(fn (array $item) => optional($item['occurred_at'])->timestamp ?? 0)
            ->values()
            ->take($limit);

        return [
            'comments' => $comments->pluck('payload'),
            'progress_updates' => $progressUpdates->pluck('payload'),
            'mentions' => $mentions->pluck('payload'),
            'activity' => $activity->pluck('payload'),
            'watchers' => $watchers->pluck('payload'),
            'pins' => $pins->pluck('payload'),
            'shared_links' => $sharedLinks->pluck('payload'),
            'items' => $items,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function pin(Project $project, array $data, User $actor): ProjectCollaborationPin
    {
        $sourceType = trim((string) ($data['source_type'] ?? ''));
        $sourceId = (int) ($data['source_id'] ?? 0);

        if ($sourceType === '' || $sourceId < 1) {
            throw ValidationException::withMessages([
                'source_type' => __('A pin source is required.'),
            ]);
        }

        $pin = ProjectCollaborationPin::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ],
            [
                'organization_id' => $project->organization_id,
                'pinned_by' => $actor->id,
                'title' => $data['title'] ?? null,
                'body' => $data['body'] ?? null,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
            ],
        );

        $this->fireCollaborationUpdated($pin, $actor, 'pinned');

        return $pin->fresh();
    }

    public function unpin(Project $project, string $sourceType, int $sourceId, User $actor): void
    {
        $pin = ProjectCollaborationPin::query()
            ->where('project_id', $project->id)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();

        if (! $pin) {
            return;
        }

        $this->fireCollaborationUpdated($pin, $actor, 'unpinned');
        $pin->delete();
    }

    public function unpinById(ProjectCollaborationPin $pin, User $actor): void
    {
        $this->fireCollaborationUpdated($pin, $actor, 'unpinned');
        $pin->delete();
    }

    protected function fireCollaborationUpdated(ProjectCollaborationPin $pin, User $actor, string $action): void
    {
        $runtime = app(WorkflowRuntimeContext::class);
        event(ProjectCollaborationUpdated::forModel(
            $pin,
            [
                'actor_id' => $actor->id,
                'project_id' => $pin->project_id,
                'action' => $action,
                'source_type' => $pin->source_type,
                'source_id' => $pin->source_id,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));
    }

    /**
     * @return Collection<int, array{type: string, id: mixed, occurred_at: mixed, actor_id: mixed, payload: mixed}>
     */
    protected function sharedLinks(Project $project, int $limit): Collection
    {
        if (! class_exists(\App\Models\ProjectSharedLink::class)) {
            return collect();
        }

        return \App\Models\ProjectSharedLink::query()
            ->where('project_id', $project->id)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($link) => [
                'type' => 'shared_link',
                'id' => $link->id,
                'occurred_at' => $link->created_at,
                'actor_id' => $link->created_by ?? $link->user_id ?? null,
                'payload' => $link,
            ]);
    }
}
