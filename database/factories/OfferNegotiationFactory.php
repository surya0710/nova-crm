<?php

namespace Database\Factories;

use App\Models\OfferLetter;
use App\Models\OfferNegotiation;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OfferNegotiation> */
class OfferNegotiationFactory extends Factory
{
    protected $model = OfferNegotiation::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'offer_letter_id' => OfferLetter::factory(),
            'requested_salary' => fake()->randomFloat(2, 55000, 160000),
            'requested_joining_date' => now()->addWeeks(6)->toDateString(),
            'candidate_comments' => fake()->sentence(),
            'recruiter_notes' => fake()->sentence(),
            'outcome' => 'pending',
        ];
    }
}
