<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\PerformanceReviewAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceReviewAssignment extends Model
{
    /** @use HasFactory<PerformanceReviewAssignmentFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'performance_cycle_id',
        'employee_id',
        'review_template_id',
        'primary_reviewer_id',
        'due_date',
        'review_type',
        'status',
        'assigned_by',
        'assigned_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'performance_cycle_id' => 'integer',
            'employee_id' => 'integer',
            'review_template_id' => 'integer',
            'primary_reviewer_id' => 'integer',
            'due_date' => 'date',
            'assigned_by' => 'integer',
            'assigned_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceCycle::class, 'performance_cycle_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PerformanceReviewTemplate::class, 'review_template_id');
    }

    public function primaryReviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'primary_reviewer_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function review(): HasOne
    {
        return $this->hasOne(PerformanceReview::class, 'review_assignment_id');
    }

    public function isImmutable(): bool
    {
        return in_array($this->status, config('hrms.performance_review.immutable_assignment_statuses', ['closed', 'cancelled']), true);
    }

    public function isEditableLifecycle(): bool
    {
        return ! $this->isImmutable();
    }
}
