<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Program> */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'portfolio_id' => Portfolio::factory(),
            'name' => fake()->words(3, true),
            'code' => strtoupper(fake()->unique()->bothify('PG-####')),
            'description' => fake()->optional()->paragraph(),
            'manager_id' => User::factory(),
            'status' => 'active',
            'color' => fake()->optional()->hexColor(),
            'start_date' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'target_end_date' => fake()->optional()->dateTimeBetween('now', '+1 year'),
            'archived_at' => null,
            'metadata' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Program $program) {
            $this->alignOrganization($program);
        })->afterCreating(function (Program $program) {
            $this->alignOrganization($program, true);
        });
    }

    protected function alignOrganization(Program $program, bool $persist = false): void
    {
        if (! $program->portfolio_id) {
            return;
        }

        $portfolio = Portfolio::query()->find($program->portfolio_id);

        if ($portfolio && $program->organization_id !== $portfolio->organization_id) {
            $program->organization_id = $portfolio->organization_id;

            if ($persist) {
                $program->saveQuietly();
            }
        }
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => 'archived',
            'archived_at' => now(),
        ]);
    }
}
