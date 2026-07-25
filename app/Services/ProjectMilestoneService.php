<?php

namespace App\Services;

use App\Events\ProjectMilestoneCompleted;
use App\Events\ProjectMilestoneCreated;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

class ProjectMilestoneService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Project $project, array $data, User $actor): ProjectMilestone
    {
        $this->assertProjectWritable($project);

        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => __('A milestone name is required.'),
            ]);
        }

        $this->validateStatus($data['status'] ?? 'pending');
        $this->validateDueDate($data['due_date'] ?? null);

        $sequence = isset($data['sequence'])
            ? (int) $data['sequence']
            : ((int) $project->milestones()->max('sequence')) + 1;

        $milestone = ProjectMilestone::query()->create([
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'name' => $name,
            'description' => $data['description'] ?? null,
            'sequence' => $sequence,
            'due_date' => $data['due_date'] ?? null,
            'status' => $data['status'] ?? 'pending',
        ]);

        $milestone = $milestone->fresh();

        $runtime = app(WorkflowRuntimeContext::class);
        event(ProjectMilestoneCreated::forModel(
            $milestone,
            [
                'actor_id' => $actor->id,
                'project_id' => $project->id,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        return $milestone;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProjectMilestone $milestone, array $data, User $actor): ProjectMilestone
    {
        $project = $milestone->project;

        if (! $project) {
            throw ValidationException::withMessages([
                'milestone' => __('The milestone record is invalid.'),
            ]);
        }

        $this->assertProjectWritable($project);

        $payload = [];

        if (array_key_exists('name', $data)) {
            $name = trim((string) $data['name']);

            if ($name === '') {
                throw ValidationException::withMessages([
                    'name' => __('A milestone name is required.'),
                ]);
            }

            $payload['name'] = $name;
        }

        if (array_key_exists('description', $data)) {
            $payload['description'] = $data['description'];
        }

        if (array_key_exists('sequence', $data)) {
            $payload['sequence'] = (int) $data['sequence'];
        }

        if (array_key_exists('due_date', $data)) {
            $this->validateDueDate($data['due_date']);
            $payload['due_date'] = $data['due_date'];
        }

        if (array_key_exists('status', $data)) {
            $this->validateStatus($data['status']);
            $payload['status'] = $data['status'];
        }

        if ($payload !== []) {
            $milestone->update($payload);
        }

        return $milestone->fresh();
    }

    public function delete(ProjectMilestone $milestone, User $actor): void
    {
        $project = $milestone->project;

        if (! $project) {
            throw ValidationException::withMessages([
                'milestone' => __('The milestone record is invalid.'),
            ]);
        }

        $this->assertProjectWritable($project);

        $milestone->delete();
        $this->resequence($project);
    }

    public function complete(ProjectMilestone $milestone, User $actor): ProjectMilestone
    {
        $project = $milestone->project;

        if (! $project) {
            throw ValidationException::withMessages([
                'milestone' => __('The milestone record is invalid.'),
            ]);
        }

        $this->assertProjectWritable($project);

        if ($milestone->isCompleted()) {
            return $milestone;
        }

        $milestone->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $milestone = $milestone->fresh();
        $project = $project->fresh(['owner', 'manager']);

        $runtime = app(WorkflowRuntimeContext::class);
        event(ProjectMilestoneCompleted::forModel(
            $milestone,
            [
                'actor_id' => $actor->id,
                'project_id' => $project->id,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        foreach ([$project->owner, $project->manager] as $recipient) {
            if (! $recipient || $recipient->id === $actor->id) {
                continue;
            }

            $recipient->notify(new CrmNotification(
                title: __('Milestone completed'),
                message: __(':milestone was completed on :project.', [
                    'milestone' => $milestone->name,
                    'project' => $project->name,
                ]),
                actionUrl: Route::has('projects.show') ? route('projects.show', $project) : null,
                organizationId: (int) $project->organization_id,
            ));
        }

        return $milestone;
    }

    /**
     * @param  array<int, int|string>  $orderedIds
     */
    public function reorder(Project $project, array $orderedIds): void
    {
        $this->assertProjectWritable($project);

        $milestones = ProjectMilestone::query()
            ->where('project_id', $project->id)
            ->whereIn('id', $orderedIds)
            ->get()
            ->keyBy('id');

        if ($milestones->count() !== count($orderedIds)) {
            throw ValidationException::withMessages([
                'ordered_ids' => __('One or more milestones do not belong to this project.'),
            ]);
        }

        foreach (array_values($orderedIds) as $index => $milestoneId) {
            $milestones[(int) $milestoneId]->update(['sequence' => $index + 1]);
        }
    }

    protected function resequence(Project $project): void
    {
        ProjectMilestone::query()
            ->where('project_id', $project->id)
            ->orderBy('sequence')
            ->orderBy('id')
            ->get()
            ->values()
            ->each(function (ProjectMilestone $milestone, int $index): void {
                $milestone->update(['sequence' => $index + 1]);
            });
    }

    protected function validateStatus(string $status): void
    {
        if (! array_key_exists($status, config('projects.milestone_statuses', []))) {
            throw ValidationException::withMessages([
                'status' => __('Invalid milestone status.'),
            ]);
        }
    }

    protected function validateDueDate(mixed $dueDate): void
    {
        if ($dueDate === null || $dueDate === '') {
            return;
        }

        try {
            $parsed = \Illuminate\Support\Carbon::parse($dueDate);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'due_date' => __('The due date is invalid.'),
            ]);
        }

        if (! $parsed->isValid()) {
            throw ValidationException::withMessages([
                'due_date' => __('The due date is invalid.'),
            ]);
        }
    }

    protected function assertProjectWritable(Project $project): void
    {
        if ($project->isArchived()) {
            throw ValidationException::withMessages([
                'project' => __('Archived projects are read-only.'),
            ]);
        }
    }
}
