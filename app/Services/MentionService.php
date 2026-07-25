<?php

namespace App\Services;

use App\Events\CommentMentioned;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMention;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class MentionService
{
    /**
     * @return list<string>
     */
    public function extractMentions(string $body): array
    {
        preg_match_all('/@([A-Za-z0-9._-]+)/', $body, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * @param  list<string>  $usernames
     * @return Collection<int, User>
     */
    public function resolveUsers(int $organizationId, array $usernames): Collection
    {
        $usernames = array_values(array_unique(array_filter(array_map(
            fn ($name) => Str::lower(trim((string) $name)),
            $usernames,
        ))));

        if ($usernames === []) {
            return new Collection;
        }

        return User::query()
            ->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organizationId))
            ->where(function (Builder $query) use ($usernames) {
                $query->whereIn(DB::raw('LOWER(email)'), $usernames);

                foreach ($usernames as $username) {
                    $query->orWhereRaw('LOWER(email) like ?', [$username.'@%'])
                        ->orWhereRaw('LOWER(name) = ?', [$username])
                        ->orWhereRaw('LOWER(REPLACE(name, \' \', \'.\')) = ?', [$username])
                        ->orWhereRaw('LOWER(REPLACE(name, \' \', \'_\')) = ?', [$username]);
                }
            })
            ->get()
            ->unique('id')
            ->values();
    }

    /**
     * @return Collection<int, ProjectMention>
     */
    public function recordMentions(
        Organization|int $organization,
        ?Project $project,
        ?Task $task,
        string $sourceType,
        int|string $sourceId,
        string $body,
        User $actor,
    ): Collection {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;
        $usernames = $this->extractMentions($body);

        if ($usernames === []) {
            return new Collection;
        }

        $users = $this->resolveUsers($organizationId, $usernames);
        $mentions = new Collection;
        $excerpt = Str::limit(trim($body), 240);
        $runtime = app(WorkflowRuntimeContext::class);

        foreach ($users as $user) {
            if ((int) $user->id === (int) $actor->id) {
                continue;
            }

            $mention = ProjectMention::query()->create([
                'organization_id' => $organizationId,
                'project_id' => $project?->id ?? $task?->project_id,
                'task_id' => $task?->id,
                'mentioned_user_id' => $user->id,
                'mentioned_by' => $actor->id,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'excerpt' => $excerpt,
            ]);

            event(CommentMentioned::forModel(
                $mention,
                [
                    'actor_id' => $actor->id,
                    'mentioned_user_id' => $user->id,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'project_id' => $mention->project_id,
                    'task_id' => $mention->task_id,
                ],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            $user->notify(new CrmNotification(
                title: __('You were mentioned'),
                message: __(':actor mentioned you.', ['actor' => $actor->name]),
                actionUrl: $this->actionUrl($project, $task),
                organizationId: $organizationId,
            ));

            $mentions->push($mention);
        }

        return $mentions;
    }

    public function highlightMentions(string $body, ?int $organizationId = null): string
    {
        $escaped = e($body);
        $usernames = $this->extractMentions($body);

        if ($usernames === []) {
            return nl2br($escaped);
        }

        $resolved = $organizationId
            ? $this->resolveUsers($organizationId, $usernames)->keyBy(fn (User $user) => Str::lower($user->name))
            : collect();

        $highlighted = preg_replace_callback(
            '/@([A-Za-z0-9._-]+)/',
            function (array $matches) use ($resolved) {
                $handle = $matches[1];
                $exists = $resolved->isEmpty()
                    || $resolved->has(Str::lower($handle))
                    || $resolved->contains(fn (User $user) => Str::startsWith(Str::lower($user->email), Str::lower($handle).'@')
                        || Str::lower($user->email) === Str::lower($handle));

                $class = $exists ? 'mention mention-resolved' : 'mention';

                return '<span class="'.$class.'">@'.e($handle).'</span>';
            },
            $escaped,
        );

        return nl2br($highlighted ?? $escaped);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, ProjectMention>
     */
    public function historyForUser(User $user, ?int $organizationId = null, array $filters = []): Collection
    {
        $organizationId ??= app(TenantContext::class)->id();

        $query = ProjectMention::query()
            ->where('mentioned_user_id', $user->id)
            ->when($organizationId, fn (Builder $q) => $q->where('organization_id', $organizationId))
            ->latest();

        if (! empty($filters['project_id'])) {
            $query->where('project_id', (int) $filters['project_id']);
        }

        if (! empty($filters['unread'])) {
            $query->whereNull('read_at');
        }

        if (! empty($filters['limit'])) {
            $query->limit((int) $filters['limit']);
        }

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, ProjectMention>
     */
    public function historyForProject(Project $project, array $filters = []): Collection
    {
        $query = ProjectMention::query()
            ->where('project_id', $project->id)
            ->latest();

        if (! empty($filters['user_id'])) {
            $query->where('mentioned_user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['limit'])) {
            $query->limit((int) $filters['limit']);
        }

        return $query->get();
    }

    protected function actionUrl(?Project $project, ?Task $task): ?string
    {
        if ($task && Route::has('tasks.show')) {
            return route('tasks.show', $task);
        }

        if ($project && Route::has('projects.show')) {
            return route('projects.show', $project);
        }

        return null;
    }
}
