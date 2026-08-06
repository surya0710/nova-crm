<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeAsset;
use App\Models\EmployeeAssetAssignment;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeAssetAssignment> */
class EmployeeAssetAssignmentFactory extends Factory
{
    protected $model = EmployeeAssetAssignment::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'employee_asset_id' => EmployeeAsset::factory(),
            'employee_id' => Employee::factory(),
            'assigned_date' => now()->toDateString(),
        ];
    }
}
