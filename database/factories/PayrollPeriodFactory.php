<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayrollPeriod> */
class PayrollPeriodFactory extends Factory
{
    protected $model = PayrollPeriod::class;

    public function definition(): array
    {
        $start = now()->startOfMonth();

        return [
            'organization_id' => Organization::factory(),
            'name' => $start->format('F Y'),
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->endOfMonth()->toDateString(),
            'status' => 'draft',
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => ['status' => 'open']);
    }

    public function locked(): static
    {
        return $this->state(fn () => ['status' => 'locked']);
    }
}
