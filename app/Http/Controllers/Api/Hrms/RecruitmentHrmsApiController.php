<?php

namespace App\Http\Controllers\Api\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Resources\Hrms\RecruitmentResource;
use App\Models\Candidate;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\OfferLetter;
use App\Services\Hrms\HRMSApiFacadeService;
use App\Services\TenantContext;
use App\Support\Api\ApiQuery;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecruitmentHrmsApiController extends Controller
{
    public function __construct(
        protected HRMSApiFacadeService $facade,
        protected TenantContext $tenant,
    ) {}

    public function jobs(Request $request): JsonResponse
    {
        $organization = $this->tenant->get();
        abort_unless($organization !== null, 404);

        $paginator = $this->facade->recruitment()->paginateJobs(
            $organization,
            $request->only('status'),
            ApiQuery::perPage($request, 15),
        );

        return ApiResponse::paginated(
            $paginator,
            mapItem: fn (JobOpening $job) => (new RecruitmentResource($job->loadMissing(['department', 'designation'])))->resolve(),
        );
    }

    public function showJob(JobOpening $job): JsonResponse
    {
        abort_unless((int) $job->organization_id === (int) $this->tenant->id(), 404);
        $this->authorize('view', $job);

        return ApiResponse::success(
            new RecruitmentResource($job->load(['department', 'designation']))
        );
    }

    public function candidates(Request $request): JsonResponse
    {
        $organization = $this->tenant->get();
        abort_unless($organization !== null, 404);

        $paginator = $this->facade->recruitment()->paginateCandidates(
            $organization,
            $request->only('q'),
            ApiQuery::perPage($request, 15),
        );

        return ApiResponse::paginated(
            $paginator,
            mapItem: fn (Candidate $c) => (new RecruitmentResource($c))->resolve(),
        );
    }

    public function showCandidate(Candidate $candidate): JsonResponse
    {
        abort_unless((int) $candidate->organization_id === (int) $this->tenant->id(), 404);
        $this->authorize('view', $candidate);

        return ApiResponse::success(new RecruitmentResource($candidate));
    }

    public function applications(Request $request): JsonResponse
    {
        $organization = $this->tenant->get();
        abort_unless($organization !== null, 404);

        $paginator = $this->facade->recruitment()->paginateApplications(
            $organization,
            $request->only(['status', 'job_opening_id']),
            ApiQuery::perPage($request, 15),
        );

        return ApiResponse::paginated(
            $paginator,
            mapItem: fn (JobApplication $a) => (new RecruitmentResource(
                $a->loadMissing(['candidate', 'jobOpening'])
            ))->resolve(),
        );
    }

    public function showApplication(JobApplication $application): JsonResponse
    {
        abort_unless((int) $application->organization_id === (int) $this->tenant->id(), 404);
        $this->authorize('view', $application);

        return ApiResponse::success(
            new RecruitmentResource($application->load(['candidate', 'jobOpening']))
        );
    }

    public function offers(Request $request): JsonResponse
    {
        $organization = $this->tenant->get();
        abort_unless($organization !== null, 404);

        $paginator = $this->facade->recruitment()->paginateOffers(
            $organization,
            $request->only('status'),
            ApiQuery::perPage($request, 15),
        );

        return ApiResponse::paginated(
            $paginator,
            mapItem: fn (OfferLetter $o) => (new RecruitmentResource(
                $o->loadMissing(['candidate', 'jobApplication'])
            ))->resolve(),
        );
    }

    public function showOffer(OfferLetter $offer): JsonResponse
    {
        abort_unless((int) $offer->organization_id === (int) $this->tenant->id(), 404);
        $this->authorize('view', $offer);

        return ApiResponse::success(
            new RecruitmentResource($offer->load(['candidate', 'jobApplication']))
        );
    }
}
