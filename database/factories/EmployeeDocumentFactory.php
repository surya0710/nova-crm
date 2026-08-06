<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeDocument> */
class EmployeeDocumentFactory extends Factory
{
    protected $model = EmployeeDocument::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'employee_id' => Employee::factory(),
            'category' => fake()->randomElement(array_keys(config('hrms.document_categories', ['other' => 'Other']))),
            'title' => fake()->sentence(3),
            'expires_at' => fake()->optional()->dateTimeBetween('now', '+2 years'),
            'verification_status' => 'pending',
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => ['verification_status' => 'verified']);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDays(5)]);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn () => ['expires_at' => now()->addDays(7)]);
    }
}
