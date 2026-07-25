<?php

namespace Database\Factories;

use App\Models\HiringDecision;
use App\Models\JobApplication;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HiringDecision> */
class HiringDecisionFactory extends Factory
{
    protected $model = HiringDecision::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'job_application_id' => JobApplication::factory(),
            'recommendation' => 'hire',
            'decision_date' => now()->toDateString(),
            'decision_by' => User::factory(),
        ];
    }
}
