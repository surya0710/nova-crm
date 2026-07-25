<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\EmployeeAppraisalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeAppraisal extends Model
{
    /** @use HasFactory<EmployeeAppraisalFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'appraisal_session_id',
        'performance_cycle_id',
        'employee_id',
        'manager_employee_id',
        'status',
        'final_comments',
        'overall_summary',
        'manager_recommendation',
        'hr_recommendation',
        'executive_notes',
        'manager_rating',
        'calibrated_rating',
        'final_rating',
        'rating_breakdown',
        'rating_calculation_snapshot',
        'appraisal_calibration_id',
        'calibration_comments',
        'submitted_at',
        'hr_reviewed_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'appraisal_session_id' => 'integer',
            'performance_cycle_id' => 'integer',
            'employee_id' => 'integer',
            'manager_employee_id' => 'integer',
            'manager_rating' => 'decimal:2',
            'calibrated_rating' => 'decimal:2',
            'final_rating' => 'decimal:2',
            'rating_breakdown' => 'array',
            'rating_calculation_snapshot' => 'array',
            'appraisal_calibration_id' => 'integer',
            'submitted_at' => 'datetime',
            'hr_reviewed_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AppraisalSession::class, 'appraisal_session_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_employee_id');
    }

    public function calibration(): BelongsTo
    {
        return $this->belongsTo(AppraisalCalibration::class, 'appraisal_calibration_id');
    }

    public function developmentPlan(): HasOne
    {
        return $this->hasOne(AppraisalDevelopmentPlan::class, 'employee_appraisal_id');
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(AppraisalRecommendation::class, 'employee_appraisal_id');
    }

    public function talentMatrixEntry(): HasOne
    {
        return $this->hasOne(TalentMatrixEntry::class, 'employee_appraisal_id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, config('hrms.appraisal.editable_appraisal_statuses', []), true);
    }

    public function isImmutable(): bool
    {
        return in_array($this->status, config('hrms.appraisal.immutable_appraisal_statuses', ['closed']), true);
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
