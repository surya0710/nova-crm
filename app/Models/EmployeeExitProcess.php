<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\EmployeeExitProcessFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeExitProcess extends Model
{
    /** @use HasFactory<EmployeeExitProcessFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'exit_type',
        'last_working_day',
        'reason',
        'exit_interview',
        'hr_notes',
        'manager_notes',
        'status',
        'checklist_assets_returned',
        'checklist_documents_completed',
        'checklist_knowledge_transfer',
        'checklist_manager_approval',
        'checklist_hr_approval',
        'initiated_by',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'last_working_day' => 'date',
            'checklist_assets_returned' => 'boolean',
            'checklist_documents_completed' => 'boolean',
            'checklist_knowledge_transfer' => 'boolean',
            'checklist_manager_approval' => 'boolean',
            'checklist_hr_approval' => 'boolean',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function initiatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }
}
