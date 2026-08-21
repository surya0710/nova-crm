<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'customer_id' => Customer::factory(),
            'name' => fake()->name(),
            'title' => fake()->optional()->jobTitle(),
            'department' => fake()->optional()->randomElement(['Sales', 'Finance', 'Operations', 'IT']),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'whatsapp' => fake()->optional()->phoneNumber(),
            'is_primary' => false,
            'is_decision_maker' => fake()->boolean(30),
            'status' => fake()->randomElement(array_keys(config('contacts.statuses'))),
            'created_by' => User::factory(),
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true, 'status' => 'active']);
    }
}
