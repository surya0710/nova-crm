<?php

namespace App\Http\Controllers\Api\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Resources\Recruitment\CandidateResource;
use App\Http\Resources\Recruitment\JobApplicationResource;
use App\Http\Resources\Recruitment\JobOpeningResource;
use App\Http\Resources\Recruitment\OfferLetterResource;
use App\Http\Resources\Recruitment\SavedReportResource;
use App\Models\Candidate;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\OfferLetter;
use App\Models\RecruitmentSavedReport;
use App\Services\Recruitment\RecruitmentApiService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecruitmentApiController extends Controller
{
    public function __construct(protected RecruitmentApiService $api) {}

    public function jobs(Request $request, TenantContext $tenant): AnonymousResourceCollection
    {
        $this->authorizePermission('recruitment.view', $tenant);

        return JobOpeningResource::collection(
            $this->api->paginateJobs($tenant->get(), $request->only('status'), $request->integer('per_page', 15))
        );
    }

    public function showJob(TenantContext $tenant, JobOpening $job): JobOpeningResource
    {
        $this->authorizePermission('recruitment.view', $tenant);
        abort_unless((int) $job->organization_id === (int) $tenant->id(), 404);
        $this->authorize('view', $job);

        return new JobOpeningResource($job->load(['department', 'designation']));
    }

    public function applications(Request $request, TenantContext $tenant): AnonymousResourceCollection
    {
        $this->authorizePermission('recruitment.view', $tenant);

        return JobApplicationResource::collection(
            $this->api->paginateApplications(
                $tenant->get(),
                $request->only(['status', 'job_opening_id']),
                $request->integer('per_page', 15),
            )
        );
    }

    public function showApplication(TenantContext $tenant, JobApplication $application): JobApplicationResource
    {
        $this->authorizePermission('recruitment.view', $tenant);
        abort_unless((int) $application->organization_id === (int) $tenant->id(), 404);
        $this->authorize('view', $application);

        return new JobApplicationResource($application->load(['candidate', 'jobOpening']));
    }

    public function candidates(Request $request, TenantContext $tenant): AnonymousResourceCollection
    {
        $this->authorizePermission('recruitment.view', $tenant);

        return CandidateResource::collection(
            $this->api->paginateCandidates($tenant->get(), $request->only('q'), $request->integer('per_page', 15))
        );
    }

    public function showCandidate(TenantContext $tenant, Candidate $candidate): CandidateResource
    {
        $this->authorizePermission('recruitment.view', $tenant);
        abort_unless((int) $candidate->organization_id === (int) $tenant->id(), 404);
        $this->authorize('view', $candidate);

        return new CandidateResource($candidate);
    }

    public function offers(Request $request, TenantContext $tenant): AnonymousResourceCollection
    {
        $this->authorizePermission('recruitment.offer.view', $tenant);

        return OfferLetterResource::collection(
            $this->api->paginateOffers($tenant->get(), $request->only('status'), $request->integer('per_page', 15))
        );
    }

    public function showOffer(TenantContext $tenant, OfferLetter $offer): OfferLetterResource
    {
        $this->authorizePermission('recruitment.offer.view', $tenant);
        abort_unless((int) $offer->organization_id === (int) $tenant->id(), 404);
        $this->authorize('view', $offer);

        return new OfferLetterResource($offer->load(['candidate', 'jobApplication']));
    }

    public function reports(Request $request, TenantContext $tenant): AnonymousResourceCollection
    {
        $this->authorizePermission('recruitment.reports.view', $tenant);

        return SavedReportResource::collection(
            $this->api->paginateReports($tenant->get(), $request->only('q'), $request->integer('per_page', 15))
        );
    }

    public function showReport(TenantContext $tenant, RecruitmentSavedReport $report): SavedReportResource
    {
        $this->authorizePermission('recruitment.reports.view', $tenant);
        abort_unless((int) $report->organization_id === (int) $tenant->id(), 404);
        $this->authorize('view', $report);

        return new SavedReportResource($report);
    }

    protected function authorizePermission(string $permission, TenantContext $tenant): void
    {
        $user = request()->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission($permission, $organization), 403);
    }
}
