<?php

namespace App\Services;

use App\Events\DiscussionCreated;
use App\Models\ClientDiscussion;
use App\Models\ClientUser;
use App\Models\ClientUploadRequest;
use App\Models\Project;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class DiscussionService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected PortalNotificationService $portalNotifications,
        protected AttachmentService $attachments,
    ) {}

    public function post(
        Project $project,
        Model $discussable,
        string $body,
        ?User $staff = null,
        ?ClientUser $client = null,
        ?int $parentId = null,
    ): ClientDiscussion {
        if ($staff === null && $client === null) {
            throw ValidationException::withMessages([
                'author' => __('An author is required.'),
            ]);
        }

        if ($client !== null) {
            app(ClientAccessService::class)->assertCanAccessProject($client, $project, 'discussions');
        }

        if ($parentId) {
            $parent = ClientDiscussion::query()
                ->where('organization_id', $project->organization_id)
                ->whereKey($parentId)
                ->firstOrFail();

            if ((int) $parent->project_id !== (int) $project->id) {
                throw ValidationException::withMessages([
                    'parent_id' => __('Reply must belong to the same project.'),
                ]);
            }
        }

        $discussion = ClientDiscussion::query()->create([
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'discussable_type' => $discussable->getMorphClass(),
            'discussable_id' => $discussable->getKey(),
            'parent_id' => $parentId,
            'body' => $body,
            'visibility' => 'client',
            'author_user_id' => $staff?->id,
            'author_client_user_id' => $client?->id,
        ]);

        $runtime = app(WorkflowRuntimeContext::class);
        event(DiscussionCreated::forModel(
            $discussion,
            [
                'actor_user_id' => $staff?->id,
                'actor_client_user_id' => $client?->id,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        $this->auditLogger->log($discussion, 'discussion_created', [], $staff);

        if ($staff) {
            $this->portalNotifications->notifyProjectClients(
                $project->organization,
                $project->id,
                __('New discussion'),
                __('A new message was posted on :name', ['name' => $project->name]),
            );
        }

        return $discussion->fresh(['replies', 'authorUser', 'authorClient']);
    }

    public function createUploadRequest(Project $project, array $data, User $actor): ClientUploadRequest
    {
        $request = ClientUploadRequest::query()->create([
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'client_user_id' => $data['client_user_id'] ?? null,
            'title' => $data['title'],
            'instructions' => $data['instructions'] ?? null,
            'status' => 'open',
            'due_at' => $data['due_at'] ?? null,
            'created_by' => $actor->id,
        ]);

        $this->auditLogger->log($request, 'upload_request_created', [], $actor);

        if (! empty($data['client_user_id'])) {
            $client = ClientUser::query()->find($data['client_user_id']);
            if ($client) {
                $this->portalNotifications->notify(
                    $client,
                    __('File upload requested'),
                    $request->title,
                );
            }
        }

        return $request;
    }

    public function fulfillUploadRequest(ClientUploadRequest $request, ClientUser $client, UploadedFile $file): ClientUploadRequest
    {
        app(ClientAccessService::class)->assertCanAccessProject($client, $request->project, 'documents');

        if ($request->status !== 'open') {
            throw ValidationException::withMessages([
                'status' => __('This upload request is no longer open.'),
            ]);
        }

        if ($request->client_user_id && (int) $request->client_user_id !== (int) $client->id) {
            abort(403);
        }

        $this->attachments->store($request, $file, null);

        $request->update([
            'status' => 'fulfilled',
            'fulfilled_at' => now(),
            'client_user_id' => $client->id,
        ]);

        if ($request->creator) {
            try {
                $this->portalNotifications->notifyStaff(
                    (int) $request->organization_id,
                    $request->creator,
                    __('Client file uploaded'),
                    __('Upload request fulfilled: :title', ['title' => $request->title]),
                );
            } catch (ValidationException) {
            }
        }

        $this->auditLogger->log($request, 'upload_request_fulfilled', [], null);

        return $request->fresh(['attachments']);
    }
}
