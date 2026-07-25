<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PinCollaborationRequest;
use App\Http\Resources\CollaborationFeedResource;
use App\Models\Project;
use App\Models\ProjectCollaborationPin;
use App\Services\CollaborationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectCollaborationController extends Controller
{
    public function __construct(protected CollaborationService $collaborationService) {}

    public function show(Request $request, Project $project): CollaborationFeedResource
    {
        $this->authorize('viewCollaboration', $project);

        $feed = $this->collaborationService->feed($project, [
            'limit' => $request->integer('limit', 50),
        ]);

        return new CollaborationFeedResource($feed);
    }

    public function pin(PinCollaborationRequest $request, Project $project): JsonResponse
    {
        $pin = $this->collaborationService->pin($project, $request->validated(), $request->user());

        return response()->json([
            'data' => [
                'id' => $pin->id,
                'source_type' => $pin->source_type,
                'source_id' => $pin->source_id,
                'title' => $pin->title,
                'body' => $pin->body,
                'sort_order' => $pin->sort_order,
                'pinned_by' => $pin->pinned_by,
            ],
        ], 201);
    }

    public function unpin(Request $request, Project $project, ProjectCollaborationPin $pin): JsonResponse
    {
        $this->authorize('manageCollaboration', $project);
        abort_unless((int) $pin->project_id === (int) $project->id, 404);

        $this->collaborationService->unpinById($pin, $request->user());

        return response()->json(['success' => true]);
    }
}
