<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Carbon\Carbon;
use Database\Factories\WfhRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WfhRequest extends Model
{
    /** @use HasFactory<WfhRequestFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'work_date',
        'start_date',
        'end_date',
        'reason',
        'status',
        'submitted_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'submitted_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvalSteps(): HasMany
    {
        return $this->hasMany(WfhApprovalStep::class)->orderBy('step_order');
    }

    public function rangeStart(): Carbon
    {
        return ($this->start_date ?? $this->work_date)->copy()->startOfDay();
    }

    public function rangeEnd(): Carbon
    {
        return ($this->end_date ?? $this->work_date)->copy()->startOfDay();
    }

    public function isMultiDay(): bool
    {
        return $this->rangeStart()->toDateString() !== $this->rangeEnd()->toDateString();
    }

    public function coversDate(Carbon|string $date): bool
    {
        $day = Carbon::parse($date)->startOfDay();

        return $day->betweenIncluded($this->rangeStart(), $this->rangeEnd());
    }

    public function dateLabel(): string
    {
        if (! $this->isMultiDay()) {
            return $this->rangeStart()->format('M j, Y');
        }

        return $this->rangeStart()->format('M j, Y').' → '.$this->rangeEnd()->format('M j, Y');
    }
}
