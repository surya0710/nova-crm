<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\JobApplication;
use App\Models\OfferLetter;
use App\Models\OfferTemplate;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OfferLetter> */
class OfferLetterFactory extends Factory
{
    protected $model = OfferLetter::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'candidate_id' => Candidate::factory(),
            'job_application_id' => JobApplication::factory(),
            'offer_template_id' => OfferTemplate::factory(),
            'proposed_salary' => fake()->randomFloat(2, 50000, 150000),
            'variable_pay' => fake()->randomFloat(2, 5000, 30000),
            'benefits' => 'Health insurance, paid time off',
            'joining_date' => now()->addMonth()->toDateString(),
            'expiry_date' => now()->addMonths(2)->toDateString(),
            'status' => 'draft',
            'generated_content' => 'Offer letter content',
        ];
    }
}
