<?php

namespace Database\Factories;

use App\Models\Deliverable;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deliverable>
 */
class DeliverableFactory extends Factory
{
    protected $model = Deliverable::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'project_id' => Project::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'status' => 'draft',
            'due_date' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'completion_percentage' => 0,
        ];
    }
}
