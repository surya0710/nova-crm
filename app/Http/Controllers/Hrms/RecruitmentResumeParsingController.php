<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\ApplyResumeParseRequest;
use App\Http\Requests\Recruitment\StoreResumeParseRequest;
use App\Models\Candidate;
use App\Models\RecruitmentResumeParseRequest;
use App\Services\Recruitment\ResumeParsingService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RecruitmentResumeParsingController extends Controller
{
    public function __construct(protected ResumeParsingService $resumeParsing)
    {
    }

    public function index(TenantContext $tenant): View
    {
        $user = request()->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.integration.view', $organization), 403);

        return view('hrms.recruitment.integrations.resume-parsing', [
            'requests' => RecruitmentResumeParseRequest::query()
                ->where('organization_id', $organization->id)
                ->latest()
                ->paginate(20),
        ]);
    }

    public function store(StoreResumeParseRequest $request, TenantContext $tenant): RedirectResponse
    {
        $user = $request->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.integration.manage', $organization), 403);

        $candidate = $request->filled('candidate_id')
            ? Candidate::query()->findOrFail($request->integer('candidate_id'))
            : null;

        $this->resumeParsing->requestParse(
            $organization,
            [
                'filename' => $request->input('filename'),
                'mime_type' => $request->input('mime_type'),
            ],
            $candidate,
            null,
            $user,
            $request->input('provider_slug'),
        );

        return back()->with('status', 'recruitment-resume-parse-requested');
    }

    public function apply(
        ApplyResumeParseRequest $request,
        RecruitmentResumeParseRequest $parseRequest,
        TenantContext $tenant,
    ): RedirectResponse {
        $user = $request->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.integration.manage', $organization), 403);
        abort_unless((int) $parseRequest->organization_id === (int) $organization->id, 404);

        $candidate = Candidate::query()->findOrFail($request->integer('candidate_id'));

        $this->resumeParsing->applyParsedData(
            $parseRequest,
            $candidate,
            $user,
            (bool) $request->boolean('overwrite_confirmed'),
        );

        return back()->with('status', 'recruitment-resume-parse-applied');
    }
}
