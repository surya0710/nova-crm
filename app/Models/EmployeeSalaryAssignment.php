<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonInterface;
use Database\Factories\EmployeeSalaryAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalaryAssignment extends Model
{
    /** @use HasFactory<EmployeeSalaryAssignmentFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'salary_structure_id',
        'effective_from',
        'effective_until',
        'annual_ctc',
        'notes',
        'assigned_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
            'annual_ctc' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isActiveOn(CarbonInterface $date): bool
    {
        $day = $date->toDateString();

        if ($this->effective_from->toDateString() > $day) {
            return false;
        }

        if ($this->effective_until === null) {
            return true;
        }

        return $this->effective_until->toDateString() >= $day;
    }
}
