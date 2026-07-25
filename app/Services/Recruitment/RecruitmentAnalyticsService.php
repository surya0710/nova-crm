<?php

namespace App\Services\Recruitment;

use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\HiringDecision;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\OfferLetter;
use App\Models\User;
use App\Services\Recruitment\Concerns\ResolvesAnalyticsFilters;
use Illuminate\Support\Facades\DB;

class RecruitmentAnalyticsService
{
    use ResolvesAnalyticsFilters;

    public function __construct(protected RecruitmentAnalyticsCache $cache) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function funnel(array $filters = [], ?User $actor = null): array
    {
        $filters = $this->withScope($filters, $actor);

        return $this->cache->remember('funnel', $filters, function () use ($filters) {
            [$from, $to] = $this->resolvePeriod($filters);
            $departmentIds = $filters['_department_ids'] ?? null;

            $stageOrder = ['applied', 'screening', 'interview', 'evaluation', 'offer', 'hired'];
            $counts = [];

            $base = JobApplication::query()
                ->where('is_draft', false)
                ->whereBetween('applied_date', [$from->toDateString(), $to->toDateString()]);

            if ($departmentIds !== null) {
                $base->whereHas('jobOpening', fn ($q) => $q->whereIn('department_id', $departmentIds));
            }

            $reachedOrBeyond = static function (string $stage) use ($stageOrder): array {
                $index = array_search($stage, $stageOrder, true);

                return $index === false ? [$stage] : array_slice($stageOrder, $index);
            };

            foreach ($stageOrder as $stage) {
                if ($stage === 'applied') {
                    $counts[$stage] = (clone $base)->count();
                } else {
                    $counts[$stage] = (clone $base)->whereIn('stage', $reachedOrBeyond($stage))->count();
                }
            }

            $onboarding = HiringDecision::query()
                ->where('onboarding_recommended', true)
                ->whereBetween('decision_date', [$from->toDateString(), $to->toDateString()])
                ->when($departmentIds !== null, function ($q) use ($departmentIds) {
                    $q->whereHas('jobApplication.jobOpening', fn ($oq) => $oq->whereIn('department_id', $departmentIds));
                })
                ->count();

            $counts['onboarding'] = $onboarding;

            $stages = [];
            $previous = null;
            $labels = config('hrms.recruitment.analytics.funnel_stages', []);

            foreach (array_merge($stageOrder, ['onboarding']) as $stage) {
                $count = $counts[$stage] ?? 0;
                $conversion = $previous === null ? 100.0 : $this->percent($count, $previous);
                $dropOff = $previous === null ? 0.0 : max(0, round(100 - $conversion, 2));

                $stages[] = [
                    'stage' => $stage,
                    'label' => $labels[$stage] ?? $stage,
                    'count' => $count,
                    'conversion_percent' => $conversion,
                    'drop_off_percent' => $dropOff,
                    'average_duration_days' => $this->averageStageDuration($stage, $filters),
                ];
                $previous = $count > 0 ? $count : $previous;
            }

            return [
                'stages' => $stages,
                'period' => [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ],
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function sourceEffectiveness(array $filters = [], ?User $actor = null): array
    {
        $filters = $this->withScope($filters, $actor);

        return $this->cache->remember('source_effectiveness', $filters, function () use ($filters) {
            [$from, $to] = $this->resolvePeriod($filters);
            $departmentIds = $filters['_department_ids'] ?? null;

            $applications = DB::table('job_applications')
                ->leftJoin('job_openings', 'job_openings.id', '=', 'job_applications.job_opening_id')
                ->whereNull('job_applications.deleted_at')
                ->where('job_applications.is_draft', false)
                ->whereBetween('job_applications.applied_date', [$from->toDateString(), $to->toDateString()])
                ->when($departmentIds !== null, fn ($q) => $q->whereIn('job_openings.department_id', $departmentIds))
                ->selectRaw("coalesce(nullif(job_applications.source, ''), 'other') as source")
                ->selectRaw('count(*) as applications')
                ->groupBy('source')
                ->pluck('applications', 'source')
                ->all();

            $interviews = DB::table('interview_rounds')
                ->join('job_applications', 'job_applications.id', '=', 'interview_rounds.job_application_id')
                ->leftJoin('job_openings', 'job_openings.id', '=', 'job_applications.job_opening_id')
                ->whereNull('interview_rounds.deleted_at')
                ->whereBetween('interview_rounds.scheduled_at', [$from, $to])
                ->when($departmentIds !== null, fn ($q) => $q->whereIn('job_openings.department_id', $departmentIds))
                ->selectRaw("coalesce(nullif(job_applications.source, ''), 'other') as source")
                ->selectRaw('count(*) as total')
                ->groupBy('source')
                ->pluck('total', 'source')
                ->all();

            $offers = DB::table('offer_letters')
                ->join('job_applications', 'job_applications.id', '=', 'offer_letters.job_application_id')
                ->leftJoin('job_openings', 'job_openings.id', '=', 'job_applications.job_opening_id')
                ->whereNull('offer_letters.deleted_at')
                ->whereBetween('offer_letters.created_at', [$from, $to])
                ->when($departmentIds !== null, fn ($q) => $q->whereIn('job_openings.department_id', $departmentIds))
                ->selectRaw("coalesce(nullif(job_applications.source, ''), 'other') as source")
                ->selectRaw('count(*) as total')
                ->groupBy('source')
                ->pluck('total', 'source')
                ->all();

            $hires = DB::table('hiring_decisions')
                ->join('job_applications', 'job_applications.id', '=', 'hiring_decisions.job_application_id')
                ->leftJoin('job_openings', 'job_openings.id', '=', 'job_applications.job_opening_id')
                ->whereNull('hiring_decisions.deleted_at')
                ->where('hiring_decisions.recommendation', 'hire')
                ->whereBetween('hiring_decisions.decision_date', [$from->toDateString(), $to->toDateString()])
                ->when($departmentIds !== null, fn ($q) => $q->whereIn('job_openings.department_id', $departmentIds))
                ->selectRaw("coalesce(nullif(job_applications.source, ''), 'other') as source")
                ->selectRaw('count(*) as total')
                ->groupBy('source')
                ->pluck('total', 'source')
                ->all();

            $sources = array_unique(array_merge(
                array_keys($applications),
                array_keys($interviews),
                array_keys($offers),
                array_keys($hires),
            ));

            $labels = config('hrms.recruitment.candidate_sources', []);
            $rows = [];

            foreach ($sources as $source) {
                $apps = (int) ($applications[$source] ?? 0);
                $hireCount = (int) ($hires[$source] ?? 0);
                $rows[] = [
                    'source' => $source,
                    'label' => $labels[$source] ?? ucfirst(str_replace('_', ' ', (string) $source)),
                    'applications' => $apps,
                    'interviews' => (int) ($interviews[$source] ?? 0),
                    'offers' => (int) ($offers[$source] ?? 0),
                    'hires' => $hireCount,
                    'conversion_rate' => $this->percent($hireCount, $apps),
                ];
            }

            usort($rows, fn ($a, $b) => $b['applications'] <=> $a['applications']);

            return $rows;
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function recruiterPerformance(array $filters = [], ?User $actor = null): array
    {
        $filters = $this->withScope($filters, $actor);

        return $this->cache->remember('recruiter_performance', $filters, function () use ($filters) {
            [$from, $to] = $this->resolveLeaderboardPeriod($filters);
            $departmentIds = $filters['_department_ids'] ?? null;

            $recruiters = DB::table('job_applications')
                ->leftJoin('users', 'users.id', '=', 'job_applications.assigned_recruiter_id')
                ->leftJoin('job_openings', 'job_openings.id', '=', 'job_applications.job_opening_id')
                ->whereNull('job_applications.deleted_at')
                ->whereNotNull('job_applications.assigned_recruiter_id')
                ->where('job_applications.is_draft', false)
                ->whereBetween('job_applications.applied_date', [$from->toDateString(), $to->toDateString()])
                ->when($departmentIds !== null, fn ($q) => $q->whereIn('job_openings.department_id', $departmentIds))
                ->groupBy('job_applications.assigned_recruiter_id', 'users.name')
                ->select([
                    'job_applications.assigned_recruiter_id as recruiter_id',
                    'users.name as recruiter_name',
                    DB::raw('count(*) as candidates_handled'),
                ])
                ->get()
                ->keyBy('recruiter_id');

            $interviewCounts = DB::table('interview_rounds')
                ->join('job_applications', 'job_applications.id', '=', 'interview_rounds.job_application_id')
                ->leftJoin('job_openings', 'job_openings.id', '=', 'job_applications.job_opening_id')
                ->whereNull('interview_rounds.deleted_at')
                ->whereNotNull('job_applications.assigned_recruiter_id')
                ->whereBetween('interview_rounds.scheduled_at', [$from, $to])
                ->when($departmentIds !== null, fn ($q) => $q->whereIn('job_openings.department_id', $departmentIds))
                ->groupBy('job_applications.assigned_recruiter_id')
                ->selectRaw('job_applications.assigned_recruiter_id as recruiter_id, count(*) as interviews_scheduled')
                ->pluck('interviews_scheduled', 'recruiter_id');

            $offerCounts = DB::table('offer_letters')
                ->join('job_applications', 'job_applications.id', '=', 'offer_letters.job_application_id')
                ->leftJoin('job_openings', 'job_openings.id', '=', 'job_applications.job_opening_id')
                ->whereNull('offer_letters.deleted_at')
                ->whereNotNull('job_applications.assigned_recruiter_id')
                ->whereBetween('offer_letters.created_at', [$from, $to])
                ->when($departmentIds !== null, fn ($q) => $q->whereIn('job_openings.department_id', $departmentIds))
                ->groupBy('job_applications.assigned_recruiter_id')
                ->selectRaw('job_applications.assigned_recruiter_id as recruiter_id, count(*) as offers_generated')
                ->pluck('offers_generated', 'recruiter_id');

            $acceptedCounts = DB::table('offer_letters')
                ->join('job_applications', 'job_applications.id', '=', 'offer_letters.job_application_id')
                ->leftJoin('job_openings', 'job_openings.id', '=', 'job_applications.job_opening_id')
                ->whereNull('offer_letters.deleted_at')
                ->where('offer_letters.status', 'accepted')
                ->whereNotNull('job_applications.assigned_recruiter_id')
                ->whereBetween('offer_letters.accepted_at', [$from, $to])
                ->when($departmentIds !== null, fn ($q) => $q->whereIn('job_openings.department_id', $departmentIds))
                ->groupBy('job_applications.assigned_recruiter_id')
                ->selectRaw('job_applications.assigned_recruiter_id as recruiter_id, count(*) as offers_accepted')
                ->pluck('offers_accepted', 'recruiter_id');

            $hireRows = HiringDecision::query()
                ->where('recommendation', 'hire')
                ->whereBetween('decision_date', [$from->toDateString(), $to->toDateString()])
                ->with(['jobApplication:id,assigned_recruiter_id,applied_date,job_opening_id', 'jobApplication.jobOpening:id,department_id'])
                ->get();

            $hireMetrics = [];
            foreach ($hireRows as $decision) {
                $app = $decision->jobApplication;
                if (! $app?->assigned_recruiter_id) {
                    continue;
                }
                if ($departmentIds !== null && ! in_array($app->jobOpening?->department_id, $departmentIds, true)) {
                    continue;
                }
                $id = $app->assigned_recruiter_id;
                $hireMetrics[$id] ??= ['hires' => 0, 'days' => []];
                $hireMetrics[$id]['hires']++;
                if ($app->applied_date && $decision->decision_date) {
                    $hireMetrics[$id]['days'][] = $app->applied_date->diffInDays($decision->decision_date);
                }
            }

            $rows = [];
            foreach ($recruiters as $id => $row) {
                $days = $hireMetrics[$id]['days'] ?? [];
                $rows[] = [
                    'recruiter_id' => (int) $id,
                    'recruiter_name' => $row->recruiter_name ?? __('Unknown'),
                    'candidates_handled' => (int) $row->candidates_handled,
                    'interviews_scheduled' => (int) ($interviewCounts[$id] ?? 0),
                    'offers_generated' => (int) ($offerCounts[$id] ?? 0),
                    'offers_accepted' => (int) ($acceptedCounts[$id] ?? 0),
                    'successful_hires' => (int) ($hireMetrics[$id]['hires'] ?? 0),
                    'average_hiring_time' => $this->average($days),
                    'average_response_time' => null,
                    'candidate_satisfaction' => null,
                ];
            }

            usort($rows, fn ($a, $b) => $b['successful_hires'] <=> $a['successful_hires']);

            return $rows;
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function candidateAnalytics(array $filters = [], ?User $actor = null): array
    {
        $filters = $this->withScope($filters, $actor);

        return $this->cache->remember('candidate_analytics', $filters, function () use ($filters) {
            [$from, $to] = $this->resolvePeriod($filters);

            $candidates = Candidate::query()->whereBetween('created_at', [$from, $to]);

            $totalCandidates = (clone $candidates)->count();
            $appsPerCandidate = $totalCandidates > 0
                ? round(JobApplication::query()
                    ->where('is_draft', false)
                    ->whereHas('candidate', fn ($q) => $q->whereBetween('created_at', [$from, $to]))
                    ->count() / $totalCandidates, 2)
                : 0;

            $statusDistribution = JobApplication::query()
                ->where('is_draft', false)
                ->whereBetween('applied_date', [$from->toDateString(), $to->toDateString()])
                ->selectRaw('stage, count(*) as total')
                ->groupBy('stage')
                ->pluck('total', 'stage')
                ->all();

            $experience = Candidate::query()
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('experience')
                ->selectRaw('experience, count(*) as total')
                ->groupBy('experience')
                ->orderByDesc('total')
                ->limit(20)
                ->pluck('total', 'experience')
                ->all();

            $locations = Candidate::query()
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('city')
                ->selectRaw("coalesce(city, 'Unknown') as location, count(*) as total")
                ->groupBy('location')
                ->orderByDesc('total')
                ->limit(20)
                ->pluck('total', 'location')
                ->all();

            $noticePeriods = Candidate::query()
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('notice_period')
                ->selectRaw('notice_period, count(*) as total')
                ->groupBy('notice_period')
                ->pluck('total', 'notice_period')
                ->all();

            $salary = Candidate::query()
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('expected_salary')
                ->selectRaw('avg(expected_salary) as avg_expected, min(expected_salary) as min_expected, max(expected_salary) as max_expected')
                ->first();

            $skills = Candidate::query()
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('skills')
                ->where('skills', '!=', '')
                ->pluck('skills');

            $skillCounts = [];
            foreach ($skills as $skillString) {
                foreach (preg_split('/[,;|]+/', (string) $skillString) ?: [] as $skill) {
                    $skill = trim($skill);
                    if ($skill === '') {
                        continue;
                    }
                    $skillCounts[$skill] = ($skillCounts[$skill] ?? 0) + 1;
                }
            }
            arsort($skillCounts);
            $skillCounts = array_slice($skillCounts, 0, 20, true);

            return [
                'applications_per_candidate' => $appsPerCandidate,
                'status_distribution' => $statusDistribution,
                'experience_distribution' => $experience,
                'skill_distribution' => $skillCounts,
                'location_distribution' => $locations,
                'notice_period_distribution' => $noticePeriods,
                'salary_expectations' => [
                    'average' => $salary?->avg_expected !== null ? round((float) $salary->avg_expected, 2) : null,
                    'min' => $salary?->min_expected !== null ? round((float) $salary->min_expected, 2) : null,
                    'max' => $salary?->max_expected !== null ? round((float) $salary->max_expected, 2) : null,
                ],
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function jobOpeningAnalytics(array $filters = [], ?User $actor = null): array
    {
        $filters = $this->withScope($filters, $actor);

        return $this->cache->remember('job_opening_analytics', $filters, function () use ($filters) {
            $departmentIds = $filters['_department_ids'] ?? null;
            $query = JobOpening::query();
            $this->applyDepartmentScope($query, $departmentIds);

            $open = (clone $query)->whereIn('status', ['published', 'paused'])->count();
            $closed = (clone $query)->where('status', 'closed')->count();
            $filled = (clone $query)->where('status', 'filled')->count();
            $total = (clone $query)->count();

            $avgApplicants = DB::table('job_openings')
                ->leftJoin('job_applications', function ($join) {
                    $join->on('job_applications.job_opening_id', '=', 'job_openings.id')
                        ->whereNull('job_applications.deleted_at')
                        ->where('job_applications.is_draft', false);
                })
                ->whereNull('job_openings.deleted_at')
                ->when($departmentIds !== null, fn ($q) => $q->whereIn('job_openings.department_id', $departmentIds))
                ->selectRaw('job_openings.id, count(job_applications.id) as apps')
                ->groupBy('job_openings.id')
                ->get()
                ->avg('apps');

            $avgInterviews = DB::table('job_openings')
                ->leftJoin('job_applications', function ($join) {
                    $join->on('job_applications.job_opening_id', '=', 'job_openings.id')
                        ->whereNull('job_applications.deleted_at');
                })
                ->leftJoin('interview_rounds', function ($join) {
                    $join->on('interview_rounds.job_application_id', '=', 'job_applications.id')
                        ->whereNull('interview_rounds.deleted_at');
                })
                ->whereNull('job_openings.deleted_at')
                ->when($departmentIds !== null, fn ($q) => $q->whereIn('job_openings.department_id', $departmentIds))
                ->selectRaw('job_openings.id, count(interview_rounds.id) as rounds')
                ->groupBy('job_openings.id')
                ->get()
                ->avg('rounds');

            $openingsWithOffers = JobOpening::query()
                ->when($departmentIds !== null, fn ($q) => $q->whereIn('department_id', $departmentIds))
                ->whereHas('applications.offerLetters')
                ->count();

            return [
                'open_positions' => $open,
                'closed_positions' => $closed,
                'filled_positions' => $filled,
                'average_applicants' => $avgApplicants !== null ? round((float) $avgApplicants, 2) : 0,
                'average_interviews' => $avgInterviews !== null ? round((float) $avgInterviews, 2) : 0,
                'offer_rate' => $this->percent($openingsWithOffers, $total),
                'fill_rate' => $this->percent($filled, $total),
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function departmentAnalytics(array $filters = [], ?User $actor = null): array
    {
        $filters = $this->withScope($filters, $actor);

        return $this->cache->remember('department_analytics', $filters, function () use ($filters) {
            [$from, $to] = $this->resolvePeriod($filters);
            $departmentIds = $filters['_department_ids'] ?? null;

            $byDepartment = DB::table('hiring_decisions')
                ->join('job_applications', 'job_applications.id', '=', 'hiring_decisions.job_application_id')
                ->join('job_openings', 'job_openings.id', '=', 'job_applications.job_opening_id')
                ->leftJoin('hrms_departments', 'hrms_departments.id', '=', 'job_openings.department_id')
                ->whereNull('hiring_decisions.deleted_at')
                ->where('hiring_decisions.recommendation', 'hire')
                ->whereBetween('hiring_decisions.decision_date', [$from->toDateString(), $to->toDateString()])
                ->when($departmentIds !== null, fn ($q) => $q->whereIn('job_openings.department_id', $departmentIds))
                ->groupBy('job_openings.department_id', 'hrms_departments.name')
                ->selectRaw('job_openings.department_id, coalesce(hrms_departments.name, ?) as name, count(*) as hires', [__('Unassigned')])
                ->orderByDesc('hires')
                ->get()
                ->map(fn ($r) => ['id' => $r->department_id, 'name' => $r->name, 'hires' => (int) $r->hires])
                ->all();

            $byLocation = DB::table('hiring_decisions')
                ->join('job_applications', 'job_applications.id', '=', 'hiring_decisions.job_application_id')
                ->join('job_openings', 'job_openings.id', '=', 'job_applications.job_opening_id')
                ->whereNull('hiring_decisions.deleted_at')
                ->where('hiring_decisions.recommendation', 'hire')
                ->whereBetween('hiring_decisions.decision_date', [$from->toDateString(), $to->toDateString()])
                ->when($departmentIds !== null, fn ($q) => $q->whereIn('job_openings.department_id', $departmentIds))
                ->groupBy('job_openings.location')
                ->selectRaw("coalesce(nullif(job_openings.location, ''), ?) as location, count(*) as hires", [__('Unspecified')])
                ->orderByDesc('hires')
                ->get()
                ->map(fn ($r) => ['location' => $r->location, 'hires' => (int) $r->hires])
                ->all();

            $byDesignation = DB::table('hiring_decisions')
                ->join('job_applications', 'job_applications.id', '=', 'hiring_decisions.job_application_id')
                ->join('job_openings', 'job_openings.id', '=', 'job_applications.job_opening_id')
                ->leftJoin('hrms_designations', 'hrms_designations.id', '=', 'job_openings.designation_id')
                ->whereNull('hiring_decisions.deleted_at')
                ->where('hiring_decisions.recommendation', 'hire')
                ->whereBetween('hiring_decisions.decision_date', [$from->toDateString(), $to->toDateString()])
                ->when($departmentIds !== null, fn ($q) => $q->whereIn('job_openings.department_id', $departmentIds))
                ->groupBy('job_openings.designation_id', 'hrms_designations.name')
                ->selectRaw('job_openings.designation_id, coalesce(hrms_designations.name, ?) as name, count(*) as hires', [__('Unassigned')])
                ->orderByDesc('hires')
                ->get()
                ->map(fn ($r) => ['id' => $r->designation_id, 'name' => $r->name, 'hires' => (int) $r->hires])
                ->all();

            $vacancyAging = JobOpening::query()
                ->whereIn('status', ['published', 'paused'])
                ->whereNotNull('publish_date')
                ->when($departmentIds !== null, fn ($q) => $q->whereIn('department_id', $departmentIds))
                ->with(['department:id,name'])
                ->get()
                ->map(fn (JobOpening $o) => [
                    'id' => $o->id,
                    'title' => $o->title,
                    'department' => $o->department?->name,
                    'publish_date' => $o->publish_date?->toDateString(),
                    'age_days' => $o->publish_date?->diffInDays(now()) ?? 0,
                ])
                ->sortByDesc('age_days')
                ->values()
                ->all();

            return [
                'hiring_by_department' => $byDepartment,
                'hiring_by_location' => $byLocation,
                'hiring_by_designation' => $byDesignation,
                'vacancy_aging' => $vacancyAging,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function averageStageDuration(string $stage, array $filters): ?float
    {
        if ($stage === 'onboarding') {
            return null;
        }

        [$from, $to] = $this->resolvePeriod($filters);

        $logs = AuditLog::query()
            ->where('auditable_type', (new JobApplication)->getMorphClass())
            ->where('event', 'status_changed')
            ->whereBetween('created_at', [$from, $to])
            ->where('properties->to', $stage)
            ->orderBy('auditable_id')
            ->orderBy('created_at')
            ->get(['auditable_id', 'created_at']);

        if ($logs->isEmpty()) {
            $apps = JobApplication::query()
                ->where('stage', $stage)
                ->where('is_draft', false)
                ->whereBetween('applied_date', [$from->toDateString(), $to->toDateString()])
                ->get(['applied_date', 'updated_at']);

            $days = [];
            foreach ($apps as $app) {
                if ($app->applied_date) {
                    $days[] = $app->applied_date->diffInDays($app->updated_at ?? now());
                }
            }

            return $this->average($days);
        }

        $enteredAt = [];
        foreach ($logs as $log) {
            $enteredAt[$log->auditable_id] ??= $log->created_at;
        }

        $durations = [];
        foreach ($enteredAt as $applicationId => $entered) {
            $left = AuditLog::query()
                ->where('auditable_type', (new JobApplication)->getMorphClass())
                ->where('auditable_id', $applicationId)
                ->where('event', 'status_changed')
                ->where('created_at', '>', $entered)
                ->orderBy('created_at')
                ->value('created_at');

            $end = $left ? \Carbon\Carbon::parse($left) : now();
            $durations[] = $entered->diffInDays($end);
        }

        return $this->average($durations);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function withScope(array $filters, ?User $actor): array
    {
        if (! array_key_exists('_department_ids', $filters)) {
            $filters['_department_ids'] = $this->authorizedDepartmentIds($actor);
        }
        $filters['period'] = $filters['period'] ?? 'month';

        return $filters;
    }
}
