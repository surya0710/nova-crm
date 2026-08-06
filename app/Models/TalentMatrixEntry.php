<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\TalentMatrixEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalentMatrixEntry extends Model
{
    /** @use HasFactory<TalentMatrixEntryFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'appraisal_session_id',
        'employee_appraisal_id',
        'employee_id',
        'performance_band',
        'potential_band',
        'performance_score',
        'potential_score',
        'classification',
        'matrix_config_snapshot',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'appraisal_session_id' => 'integer',
            'employee_appraisal_id' => 'integer',
            'employee_id' => 'integer',
            'performance_band' => 'integer',
            'potential_band' => 'integer',
            'performance_score' => 'decimal:2',
            'potential_score' => 'decimal:2',
            'matrix_config_snapshot' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AppraisalSession::class, 'appraisal_session_id');
    }

    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(EmployeeAppraisal::class, 'employee_appraisal_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
