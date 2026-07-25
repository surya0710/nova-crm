<?php

namespace App\Services\Recruitment;

use App\Models\Candidate;
use App\Models\HiringDecision;
use App\Models\JobApplication;
use App\Models\OfferLetter;
use App\Models\User;
use App\Services\Recruitment\Concerns\ResolvesAnalyticsFilters;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class RecruitmentTrendService
{
    use ResolvesAnalyticsFilters;

    public function __construct(protected RecruitmentAnalyticsCache $cache) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function trends(array $filters = [], ?User $actor = null): array
    {
        if (! array_key_exists('_department_ids', $filters)) {
            $filters['_department_ids'] = $this->authorizedDepartmentIds($actor);
        }
        $filters['period'] = $filters['period'] ?? 'month';

        return $this->cache->remember('trends', $filters, function () use ($filters) {
            [$from, $to] = $this->resolvePeriod($filters);
            $departmentIds = $filters['_department_ids'] ?? null;
            $bucket = $this->bucketSize($from, $to);
            $labels = $this->bucketLabels($from, $to, $bucket);

            return [
                'hiring_trends' => $this->seriesFromQuery(
                    HiringDecision::query()
                        ->where('recommendation', 'hire')
                        ->whereBetween('decision_date', [$from->toDateString(), $to->toDateString()])
                        ->when($departmentIds !== null, function ($q) use ($departmentIds) {
                            $q->whereHas('jobApplication.jobOpening', fn ($oq) => $oq->whereIn('department_id', $departmentIds));
                        }),
                    'decision_date',
                    $labels,
                    $bucket,
                ),
                'candidate_growth' => $this->seriesFromQuery(
                    Candidate::query()->whereBetween('created_at', [$from, $to]),
                    'created_at',
                    $labels,
                    $bucket,
                ),
                'offer_trends' => $this->seriesFromQuery(
                    OfferLetter::query()
                        ->whereBetween('created_at', [$from, $to])
                        ->when($departmentIds !== null, function ($q) use ($departmentIds) {
                            $q->whereHas('jobApplication.jobOpening', fn ($oq) => $oq->whereIn('department_id', $departmentIds));
                        }),
                    'created_at',
                    $labels,
                    $bucket,
                ),
                'recruitment_volume' => $this->seriesFromQuery(
                    JobApplication::query()
                        ->where('is_draft', false)
                        ->whereBetween('applied_date', [$from->toDateString(), $to->toDateString()])
                        ->when($departmentIds !== null, function ($q) use ($departmentIds) {
                            $q->whereHas('jobOpening', fn ($oq) => $oq->whereIn('department_id', $departmentIds));
                        }),
                    'applied_date',
                    $labels,
                    $bucket,
                ),
                'source_trends' => $this->sourceTrends($from, $to, $departmentIds, $labels, $bucket),
                'bucket' => $bucket,
                'period' => [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ],
            ];
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  list<string>  $labels
     * @return list<array{label: string, total: int}>
     */
    protected function seriesFromQuery($query, string $dateColumn, array $labels, string $bucket): array
    {
        $format = $bucket === 'month' ? '%Y-%m' : '%Y-%m-%d';
        $rows = (clone $query)
            ->selectRaw("date_format({$dateColumn}, '{$format}') as bucket, count(*) as total")
            ->groupBy('bucket')
            ->pluck('total', 'bucket')
            ->all();

        return array_map(fn (string $label) => [
            'label' => $label,
            'total' => (int) ($rows[$label] ?? 0),
        ], $labels);
    }

    /**
     * @param  list<int>|null  $departmentIds
     * @param  list<string>  $labels
     * @return list<array<string, mixed>>
     */
    protected function sourceTrends(Carbon $from, Carbon $to, ?array $departmentIds, array $labels, string $bucket): array
    {
        $format = $bucket === 'month' ? '%Y-%m' : '%Y-%m-%d';

        $rows = DB::table('job_applications')
            ->leftJoin('job_openings', 'job_openings.id', '=', 'job_applications.job_opening_id')
            ->whereNull('job_applications.deleted_at')
            ->where('job_applications.is_draft', false)
            ->whereBetween('job_applications.applied_date', [$from->toDateString(), $to->toDateString()])
            ->when($departmentIds !== null, fn ($q) => $q->whereIn('job_openings.department_id', $departmentIds))
            ->selectRaw("coalesce(nullif(job_applications.source, ''), 'other') as source")
            ->selectRaw("date_format(job_applications.applied_date, '{$format}') as bucket")
            ->selectRaw('count(*) as total')
            ->groupBy('source', 'bucket')
            ->get();

        $bySource = [];
        foreach ($rows as $row) {
            $bySource[$row->source][$row->bucket] = (int) $row->total;
        }

        $sourceLabels = config('hrms.recruitment.candidate_sources', []);
        $result = [];
        foreach ($bySource as $source => $buckets) {
            $result[] = [
                'source' => $source,
                'label' => $sourceLabels[$source] ?? ucfirst((string) $source),
                'series' => array_map(fn (string $label) => [
                    'label' => $label,
                    'total' => (int) ($buckets[$label] ?? 0),
                ], $labels),
            ];
        }

        return $result;
    }

    protected function bucketSize(Carbon $from, Carbon $to): string
    {
        return $from->diffInDays($to) > 62 ? 'month' : 'day';
    }

    /**
     * @return list<string>
     */
    protected function bucketLabels(Carbon $from, Carbon $to, string $bucket): array
    {
        if ($bucket === 'month') {
            $period = CarbonPeriod::create($from->copy()->startOfMonth(), '1 month', $to->copy()->endOfMonth());
            $labels = [];
            foreach ($period as $date) {
                $labels[] = $date->format('Y-m');
            }

            return $labels;
        }

        $period = CarbonPeriod::create($from->copy()->startOfDay(), '1 day', $to->copy()->endOfDay());
        $labels = [];
        foreach ($period as $date) {
            $labels[] = $date->format('Y-m-d');
        }

        return $labels;
    }
}
