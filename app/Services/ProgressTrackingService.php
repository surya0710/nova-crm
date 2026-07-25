<?php

namespace App\Services;

use App\Events\ProgressUpdated;
use App\Models\ProgressUpdate;
use App\Models\Project;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

class ProgressTrackingService
{
    protected ?MetadataEntityFormService $metadataForms = null;

    public function list(Project $project, int $perPage = 15): LengthAwarePaginator
    {
        return $project->progressUpdates()
            ->with(['updater', 'milestone'])
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Project $project, array $data, User $actor): ProgressUpdate
    {
        $this->assertProjectWritable($project);

        $percentage = (int) ($data['progress_percentage'] ?? 0);
        $this->validatePercentage($percentage);

        $summary = trim((string) ($data['summary'] ?? ''));

        if ($summary === '') {
            throw ValidationException::withMessages([
                'summary' => __('A progress summary is required.'),
            ]);
        }

        $update = ProgressUpdate::query()->create([
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'milestone_id' => $data['milestone_id'] ?? null,
            'updated_by' => $actor->id,
            'progress_percentage' => $percentage,
            'summary' => $summary,
            'blockers' => $data['blockers'] ?? null,
            'next_steps' => $data['next_steps'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);

        if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
            $this->persistMetadata($update, $data['custom_fields']);
            $update = $update->fresh();
        } elseif (isset($data['metadata']) && is_array($data['metadata'])) {
            $this->persistMetadata($update, $data['metadata']);
            $update = $update->fresh();
        }

        $project->update(['completion_percentage' => $percentage]);

        $runtime = app(WorkflowRuntimeContext::class);
        event(ProgressUpdated::forModel(
            $project->fresh(),
            [
                'actor_id' => $actor->id,
                'progress_update_id' => $update->id,
                'progress_percentage' => $percentage,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        $this->notifyStakeholders($project, $actor, $update);

        app(WatcherService::class)->notifyWatchers(
            $project,
            'project.progress_updated',
            __(':actor posted a progress update on :project.', [
                'actor' => $actor->name,
                'project' => $project->name,
            ]),
            $actor,
            __('Project progress updated'),
        );

        return $update->fresh(['updater', 'milestone']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProgressUpdate $update, array $data, User $actor): ProgressUpdate
    {
        $project = $update->project;

        if (! $project) {
            throw ValidationException::withMessages([
                'progress_update' => __('The progress update record is invalid.'),
            ]);
        }

        $this->assertProjectWritable($project);

        $payload = [];

        if (array_key_exists('progress_percentage', $data)) {
            $this->validatePercentage((int) $data['progress_percentage']);
            $payload['progress_percentage'] = (int) $data['progress_percentage'];
        }

        if (array_key_exists('summary', $data)) {
            $summary = trim((string) $data['summary']);

            if ($summary === '') {
                throw ValidationException::withMessages([
                    'summary' => __('A progress summary is required.'),
                ]);
            }

            $payload['summary'] = $summary;
        }

        if (array_key_exists('blockers', $data)) {
            $payload['blockers'] = $data['blockers'];
        }

        if (array_key_exists('next_steps', $data)) {
            $payload['next_steps'] = $data['next_steps'];
        }

        if (array_key_exists('milestone_id', $data)) {
            $payload['milestone_id'] = $data['milestone_id'];
        }

        if ($payload !== []) {
            $update->update($payload);
        }

        if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
            $this->persistMetadata($update, $data['custom_fields']);
        } elseif (isset($data['metadata']) && is_array($data['metadata'])) {
            $this->persistMetadata($update, $data['metadata']);
        }

        if (array_key_exists('progress_percentage', $data)) {
            $project->update(['completion_percentage' => (int) $data['progress_percentage']]);
        }

        return $update->fresh(['updater', 'milestone']);
    }

    public function delete(ProgressUpdate $update, User $actor): void
    {
        $project = $update->project;

        if (! $project) {
            throw ValidationException::withMessages([
                'progress_update' => __('The progress update record is invalid.'),
            ]);
        }

        $this->assertProjectWritable($project);

        $update->delete();
    }

    public function validatePercentage(int $pct): void
    {
        if ($pct < 0 || $pct > 100) {
            throw ValidationException::withMessages([
                'progress_percentage' => __('Progress percentage must be between 0 and 100.'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $values
     */
    protected function persistMetadata(ProgressUpdate $update, array $values): void
    {
        $forms = $this->metadataForms();

        if ($forms) {
            $forms->persistValidatedValues($update, $values);
        }
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

    protected function assertProjectWritable(Project $project): void
    {
        if ($project->isReadOnly()) {
            throw ValidationException::withMessages([
                'project' => __('Archived projects are read-only.'),
            ]);
        }
    }

    protected function notifyStakeholders(Project $project, User $actor, ProgressUpdate $update): void
    {
        $project->loadMissing(['owner', 'manager']);

        $recipients = collect([$project->owner, $project->manager])
            ->filter()
            ->unique('id')
            ->reject(fn (User $user) => $user->id === $actor->id);

        foreach ($recipients as $recipient) {
            $recipient->notify(new CrmNotification(
                title: __('Progress update posted'),
                message: __('A progress update was posted on :project (:pct% complete).', [
                    'project' => $project->name,
                    'pct' => $update->progress_percentage,
                ]),
                actionUrl: Route::has('projects.show') ? route('projects.show', $project) : null,
                organizationId: (int) $project->organization_id,
            ));
        }
    }
}
