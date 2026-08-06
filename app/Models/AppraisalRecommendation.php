<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\AppraisalRecommendationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppraisalRecommendation extends Model
{
    /** @use HasFactory<AppraisalRecommendationFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'employee_appraisal_id',
        'recommendation_type',
        'promotion_recommendation',
        'target_designation_id',
        'effective_date',
        'justification',
        'increment_percent',
        'bonus_recommendation',
        'equity_recommendation',
        'adjustment_notes',
        'critical_role_flag',
        'readiness_level',
        'succession_notes',
    ];

    protected function casts(): array
    {
        return [
            'employee_appraisal_id' => 'integer',
            'target_designation_id' => 'integer',
            'effective_date' => 'date',
            'increment_percent' => 'decimal:2',
            'bonus_recommendation' => 'decimal:2',
            'equity_recommendation' => 'decimal:2',
            'critical_role_flag' => 'boolean',
        ];
    }

    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(EmployeeAppraisal::class, 'employee_appraisal_id');
    }

    public function targetDesignation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'target_designation_id');
    }
}
