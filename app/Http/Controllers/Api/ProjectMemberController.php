<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectMemberRequest;
use App\Http\Requests\UpdateProjectMemberRequest;
use App\Http\Resources\ProjectMemberResource;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Services\ProjectMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ProjectMemberController extends Controller
{
    public function __construct(protected ProjectMemberService $memberService) {}

    public function index(Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        $members = ProjectMember::query()
            ->where('project_id', $project->id)
            ->with('user')
            ->orderBy('project_role')
            ->orderBy('id')
            ->paginate(request()->integer('per_page', 50));

        return ProjectMemberResource::collection($members);
    }

    public function store(StoreProjectMemberRequest $request, Project $project): JsonResponse
    {
        $this->authorize('assignMembers', $project);

        $validated = $request->validated();

        try {
            $member = $this->memberService->add(
                $project,
                User::query()->findOrFail($validated['user_id']),
                $validated['project_role'],
                $request->user(),
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        $member->load('user');

        return (new ProjectMemberResource($member))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProjectMemberRequest $request, Project $project, ProjectMember $member): ProjectMemberResource|JsonResponse
    {
        $this->assertMemberBelongsToProject($project, $member);

        try {
            $member = $this->memberService->changeRole(
                $member,
                $request->validated('project_role'),
                $request->user(),
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        $member->load('user');

        return new ProjectMemberResource($member);
    }

    public function destroy(Project $project, ProjectMember $member, Request $request): JsonResponse
    {
        $this->authorize('delete', $member);
        $this->assertMemberBelongsToProject($project, $member);

        try {
            $this->memberService->remove($project, $member, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    protected function assertMemberBelongsToProject(Project $project, ProjectMember $member): void
    {
        abort_unless((int) $member->project_id === (int) $project->id, 404);
    }
}
