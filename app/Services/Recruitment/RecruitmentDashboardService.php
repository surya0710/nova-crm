<?php

namespace App\Services\Recruitment;

use App\Models\JobRequisition;
use App\Models\OfferApproval;
use App\Models\OfferLetter;
use App\Models\User;
use App\Services\Recruitment\Concerns\ResolvesAnalyticsFilters;
use Illuminate\Support\Facades\DB;

class RecruitmentDashboardService
{
    use ResolvesAnalyticsFilters;

    public function __construct(
        protected RecruitmentKpiService $kpis,
        protected RecruitmentAnalyticsCache $cache,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function executiveDashboard(array $filters = [], ?User $actor = null): array
    {
        return [
            'kpis' => $this->kpis->executiveKpis($filters, $actor),
            'time_metrics' => $this->kpis->timeMetrics($filters, $actor),
            'hiring_manager' => $this->hiringManagerMetrics($filters, $actor),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function hiringManagerMetrics(array $filters = [], ?User $actor = null): array
    {
        $filters['period'] = $filters['period'] ?? 'month';
        if (! array_key_exists('_department_ids', $filters)) {
            $filters['_department_ids'] = $this->authorizedDepartmentIds($actor);
        }

        return $this->cache->remember('hiring_manager_metrics', $filters, function () use ($filters) {
            [$from, $to] = $this->resolvePeriod($filters);
            $departmentIds = $filters['_department_ids'] ?? null;

            $requisitions = JobRequisition::query();
            $this->applyDepartmentScope($requisitions, $departmentIds);

            $openRequisitions = (clone $requisitions)
                ->whereIn('status', ['draft', 'pending_approval', 'approved'])
                ->count();

            $pendingApprovals = (clone $requisitions)
                ->where('status', 'pending_approval')
                ->count();

            $interviewStats = DB::table('interview_rounds')
                ->join('job_applications', 'job_applications.id', '=', 'interview_rounds.job_application_id')
                ->join('job_openings', 'job_openings.id', '=', 'job_applications.job_opening_id')
                ->whereNull('interview_rounds.deleted_at')
                ->whereBetween('interview_rounds.scheduled_at', [$from, $to])
                ->when($departmentIds !== null, fn ($q) => $q->whereIn('job_openings.department_id', $departmentIds))
                ->selectRaw("sum(case when interview_rounds.status = 'completed' then 1 else 0 end) as completed")
                ->selectRaw("sum(case when interview_rounds.status in ('scheduled','completed','cancelled','no_show') then 1 else 0 end) as total")
                ->first();

            $completed = (int) ($interviewStats->completed ?? 0);
            $total = (int) ($interviewStats->total ?? 0);

            $hiringDecisions = DB::table('hiring_decisions')
                ->join('job_applications', 'job_applications.id', '=', 'hiring_decisions.job_application_id')
                ->join('job_openings', 'job_openings.id', '=', 'job_applications.job_opening_id')
                ->whereNull('hiring_decisions.deleted_at')
                ->whereBetween('hiring_decisions.decision_date', [$from->toDateString(), $to->toDateString()])
                ->when($departmentIds !== null, fn ($q) => $q->whereIn('job_openings.department_id', $departmentIds))
                ->selectRaw('recommendation, count(*) as total')
                ->groupBy('recommendation')
                ->pluck('total', 'recommendation')
                ->all();

            $approvalTimes = OfferApproval::query()
                ->where('status', 'approved')
                ->whereNotNull('approved_at')
                ->whereBetween('approved_at', [$from, $to])
                ->when($departmentIds !== null, function ($q) use ($departmentIds) {
                    $q->whereHas('offerLetter.jobApplication.jobOpening', fn ($oq) => $oq->whereIn('department_id', $departmentIds));
                })
                ->get(['created_at', 'approved_at'])
                ->map(fn (OfferApproval $a) => $a->created_at->diffInHours($a->approved_at) / 24)
                ->all();

            $offerApprovalPending = OfferLetter::query()
                ->where('status', 'pending_approval')
                ->when($departmentIds !== null, function ($q) use ($departmentIds) {
                    $q->whereHas('jobApplication.jobOpening', fn ($oq) => $oq->whereIn('department_id', $departmentIds));
                })
                ->count();

            return [
                'open_requisitions' => $openRequisitions,
                'pending_approvals' => $pendingApprovals,
                'interview_completion_rate' => $this->percent($completed, $total),
                'hiring_decisions' => [
                    'hire' => (int) ($hiringDecisions['hire'] ?? 0),
                    'hold' => (int) ($hiringDecisions['hold'] ?? 0),
                    'reject' => (int) ($hiringDecisions['reject'] ?? 0),
                    'total' => array_sum(array_map('intval', $hiringDecisions)),
                ],
                'average_approval_time_days' => $this->average($approvalTimes),
                'offer_approval_pending' => $offerApprovalPending,
                'offer_approval_time_days' => $this->average($approvalTimes),
            ];
        });
    }
}
