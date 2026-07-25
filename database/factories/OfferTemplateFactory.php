<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\OfferTemplate;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OfferTemplate> */
class OfferTemplateFactory extends Factory
{
    protected $model = OfferTemplate::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(3, true).' Offer Template',
            'department_id' => Department::factory(),
            'employment_type' => 'full_time',
            'is_active' => true,
            'template_content' => 'Dear {{candidate_name}}, we are pleased to offer you the position of {{position}} at a salary of {{salary}}.',
        ];
    }
}
