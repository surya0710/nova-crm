<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\EmployeeWfhAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeWfhAssignment extends Model
{
    /** @use HasFactory<EmployeeWfhAssignmentFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'policy_type',
        'weekdays',
        'effective_from',
        'effective_to',
        'is_active',
        'reason',
        'assigned_by',
    ];

    protected function casts(): array
    {
        return [
            'weekdays' => 'array',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isEffectiveOn(\DateTimeInterface|string|null $date = null): bool
    {
        $day = $date === null
            ? now()->startOfDay()
            : \Carbon\Carbon::parse($date)->startOfDay();

        if (! $this->is_active) {
            return false;
        }

        if ($this->effective_from !== null && $day->lt($this->effective_from->copy()->startOfDay())) {
            return false;
        }

        if ($this->effective_to !== null && $day->gt($this->effective_to->copy()->startOfDay())) {
            return false;
        }

        return true;
    }

    public function matchesWeekday(\DateTimeInterface|string|null $date = null): bool
    {
        if ($this->policy_type !== 'selected_days') {
            return true;
        }

        $day = $date === null
            ? now()
            : \Carbon\Carbon::parse($date);

        $weekdays = array_map('intval', $this->weekdays ?? []);

        return in_array((int) $day->isoWeekday(), $weekdays, true);
    }
}
