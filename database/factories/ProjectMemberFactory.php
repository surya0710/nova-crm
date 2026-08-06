<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProjectMember> */
class ProjectMemberFactory extends Factory
{
    protected $model = ProjectMember::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'project_role' => fake()->randomElement(array_keys(config('projects.roles'))),
            'joined_at' => now(),
            'left_at' => null,
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ProjectMember $member) {
            $this->alignOrganization($member);
        })->afterCreating(function (ProjectMember $member) {
            $this->alignOrganization($member, true);
        });
    }

    protected function alignOrganization(ProjectMember $member, bool $persist = false): void
    {
        $project = $member->project_id
            ? Project::query()->find($member->project_id)
            : null;

        if ($project && $member->organization_id !== $project->organization_id) {
            $member->organization_id = $project->organization_id;

            if ($persist) {
                $member->saveQuietly();
            }
        }
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
            'left_at' => now(),
        ]);
    }
}
