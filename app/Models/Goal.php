<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\GoalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Goal extends Model
{
    /** @use HasFactory<GoalFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'performance_cycle_id',
        'goal_template_id',
        'kpi_id',
        'goal_category_id',
        'title',
        'description',
        'goal_type',
        'assignee_type',
        'employee_id',
        'team_id',
        'department_id',
        'measurement_type',
        'target_value',
        'current_value',
        'weight',
        'achievement_percentage',
        'due_date',
        'status',
        'assigned_by',
        'assigned_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'performance_cycle_id' => 'integer',
            'goal_template_id' => 'integer',
            'kpi_id' => 'integer',
            'goal_category_id' => 'integer',
            'employee_id' => 'integer',
            'team_id' => 'integer',
            'department_id' => 'integer',
            'target_value' => 'decimal:4',
            'current_value' => 'decimal:4',
            'weight' => 'decimal:2',
            'achievement_percentage' => 'decimal:2',
            'due_date' => 'date',
            'assigned_by' => 'integer',
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(GoalTemplate::class, 'goal_template_id');
    }

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(Kpi::class, 'kpi_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GoalCategory::class, 'goal_category_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(HrmsTeam::class, 'team_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function progressUpdates(): HasMany
    {
        return $this->hasMany(GoalProgressUpdate::class, 'goal_id')->orderByDesc('id');
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(GoalCheckin::class, 'goal_id')->orderByDesc('id');
    }

    public function isEditable(): bool
    {
        return ! in_array($this->status, ['completed', 'cancelled'], true);
    }

    public function isActiveStatus(): bool
    {
        return in_array($this->status, ['assigned', 'in_progress'], true);
    }
}
