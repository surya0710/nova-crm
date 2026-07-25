<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\AppraisalSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppraisalSession extends Model
{
    /** @use HasFactory<AppraisalSessionFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'performance_cycle_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'rating_weights',
        'talent_matrix_config',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'performance_cycle_id' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'rating_weights' => 'array',
            'talent_matrix_config' => 'array',
            'created_by' => 'integer',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function employeeAppraisals(): HasMany
    {
        return $this->hasMany(EmployeeAppraisal::class, 'appraisal_session_id');
    }

    public function calibrations(): HasMany
    {
        return $this->hasMany(AppraisalCalibration::class, 'appraisal_session_id');
    }

    public function talentMatrixEntries(): HasMany
    {
        return $this->hasMany(TalentMatrixEntry::class, 'appraisal_session_id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, config('hrms.appraisal.editable_session_statuses', ['draft', 'scheduled']), true);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['closed', 'archived'], true);
    }
}
