<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeStatutoryProfile;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeStatutoryProfile> */
class EmployeeStatutoryProfileFactory extends Factory
{
    protected $model = EmployeeStatutoryProfile::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'employee_id' => Employee::factory(),
            'pf_eligible' => true,
            'pf_uan' => '100123456789',
            'esi_eligible' => true,
            'esi_number' => '1234567890',
            'professional_tax_state' => 'MH',
            'tax_regime' => 'new',
            'pan' => 'ABCDE1234F',
            'aadhaar' => null,
            'tan_reference' => null,
        ];
    }
}
