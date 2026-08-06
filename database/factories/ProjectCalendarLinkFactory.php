<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectCalendarLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProjectCalendarLink> */
class ProjectCalendarLinkFactory extends Factory
{
    protected $model = ProjectCalendarLink::class;

    public function definition(): array
    {
        $startsAt = fake()->optional()->dateTimeBetween('now', '+1 month');

        return [
            'organization_id' => Organization::factory(),
            'project_id' => Project::factory(),
            'task_id' => null,
            'milestone_id' => null,
            'user_id' => User::factory(),
            'provider' => 'internal',
            'external_event_id' => null,
            'event_type' => fake()->randomElement(['deadline', 'milestone', 'meeting', 'reminder']),
            'title' => fake()->sentence(3),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt ? fake()->optional()->dateTimeBetween($startsAt, '+2 hours') : null,
            'due_date' => fake()->optional()->dateTimeBetween('now', '+2 months'),
            'sync_status' => 'synced',
            'last_synced_at' => now(),
            'metadata' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ProjectCalendarLink $link) {
            $this->alignOrganization($link);
        })->afterCreating(function (ProjectCalendarLink $link) {
            $this->alignOrganization($link, true);
        });
    }

    protected function alignOrganization(ProjectCalendarLink $link, bool $persist = false): void
    {
        $project = $link->project_id
            ? Project::query()->find($link->project_id)
            : null;

        if ($project && $link->organization_id !== $project->organization_id) {
            $link->organization_id = $project->organization_id;

            if ($persist) {
                $link->saveQuietly();
            }
        }
    }
}
