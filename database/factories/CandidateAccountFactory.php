<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\CandidateAccount;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<CandidateAccount> */
class CandidateAccountFactory extends Factory
{
    protected $model = CandidateAccount::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'candidate_id' => function (array $attributes) {
                return Candidate::factory()->create([
                    'organization_id' => $attributes['organization_id'],
                ])->id;
            },
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ];
    }
}
