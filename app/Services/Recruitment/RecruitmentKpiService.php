<?php

namespace App\Services\Recruitment;

use App\Models\Candidate;
use App\Models\HiringDecision;
use App\Models\InterviewRound;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\OfferLetter;
use App\Models\User;
use App\Services\Recruitment\Concerns\ResolvesAnalyticsFilters;

class RecruitmentKpiService
{
    use ResolvesAnalyticsFilters;

    public function __construct(protected RecruitmentAnalyticsCache $cache) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function executiveKpis(array $filters = [], ?User $actor = null): array
    {
        $filters = $this->normalizeFilters($filters, $actor);

        return $this->cache->remember('executive_kpis', $filters, function () use ($filters) {
            [$from, $to] = $this->resolvePeriod($filters);
            $departmentIds = $filters['_department_ids'] ?? null;

            $openingsQuery = JobOpening::query();
            $this->applyDepartmentScope($openingsQuery, $departmentIds);

            $applicationsBase = JobApplication::query()->where('is_draft', false);
            if ($departmentIds !== null) {
                $applicationsBase->whereHas('jobOpening', fn ($q) => $q->whereIn('department_id', $departmentIds));
            }

            $openPositions = (clone $openingsQuery)->whereIn('status', ['published', 'paused'])->count();
            $activeCandidates = Candidate::query()
                ->whereHas('applications', function ($q) use ($departmentIds) {
                    $q->where('status', 'active')->where('is_draft', false);
                    if ($departmentIds !== null) {
                        $q->whereHas('jobOpening', fn ($oq) => $oq->whereIn('department_id', $departmentIds));
                    }
                })
                ->count();

            $interviewsScheduled = InterviewRound::query()
                ->where('status', 'scheduled')
                ->whereBetween('scheduled_at', [$from, $to])
                ->when($departmentIds !== null, function ($q) use ($departmentIds) {
                    $q->whereHas('jobApplication.jobOpening', fn ($oq) => $oq->whereIn('department_id', $departmentIds));
                })
                ->count();

            $offersPending = OfferLetter::query()
                ->whereIn('status', ['pending_approval', 'approved', 'sent'])
                ->when($departmentIds !== null, function ($q) use ($departmentIds) {
                    $q->whereHas('jobApplication.jobOpening', fn ($oq) => $oq->whereIn('department_id', $departmentIds));
                })
                ->count();

            $offersAccepted = OfferLetter::query()
                ->where('status', 'accepted')
                ->whereBetween('accepted_at', [$from, $to])
                ->when($departmentIds !== null, function ($q) use ($departmentIds) {
                    $q->whereHas('jobApplication.jobOpening', fn ($oq) => $oq->whereIn('department_id', $departmentIds));
                })
                ->count();

            $offersSentOrDecided = OfferLetter::query()
                ->where(function ($q) use ($from, $to) {
                    $q->whereBetween('sent_at', [$from, $to])
                        ->orWhereBetween('accepted_at', [$from, $to])
                        ->orWhereBetween('rejected_at', [$from, $to]);
                })
                ->when($departmentIds !== null, function ($q) use ($departmentIds) {
                    $q->whereHas('jobApplication.jobOpening', fn ($oq) => $oq->whereIn('department_id', $departmentIds));
                })
                ->count();

            $applicationsInPeriod = (clone $applicationsBase)
                ->whereBetween('applied_date', [$from->toDateString(), $to->toDateString()])
                ->count();

            $hiresInPeriod = HiringDecision::query()
                ->where('recommendation', 'hire')
                ->whereBetween('decision_date', [$from->toDateString(), $to->toDateString()])
                ->when($departmentIds !== null, function ($q) use ($departmentIds) {
                    $q->whereHas('jobApplication.jobOpening', fn ($oq) => $oq->whereIn('department_id', $departmentIds));
                })
                ->count();

            $newCandidates = Candidate::query()
                ->whereBetween('created_at', [$from, $to])
                ->count();

            $activeRecruiters = (clone $applicationsBase)
                ->whereNotNull('assigned_recruiter_id')
                ->where(function ($q) use ($from, $to) {
                    $q->where('status', 'active')
                        ->orWhereBetween('applied_date', [$from->toDateString(), $to->toDateString()]);
                })
                ->distinct()
                ->count('assigned_recruiter_id');

            return [
                'open_positions' => $openPositions,
                'active_candidates' => $activeCandidates,
                'interviews_scheduled' => $interviewsScheduled,
                'offers_pending' => $offersPending,
                'offers_accepted' => $offersAccepted,
                'hiring_rate' => $this->percent($hiresInPeriod, $applicationsInPeriod),
                'time_to_hire' => $this->averageTimeToHire($filters),
                'time_to_fill' => $this->averageTimeToFill($filters),
                'offer_acceptance_rate' => $this->percent($offersAccepted, max($offersSentOrDecided, $offersAccepted)),
                'applications_this_period' => $applicationsInPeriod,
                'new_candidates' => $newCandidates,
                'active_recruiters' => $activeRecruiters,
                'hires_this_period' => $hiresInPeriod,
                'period' => [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                    'label' => $filters['period'] ?? 'month',
                ],
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function averageTimeToHire(array $filters = [], ?User $actor = null): ?float
    {
        $filters = $this->normalizeFilters($filters, $actor);
        [$from, $to] = $this->resolvePeriod($filters);
        $departmentIds = $filters['_department_ids'] ?? null;

        $rows = HiringDecision::query()
            ->where('recommendation', 'hire')
            ->whereBetween('decision_date', [$from->toDateString(), $to->toDateString()])
            ->when($departmentIds !== null, function ($q) use ($departmentIds) {
                $q->whereHas('jobApplication.jobOpening', fn ($oq) => $oq->whereIn('department_id', $departmentIds));
            })
            ->join('job_applications', 'job_applications.id', '=', 'hiring_decisions.job_application_id')
            ->whereNull('job_applications.deleted_at')
            ->select([
                'hiring_decisions.decision_date',
                'job_applications.applied_date',
            ])
            ->get();

        $days = [];
        foreach ($rows as $row) {
            if ($row->applied_date && $row->decision_date) {
                $applied = \Carbon\Carbon::parse($row->applied_date);
                $decision = \Carbon\Carbon::parse($row->decision_date);
                $days[] = $applied->diffInDays($decision);
            }
        }

        return $this->average($days);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function averageTimeToFill(array $filters = [], ?User $actor = null): ?float
    {
        $filters = $this->normalizeFilters($filters, $actor);
        [$from, $to] = $this->resolvePeriod($filters);
        $departmentIds = $filters['_department_ids'] ?? null;

        $openings = JobOpening::query()
            ->where('status', 'filled')
            ->whereNotNull('publish_date')
            ->whereNotNull('closing_date')
            ->whereBetween('closing_date', [$from->toDateString(), $to->toDateString()])
            ->when($departmentIds !== null, fn ($q) => $q->whereIn('department_id', $departmentIds))
            ->get(['publish_date', 'closing_date']);

        $days = [];
        foreach ($openings as $opening) {
            $days[] = $opening->publish_date->diffInDays($opening->closing_date);
        }

        return $this->average($days);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float|null>
     */
    public function timeMetrics(array $filters = [], ?User $actor = null): array
    {
        $filters = $this->normalizeFilters($filters, $actor);

        return $this->cache->remember('time_metrics', $filters, function () use ($filters) {
            [$from, $to] = $this->resolvePeriod($filters);
            $departmentIds = $filters['_department_ids'] ?? null;

            $interviewDurations = InterviewRound::query()
                ->where('status', 'completed')
                ->whereNotNull('duration_minutes')
                ->whereBetween('scheduled_at', [$from, $to])
                ->when($departmentIds !== null, function ($q) use ($departmentIds) {
                    $q->whereHas('jobApplication.jobOpening', fn ($oq) => $oq->whereIn('department_id', $departmentIds));
                })
                ->avg('duration_minutes');

            $offerApprovalDays = \App\Models\OfferApproval::query()
                ->where('status', 'approved')
                ->whereNotNull('approved_at')
                ->whereBetween('approved_at', [$from, $to])
                ->when($departmentIds !== null, function ($q) use ($departmentIds) {
                    $q->whereHas('offerLetter.jobApplication.jobOpening', fn ($oq) => $oq->whereIn('department_id', $departmentIds));
                })
                ->get(['created_at', 'approved_at'])
                ->map(fn ($a) => $a->created_at->diffInHours($a->approved_at) / 24)
                ->all();

            $offerAcceptanceDays = OfferLetter::query()
                ->where('status', 'accepted')
                ->whereNotNull('sent_at')
                ->whereNotNull('accepted_at')
                ->whereBetween('accepted_at', [$from, $to])
                ->when($departmentIds !== null, function ($q) use ($departmentIds) {
                    $q->whereHas('jobApplication.jobOpening', fn ($oq) => $oq->whereIn('department_id', $departmentIds));
                })
                ->get(['sent_at', 'accepted_at'])
                ->map(fn (OfferLetter $o) => $o->sent_at->diffInDays($o->accepted_at))
                ->all();

            $cycleDays = HiringDecision::query()
                ->where('recommendation', 'hire')
                ->whereBetween('decision_date', [$from->toDateString(), $to->toDateString()])
                ->when($departmentIds !== null, function ($q) use ($departmentIds) {
                    $q->whereHas('jobApplication.jobOpening', fn ($oq) => $oq->whereIn('department_id', $departmentIds));
                })
                ->with(['jobApplication.jobOpening:id,publish_date'])
                ->get()
                ->map(function (HiringDecision $decision) {
                    $publish = $decision->jobApplication?->jobOpening?->publish_date;
                    if (! $publish || ! $decision->decision_date) {
                        return null;
                    }

                    return $publish->diffInDays($decision->decision_date);
                })
                ->filter(fn ($v) => $v !== null)
                ->all();

            return [
                'time_to_hire' => $this->averageTimeToHire($filters),
                'time_to_fill' => $this->averageTimeToFill($filters),
                'interview_duration_minutes' => $interviewDurations !== null ? round((float) $interviewDurations, 2) : null,
                'offer_approval_days' => $this->average($offerApprovalDays),
                'offer_acceptance_days' => $this->average($offerAcceptanceDays),
                'average_hiring_cycle_days' => $this->average($cycleDays),
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function normalizeFilters(array $filters, ?User $actor): array
    {
        if (! array_key_exists('_department_ids', $filters)) {
            $filters['_department_ids'] = $this->authorizedDepartmentIds($actor);
        }

        $filters['period'] = $filters['period'] ?? 'month';

        return $filters;
    }
}
