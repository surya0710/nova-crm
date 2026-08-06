<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeAsset;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeAsset> */
class EmployeeAssetFactory extends Factory
{
    protected $model = EmployeeAsset::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'asset_code' => strtoupper(fake()->unique()->bothify('AST-####')),
            'name' => fake()->randomElement(['Laptop', 'Desktop', 'Phone', 'Monitor', 'Headset']),
            'category' => fake()->randomElement(array_keys(config('hrms.asset_categories', []))),
            'serial_number' => strtoupper(fake()->bothify('SN-########')),
            'status' => 'available',
        ];
    }

    public function assigned(?Employee $employee = null): static
    {
        return $this->state(function (array $attributes) use ($employee): array {
            $employee ??= Employee::factory()->create(['organization_id' => $attributes['organization_id']]);

            return [
                'employee_id' => $employee->id,
                'assigned_date' => now()->toDateString(),
                'status' => 'assigned',
            ];
        });
    }
}
