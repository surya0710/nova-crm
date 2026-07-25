<?php

namespace Database\Factories;

use App\Models\AppraisalSession;
use App\Models\Employee;
use App\Models\EmployeeAppraisal;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeAppraisal> */
class EmployeeAppraisalFactory extends Factory
{
    protected $model = EmployeeAppraisal::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'appraisal_session_id' => AppraisalSession::factory(),
            'performance_cycle_id' => fn (array $attrs) => AppraisalSession::query()->find($attrs['appraisal_session_id'])?->performance_cycle_id,
            'employee_id' => Employee::factory(),
            'manager_employee_id' => null,
            'status' => 'generated',
            'manager_rating' => null,
            'calibrated_rating' => null,
            'final_rating' => null,
        ];
    }
}
