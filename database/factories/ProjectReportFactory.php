<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProjectReport> */
class ProjectReportFactory extends Factory
{
    protected $model = ProjectReport::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'project_id' => Project::factory(),
            'report_type' => fake()->randomElement(array_keys(config('projects.report_types', ['summary' => 'Summary']))),
            'generated_by' => User::factory(),
            'filters' => [],
            'storage_path' => 'project-reports/'.fake()->uuid().'.csv',
            'generated_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ProjectReport $report) {
            $this->alignOrganization($report);
        })->afterCreating(function (ProjectReport $report) {
            $this->alignOrganization($report, true);
        });
    }

    protected function alignOrganization(ProjectReport $report, bool $persist = false): void
    {
        if (! $report->project_id) {
            return;
        }

        $project = Project::query()->find($report->project_id);

        if ($project && $report->organization_id !== $project->organization_id) {
            $report->organization_id = $project->organization_id;

            if ($persist) {
                $report->saveQuietly();
            }
        }
    }

    public function organizationWide(): static
    {
        return $this->state(fn () => [
            'project_id' => null,
        ]);
    }
}
