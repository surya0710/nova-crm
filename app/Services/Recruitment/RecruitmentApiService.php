<?php

namespace App\Services\Recruitment;

use App\Models\Candidate;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\OfferLetter;
use App\Models\Organization;
use App\Models\RecruitmentSavedReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Read-oriented API facade — recruitment business mutations stay in domain services.
 */
class RecruitmentApiService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateJobs(Organization $organization, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = JobOpening::query()
            ->where('organization_id', $organization->id)
            ->with(['department', 'designation']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateApplications(Organization $organization, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = JobApplication::query()
            ->where('organization_id', $organization->id)
            ->with(['candidate', 'jobOpening']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['job_opening_id'])) {
            $query->where('job_opening_id', $filters['job_opening_id']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateCandidates(Organization $organization, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Candidate::query()->where('organization_id', $organization->id);

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($builder) use ($q) {
                $builder->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateOffers(Organization $organization, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = OfferLetter::query()
            ->where('organization_id', $organization->id)
            ->with(['jobApplication.candidate', 'candidate']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateReports(Organization $organization, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = RecruitmentSavedReport::query()
            ->where('organization_id', $organization->id);

        if (! empty($filters['q'])) {
            $query->where('report_name', 'like', '%'.$filters['q'].'%');
        }

        return $query->latest()->paginate($perPage);
    }
}
