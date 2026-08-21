<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerTicket;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerTicket>
 */
class CustomerTicketFactory extends Factory
{
    protected $model = CustomerTicket::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'customer_id' => Customer::factory(),
            'number' => 'TKT-'.now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'subject' => fake()->sentence(6),
            'body' => fake()->optional()->paragraph(),
            'status' => 'open',
            'priority' => fake()->randomElement(array_keys(config('customer_tickets.priorities'))),
            'created_by' => User::factory(),
        ];
    }
}
