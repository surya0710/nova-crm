<?php

namespace Database\Factories;

use App\Models\Competency;
use App\Models\CompetencyCategory;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Competency> */
class CompetencyFactory extends Factory
{
    protected $model = Competency::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'competency_category_id' => CompetencyCategory::factory(),
            'name' => fake()->unique()->words(2, true),
            'code' => strtoupper(fake()->unique()->bothify('CMP-###')),
            'description' => null,
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Competency $competency) {
            if ($competency->competency_category_id && ! $competency->organization_id) {
                $category = CompetencyCategory::query()->find($competency->competency_category_id);
                if ($category) {
                    $competency->organization_id = $category->organization_id;
                }
            }
        })->afterCreating(function (Competency $competency) {
            $category = $competency->category;
            if ($category && $competency->organization_id !== $category->organization_id) {
                $competency->forceFill(['organization_id' => $category->organization_id])->saveQuietly();
            }
        });
    }
}
