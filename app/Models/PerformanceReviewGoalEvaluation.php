<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReviewGoalEvaluation extends Model
{
    use Auditable, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'performance_review_id',
        'goal_id',
        'goal_title',
        'goal_description',
        'measurement_type',
        'target_value',
        'current_value',
        'achievement_percentage',
        'weight',
        'completion_status',
        'kpi_name',
        'kpi_code',
        'kpi_value',
        'comments',
        'rating',
    ];

    protected function casts(): array
    {
        return [
            'performance_review_id' => 'integer',
            'goal_id' => 'integer',
            'target_value' => 'decimal:4',
            'current_value' => 'decimal:4',
            'achievement_percentage' => 'decimal:2',
            'weight' => 'decimal:2',
            'kpi_value' => 'decimal:4',
            'rating' => 'decimal:2',
        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(PerformanceReview::class, 'performance_review_id');
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class, 'goal_id');
    }
}
