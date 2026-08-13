<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\WfhRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WfhRequest> */
class WfhRequestFactory extends Factory
{
    protected $model = WfhRequest::class;

    public function definition(): array
    {
        $day = now()->toDateString();

        return [
            'organization_id' => Organization::factory(),
            'employee_id' => Employee::factory(),
            'work_date' => $day,
            'start_date' => $day,
            'end_date' => $day,
            'reason' => fake()->optional()->sentence(),
            'status' => 'pending',
            'submitted_at' => now(),
            'cancelled_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => 'approved',
            'submitted_at' => now()->subDay(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
    }

    public function range(string $start, string $end): static
    {
        return $this->state(fn (): array => [
            'work_date' => $start,
            'start_date' => $start,
            'end_date' => $end,
        ]);
    }
}
