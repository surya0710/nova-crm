<?php

namespace Database\Factories;

use App\Models\OfferApproval;
use App\Models\OfferLetter;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OfferApproval> */
class OfferApprovalFactory extends Factory
{
    protected $model = OfferApproval::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'offer_letter_id' => OfferLetter::factory(),
            'approver_id' => User::factory(),
            'status' => 'pending',
        ];
    }
}
