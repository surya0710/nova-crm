<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\PublishRecruitmentJobBoardRequest;
use App\Models\JobOpening;
use App\Models\RecruitmentJobBoardListing;
use App\Models\RecruitmentProvider;
use App\Services\Recruitment\RecruitmentJobBoardService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RecruitmentJobBoardController extends Controller
{
    public function __construct(protected RecruitmentJobBoardService $jobBoards)
    {
    }

    public function index(TenantContext $tenant): View
    {
        $user = request()->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.integration.view', $organization), 403);

        return view('hrms.recruitment.integrations.job-boards', [
            'listings' => RecruitmentJobBoardListing::query()
                ->where('organization_id', $organization->id)
                ->with(['jobOpening', 'provider'])
                ->latest()
                ->paginate(20),
            'providers' => RecruitmentProvider::query()
                ->where('organization_id', $organization->id)
                ->where('category', 'job_board')
                ->orderBy('slug')
                ->get(),
            'openings' => JobOpening::query()
                ->where('organization_id', $organization->id)
                ->where('status', 'published')
                ->latest()
                ->limit(100)
                ->get(),
        ]);
    }

    public function publish(PublishRecruitmentJobBoardRequest $request, TenantContext $tenant): RedirectResponse
    {
        $user = $request->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.integration.manage', $organization), 403);

        $opening = JobOpening::query()->findOrFail($request->integer('job_opening_id'));
        $provider = RecruitmentProvider::query()->findOrFail($request->integer('recruitment_provider_id'));
        abort_unless((int) $opening->organization_id === (int) $organization->id, 404);
        abort_unless((int) $provider->organization_id === (int) $organization->id, 404);

        $listing = $this->jobBoards->publishOpening($opening, $provider, $user);

        $key = match ($listing->status) {
            'failed' => 'recruitment-job-board-failed',
            'updated' => 'recruitment-job-board-updated',
            default => 'recruitment-job-board-published',
        };

        return back()->with('status', $key);
    }

    public function sync(RecruitmentJobBoardListing $listing, TenantContext $tenant): RedirectResponse
    {
        $user = request()->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.integration.manage', $organization), 403);
        abort_unless((int) $listing->organization_id === (int) $organization->id, 404);

        $this->jobBoards->syncStatus($listing, $user);

        return back()->with('status', 'recruitment-job-board-synced');
    }

    public function close(RecruitmentJobBoardListing $listing, TenantContext $tenant): RedirectResponse
    {
        $user = request()->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.integration.manage', $organization), 403);
        abort_unless((int) $listing->organization_id === (int) $organization->id, 404);

        $this->jobBoards->closeListing($listing, $user);

        return back()->with('status', 'recruitment-job-board-closed');
    }
}