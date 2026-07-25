<?php

namespace Database\Factories;

use App\Models\AppraisalSession;
use App\Models\Employee;
use App\Models\EmployeeAppraisal;
use App\Models\Organization;
use App\Models\TalentMatrixEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TalentMatrixEntry> */
class TalentMatrixEntryFactory extends Factory
{
    protected $model = TalentMatrixEntry::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'appraisal_session_id' => AppraisalSession::factory(),
            'employee_appraisal_id' => EmployeeAppraisal::factory(),
            'employee_id' => Employee::factory(),
            'performance_band' => 2,
            'potential_band' => 2,
            'classification' => 'Core Contributor',
        ];
    }
}
