<?php

namespace Database\Factories;

use App\Models\AppraisalCalibration;
use App\Models\AppraisalSession;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AppraisalCalibration> */
class AppraisalCalibrationFactory extends Factory
{
    protected $model = AppraisalCalibration::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'appraisal_session_id' => AppraisalSession::factory(),
            'name' => 'Calibration Session',
            'description' => null,
            'status' => 'draft',
            'participant_employee_ids' => [],
            'adjustments' => [],
            'created_by' => null,
        ];
    }
}
