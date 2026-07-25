<?php

namespace App\Services;

use App\Events\ProjectLifecycleChanged;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectLifecycleStage;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProjectLifecycleService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, array $data): ProjectLifecycleStage
    {
        $payload = $this->validatedPayload($organization, $data);

        $stage = ProjectLifecycleStage::query()->create([
            'organization_id' => $organization->id,
            ...$payload,
        ]);

        if ($stage->is_default) {
            $this->unsetOtherDefaults($stage);
        }

        return $stage->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProjectLifecycleStage $stage, array $data): ProjectLifecycleStage
    {
        $payload = $this->validatedPayload($stage->organization, $data, $stage);

        $stage->update($payload);

        if ($stage->is_default) {
            $this->unsetOtherDefaults($stage);
        }

        return $stage->fresh();
    }

    public function delete(ProjectLifecycleStage $stage): void
    {
        if ($stage->projects()->exists()) {
            throw ValidationException::withMessages([
                'stage' => __('Cannot delete a lifecycle stage that is used by projects.'),
            ]);
        }

        if ($stage->is_default) {
            throw ValidationException::withMessages([
                'stage' => __('The default lifecycle stage cannot be deleted.'),
            ]);
        }

        $stage->delete();
    }

    public function changeStage(Project $project, ProjectLifecycleStage|int $stage, User $actor): Project
    {
        if ($project->isArchived()) {
            throw ValidationException::withMessages([
                'project' => __('Archived projects are read-only.'),
            ]);
        }

        $stageModel = $stage instanceof ProjectLifecycleStage
            ? $stage
            : ProjectLifecycleStage::query()->findOrFail($stage);

        if ((int) $stageModel->organization_id !== (int) $project->organization_id) {
            throw ValidationException::withMessages([
                'lifecycle_stage_id' => __('The lifecycle stage does not belong to this organization.'),
            ]);
        }

        $previousStageId = $project->lifecycle_stage_id;

        if ((int) $previousStageId === (int) $stageModel->id) {
            return $project;
        }

        $project->update(['lifecycle_stage_id' => $stageModel->id]);
        $project = $project->fresh(['lifecycleStage']);

        $runtime = app(WorkflowRuntimeContext::class);
        event(ProjectLifecycleChanged::forModel(
            $project,
            [
                'actor_id' => $actor->id,
                'previous_stage_id' => $previousStageId,
                'stage_id' => $stageModel->id,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        return $project;
    }

    protected function unsetOtherDefaults(ProjectLifecycleStage $stage): void
    {
        ProjectLifecycleStage::query()
            ->where('organization_id', $stage->organization_id)
            ->whereKeyNot($stage->id)
            ->update(['is_default' => false]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function validatedPayload(Organization $organization, array $data, ?ProjectLifecycleStage $ignore = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => __('A lifecycle stage name is required.'),
            ]);
        }

        $slug = trim((string) ($data['slug'] ?? ''));
        $slug = $slug !== '' ? Str::slug($slug) : Str::slug($name);

        if ($slug === '') {
            throw ValidationException::withMessages([
                'slug' => __('A valid lifecycle stage slug is required.'),
            ]);
        }

        $slugQuery = ProjectLifecycleStage::query()
            ->where('organization_id', $organization->id)
            ->where('slug', $slug);

        if ($ignore) {
            $slugQuery->whereKeyNot($ignore->id);
        }

        if ($slugQuery->exists()) {
            throw ValidationException::withMessages([
                'slug' => __('This lifecycle stage slug is already in use.'),
            ]);
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'sequence' => isset($data['sequence']) ? (int) $data['sequence'] : ($ignore?->sequence ?? 0),
            'color' => $data['color'] ?? null,
            'is_default' => array_key_exists('is_default', $data) ? (bool) $data['is_default'] : ($ignore?->is_default ?? false),
        ];
    }
}
