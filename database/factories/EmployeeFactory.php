<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Employee> */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'employee_code' => strtoupper(fake()->unique()->bothify('EMP-####')),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'employment_type' => 'full_time',
            'status' => 'active',
            'joining_date' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
        ];
    }

    public function onProbation(): static
    {
        return $this->state(fn () => [
            'status' => 'on_probation',
            'probation_end_date' => now()->addDays(30)->toDateString(),
        ]);
    }
}
