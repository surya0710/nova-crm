<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\CrmActivity;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrmActivity>
 */
class CrmActivityFactory extends Factory
{
    protected $model = CrmActivity::class;

    public function definition(): array
    {
        $type = fake()->randomElement(array_keys(config('crm_activities.types') ?? ['task' => 'Task']));

        return [
            'organization_id' => Organization::factory(),
            'customer_id' => Customer::factory(),
            'contact_id' => null,
            'type' => $type,
            'subject' => fake()->sentence(4),
            'body' => fake()->optional()->paragraph(),
            'occurred_at' => now(),
            'due_at' => $type === 'follow_up' ? now()->addDay() : null,
            'duration_minutes' => in_array($type, ['call', 'meeting'], true) ? fake()->randomElement([15, 30, 45, 60]) : null,
            'direction' => in_array($type, ['call', 'email'], true) ? 'outbound' : null,
            'outcome' => null,
            'assigned_to' => User::factory(),
            'created_by' => User::factory(),
            'status' => $type === 'follow_up' || $type === 'task' ? 'open' : 'completed',
            'priority' => 'medium',
        ];
    }

    public function forContact(Contact $contact): static
    {
        return $this->state(fn () => [
            'organization_id' => $contact->organization_id,
            'customer_id' => $contact->customer_id,
            'contact_id' => $contact->id,
        ]);
    }
}
