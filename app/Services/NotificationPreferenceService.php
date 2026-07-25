<?php

namespace App\Services;

use App\Events\NotificationPreferenceUpdated;
use App\Models\NotificationPreference;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NotificationPreferenceService
{
    public function getOrCreate(User $user, ?int $organizationId = null): NotificationPreference
    {
        $organizationId ??= app(TenantContext::class)->id();

        if (! $organizationId) {
            throw ValidationException::withMessages([
                'organization' => __('An organization context is required.'),
            ]);
        }

        return NotificationPreference::query()->firstOrCreate(
            [
                'organization_id' => $organizationId,
                'user_id' => $user->id,
            ],
            [
                'in_app_enabled' => true,
                'email_enabled' => true,
                'digest_enabled' => false,
                'digest_frequency' => 'daily',
                'muted_projects' => [],
                'muted_tasks' => [],
                'event_preferences' => [],
                'channels' => [],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data, ?int $organizationId = null, ?User $actor = null): NotificationPreference
    {
        $actor ??= $user;

        return DB::transaction(function () use ($user, $data, $organizationId, $actor) {
            $preference = $this->getOrCreate($user, $organizationId);

            $payload = collect($data)->only([
                'in_app_enabled',
                'email_enabled',
                'digest_enabled',
                'digest_frequency',
                'muted_projects',
                'muted_tasks',
                'event_preferences',
                'channels',
            ])->all();

            foreach (['in_app_enabled', 'email_enabled', 'digest_enabled'] as $boolField) {
                if (array_key_exists($boolField, $payload)) {
                    $payload[$boolField] = (bool) $payload[$boolField];
                }
            }

            foreach (['muted_projects', 'muted_tasks'] as $listField) {
                if (array_key_exists($listField, $payload) && is_array($payload[$listField])) {
                    $payload[$listField] = array_values(array_unique(array_map('intval', $payload[$listField])));
                }
            }

            if ($payload !== []) {
                $preference->update($payload);
            }

            $preference = $preference->fresh();

            $runtime = app(WorkflowRuntimeContext::class);
            event(NotificationPreferenceUpdated::forModel(
                $preference,
                ['actor_id' => $actor->id, 'changes' => array_keys($payload)],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            return $preference;
        });
    }

    public function isMuted(User $user, Project|Task $subject): bool
    {
        $preference = NotificationPreference::query()
            ->where('organization_id', $subject->organization_id)
            ->where('user_id', $user->id)
            ->first();

        if (! $preference) {
            return false;
        }

        if ($subject instanceof Project) {
            return in_array((int) $subject->id, $this->idList($preference->muted_projects), true);
        }

        if (in_array((int) $subject->id, $this->idList($preference->muted_tasks), true)) {
            return true;
        }

        if ($subject->project_id) {
            return in_array((int) $subject->project_id, $this->idList($preference->muted_projects), true);
        }

        return false;
    }

    public function shouldNotify(User $user, string $eventType, ?int $organizationId = null): bool
    {
        $organizationId ??= app(TenantContext::class)->id();

        if (! $organizationId) {
            return true;
        }

        $preference = NotificationPreference::query()
            ->where('organization_id', $organizationId)
            ->where('user_id', $user->id)
            ->first();

        if (! $preference) {
            return true;
        }

        if (! $preference->in_app_enabled && ! $preference->email_enabled) {
            return false;
        }

        $events = is_array($preference->event_preferences) ? $preference->event_preferences : [];

        if ($events === []) {
            return true;
        }

        if (array_key_exists($eventType, $events)) {
            return (bool) $events[$eventType];
        }

        // Support nested map like ['project.delayed' => false] or ['project' => ['delayed' => false]]
        $parts = explode('.', $eventType);
        $cursor = $events;
        foreach ($parts as $part) {
            if (! is_array($cursor) || ! array_key_exists($part, $cursor)) {
                return true;
            }
            $cursor = $cursor[$part];
        }

        return (bool) $cursor;
    }

    /**
     * @param  mixed  $value
     * @return list<int>
     */
    protected function idList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $value)));
    }
}
