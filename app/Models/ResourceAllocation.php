<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ResourceAllocationFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceAllocation extends Model
{
    /** @use HasFactory<ResourceAllocationFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'project_id',
        'task_id',
        'allocation_type',
        'allocation_percentage',
        'planned_hours',
        'planned_start_date',
        'planned_end_date',
        'notes',
        'metadata',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'allocation_percentage' => 'integer',
            'planned_hours' => 'decimal:2',
            'planned_start_date' => 'date',
            'planned_end_date' => 'date',
            'metadata' => 'array',
        ];
    }

    /**
     * Alias Metadata Platform `custom_fields` onto the resource_allocations.metadata JSON column.
     */
    protected function customFields(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->metadata,
            set: fn ($value) => ['metadata' => $value],
        );
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getAllocationTypeLabelAttribute(): string
    {
        return config(
            'resources.allocation_types.'.$this->allocation_type,
            ucfirst(str_replace('_', ' ', (string) $this->allocation_type))
        );
    }
}
