<?php

namespace App\Services;

use App\Events\DeliverableCreated;
use App\Events\DeliverableSubmitted;
use App\Models\Deliverable;
use App\Models\DeliverableVersion;
use App\Models\Project;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DeliverableService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected PortalNotificationService $portalNotifications,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Project $project, array $data, User $actor): Deliverable
    {
        $deliverable = Deliverable::query()->create([
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'milestone_id' => $data['milestone_id'] ?? null,
            'task_id' => $data['task_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'due_date' => $data['due_date'] ?? null,
            'completion_percentage' => (int) ($data['completion_percentage'] ?? 0),
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
            'metadata' => $data['metadata'] ?? null,
        ]);

        $runtime = app(WorkflowRuntimeContext::class);
        event(DeliverableCreated::forModel(
            $deliverable,
            ['actor_id' => $actor->id],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        $this->auditLogger->log($deliverable, 'created', [], $actor);

        return $deliverable->fresh(['project', 'milestone', 'versions']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Deliverable $deliverable, array $data, User $actor): Deliverable
    {
        $this->assertMutable($deliverable);

        $deliverable->update([
            'milestone_id' => array_key_exists('milestone_id', $data) ? $data['milestone_id'] : $deliverable->milestone_id,
            'task_id' => array_key_exists('task_id', $data) ? $data['task_id'] : $deliverable->task_id,
            'title' => $data['title'] ?? $deliverable->title,
            'description' => array_key_exists('description', $data) ? $data['description'] : $deliverable->description,
            'due_date' => array_key_exists('due_date', $data) ? $data['due_date'] : $deliverable->due_date,
            'completion_percentage' => array_key_exists('completion_percentage', $data)
                ? (int) $data['completion_percentage']
                : $deliverable->completion_percentage,
            'updated_by' => $actor->id,
            'metadata' => array_key_exists('metadata', $data) ? $data['metadata'] : $deliverable->metadata,
        ]);

        $this->auditLogger->log($deliverable, 'updated', [], $actor);

        return $deliverable->fresh(['project', 'milestone', 'versions']);
    }

    public function submit(Deliverable $deliverable, User $actor, ?UploadedFile $file = null, ?string $notes = null): Deliverable
    {
        if (! in_array($deliverable->status, ['draft', 'revised', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'status' => __('Only draft, revised, or rejected deliverables can be submitted.'),
            ]);
        }

        return DB::transaction(function () use ($deliverable, $actor, $file, $notes): Deliverable {
            if ($file) {
                $this->addVersion($deliverable, $file, $actor, null, $notes);
            }

            $deliverable->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $runtime = app(WorkflowRuntimeContext::class);
            event(DeliverableSubmitted::forModel(
                $deliverable,
                ['actor_id' => $actor->id],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));

            $this->auditLogger->log($deliverable, 'submitted', [], $actor);

            $this->portalNotifications->notifyProjectClients(
                $deliverable->organization,
                $deliverable->project_id,
                __('Deliverable submitted'),
                __('A deliverable is ready for review: :title', ['title' => $deliverable->title]),
            );

            return $deliverable->fresh(['versions']);
        });
    }

    public function markClientReview(Deliverable $deliverable, User $actor): Deliverable
    {
        if ($deliverable->status !== 'submitted') {
            throw ValidationException::withMessages([
                'status' => __('Deliverable must be submitted before client review.'),
            ]);
        }

        $deliverable->update([
            'status' => 'client_review',
            'updated_by' => $actor->id,
        ]);

        $this->portalNotifications->notifyProjectClients(
            $deliverable->organization,
            $deliverable->project_id,
            __('Approval requested'),
            __('Please review deliverable: :title', ['title' => $deliverable->title]),
        );

        return $deliverable->fresh();
    }

    public function complete(Deliverable $deliverable, User $actor): Deliverable
    {
        if ($deliverable->status !== 'approved') {
            throw ValidationException::withMessages([
                'status' => __('Only approved deliverables can be completed.'),
            ]);
        }

        $deliverable->update([
            'status' => 'completed',
            'completion_percentage' => 100,
            'completed_at' => now(),
            'updated_by' => $actor->id,
        ]);

        $this->auditLogger->log($deliverable, 'completed', [], $actor);

        return $deliverable->fresh();
    }

    public function addVersion(
        Deliverable $deliverable,
        UploadedFile $file,
        ?User $user = null,
        ?int $clientUserId = null,
        ?string $notes = null,
    ): DeliverableVersion {
        $next = (int) $deliverable->versions()->max('version_number') + 1;
        $directory = 'deliverables/'.$deliverable->organization_id.'/'.$deliverable->id;
        $path = $file->store($directory, 'local');

        $version = DeliverableVersion::query()->create([
            'organization_id' => $deliverable->organization_id,
            'deliverable_id' => $deliverable->id,
            'version_number' => $next,
            'label' => 'v'.$next,
            'notes' => $notes,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by_user_id' => $user?->id,
            'uploaded_by_client_user_id' => $clientUserId,
        ]);

        $this->auditLogger->log($deliverable, 'version_uploaded', [
            'version_number' => $next,
            'file' => $version->original_name,
        ], $user);

        return $version;
    }

    public function downloadVersion(DeliverableVersion $version)
    {
        if (! $version->path || ! Storage::disk($version->disk)->exists($version->path)) {
            abort(404);
        }

        return Storage::disk($version->disk)->download($version->path, $version->original_name ?? 'deliverable');
    }

    protected function assertMutable(Deliverable $deliverable): void
    {
        if (in_array($deliverable->status, ['completed'], true)) {
            throw ValidationException::withMessages([
                'status' => __('Completed deliverables cannot be edited.'),
            ]);
        }
    }
}
