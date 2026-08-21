<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->name(),
            'company' => fake()->company(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'website' => fake()->optional()->url(),
            'industry' => fake()->randomElement(['Technology', 'Healthcare', 'Finance', 'Retail', 'Manufacturing']),
            'status' => fake()->randomElement(array_keys(config('customers.statuses'))),
            'type' => fake()->randomElement(array_keys(config('customers.types'))),
            'lifecycle_stage' => fake()->randomElement(array_keys(config('customers.lifecycle_stages'))),
            'segment' => fake()->optional()->randomElement(array_keys(config('customers.segments'))),
            'source' => fake()->optional()->randomElement(array_keys(config('customers.sources'))),
            'city' => fake()->city(),
            'country' => fake()->country(),
            'tags' => fake()->optional()->randomElements(['vip', 'enterprise', 'recurring'], fake()->numberBetween(0, 2)),
            'created_by' => User::factory(),
            'last_activity_at' => now(),
        ];
    }
}
