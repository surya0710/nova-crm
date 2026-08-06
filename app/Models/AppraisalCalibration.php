<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\AppraisalCalibrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppraisalCalibration extends Model
{
    /** @use HasFactory<AppraisalCalibrationFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'appraisal_session_id',
        'name',
        'description',
        'status',
        'participant_employee_ids',
        'adjustments',
        'session_comments',
        'scheduled_at',
        'started_at',
        'completed_at',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'appraisal_session_id' => 'integer',
            'participant_employee_ids' => 'array',
            'adjustments' => 'array',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
            'created_by' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AppraisalSession::class, 'appraisal_session_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function calibratedAppraisals(): HasMany
    {
        return $this->hasMany(EmployeeAppraisal::class, 'appraisal_calibration_id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'scheduled'], true);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
