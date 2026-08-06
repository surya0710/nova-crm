<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\PortfolioReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PortfolioReport> */
class PortfolioReportFactory extends Factory
{
    protected $model = PortfolioReport::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'portfolio_id' => Portfolio::factory(),
            'program_id' => null,
            'report_type' => fake()->randomElement(['summary', 'risk', 'budget', 'status']),
            'format' => 'pdf',
            'generated_by' => User::factory(),
            'filters' => [],
            'storage_path' => 'portfolio-reports/'.fake()->uuid().'.pdf',
            'generated_at' => now(),
            'scheduled_for' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (PortfolioReport $report) {
            $this->alignOrganization($report);
        })->afterCreating(function (PortfolioReport $report) {
            $this->alignOrganization($report, true);
        });
    }

    protected function alignOrganization(PortfolioReport $report, bool $persist = false): void
    {
        if (! $report->portfolio_id) {
            return;
        }

        $portfolio = Portfolio::query()->find($report->portfolio_id);

        if ($portfolio && $report->organization_id !== $portfolio->organization_id) {
            $report->organization_id = $portfolio->organization_id;

            if ($persist) {
                $report->saveQuietly();
            }
        }
    }
}
