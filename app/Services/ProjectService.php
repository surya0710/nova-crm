<?php

namespace App\Services;

use App\Events\ProjectArchived;
use App\Events\ProjectCreated;
use App\Events\ProjectRestored;
use App\Events\ProjectUpdated;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectLifecycleStage;
use App\Models\ProjectMember;
use App\Models\ProjectStatus;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Services\MetadataEntityFormService;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProjectService
{
    protected ?ProjectDefaultsService $defaults = null;

    protected ?MetadataEntityFormService $metadataForms = null;

    protected ?ProjectMemberService $memberService = null;

    public function __construct(
        ?ProjectDefaultsService $defaults = null,
        ?MetadataEntityFormService $metadataForms = null,
        ?ProjectMemberService $memberService = null,
    ) {
        $this->defaults = $defaults;
        $this->metadataForms = $metadataForms;
        $this->memberService = $memberService;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor, array $metadataValues = []): Project
    {
        return DB::transaction(function () use ($data, $actor, $metadataValues) {
            $organizationId = (int) ($data['organization_id'] ?? app(TenantContext::class)->id());
            $organization = Organization::query()->findOrFail($organizationId);

            if (! ProjectStatus::query()->where('organization_id', $organizationId)->exists()) {
                $this->defaults()->seedAll($organization);
            }

            $this->assertOrgMember($organization, (int) $data['owner_id'], 'owner_id');
            $this->assertOrgMember($organization, (int) $data['manager_id'], 'manager_id');
            $this->validatePriority($data['priority'] ?? 'medium');

            $payload = [
                ...$data,
                'organization_id' => $organizationId,
                'project_number' => $data['project_number'] ?? $this->nextProjectNumber($organization),
                'slug' => $data['slug'] ?? $this->generateSlug($data['name'], $organizationId),
                'status_id' => $data['status_id'] ?? ProjectStatus::query()
                    ->where('organization_id', $organizationId)
                    ->where('is_default', true)
                    ->value('id'),
                'lifecycle_stage_id' => $data['lifecycle_stage_id'] ?? ProjectLifecycleStage::query()
                    ->where('organization_id', $organizationId)
                    ->where('is_default', true)
                    ->value('id'),
                'priority' => $data['priority'] ?? 'medium',
                'is_archived' => false,
            ];

            $project = Project::query()->create($payload);

            $this->syncLeadershipMembership($project, (int) $payload['owner_id'], (int) $payload['manager_id'], $actor);

            $this->persistMetadata($project, $metadataValues);
            $project = $project->fresh(['owner', 'manager', 'status', 'lifecycleStage']);

            event(ProjectCreated::forModel($project, ['actor_id' => $actor->id]));

            $this->notifyLeadership($project, $actor, __('Project created'), __('You were assigned to the new project :project.', [
                'project' => $project->name,
            ]));

            return $project;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Project $project, array $data, User $actor, array $metadataValues = []): Project
    {
        if ($project->isArchived()) {
            throw ValidationException::withMessages([
                'project' => __('Archived projects are read-only.'),
            ]);
        }

        return DB::transaction(function () use ($project, $data, $actor, $metadataValues) {
            $previousOwnerId = (int) $project->owner_id;
            $previousManagerId = (int) $project->manager_id;

            if (array_key_exists('owner_id', $data)) {
                $this->assertOrgMember($project->organization, (int) $data['owner_id'], 'owner_id');
            }

            if (array_key_exists('manager_id', $data)) {
                $this->assertOrgMember($project->organization, (int) $data['manager_id'], 'manager_id');
            }

            if (array_key_exists('priority', $data)) {
                $this->validatePriority($data['priority']);
            }

            if (array_key_exists('name', $data) || array_key_exists('slug', $data)) {
                $name = $data['name'] ?? $project->name;

                if (array_key_exists('slug', $data)) {
                    $slug = Str::slug((string) $data['slug']);
                } else {
                    $slug = $this->generateSlug($name, (int) $project->organization_id, $project->id);
                }

                $data['slug'] = $slug;
            }

            $project->update($data);

            $changes = array_values(array_filter(
                array_keys($data),
                fn (string $attribute) => $project->wasChanged($attribute),
            ));

            $project = $project->fresh(['owner', 'manager', 'status', 'lifecycleStage']);

            if (
                (array_key_exists('owner_id', $data) && (int) $project->owner_id !== $previousOwnerId)
                || (array_key_exists('manager_id', $data) && (int) $project->manager_id !== $previousManagerId)
            ) {
                $this->syncLeadershipMembership(
                    $project,
                    (int) $project->owner_id,
                    (int) $project->manager_id,
                    $actor,
                );

                if ((int) $project->owner_id !== $previousOwnerId && $project->owner) {
                    $this->notifyUser(
                        $project->owner,
                        $actor,
                        __('Project ownership changed'),
                        __('You are now the owner of :project.', ['project' => $project->name]),
                        $project,
                    );
                }

                if ((int) $project->manager_id !== $previousManagerId && $project->manager) {
                    $this->notifyUser(
                        $project->manager,
                        $actor,
                        __('Project manager changed'),
                        __('You are now the manager of :project.', ['project' => $project->name]),
                        $project,
                    );
                }
            }

            $metadataResult = $this->persistMetadata($project, $metadataValues);
            $project = $project->fresh(['owner', 'manager', 'status', 'lifecycleStage']);

            if (($metadataResult['changed'] ?? false) === true) {
                $changes[] = 'metadata';
            }

            if ($changes !== []) {
                $runtime = app(WorkflowRuntimeContext::class);
                event(ProjectUpdated::forModel(
                    $project,
                    ['actor_id' => $actor->id, 'changes' => $changes],
                    causationId: $runtime->causationId,
                    depth: $runtime->causationId ? $runtime->depth + 1 : 0,
                ));
            }

            return $project;
        });
    }

    public function archive(Project $project, User $actor): Project
    {
        if ($project->isArchived()) {
            return $project;
        }

        $updates = ['is_archived' => true];

        $archivedStatus = ProjectStatus::query()
            ->where('organization_id', $project->organization_id)
            ->where('slug', 'archived')
            ->first();

        if ($archivedStatus) {
            $updates['status_id'] = $archivedStatus->id;
        }

        $project->update($updates);
        $project = $project->fresh(['owner', 'manager', 'status']);

        $runtime = app(WorkflowRuntimeContext::class);
        event(ProjectArchived::forModel(
            $project,
            ['actor_id' => $actor->id],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        $this->notifyLeadership($project, $actor, __('Project archived'), __(':project was archived.', [
            'project' => $project->name,
        ]));

        return $project;
    }

    public function restore(Project $project, User $actor): Project
    {
        if (! $project->isArchived()) {
            return $project;
        }

        $project->update(['is_archived' => false]);
        $project = $project->fresh(['owner', 'manager', 'status']);

        $runtime = app(WorkflowRuntimeContext::class);
        event(ProjectRestored::forModel(
            $project,
            ['actor_id' => $actor->id],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        return $project;
    }

    public function delete(Project $project, User $actor): void
    {
        $project->loadMissing('status');

        if ($project->status && $project->status->slug === 'completed') {
            throw ValidationException::withMessages([
                'project' => __('Completed projects cannot be deleted.'),
            ]);
        }

        $project->delete();
    }

    public function nextProjectNumber(Organization|int $organization): string
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;
        $prefix = (string) config('projects.number_prefix', 'PRJ');
        $padding = (int) config('projects.number_padding', 4);

        $latestNumber = Project::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->orderByDesc('id')
            ->value('project_number');

        $next = 1;

        if (is_string($latestNumber) && preg_match('/^'.preg_quote($prefix, '/').'-(\d+)$/', $latestNumber, $matches)) {
            $next = ((int) $matches[1]) + 1;
        } else {
            $next = Project::query()
                ->withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->count() + 1;
        }

        return $prefix.'-'.str_pad((string) $next, $padding, '0', STR_PAD_LEFT);
    }

    public function generateSlug(string $name, int $orgId, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $original = $slug !== '' ? $slug : 'project';
        $candidate = $original;
        $count = 1;

        while ($this->slugExists($orgId, $candidate, $ignoreId)) {
            $candidate = $original.'-'.$count;
            $count++;
        }

        return $candidate;
    }

    protected function slugExists(int $orgId, string $slug, ?int $ignoreId): bool
    {
        $query = Project::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $orgId)
            ->where('slug', $slug);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    protected function syncLeadershipMembership(Project $project, int $ownerId, int $managerId, User $actor): void
    {
        $this->ensureMemberRole($project, $ownerId, 'owner', $actor);

        if ($managerId !== $ownerId) {
            $this->ensureMemberRole($project, $managerId, 'manager', $actor);
        } elseif ($managerId === $ownerId) {
            $member = ProjectMember::query()
                ->where('organization_id', $project->organization_id)
                ->where('project_id', $project->id)
                ->where('user_id', $ownerId)
                ->first();

            if ($member && $member->is_active && $member->project_role !== 'owner') {
                $this->members()->changeRole($member, 'owner', $actor);
            }
        }
    }

    protected function ensureMemberRole(Project $project, int $userId, string $role, User $actor): void
    {
        $member = ProjectMember::query()
            ->where('organization_id', $project->organization_id)
            ->where('project_id', $project->id)
            ->where('user_id', $userId)
            ->first();

        if (! $member) {
            $this->members()->add($project, User::query()->findOrFail($userId), $role, $actor);

            return;
        }

        if (! $member->is_active) {
            $this->members()->add($project, User::query()->findOrFail($userId), $role, $actor);

            return;
        }

        if ($member->project_role !== $role) {
            $this->members()->changeRole($member, $role, $actor);
        }
    }

    /**
     * @return array{changed: bool}|null
     */
    protected function persistMetadata(Project $project, array $metadataValues): ?array
    {
        if ($metadataValues === [] || ! $this->metadataForms()) {
            return null;
        }

        return $this->metadataForms()->persistValidatedValues($project, $metadataValues);
    }

    protected function validatePriority(string $priority): void
    {
        if (! array_key_exists($priority, config('projects.priorities', []))) {
            throw ValidationException::withMessages([
                'priority' => __('Invalid project priority.'),
            ]);
        }
    }

    protected function assertOrgMember(Organization $organization, int $userId, string $field): void
    {
        if (! $organization->users()->whereKey($userId)->exists()) {
            throw ValidationException::withMessages([
                $field => __('The selected user is not an organization member.'),
            ]);
        }
    }

    protected function notifyLeadership(Project $project, User $actor, string $title, string $message): void
    {
        foreach ([$project->owner, $project->manager] as $recipient) {
            $this->notifyUser($recipient, $actor, $title, $message, $project);
        }
    }

    protected function notifyUser(?User $recipient, User $actor, string $title, string $message, Project $project): void
    {
        if (! $recipient || $recipient->id === $actor->id) {
            return;
        }

        $recipient->notify(new CrmNotification(
            title: $title,
            message: $message,
            actionUrl: Route::has('projects.show') ? route('projects.show', $project) : null,
            organizationId: (int) $project->organization_id,
        ));
    }

    protected function defaults(): ProjectDefaultsService
    {
        return $this->defaults ??= app(ProjectDefaultsService::class);
    }

    protected function metadataForms(): ?MetadataEntityFormService
    {
        if ($this->metadataForms !== null) {
            return $this->metadataForms;
        }

        if (! class_exists(MetadataEntityFormService::class)) {
            return null;
        }

        return $this->metadataForms = app(MetadataEntityFormService::class);
    }

    protected function members(): ProjectMemberService
    {
        return $this->memberService ??= app(ProjectMemberService::class);
    }
}
