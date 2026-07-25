<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectMentionResource;
use App\Services\MentionService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class ProjectMentionController extends Controller
{
    public function __construct(protected MentionService $mentionService) {}

    public function index(Request $request, TenantContext $tenant): AnonymousResourceCollection
    {
        abort_unless($request->user()?->hasPermission('projects.mentions.view'), 403);

        $mentions = $this->mentionService->historyForUser($request->user(), $tenant->id(), [
            'project_id' => $request->integer('project_id') ?: null,
            'unread' => $request->boolean('unread') ?: null,
            'limit' => $request->integer('limit', 50),
        ]);

        $mentions->load(['mentionedBy', 'project', 'task']);

        return ProjectMentionResource::collection($mentions);
    }

    public function autocomplete(Request $request, TenantContext $tenant): JsonResponse
    {
        abort_unless(
            $request->user()?->hasPermission('projects.mentions.view')
                || $request->user()?->hasPermission('tasks.comment'),
            403
        );

        $organizationId = $tenant->id();
        abort_unless($organizationId, 422);

        $query = Str::lower(trim($request->string('q')->toString()));

        $users = \App\Models\User::query()
            ->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organizationId))
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($inner) use ($query) {
                    $inner->whereRaw('LOWER(name) like ?', ['%'.$query.'%'])
                        ->orWhereRaw('LOWER(email) like ?', ['%'.$query.'%']);
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email']);

        return response()->json([
            'data' => $users->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'handle' => Str::of($user->email)->before('@')->toString()
                    ?: Str::slug($user->name, '.'),
            ]),
        ]);
    }
}
