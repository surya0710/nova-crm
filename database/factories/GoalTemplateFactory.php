<?php

namespace Database\Factories;

use App\Models\GoalCategory;
use App\Models\GoalTemplate;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GoalTemplate> */
class GoalTemplateFactory extends Factory
{
    protected $model = GoalTemplate::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'goal_category_id' => null,
            'title' => fake()->sentence(3),
            'description' => null,
            'goal_type' => 'individual',
            'default_weight' => 20,
            'measurement_type' => 'percentage',
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (GoalTemplate $template) {
            if ($template->goal_category_id && ! $template->organization_id) {
                $category = GoalCategory::query()->find($template->goal_category_id);
                if ($category) {
                    $template->organization_id = $category->organization_id;
                }
            }
        })->afterCreating(function (GoalTemplate $template) {
            if (! $template->goal_category_id) {
                return;
            }
            $category = $template->category;
            if ($category && $template->organization_id !== $category->organization_id) {
                $template->forceFill(['organization_id' => $category->organization_id])->saveQuietly();
            }
        });
    }
}
