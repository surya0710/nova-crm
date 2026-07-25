<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\PerformanceReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceReview extends Model
{
    /** @use HasFactory<PerformanceReviewFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'review_assignment_id',
        'performance_cycle_id',
        'employee_id',
        'review_template_id',
        'reviewer_id',
        'review_type',
        'status',
        'overall_comments',
        'development_notes',
        'strengths',
        'improvement_areas',
        'snapshot',
        'snapshot_hash',
        'started_at',
        'submitted_at',
        'reviewed_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'review_assignment_id' => 'integer',
            'performance_cycle_id' => 'integer',
            'employee_id' => 'integer',
            'review_template_id' => 'integer',
            'reviewer_id' => 'integer',
            'snapshot' => 'array',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(PerformanceReviewAssignment::class, 'review_assignment_id');
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reviewer_id');
    }

    public function competencyEvaluations(): HasMany
    {
        return $this->hasMany(PerformanceReviewCompetencyEvaluation::class, 'performance_review_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function goalEvaluations(): HasMany
    {
        return $this->hasMany(PerformanceReviewGoalEvaluation::class, 'performance_review_id')
            ->orderBy('id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, config('hrms.performance_review.editable_statuses', ['draft', 'in_progress']), true);
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
