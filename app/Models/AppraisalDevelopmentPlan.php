<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\AppraisalDevelopmentPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppraisalDevelopmentPlan extends Model
{
    /** @use HasFactory<AppraisalDevelopmentPlanFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'employee_appraisal_id',
        'strengths',
        'improvement_areas',
        'learning_objectives',
        'required_training',
        'career_aspirations',
        'target_completion_date',
    ];

    protected function casts(): array
    {
        return [
            'employee_appraisal_id' => 'integer',
            'target_completion_date' => 'date',
        ];
    }

    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(EmployeeAppraisal::class, 'employee_appraisal_id');
    }
}
