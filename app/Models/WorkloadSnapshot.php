<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\WorkloadSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkloadSnapshot extends Model
{
    /** @use HasFactory<WorkloadSnapshotFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'snapshot_date',
        'allocated_hours',
        'available_hours',
        'utilization_percentage',
        'overall_status',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'allocated_hours' => 'decimal:2',
            'available_hours' => 'decimal:2',
            'utilization_percentage' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getOverallStatusLabelAttribute(): string
    {
        return config(
            'resources.utilization_statuses.'.$this->overall_status,
            ucfirst(str_replace('_', ' ', (string) $this->overall_status))
        );
    }
}
