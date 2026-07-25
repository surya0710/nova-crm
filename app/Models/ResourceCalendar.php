<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ResourceCalendarFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceCalendar extends Model
{
    /** @use HasFactory<ResourceCalendarFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'working_hours_per_day',
        'working_days',
        'timezone',
        'effective_from',
        'effective_to',
    ];

    protected function casts(): array
    {
        return [
            'working_hours_per_day' => 'decimal:2',
            'working_days' => 'array',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
