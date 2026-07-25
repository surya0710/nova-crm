<?php

namespace App\Services;

use App\Events\PortfolioReportGenerated;
use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\PortfolioReport;
use App\Models\Program;
use App\Models\ProjectBudget;
use App\Models\ProjectRisk;
use App\Models\User;
use App\Notifications\CrmNotification;
use App\Workflow\WorkflowRuntimeContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PortfolioReportingService
{
    public const REPORT_TYPES = [
        'portfolio' => 'Portfolio Summary',
        'program' => 'Program Summary',
        'risk' => 'Risk Report',
        'budget' => 'Budget Report',
        'executive' => 'Executive Report',
        'variance' => 'Variance Report',
        'forecast' => 'Forecast Report',
    ];

    public function __construct(
        protected PortfolioStatisticsService $statistics,
        protected ForecastService $forecast,
        protected RiskManagementService $risks,
        protected ?VarianceAnalysisService $variance = null,
        protected ?ExecutiveDashboardService $executive = null,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function generate(
        Organization $organization,
        string $reportType,
        string $format,
        array $filters,
        User $actor,
        ?Portfolio $portfolio = null,
        ?Program $program = null,
    ): PortfolioReport {
        $this->validateReportType($reportType);
        $this->validateFormat($format);

        $payload = $this->buildPayload($reportType, $organization, $filters, $portfolio, $program);
        $storagePath = $this->exportPayload($payload, $reportType, $format, $organization);

        $report = PortfolioReport::query()->create([
            'organization_id' => $organization->id,
            'portfolio_id' => $portfolio?->id ?? ($filters['portfolio_id'] ?? null),
            'program_id' => $program?->id ?? ($filters['program_id'] ?? null),
            'report_type' => $reportType,
            'format' => $format,
            'generated_by' => $actor->id,
            'filters' => $filters,
            'storage_path' => $storagePath,
            'generated_at' => now(),
        ]);

        $runtime = app(WorkflowRuntimeContext::class);
        event(PortfolioReportGenerated::forModel(
            $report->fresh(),
            [
                'actor_id' => $actor->id,
                'report_type' => $reportType,
                'format' => $format,
                'portfolio_id' => $report->portfolio_id,
                'program_id' => $report->program_id,
            ],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        $this->notifyGenerator($organization, $actor, $report, $portfolio);

        return $report->fresh(['generator', 'portfolio', 'program']);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function buildPayload(
        string $reportType,
        Organization $organization,
        array $filters,
        ?Portfolio $portfolio,
        ?Program $program,
    ): array {
        return match ($reportType) {
            'portfolio' => $this->buildPortfolioPayload($organization, $portfolio, $filters),
            'program' => $this->buildProgramPayload($organization, $program, $filters),
            'risk' => $this->buildRiskPayload($organization, $filters),
            'budget' => $this->buildBudgetPayload($organization, $portfolio, $filters),
            'executive' => $this->buildExecutivePayload($organization),
            'variance' => $this->buildVariancePayload($organization, $portfolio, $filters),
            'forecast' => $this->buildForecastPayload($organization, $portfolio),
            default => throw ValidationException::withMessages([
                'report_type' => __('Invalid report type.'),
            ]),
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function buildPortfolioPayload(Organization $organization, ?Portfolio $portfolio, array $filters): array
    {
        if (! $portfolio && ! empty($filters['portfolio_id'])) {
            $portfolio = Portfolio::query()
                ->where('organization_id', $organization->id)
                ->findOrFail((int) $filters['portfolio_id']);
        }

        if (! $portfolio) {
            $rows = Portfolio::query()
                ->where('organization_id', $organization->id)
                ->whereNull('archived_at')
                ->withCount('projects')
                ->get()
                ->map(fn (Portfolio $p) => [
                    $p->code,
                    $p->name,
                    $p->status,
                    $p->projects_count,
                    $p->owner?->name ?? '',
                ])->all();

            return [
                'title' => __('Portfolios Report'),
                'headers' => [__('Code'), __('Name'), __('Status'), __('Projects'), __('Owner')],
                'rows' => $rows,
            ];
        }

        $stats = $this->statistics->forPortfolio($portfolio);

        return [
            'title' => __('Portfolio Summary: :name', ['name' => $portfolio->name]),
            'portfolio' => $portfolio->only(['id', 'name', 'code', 'status']),
            'statistics' => $stats,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function buildProgramPayload(Organization $organization, ?Program $program, array $filters): array
    {
        if (! $program && ! empty($filters['program_id'])) {
            $program = Program::query()
                ->where('organization_id', $organization->id)
                ->findOrFail((int) $filters['program_id']);
        }

        if (! $program) {
            throw ValidationException::withMessages([
                'program_id' => __('A program is required for this report type.'),
            ]);
        }

        $program->loadCount('projects');

        return [
            'title' => __('Program Summary: :name', ['name' => $program->name]),
            'program' => $program->only(['id', 'name', 'code', 'status', 'portfolio_id']),
            'project_count' => $program->projects_count,
            'projects' => $program->projects()->get(['projects.id', 'projects.name', 'projects.completion_percentage'])
                ->map(fn ($p) => [$p->name, $p->completion_percentage])
                ->all(),
            'headers' => [__('Project'), __('Completion %')],
            'rows' => $program->projects()->get(['projects.id', 'projects.name', 'projects.completion_percentage'])
                ->map(fn ($p) => [$p->name, $p->completion_percentage])
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function buildRiskPayload(Organization $organization, array $filters): array
    {
        $risks = $this->risks->list($organization, $filters);
        $matrix = $this->risks->matrix(
            $organization,
            isset($filters['project_id']) ? (int) $filters['project_id'] : null,
            isset($filters['portfolio_id']) ? (int) $filters['portfolio_id'] : null,
        );

        return [
            'title' => __('Risk Report'),
            'headers' => [__('Title'), __('Status'), __('Probability'), __('Impact'), __('Severity'), __('Owner')],
            'rows' => $risks->map(fn (ProjectRisk $risk) => [
                $risk->title,
                $risk->status,
                $risk->probability,
                $risk->impact,
                $risk->severity,
                $risk->owner?->name ?? '',
            ])->all(),
            'matrix' => $matrix,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function buildBudgetPayload(Organization $organization, ?Portfolio $portfolio, array $filters): array
    {
        $query = ProjectBudget::query()
            ->where('organization_id', $organization->id)
            ->with('project');

        if ($portfolio) {
            $query->whereIn('project_id', $portfolio->projects()->pluck('projects.id'));
        } elseif (! empty($filters['portfolio_id'])) {
            $ids = Portfolio::query()->find((int) $filters['portfolio_id'])?->projects()->pluck('projects.id') ?? collect();
            $query->whereIn('project_id', $ids);
        }

        $budgets = $query->get();

        return [
            'title' => __('Budget Report'),
            'headers' => [__('Project'), __('Budget'), __('Planned'), __('Actual'), __('Forecast'), __('Variance'), __('Status')],
            'rows' => $budgets->map(fn (ProjectBudget $b) => [
                $b->project?->name ?? '',
                $b->name,
                $b->planned_total,
                $b->actual_total,
                $b->forecast_total,
                $b->variance_total,
                $b->status,
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildExecutivePayload(Organization $organization): array
    {
        $dashboard = $this->executive()
            ? $this->executive()->forOrganization($organization)
            : ['note' => 'Executive dashboard unavailable'];

        return [
            'title' => __('Executive Portfolio Report'),
            'dashboard' => $dashboard,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function buildVariancePayload(Organization $organization, ?Portfolio $portfolio, array $filters): array
    {
        if (! $this->variance()) {
            return [
                'title' => __('Variance Report'),
                'headers' => [__('Message')],
                'rows' => [[__('Variance analysis service unavailable')]],
            ];
        }

        $portfolio ??= ! empty($filters['portfolio_id'])
            ? Portfolio::query()->where('organization_id', $organization->id)->find((int) $filters['portfolio_id'])
            : null;

        $projects = $portfolio
            ? $portfolio->projects()->get()
            : \App\Models\Project::query()->where('organization_id', $organization->id)->where('is_archived', false)->limit(50)->get();

        $rows = [];
        foreach ($projects as $project) {
            $analysis = $this->variance()->forProject($project);
            if ($analysis === null) {
                continue;
            }
            $rows[] = [
                $project->name,
                $analysis['schedule']['drift_percent'] ?? '',
                $analysis['budget']['drift_percent'] ?? '',
                $analysis['scope']['drift_percent'] ?? '',
                $analysis['progress']['drift_percent'] ?? '',
                ($analysis['flags'] ?? []) ? implode(',', $analysis['flags']) : '',
            ];
        }

        return [
            'title' => __('Variance Report'),
            'headers' => [__('Project'), __('Schedule %'), __('Budget %'), __('Scope %'), __('Progress %'), __('Flags')],
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildForecastPayload(Organization $organization, ?Portfolio $portfolio): array
    {
        if ($portfolio) {
            $forecast = $this->forecast->forPortfolio($portfolio, null, false);

            return [
                'title' => __('Forecast Report: :name', ['name' => $portfolio->name]),
                'forecast' => $forecast,
            ];
        }

        return [
            'title' => __('Organization Forecast Report'),
            'capacity_note' => __('Select a portfolio for detailed forecast.'),
            'organization_id' => $organization->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function exportPayload(
        array $payload,
        string $reportType,
        string $format,
        Organization $organization,
    ): string {
        $directory = 'portfolio-reports/'.$organization->id;
        Storage::makeDirectory($directory);

        $basename = $reportType.'-'.now()->format('Ymd-His');

        return match ($format) {
            'csv' => $this->exportCsv($directory, $basename, $payload),
            'pdf' => $this->exportPdf($directory, $basename, $payload),
            'excel' => $this->exportExcel($directory, $basename, $payload),
            default => $this->exportCsv($directory, $basename, $payload),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function exportCsv(string $directory, string $basename, array $payload): string
    {
        $path = $directory.'/'.$basename.'.csv';
        $handle = fopen(Storage::path($path), 'w');

        fputcsv($handle, [($payload['title'] ?? __('Report'))]);

        if (isset($payload['headers'], $payload['rows'])) {
            fputcsv($handle, $payload['headers']);
            foreach ($payload['rows'] as $row) {
                fputcsv($handle, $row);
            }
        } else {
            foreach ($this->flattenPayload($payload) as $line) {
                fputcsv($handle, [$line]);
            }
        }

        fclose($handle);

        return $path;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function exportPdf(string $directory, string $basename, array $payload): string
    {
        $html = $this->payloadToHtml($payload);
        $path = $directory.'/'.$basename.'.pdf';

        Pdf::loadHTML($html)->save(Storage::path($path));

        return $path;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function exportExcel(string $directory, string $basename, array $payload): string
    {
        $path = $directory.'/'.$basename.'.xlsx';
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue([1, 1], (string) ($payload['title'] ?? __('Report')));

        $rowIndex = 3;

        if (isset($payload['headers'], $payload['rows'])) {
            foreach (array_values($payload['headers']) as $colIndex => $header) {
                $sheet->setCellValue([$colIndex + 1, $rowIndex], (string) $header);
            }
            $rowIndex++;

            foreach ($payload['rows'] as $row) {
                foreach (array_values($row) as $colIndex => $value) {
                    $sheet->setCellValue([$colIndex + 1, $rowIndex], $value);
                }
                $rowIndex++;
            }
        } else {
            foreach ($this->flattenPayload($payload) as $line) {
                $sheet->setCellValue([1, $rowIndex], $line);
                $rowIndex++;
            }
        }

        (new Xlsx($spreadsheet))->save(Storage::path($path));

        return $path;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function payloadToHtml(array $payload): string
    {
        $title = e((string) ($payload['title'] ?? __('Report')));
        $body = '';

        if (isset($payload['headers'], $payload['rows'])) {
            $body .= '<table border="1" cellpadding="4"><thead><tr>';
            foreach ($payload['headers'] as $header) {
                $body .= '<th>'.e((string) $header).'</th>';
            }
            $body .= '</tr></thead><tbody>';
            foreach ($payload['rows'] as $row) {
                $body .= '<tr>';
                foreach ($row as $cell) {
                    $body .= '<td>'.e((string) $cell).'</td>';
                }
                $body .= '</tr>';
            }
            $body .= '</tbody></table>';
        } else {
            foreach ($this->flattenPayload($payload) as $line) {
                $body .= '<p>'.e($line).'</p>';
            }
        }

        return "<html><head><meta charset=\"utf-8\"><title>{$title}</title></head><body><h1>{$title}</h1>{$body}</body></html>";
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    protected function flattenPayload(array $payload, string $prefix = ''): array
    {
        $lines = [];

        foreach ($payload as $key => $value) {
            if ($key === 'title') {
                continue;
            }

            $label = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $lines = array_merge($lines, $this->flattenPayload($value, $label));
            } else {
                $lines[] = $label.': '.(string) $value;
            }
        }

        return $lines;
    }

    protected function validateReportType(string $reportType): void
    {
        $types = config('projects.portfolio_report_types', self::REPORT_TYPES);

        if (! array_key_exists($reportType, $types)) {
            throw ValidationException::withMessages([
                'report_type' => __('Invalid report type.'),
            ]);
        }
    }

    protected function validateFormat(string $format): void
    {
        $formats = config('projects.portfolio_report_formats', config('projects.report_formats', [
            'pdf' => 'PDF',
            'excel' => 'Excel',
            'csv' => 'CSV',
        ]));

        if (! array_key_exists($format, $formats)) {
            throw ValidationException::withMessages([
                'format' => __('Invalid report format.'),
            ]);
        }
    }

    protected function notifyGenerator(
        Organization $organization,
        User $actor,
        PortfolioReport $report,
        ?Portfolio $portfolio,
    ): void {
        $typeLabel = self::REPORT_TYPES[$report->report_type] ?? $report->report_type;

        $actor->notify(new CrmNotification(
            title: __('Portfolio report generated'),
            message: __('Your :type report is ready.', ['type' => $typeLabel]),
            actionUrl: Route::has('portfolios.show') && $portfolio
                ? route('portfolios.show', $portfolio)
                : null,
            organizationId: (int) $organization->id,
        ));
    }

    protected function variance(): ?VarianceAnalysisService
    {
        if ($this->variance !== null) {
            return $this->variance;
        }

        if (! class_exists(VarianceAnalysisService::class)) {
            return null;
        }

        return $this->variance = app(VarianceAnalysisService::class);
    }

    protected function executive(): ?ExecutiveDashboardService
    {
        if ($this->executive !== null) {
            return $this->executive;
        }

        if (! class_exists(ExecutiveDashboardService::class)) {
            return null;
        }

        return $this->executive = app(ExecutiveDashboardService::class);
    }
}
