<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Project;
use App\Services\DiscussionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PortalDiscussionController extends Controller
{
    public function __construct(protected DiscussionService $discussions) {}

    public function store(Request $request, Organization $organization, Project $project): RedirectResponse
    {
        abort_unless((int) $project->organization_id === (int) $organization->id, 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'parent_id' => ['nullable', 'integer'],
            'discussable_type' => ['nullable', 'string'],
            'discussable_id' => ['nullable', 'integer'],
        ]);

        $discussable = $project;
        if (! empty($data['discussable_type']) && ! empty($data['discussable_id'])) {
            $map = [
                'project' => Project::class,
                'deliverable' => \App\Models\Deliverable::class,
            ];
            $class = $map[$data['discussable_type']] ?? null;
            abort_unless($class, 422);
            $discussable = $class::query()
                ->where('organization_id', $organization->id)
                ->whereKey($data['discussable_id'])
                ->firstOrFail();
        }

        $this->discussions->post(
            $project,
            $discussable,
            $data['body'],
            null,
            $request->user('client'),
            $data['parent_id'] ?? null,
        );

        return back()->with('status', __('Message posted.'));
    }
}
